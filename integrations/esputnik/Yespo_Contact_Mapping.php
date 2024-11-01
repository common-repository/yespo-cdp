<?php

namespace Yespo\Integrations\Esputnik;

class Yespo_Contact_Mapping
{
    public static function woo_to_yes($user_data){
        return self::data_woo_to_yes(self::user_transformation_to_array($user_data));
    }

    public static function update_woo_to_yes($request, $user_id){
        return self::data_woo_to_yes(self::update_user_transformation_to_array($request, $user_id));
    }

    public static function guest_user_woo_to_yes($order){
        return self::data_woo_to_yes(self::order_transformation_to_array($order));
    }

    public static function guest_user_admin_woo_to_yes($post){
        return self::data_woo_to_yes(self::admin_order_transformation_to_array($post));
    }

    public static function subscribed_user_woo_to_yes($email){
        return self::data_woo_to_yes(self::subscription_transformation_to_array($email));
    }

    public static function clean_user_phone_data($email){
        return self::remove_phone_number_array($email);
    }

    public static function clean_user_personal_data($email){
        return self::remove_all_personal_data($email);
    }

    private static function data_woo_to_yes($user)
    {
        $address = !empty($user['address_1']) ? $user['address_1'] : (!empty($user['address_2']) ? $user['address_2'] : ' ');
        $region = ($user['state']) ?? $user['country'] ?? ' ';

        $data['channels'][] = [
            'value' => sanitize_email($user['email']),
            'type' => 'email'
        ];
        if (isset($user['phone']) && !empty($user['phone'])) {
            $phoneNumber = preg_replace("/[^0-9]/", "", $user['phone']);
        } else $phoneNumber = ' ';
        $data['channels'][] = [
            'value' => sanitize_text_field($phoneNumber),
            'type' => 'sms'
        ];

        if(isset($user['ID'])) $data['externalCustomerId'] = absint($user['ID']);
        if($user['first_name'] !== null && Yespo_Contact_Validation::name_validation($user['first_name'])) $data['firstName'] = sanitize_text_field($user['first_name']);
        else $data['firstName'] = ' ';

        if($user['last_name'] !== null && Yespo_Contact_Validation::lastname_validation($user['last_name'])) $data['lastName'] = sanitize_text_field($user['last_name']);
        else $data['lastName'] = ' ';

        $data['address'] = [
            'region' => sanitize_text_field($region),
            'town' => sanitize_text_field($user['city']) ?? ' ',
            'address' => sanitize_text_field($address),
            'postcode' => sanitize_text_field($user['postcode']) ?? ' '
        ];
        if($user['languageCode']) $data['languageCode'] = sanitize_text_field($user['languageCode']);
        if (!empty($meta_data) && is_array($meta_data)){
            $data['fields'] = self::fields_transformation($meta_data);
        }

        return $data;
    }

    //array users for bulk export
    public static function create_bulk_export_array($users){
        $data = [];
        $contact = new Yespo_Export_Users();

        if($users && count($users) > 0){
            $data['dedupeOn'] = 'externalCustomerId';

            $user_objects = get_users(['include' => $users]);

            foreach($user_objects as $user){
                $user_array = self::user_transformation_to_array($user);
                $data['contacts'][] = self::data_woo_to_yes($user_array);
                $contact->add_entry_queue_items($user->user_email);
            }
        }
        return $data;
    }

    //remove user phone
    private static function remove_phone_number_array($email){
        return [
            'dedupeOn' => 'email',
            'contactFields' => ['sms'],
            'contacts' => [
                [
                    'channels' => [
                        [
                            'type' => 'email',
                            'value' => sanitize_email($email)
                        ]
                    ]
                ]
            ]
        ];
    }

    //removes personal user data
    private static function remove_all_personal_data($email){
        $data['channels'][] = [
            'value' => sanitize_email($email),
            'type' => 'email'
        ];
        $data['firstName'] = ' ';
        $data['lastName'] = ' ';
        $data['address'] = [
            'region' => ' ',
            'town' => ' ',
            'address' => ' ',
            'postcode' => ' '
        ];
        $data['languageCode'] = ' ';

        return $data;
    }

    //necessary to improve code after getting incoming data
    private static function fields_transformation($fields) {
        return array_map(function($field) {
            return [
                'id' => sanitize_text_field($field['id']),
                'value' => sanitize_text_field($field['value'])
            ];
        }, $fields);
    }

    private static function user_transformation_to_array($user){
        return [
            'email' => sanitize_email($user->data->user_email),
            'ID' => absint($user->ID),
            'first_name' => !empty($user->first_name) ? sanitize_text_field($user->first_name) : (!empty($user->billing_first_name) ? sanitize_text_field($user->billing_first_name) : (!empty($user->shipping_first_name) ? sanitize_text_field($user->shipping_first_name) : '')),
            'last_name' => !empty($user->last_name) ? sanitize_text_field($user->last_name) : (!empty($user->billing_last_name) ? sanitize_text_field($user->billing_last_name) : (!empty($user->shipping_last_name) ? sanitize_text_field($user->shipping_last_name) : '')),
            'state' => !empty(self::get_state_name($user->billing_country, $user->billing_state)) ? self::get_state_name($user->billing_country, $user->billing_state) : (!empty(self::get_state_name($user->shipping_country, $user->shipping_state)) ? self::get_state_name($user->shipping_country, $user->shipping_state) : ''),
            'country_id' => !empty($user->billing_country) ? sanitize_text_field($user->billing_country) : (!empty($user->shipping_country) ? sanitize_text_field($user->shipping_country) : ''),
            'city' => !empty($user->billing_city) ? sanitize_text_field($user->billing_city) : (!empty($user->shipping_city) ? sanitize_text_field($user->shipping_city) : ''),
            'address_1' => !empty($user->billing_address_1) ? sanitize_text_field($user->billing_address_1) : (!empty($user->shipping_address_1) ? sanitize_text_field($user->shipping_address_1) : ''),
            'address_2' => !empty($user->billing_address_2) ? sanitize_text_field($user->billing_address_2) : (!empty($user->shipping_address_2) ? sanitize_text_field($user->shipping_address_2) : ''),
            'phone' => !empty($user->billing_phone) ? sanitize_text_field($user->billing_phone) : (!empty($user->shipping_phone) ? sanitize_text_field($user->shipping_phone) : ''),
            'postcode' => !empty($user->billing_postcode) ? sanitize_text_field($user->billing_postcode) : (!empty($user->shipping_postcode) ? sanitize_text_field($user->shipping_postcode) : ''),
            'languageCode' => !empty(substr(get_user_meta($user->ID, 'locale', true), 0, 2)) ? substr(get_user_meta($user->ID, 'locale', true), 0, 2) : ( get_bloginfo('language') ? get_bloginfo('language') : '')
        ];
    }

    private static function update_user_transformation_to_array($request, $user_id){
        return [
            'email' => sanitize_email($request['billing_email']),
            'ID' => absint($user_id),
            'first_name' => isset($request['billing_first_name']) ? sanitize_text_field($request['billing_first_name']) : '',
            'last_name' => isset($request['billing_last_name']) ? sanitize_text_field($request['billing_last_name']) : '',
            'state' => isset($request['billing_state']) ? sanitize_text_field($request['billing_state']) : '',
            'country_id' => isset($request['billing_country']) ? sanitize_text_field($request['billing_country']) : '',
            'city' => isset($request['billing_city']) ? sanitize_text_field($request['billing_city']) : '',
            'address_1' => isset($request['billing_address_1']) ? sanitize_text_field($request['billing_address_1']) : '',
            'address_2' => isset($request['billing_address_2']) ? sanitize_text_field($request['billing_address_2']) : '',
            'phone' => isset($request['billing_phone']) ? sanitize_text_field($request['billing_phone']) : '',
            'postcode' => isset($request['billing_postcode']) ? sanitize_text_field($request['billing_postcode']) : '',
            'languageCode' => ''
        ];
    }

    private static function order_transformation_to_array($order){
        return [
            'email' => !empty($order->get_billing_email()) ? $order->get_billing_email() : (!empty($order->get_shipping_email()) ? $order->get_shipping_email() : ''),
            'ID' => self::get_registered_user_id($order),
            'first_name' => !empty($order->get_billing_first_name()) ? $order->get_billing_first_name() : (!empty($order->get_shipping_first_name()) ? $order->get_shipping_first_name() : ''),
            'last_name' => !empty($order->get_billing_last_name()) ? $order->get_billing_last_name() : (!empty($order->get_shipping_last_name()) ? $order->get_shipping_last_name() : ''),
            'state' => !empty(self::get_state_name($order->get_billing_country(), $order->get_billing_state())) ? self::get_state_name($order->get_billing_country(), $order->get_billing_state()) : (!empty(self::get_state_name($order->get_shipping_country(), $order->get_shipping_state())) ? self::get_state_name($order->get_shipping_country(), $order->get_shipping_state()) : ''),
            'country_id' => !empty($order->get_billing_country()) ? $order->get_billing_country() : (!empty($order->get_shipping_country()) ? $order->get_shipping_country() : ''),
            'city' => !empty($order->get_billing_city()) ? $order->get_billing_city() : (!empty($order->get_shipping_city()) ? $order->get_shipping_city() : ''),
            'address_1' => (!empty($order->get_billing_address_1()) ? $order->get_billing_address_1() : '') . (!empty($order->get_billing_address_2()) ? ', ' . $order->get_billing_address_2() : '') ?? (!empty($order->get_shipping_address_1()) ? $order->get_shipping_address_1() : '') . (!empty($order->get_shipping_address_2()) ? ', ' . $order->get_shipping_address_2() : ''),
            'address_2' => (!empty($order->get_billing_address_1()) ? $order->get_billing_address_1() : '') . (!empty($order->get_billing_address_2()) ? ', ' . $order->get_billing_address_2() : '') ?? (!empty($order->get_shipping_address_1()) ? $order->get_shipping_address_1() : '') . (!empty($order->get_shipping_address_2()) ? ', ' . $order->get_shipping_address_2() : ''),
            'phone' => !empty($order->get_billing_phone()) ? $order->get_billing_phone() : (!empty($order->get_shipping_phone()) ? $order->get_shipping_phone() : ''),
            'postcode' => !empty($order->get_billing_postcode()) ? $order->get_billing_postcode() : (!empty($order->get_shipping_postcode()) ? $order->get_shipping_postcode() : '')
        ];
    }

    private static function admin_order_transformation_to_array($post){
        return [
            'email' => sanitize_email($post['_billing_email']),
            'first_name' => !empty($post['_billing_first_name']) ? sanitize_text_field($post['_billing_first_name']) : (!empty($post['_shipping_first_name']) ? sanitize_text_field($post['_shipping_first_name']) : ''),
            'last_name' => !empty($post['_billing_last_name']) ? sanitize_text_field($post['_billing_last_name']) : (!empty($post['_shipping_last_name']) ? sanitize_text_field($post['_shipping_last_name']) : ''),
            'state' => !empty(self::get_state_name($post['_billing_country'], $post['_billing_state'])) ? self::get_state_name($post['_billing_country'], $post['_billing_state']) : (!empty(self::get_state_name($post['_shipping_country'], $post['_shipping_state'])) ? self::get_state_name($post['_shipping_country'], $post['_shipping_state']) : ''),
            'country_id' => !empty($post['_billing_country']) ? sanitize_text_field($post['_billing_country']) : (!empty($post['_shipping_country']) ? sanitize_text_field($post['_shipping_country']) : ''),
            'city' => !empty($post['_billing_city']) ? sanitize_text_field($post['_billing_city']) : (!empty($post['_shipping_city']) ? sanitize_text_field($post['_shipping_city']) : ''),
            'address_1' => !empty($post['_billing_address_1']) ? sanitize_text_field($post['_billing_address_1']) : (!empty($post['_shipping_address_1']) ? sanitize_text_field($post['_shipping_address_1']) : ''),
            'address_2' => !empty($post['_billing_address_2']) ? sanitize_text_field($post['_billing_address_2']) : (!empty($post['_shipping_address_2']) ? sanitize_text_field($post['_shipping_address_2']) : ''),
            'phone' => !empty($post['_billing_phone']) ? sanitize_text_field($post['_billing_phone']) : '',
            'postcode' => !empty($post['_billing_postcode']) ? sanitize_text_field($post['_billing_postcode']) : (!empty($post['_shipping_postcode']) ? sanitize_text_field($post['_shipping_postcode']) : '')
        ];
    }

    private static function subscription_transformation_to_array($email){
        return [
            'email' => sanitize_email( $email ),
            'ID'    => sanitize_email( $email )
        ];
    }

    private static function get_registered_user_id($order){
        $user = get_user_by('email', $order->get_billing_email());
        if($user) return $user->ID;
        else return null;
    }

    /** get country name by ID **/
    private static function get_country_name($country_id){
        if(!empty($country_id)) {
            $country_list = WC()->countries->get_countries();
            if (isset($country_list[$country_id])) {
                return $country_list[$country_id];
            }
        }
    }
    /** get state name by ID **/
    private static function get_state_name($country_id, $state_id){
        if(!empty($country_id) && !empty($state_id)) {
            $states = WC()->countries->get_states($country_id);
            if (isset($states[$state_id])) {
                return $states[$state_id];
            }
        }
    }
}