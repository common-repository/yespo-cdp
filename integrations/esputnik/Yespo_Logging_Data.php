<?php

namespace Yespo\Integrations\Esputnik;

use Exception;

class Yespo_Logging_Data
{
    private $wpdb;
    private $table_name;
    private $table_name_order;

    public function __construct(){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'yespo_contact_log';
        $this->table_name_order = $this->wpdb->prefix . 'yespo_order_log';

    }
    public function create(string $user_id, string $contact_id, string $action){
        global $wpdb;
        $table_name = esc_sql($this->table_name);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name)
            return $this->create_log_entry_user($user_id, $contact_id, $action); //if success returns 1
    }

    public function update_contact_log($user_id, $action, $response){
        global $wpdb;
        $table_name = esc_sql($this->table_name);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name)
            $this->update_log_entry_user($user_id, $action, $response);
    }

    public function create_entry_order($order_id, $action = 'update', $status = 200){
        global $wpdb;
        $table_name_order = esc_sql($this->table_name_order);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name_order)) === $table_name_order)
            return $this->create_log_entry_order($order_id, $action, $status); //if success returns 1
    }

    public function create_single_contact_log($email){
        if(!empty($email)){
            $user = get_user_by('email', $email);
            if($user && $user->ID){
                $yespo_contact_id = (new Yespo_Contact())->get_yespo_user_id($user->ID);
                if(!empty($yespo_contact_id)) return $this->create_log_entry_user($user->ID, $yespo_contact_id, 'update');
            }
        }
    }

    /** create new log user entry in database **/
    private function create_log_entry_user(string $user_id, string $contact_id, string $action){
        global $wpdb;
        $table_name = esc_sql($this->table_name);

        $data = array(
            'user_id' => sanitize_text_field($user_id),
            'contact_id' => sanitize_text_field($contact_id),
            'action' => sanitize_text_field($action),
            'yespo' => 1,
            'log_date' => gmdate('Y-m-d H:i:s', time())
        );

        try {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            return $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO %i (user_id, contact_id, action, yespo, log_date)
                    VALUES (%s, %s, %s, %d, %s)",
                    $table_name,
                    $data['user_id'],
                    $data['contact_id'],
                    $data['action'],
                    $data['yespo'],
                    $data['log_date']
                )
            );

        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    private function update_log_entry_user($user_id, $action, $response){
        global $wpdb;
        $table_name = esc_sql($this->table_name);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE %i SET yespo = %d WHERE action = %s AND user_id = %s",
                $table_name,
                sanitize_text_field($response),
                sanitize_text_field($action),
                sanitize_text_field($user_id)
            )
        );
    }

    /** create new log order entry in database **/
    private function create_log_entry_order(string $order_id, string $action, $status){
        global $wpdb;
        $table_name_order = esc_sql($this->table_name_order);

        if(!$this->check_presence_in_database($order_id, $action, 'completed')) {
            $data = [
                'order_id' => sanitize_text_field($order_id),
                'action' => sanitize_text_field($action),
                'status' => sanitize_text_field($status),
                'created_at' => current_time('mysql', 1)
            ];

            try {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                return $wpdb->query(
                    $wpdb->prepare(
                        "
                        INSERT INTO %i (order_id, action, status, created_at) 
                        VALUES (%s, %s, %s, %s)
                        ",
                        $table_name_order,
                        $data['order_id'],
                        $data['action'],
                        $data['status'],
                        $data['created_at']
                    )
                );

            } catch (Exception $e) {
                return "Error: " . $e->getMessage();
            }
        }
    }

    private function check_presence_in_database(string $order_id, string $action, string $status){
        global $wpdb;
        $table_name_order = esc_sql($this->table_name_order);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE order_id = %s AND action = %s AND status = %s",
                sanitize_text_field($table_name_order),
                sanitize_text_field($order_id),
                sanitize_text_field($action),
                sanitize_text_field($status)
            )
        );
    }

}