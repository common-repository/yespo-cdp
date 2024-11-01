<?php

namespace Yespo\Integrations\Esputnik;

use WP_Query;

class Yespo_Export_Orders
{
    private $period_selection = 300;
    private $period_selection_since = 300;
    private $period_selection_up = 30;
    private $number_for_export = 1000;
    private $export_time = 7.5;
    private $table_name;
    private $table_yespo_queue_orders;
    private $table_posts;
    private $meta_key;
    private $wpdb;
    private $time_limit;
    private $gmt;
    private $shop_order = 'shop_order';
    private $shop_order_placehold = 'shop_order_placehold';

    private $table_yespo_curl_json;
    private $table_yespo_removed;
    private $id_more_then;
    private $is_response_error;

    public function __construct(){
        global $wpdb;
        $this->meta_key = (new Yespo_Order())->get_meta_key();
        $this->wpdb = $wpdb;
        $this->table_posts = $this->wpdb->prefix . 'wc_orders';
        $this->table_name = $this->wpdb->prefix . 'yespo_export_status_log';
        $this->table_yespo_queue_orders = $this->wpdb->prefix . 'yespo_queue_orders';
        $this->time_limit = current_time('timestamp') - $this->period_selection;
        $this->gmt = time() - $this->period_selection;

        $this->table_yespo_curl_json = $wpdb->prefix . 'yespo_curl_json';
        $this->table_yespo_removed = $this->wpdb->prefix . 'yespo_removed_users';
        $this->id_more_then = $this->get_exported_order_id();
        $this->is_response_error = Yespo_Errors::get_error_entry();
    }

    public function add_orders_export_task(){
        $status = $this->get_order_export_status_processed('active');
        if(empty($status)){
            $data = [
                'export_type' => 'orders',
                'total' => intval( $this->get_export_orders_count() ),
                'exported' => 0,
                'status' => 'active'
            ];
            if($data['total'] > 0) {
                $result = $this->insert_export_orders_data($data);

                if ($result !== false) return true;
                else return false;
            }
        }
        else return false;
    }

    public function start_export_orders() {
        $status = $this->get_order_export_status_processed('active');
        if(!empty($status) && $status->status == 'active'){
            $total = intval($status->total);
            $exported = intval($status->exported);
            $current_status = $status->status;
            $live_exported = 0;

            if($total - $exported < $this->number_for_export) $this->number_for_export = $total - $exported;

            for($i = 0; $i < $this->number_for_export; $i++){

                $result = $this->export_orders_to_esputnik();
                if($result){
                    $live_exported += 1;
                }
            }

            if(($total <= $exported + $live_exported) || $this->get_export_orders_count() < 1){
                $current_status = 'completed';
                $exported = $total;
            } else $exported += $live_exported;

            $this->update_table_data($status->id, $exported, $current_status);
        } else {
            $status = $this->get_order_export_status();
            if(!empty($status) && $status->status === 'completed' && $status->code === null){
                $this->update_table_data($status->id, intval($status->total), $status->status);
            }
        }
    }

    public function schedule_export_orders(){

        $orders = $this->get_latest_orders();
        $status = $this->get_order_export_status_processed('error');
        $orders_when_error = $this->get_orders_while_error();
        $this->update_after_activation();

        if($this->is_response_error == null) {
            if (count($orders) > 0) {
                foreach ($orders as $order) {
                    $item = wc_get_order($order);
                    if (!empty($item) && !is_bool($item) && method_exists($item, 'get_billing_email') && !empty($item->get_billing_email())) {
                        if (!$this->is_email_in_removed_users($item->get_billing_email())) {
                            (new Yespo_Order())->create_order_on_yespo($item, 'update');
                        }
                    }
                }
            } else if(!empty($status) && $status->status == 'error') {
                $fresh_error = Yespo_Errors::get_error_entry();
                if ($fresh_error === null) {
                    $error = Yespo_Errors::get_error_entry_old();
                    if ($error !== null && isset($error->error) && ($error->error === 429 || $error->error === 500)) {
                        $this->update_table_data($status->id, intval($status->exported), 'active', '200');
                    }
                }
            } else if( count($orders_when_error) > 0 ){
                //$this->start_bulk_export_orders(); //send orders what created when was error 500 or 429
            } else {
                $status = $this->get_order_export_status();
                if (!empty($status) && ($status->status === 'completed' || $this->get_export_orders_count() < 1) && $status->code === null) {
                    $this->update_table_data($status->id, intval($status->total), 'completed');
                }
            }
        }

    }

    public function start_bulk_export_orders(){
        $status = $this->get_order_export_status_processed('active');

        //$orders = $this->get_bulk_export_orders();
        if(!empty($status) && $status->status == 'active' && !$this->check_queue_items_for_session() && $this->is_response_error == null){
            $startTime = microtime(true);
            $total = intval($status->total);
            $exported = intval($status->exported);
            $current_status = $status->status;
            $live_exported = 0;
            $code = $status->code;
            $export_quantity = 0;

            if($total - $exported < $this->number_for_export) $this->number_for_export = $total - $exported;

            do {
                $export_quantity++;
                $orders = $this->get_bulk_export_orders();

                $export_res = (new Yespo_Order())->create_bulk_orders_on_yespo(Yespo_Order_Mapping::create_bulk_order_export_array($orders), 'update');

                if(is_array($export_res) && isset($export_res['error']) && ($export_res['error'] == 429 || $export_res['error'] == 500)){
                    $this->update_entry_queue_items('FINISHED');
                    Yespo_Errors::set_error_entry($export_res['error']);
                } else if (is_array($export_res) && $export_res['error'] == 0){
                    $this->update_entry_queue_items('FINISHED');
                    $code = '0';
                } else {
                    $code = '200';
                    $last_element = end($orders);
                    $endTime = microtime(true);
                    $live_exported += count($orders);
                    if ($export_res) {
                        $this->update_entry_queue_items('FINISHED');
                    }

                    if (count($orders) > 0) $this->set_exported_order_id($last_element);
                }

                $this->is_response_error = Yespo_Errors::get_error_entry();

            } while ( ($endTime - $startTime) <= $this->export_time && $export_quantity < 3 && $this->is_response_error == null);

            if($total <= $exported + $live_exported){
                $current_status = 'completed';
                $exported = $total;
            } else $exported += $live_exported;

            $is_error = $this->check_orders_for_error();
            if($is_error){
                $current_status = 'error';
                $code = $is_error;
            }
            $this->update_table_data($status->id, $exported, $current_status, $code);
            if($current_status == 'completed'){
                $newUser = new Yespo_Export_Users;
                $usersExp = $newUser->get_users_export_count();
                if($usersExp > 0){
                    $newUser->add_users_export_task();
                    $newUser->start_active_bulk_export_users();
                }
            } //export users if exist
        } else {
            $status = $this->get_order_export_status();
            if(!empty($status) && ($status->status === 'completed' || $this->get_export_orders_count() < 1) && $status->code === null){
                $this->update_table_data($status->id, intval($status->total), $status->status);

                $newUser = new Yespo_Export_Users;
                $usersExp = $newUser->get_users_export_count();
                if($usersExp > 0){
                    $newUser->add_users_export_task();
                    $newUser->start_active_bulk_export_users();
                }
            }
        }

    }

    public function start_unexported_orders_because_errors(){
        if($this->get_export_orders_count() > 0){
            $entry_active = $this->get_order_export_status_processed('active');
            if($entry_active == null){
                $entry_completed = $this->get_order_export_status_processed('completed');
                if($entry_completed && $entry_completed->updated_at){
                    $orders = $this->get_unexported_orders_because_error($entry_completed->updated_at);
                    if($orders && count($orders) > 0) {
                        (new Yespo_Order())->create_bulk_orders_on_yespo(Yespo_Order_Mapping::create_bulk_order_export_array($orders), 'update');
                    }
                }
            }
        }
    }

    public function get_final_orders_exported(){
        $status = $this->get_order_export_status();
        return $this->update_table_data($status->id, intval($status->total), $status->status, '200');
    }

    public function export_orders_to_esputnik(){
        $orders = $this->get_orders_export_esputnik();
        if(count($orders) > 0 && isset($orders[0])){
            return (new Yespo_Order())->create_order_on_yespo(
                wc_get_order($orders[0])
            );
        }
    }

    public function get_process_orders_exported(){
        return $this->get_order_export_status();
    }

    public function stop_export_orders(){
        $status = $this->get_order_export_status_processed('active');
        if(!empty($status) && $status->status == 'active'){
            $this->update_table_data($status->id, intval($status->exported), 'stopped', '200');
            return $status;
        }
    }

    public function check_orders_for_stopped(){
        $status = $this->get_order_export_status_processed('stopped');
        if($status) return true;
        return false;
    }

    public function resume_export_orders(){
        $status = $this->get_order_export_status_processed('stopped');
        if(!empty($status) && $status->status == 'stopped'){
            $this->update_table_data($status->id, intval($status->exported), 'active', '200');
            return $status;
        }
    }

    public function get_order_export_status(){
        global $wpdb;
        $table_name = esc_sql($this->table_name);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE export_type = %s ORDER BY id DESC LIMIT 1",
                $table_name,
                'orders'
            )
        );
    }

    public function error_export_orders($code){
        $status = $this->get_order_export_status_processed('active');
        if(!empty($status) && $status->status == 'active'){
            $this->update_table_data($status->id, intval($status->exported), 'error', $code);
            return $status;
        }
    }

    public function check_orders_for_error(){
        $status = $this->get_order_export_status_processed('error');
        if($status) return $status->code;
        return false;
    }

    public function get_order_export_status_processed($action){
        global $wpdb;
        $table_name = esc_sql($this->table_name);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE export_type = %s AND status = %s ORDER BY id DESC LIMIT 1",
                $table_name,
                'orders',
                $action
            )
        );
    }

    public function update_after_activation(){
        $order = $this->get_order_export_status_processed('active');
        if(empty($order)) $order = $this->get_order_export_status_processed('stopped');
        if(!empty($order) && ($order->status == 'stopped' || $order->status == 'active') ){
            $exportEntry = intval($order->total) - intval($order->exported);
            $export = $this->get_export_orders_count();
            if($exportEntry != $export){
                $newTotal = intval($order->total) + ($export - $exportEntry);
                $this->update_table_total($order->id, $newTotal);
            }
        }
    }

    private function update_table_total($id, $total){
        global $wpdb;
        $table_name = esc_sql($this->table_name);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE %i SET total = %d WHERE id = %d",
                $table_name,
                $total,
                $id
            )
        );
    }

    public function update_table_data($id, $exported, $status, $code = null){
        global $wpdb;

        $table_name = esc_sql($this->table_name);
        $id = intval($id);
        $exported = intval($exported);
        $status = sanitize_text_field($status);
        $code = sanitize_text_field($code);
        $updated_at = gmdate('Y-m-d H:i:s', time());

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->query(
            $wpdb->prepare(
                "
                    UPDATE %i
                    SET exported = %d, status = %s, code = %s, updated_at = %s 
                    WHERE id = %d
                ",
                $table_name, $exported, $status, $code, $updated_at, $id
            )
        );
    }

    public function get_total_orders(){
        global $wpdb;
        $table_posts = esc_sql($this->table_posts);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM %i
                 WHERE type = %s 
                 AND status != %s",
                $table_posts,
                'shop_order',
                'wc-checkout-draft'
            )
        );
    }
    public function get_export_orders_count(){
        global $wpdb;
        $table_posts = esc_sql($this->table_posts);
        $prefix = esc_sql($this->wpdb->prefix);
        $prefix_postmeta_table = esc_sql($prefix . 'postmeta');
        $meta_key = esc_sql($this->meta_key);

        // phpcs:ignore WordPress.DB
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE type = %s AND status != %s AND ID NOT IN ( SELECT post_id FROM {$prefix_postmeta_table} WHERE meta_key = %s AND meta_value = 'true')",$table_posts, 'shop_order', 'wc-checkout-draft', $meta_key));
    }

    public function get_orders_export_esputnik(){
        $orders = $this->get_orders_from_database_without_metakey();
        $order_ids = [];
        if($orders && count($orders) > 0){
            foreach ($orders as $order) {
                $order_ids[] = $order->id;
            }
        }
        return $order_ids;
    }

    /**
     * entry to yespo queue orders
     **/

    public function add_entry_queue_items() {
        global $wpdb;
        $table_yespo_queue_orders = esc_sql($this->table_yespo_queue_orders);
        $count = $this->check_last_entry_status('STARTED');

        if ($count == 0) {
            $data = [
                'yespo_status' => 'STARTED'
            ];

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            return $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO %i (yespo_status) VALUES (%s)",
                    $table_yespo_queue_orders,
                    $data['yespo_status']
                )
            );
        }

        return false;
    }

    public function update_entry_queue_items($status) {
        global $wpdb;
        $table_yespo_queue_orders = esc_sql($this->table_yespo_queue_orders);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $last_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID 
                FROM %i 
                ORDER BY ID DESC 
                LIMIT 1",
                $table_yespo_queue_orders
            )
        );

        if ($last_id) {

            $data = ['yespo_status' => $wpdb->prepare('%s', $status)];

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            return $wpdb->query(
                $wpdb->prepare(
                    "
                    UPDATE %i
                    SET yespo_status = %s
                    WHERE ID = %d
                    ",
                    $table_yespo_queue_orders,
                    $data['yespo_status'],
                    $last_id
                )
            );

        }

        return false;
    }
    public function check_queue_items_for_session() {
        return $this->check_last_entry_status('STARTED');
    }

    private function check_last_entry_status($status) {
        global $wpdb;
        $table_yespo_queue_orders = esc_sql($this->table_yespo_queue_orders);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $last_status = $wpdb->get_var(
            $wpdb->prepare(
            "SELECT yespo_status 
                FROM %i 
                ORDER BY ID DESC 
                LIMIT 1",
                $table_yespo_queue_orders
            )
        );
        return $last_status === $status;
    }


    private function get_latest_orders(){
        $results = $this->get_orders_from_db($this->time_limit);
        if(empty($results)) $results = $this->get_orders_from_db($this->gmt);

        $orders = [];
        if(count($results) > 0){
            foreach ($results as $post){
                $orders[] = $post->id;
            }
        }
        return $orders;
    }

    public function get_bulk_export_orders(){
        global $wpdb;
        $period_start = gmdate('Y-m-d H:i:s', time() - $this->period_selection);
        $table_posts = esc_sql($this->table_posts);
        $prefix = esc_sql($wpdb->prefix);
        $prefix_postmeta_table = esc_sql($prefix . 'postmeta');
        $meta_key = esc_sql($this->meta_key);
        $number_for_export = absint($this->number_for_export);
        $id_more_then = absint($this->id_more_then);

        // phpcs:ignore WordPress.DB
        return $wpdb->get_results($wpdb->prepare("SELECT id FROM %i WHERE type = %s AND status != %s AND ID NOT IN ( SELECT post_id FROM {$prefix_postmeta_table} WHERE meta_key = %s AND meta_value = 'true' ) AND date_created_gmt < %s AND ID > %d ORDER BY ID ASC LIMIT %d",$table_posts, 'shop_order', 'wc-checkout-draft', $meta_key, $period_start, $id_more_then, $number_for_export),OBJECT);
    }

    public function get_unexported_orders_because_error($last_exported) {
        global $wpdb;
        $period_start = gmdate('Y-m-d H:i:s', time() - $this->period_selection);
        $table_posts = esc_sql($this->table_posts);
        $prefix = esc_sql($this->wpdb->prefix);
        $prefix_postmeta_table = esc_sql($prefix . 'postmeta');
        $meta_key = esc_sql($this->meta_key);
        $number_for_export = absint($this->number_for_export);
        $id_more_then = absint($this->id_more_then);

        // phpcs:ignore WordPress.DB
        return $wpdb->get_results($wpdb->prepare("SELECT id FROM %i WHERE type = %s AND status != %s AND ID NOT IN ( SELECT post_id FROM {$prefix_postmeta_table} WHERE meta_key = %s AND meta_value = 'true' ) AND date_created_gmt >= %s AND date_created_gmt <= %s AND ID > %d ORDER BY ID ASC LIMIT %d",$table_posts, 'shop_order', 'wc-checkout-draft', $meta_key, $last_exported, $period_start, $id_more_then, $number_for_export),OBJECT);
    }

    private function get_orders_from_database_without_metakey(){
        global $wpdb;
        $table_posts = esc_sql($this->table_posts);
        $prefix = esc_sql($this->wpdb->prefix);
        $prefix_postmeta_table = esc_sql($prefix . 'postmeta');

        // phpcs:ignore WordPress.DB
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE type = %s AND status != %s AND ID NOT IN ( SELECT post_id FROM {$prefix_postmeta_table} WHERE meta_key = %s AND meta_value = 'true')",
                $table_posts,
                'shop_order',
                'wc-checkout-draft',
                $this->meta_key
            )
        );
    }

    private function get_orders_from_db($time){
        global $wpdb;
        $table_posts = esc_sql($this->table_posts);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE type = %s AND status != %s AND date_updated_gmt BETWEEN %s AND %s",
                $table_posts,
                'shop_order',
                'wc-checkout-draft',
                gmdate('Y-m-d H:i:s', time() - $this->period_selection_since),
                gmdate('Y-m-d H:i:s', time() - $this->period_selection_up)
            )
        );
    }

    public function is_email_in_removed_users($email) {
        global $wpdb;
        $current_timestamp = strtotime(current_time('mysql'));
        $searched_time = gmdate('Y-m-d H:i:s', $current_timestamp - 360);
        $table_yespo_removed = esc_sql($this->table_yespo_removed);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = $wpdb->get_var(
            $wpdb->prepare(
            "SELECT COUNT(*) FROM %i WHERE email = %s AND time >= %s",
                $table_yespo_removed,
                $email,
                $searched_time
            )
        );
        return $count > 0;
    }

    //add json of exported orders
    public function add_json_log_entry($orders) {
        global $wpdb;
        $json = wp_json_encode($orders);
        $table_yespo_curl_json = esc_sql($this->table_yespo_curl_json);

        if ($json !== false) {
            $created_at = gmdate('Y-m-d H:i:s');

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            return $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO %i (text, created_at) VALUES (%s, %s)",
                    $table_yespo_curl_json,
                    $json,
                    $created_at
                )
            );
        }

        return false;
    }

    private function get_exported_order_id(){
        if ( get_option( 'yespo_options' ) !== false ) {
            $options = get_option('yespo_options', array());
            $yespo_api_key = 0;
            if (isset($options['yespo_highest_exported_order'])) $yespo_api_key = intval($options['yespo_highest_exported_order']);

            return $yespo_api_key;
        }
        return 0;
    }

    private function set_exported_order_id($order){
        if ( get_option( 'yespo_options' ) !== false ) {
            $options = get_option('yespo_options', array());
            $options['yespo_highest_exported_order'] = intval($order->id);
            update_option('yespo_options', $options);
        }
    }

    private function get_orders_while_error(){
        $error = Yespo_Errors::get_error_entry_old();
        if(!empty($error)){
            return $this->get_unsent_orders_since_time($error->time);
        }
    }

    public function get_unsent_orders_since_time($time){
        global $wpdb;
        $table_posts = esc_sql($this->table_posts);
        $postmeta = esc_sql($this->wpdb->postmeta);

        // phpcs:ignore WordPress.DB
        return $wpdb->get_results($wpdb->prepare("SELECT p.id FROM %i p LEFT JOIN {$postmeta} pm ON p.id = pm.post_id AND pm.meta_key = 'sent_order_to_yespo' WHERE p.type = %s AND p.status != %s AND p.date_updated_gmt BETWEEN %s AND %s AND pm.post_id IS NULL",
                $table_posts,
                'shop_order',
                'wc-checkout-draft',
                gmdate('Y-m-d H:i:s', time() - $time),
                gmdate('Y-m-d H:i:s', time() - $this->period_selection)
            )
        );
    }

    private function insert_export_orders_data($data) {
        global $wpdb;

        $table_name = esc_sql($this->table_name);
        $data['export_type'] = sanitize_text_field($data['export_type']);
        $data['total'] = absint($data['total']);
        $data['exported'] = absint($data['exported']);
        $data['status'] = sanitize_text_field($data['status']);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO %i (export_type, total, exported, status)
                VALUES (%s, %d, %d, %s)",
                $table_name,
                $data['export_type'],
                $data['total'],
                $data['exported'],
                $data['status']
            )
        );

    }

}