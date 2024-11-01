<?php
/*** CURL REQUEST CLASS ***/

namespace Yespo\Integrations\Esputnik;

use Exception;

class Yespo_Curl_Request
{
    public static function curl_request(
        $url,
        $custom_request,
        $auth_data,
        $user_data = '',
        $type_response = ''
    ){
        try {
            $args = [
                'method'  => $custom_request,
                'timeout' => 30,
                'headers' => [
                    'Accept'        => 'application/json; charset=UTF-8',
                    'Authorization' => 'Basic ' . base64_encode(':' . $auth_data['yespo_api_key']),
                    'Content-Type'  => 'application/json',
                ],
                'body'    => !empty($user_data) ? wp_json_encode($user_data) : '',
            ];

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                return 'Error: ' . $response->get_error_message();
            }

            $http_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            //$http_code = 0;

            if (
                $custom_request === 'DELETE' ||
                $type_response === 'orders' ||
                $http_code === 400 ||
                $http_code === 429 ||
                $http_code === 500 ||
                $http_code === 0
            ) {
                $response_body = $http_code;
            }

            if (!empty($user_data)) {
                (new Yespo_Export_Orders())->add_json_log_entry($user_data);
            }

            return $response_body;

        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}