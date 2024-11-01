<?php

namespace Yespo\Integrations\Esputnik;

use Exception;

class Yespo_Account
{
    const YESPO_REMOTE_ESPUTNIK_URL = "https://yespo.io/api/v1/account/info";

    public function send_keys($api_key) {
        try {
            $response = wp_remote_get(self::YESPO_REMOTE_ESPUTNIK_URL, [
                'timeout' => 30,
                'headers' => [
                    'Accept' => 'application/json; charset=UTF-8',
                    'Authorization' => 'Basic ' . base64_encode(':' . $api_key)
                ],
            ]);

            if (is_wp_error($response)) {
                return 'Error: ' . $response->get_error_message();
            }

            $status_code = wp_remote_retrieve_response_code($response);

            return $status_code;

        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function get_profile_name(){
        return Yespo_Curl_Request::curl_request(self::YESPO_REMOTE_ESPUTNIK_URL, 'GET', get_option('yespo_options'));
    }

    public function add_entry_auth_log($api_key, $response){
        global $wpdb;

        $table_yespo_auth = esc_sql($wpdb->prefix . 'yespo_auth_log');
        $api_key = sanitize_text_field($api_key);
        $response = sanitize_text_field($response);
        $time = gmdate('Y-m-d H:i:s');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->query(
            $wpdb->prepare(
            "INSERT INTO %i (api_key, response, time) 
                VALUES (%s, %s, %s)",
                $table_yespo_auth,
                $api_key,
                $response,
                $time
            )
        );

    }
}