<?php

namespace Yespo\Integrations\Esputnik;

use WP_Query;

class Yespo_Order
{
    const REMOTE_ORDER_YESPO_URL = 'https://yespo.io/api/v1/orders';
    const CUSTOM_ORDER_REQUEST = 'POST';
    private $authData;
    private $table_name_order;
    private $wpdb;
    const ORDER_META_KEY = 'sent_order_to_yespo';

    public function __construct(){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->authData = get_option('yespo_options');
        $this->table_name_order = $this->wpdb->prefix . 'yespo_order_log';
    }
    public function create_order_on_yespo($order, $operation = 'update'){

        if (empty($this->authData)) {
            return __( 'Empty user authorization data', 'yespo-cdp' );
        }

        $data = Yespo_Order_Mapping::order_woo_to_yes($order);

        if($data){
            $response = Yespo_Curl_Request::curl_request(self::REMOTE_ORDER_YESPO_URL, self::CUSTOM_ORDER_REQUEST, $this->authData, $data, 'orders');
            if (($response > 199 && $response < 300) || $response == 400) {
                if ($order && is_a($order, 'WC_Order') && $order->get_id()) {
                    update_post_meta($order->get_id(), self::ORDER_META_KEY, 'true');
                    if($response == 400) update_post_meta($order->get_id(), Yespo_Errors::get_mark_br(), 'true'); //400 error
                    (new Yespo_Logging_Data())->create_entry_order($order->get_id(), $operation, $response); //add entry to logfile
                    (new Yespo_Logging_Data())->create_single_contact_log($order->get_billing_email()); //add entry contact log file
                }
            } else if($response == 429 || $response == 500){
                Yespo_Errors::set_error_entry($response);
            }
        } else{
            update_post_meta($order->get_id(), self::ORDER_META_KEY, 'true');
        }
        return true;

    }

    public function create_bulk_orders_on_yespo($orders, $operation = 'update'){

        global $wpdb;
        if (empty($this->authData)) {
            return __( 'Empty user authorization data', 'yespo-cdp' );
        }

        //(new Yespo_Export_Orders())->add_json_log_entry($orders);// add log entry to DB

        if($orders['orders'] > 0) {
            $response = Yespo_Curl_Request::curl_request(self::REMOTE_ORDER_YESPO_URL, self::CUSTOM_ORDER_REQUEST, $this->authData, $orders, 'orders');

            (new Yespo_Export_Orders())->add_entry_queue_items();

            if (($response > 199 && $response < 300) || $response == 400) {
                $orderCounter = 0;
                $values = [];
                $order_logs = [];

                foreach ($orders['orders'] as $item) {
                    $order = wc_get_order($item['externalOrderId']);
                    if ($order && is_a($order, 'WC_Order') && $order->get_id()) {
                        $order_id = $order->get_id();
                        $values[] = $order_id;
                        if($response == 400) $error_400[] = $order_id;
                        $order_logs[] = $order_id;
                        $orderCounter++;
                    }
                }

                if (!empty($values)) $this->add_labels_to_orders($values, self::ORDER_META_KEY, 'true');
                if(isset($error_400) && count($error_400) > 0) Yespo_Errors::error_400($error_400,'orders');

                //add log entries
                if (!empty($order_logs)) {
                    $this->add_log_order_entry($order_logs, $operation, $response, gmdate('Y-m-d H:i:s', time()));
                }

                return $orderCounter;

            } else if($response === 401){
                (new Yespo_Export_Orders())->error_export_orders('401');
            } else if($response === 0 || strpos($response, 'Connection refused') !== false){
                //(new Yespo_Export_Orders())->error_export_orders('555');
                return ['error'=> 0];
            } else if($response == 429 || $response == 500){
                return ['error'=> $response];
            }
        }
        return false;
    }

    public function add_labels_to_orders($values, $meta_key, $meta_value){
        global $wpdb;

        $ordermeta_table = esc_sql($wpdb->usermeta);
        $placeholders = [];
        $query_values = [];
        $post_meta = esc_sql( $wpdb->postmeta );

        foreach ($values as $order_id) {
            $placeholders[] = "(%d, %s, %s)";
            $query_values[] = $order_id;
            $query_values[] = $meta_key;
            $query_values[] = $meta_value;
        }

        if (!empty($query_values)) {
            $placeholders_string = implode(", ", $placeholders);

            // phpcs:ignore WordPress.DB
            return $wpdb->query($wpdb->prepare("INSERT INTO {$post_meta} (post_id, meta_key, meta_value) VALUES {$placeholders_string} ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",...$query_values));
        }
        return false;
    }

    private function find_orders_by_user_email($email){
        $customer_orders = wc_get_orders( array(
            'limit'    => -1,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'customer' => sanitize_email($email),
        ) );
        $orders = [];
        foreach( $customer_orders as $order ) {
            $orders[] = $order->get_id();
        }
        return array_unique($orders);
    }

    public function get_meta_key(){
        return self::ORDER_META_KEY;
    }

    public function add_time_label($order_id){
        $order = wc_get_order($order_id);
        $status = $order->get_status();

        if (strpos($status, 'draft') === false) {
            $order_time = $order->get_meta('yespo_order_time');

            if (empty($order_time)) {
                $current_time = gmdate('Y-m-d H:i:s', current_time('timestamp', true));
                $order->update_meta_data('yespo_order_time', $current_time);
                $order->save();
            }
        }
    }

    public function add_label_deleted_customer($email){
        if(!empty($email)) {
            $orders = $this->get_orders_by_email($email);

            if ($orders) {
                foreach ($orders as $order) {
                    $order->update_meta_data('yespo_customer_removed', 'deleted');
                    $order->save();
                }
            }
        }
    }

    public function get_orders_by_email($email){
        $query = new WP_Query($this->args_get_orders_by_email($email));

        if ($query->have_posts()) {
            $orders = array();
            while ($query->have_posts()) {
                $query->the_post();
                $order_id = get_the_ID();
                $orders[] = wc_get_order($order_id);
            }
            wp_reset_postdata();
            return $orders;
        } else {
            return null;
        }
    }
    private function args_get_orders_by_email($email){
        $post_types = ['shop_order', 'shop_order_placehold'];

        $existing_post_types = array_filter($post_types, function($type) {
            return post_type_exists($type);
        });

        return [
            'post_type' => $existing_post_types,
            'post_status' => 'any',
            // phpcs:ignore WordPress.DB.SlowDBQuery
            'meta_query' => array(
                array(
                    'key' => '_billing_email',
                    'value' => $email,
                    'compare' => '='
                )
            )
        ];
    }

    private function add_log_order_entry($order_logs, $operation, $response, $time){
        global $wpdb;

        $table_name_order = esc_sql($this->table_name_order);
        $placeholders = [];
        $query_values = [];

        foreach ($order_logs as $order_id) {
            $placeholders[] = "(%d, %s, %s, %s)";
            $query_values[] = $order_id;
            $query_values[] = $operation;
            $query_values[] = $response;
            $query_values[] = $time;
        }

        if (!empty($query_values)) {
            $placeholders_string = implode(", ", $placeholders);

            $sql = "
                    INSERT INTO {$table_name_order} (order_id, action, status, created_at) 
                    VALUES {$placeholders_string}
                    ";

            // phpcs:ignore WordPress.DB
            $prepared_sql = $wpdb->prepare($sql, ...$query_values);

            // phpcs:ignore WordPress.DB
            return $wpdb->query($prepared_sql);
        }

        return false;
    }

}