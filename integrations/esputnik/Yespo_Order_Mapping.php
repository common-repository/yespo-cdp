<?php

namespace Yespo\Integrations\Esputnik;

use DateTime;
use WP_User_Query;

class Yespo_Order_Mapping
{
    const INITIALIZED = 'INITIALIZED';
    const IN_PROGRESS = 'IN_PROGRESS';
    const CANCELLED = 'CANCELLED';
    const DELIVERED = 'DELIVERED';
    const NO_CATEGORY = 'no category';
    const ORDER_META_KEY = 'sent_order_to_yespo';

    public static function order_woo_to_yes($order){
        $orderArray = self::order_transformation_to_array($order);
        if (isset($orderArray['phone']) && !empty($orderArray['phone'])) {
            $phoneNumber = $orderArray['phone'];
        } else $phoneNumber = '';

        $data['orders'][0]['status'] = $orderArray['status'];
        $data['orders'][0]['externalOrderId'] = $orderArray['externalOrderId'];
        if($orderArray['externalCustomerId']) $data['orders'][0]['externalCustomerId'] = $orderArray['externalCustomerId'];
        $data['orders'][0]['totalCost'] = $orderArray['totalCost'];
        $data['orders'][0]['email'] = $orderArray['email'];
        $data['orders'][0]['date'] = $orderArray['date'];
        $data['orders'][0]['currency'] = $orderArray['currency'];
        if(Yespo_Contact_Validation::name_validation($orderArray['firstName'])) $data['orders'][0]['firstName'] = $orderArray['firstName'];
        if(Yespo_Contact_Validation::lastname_validation($orderArray['lastName'])) $data['orders'][0]['lastName'] = $orderArray['lastName'];
        $data['orders'][0]['deliveryAddress'] = $orderArray['deliveryAddress'];
        $data['orders'][0]['phone'] = preg_replace("/[^0-9]/", "", $phoneNumber);
        $data['orders'][0]['shipping'] = $orderArray['shipping'];
        $data['orders'][0]['discount'] = $orderArray['discount'];
        $data['orders'][0]['taxes'] = $orderArray['taxes'];
        $data['orders'][0]['source'] = $orderArray['source'];
        $data['orders'][0]['paymentMethod'] = $orderArray['paymentMethod'];
        $data['orders'][0]['items'] = self::get_orders_items($order);
        if($orderArray['additionalInfo']) $data['orders'][0]['additionalInfo']['comment'] = $orderArray['additionalInfo'];

        if(empty($data['orders'][0]['email']) && empty($data['orders'][0]['phone'])){
            return false;
        }
        return $data;
    }

    //array users for bulk export
    public static function order_bulk_woo_to_yes($order){
        $orderArray = self::order_transformation_to_array($order);
        if (isset($orderArray['phone']) && !empty($orderArray['phone'])) {
            $phoneNumber = $orderArray['phone'];
        } else $phoneNumber = '';

        $data['status'] = $orderArray['status'];
        $data['externalOrderId'] = $orderArray['externalOrderId'];
        if($orderArray['externalCustomerId']) $data['externalCustomerId'] = $orderArray['externalCustomerId'];
        $data['totalCost'] = $orderArray['totalCost'];
        $data['email'] = $orderArray['email'];
        $data['date'] = $orderArray['date'];
        $data['currency'] = $orderArray['currency'];
        if(Yespo_Contact_Validation::name_validation($orderArray['firstName'])) $data['firstName'] = $orderArray['firstName'];
        if(Yespo_Contact_Validation::lastname_validation($orderArray['lastName'])) $data['lastName'] = $orderArray['lastName'];
        $data['deliveryAddress'] = $orderArray['deliveryAddress'];
        $data['phone'] = preg_replace("/[^0-9]/", "", $phoneNumber);
        $data['shipping'] = $orderArray['shipping'];
        $data['discount'] = $orderArray['discount'];
        $data['taxes'] = $orderArray['taxes'];
        $data['source'] = $orderArray['source'];
        $data['paymentMethod'] = $orderArray['paymentMethod'];
        $data['items'] = self::get_orders_items($order);
        if($orderArray['additionalInfo']) $data['additionalInfo']['comment'] = $orderArray['additionalInfo'];

        return $data;
    }
    public static function create_bulk_order_export_array($orders){
        $data = [];
        if($orders && count($orders) > 0){
            foreach($orders as $order){
                $data['orders'][] = self::order_bulk_woo_to_yes(wc_get_order($order->id));
            }
        }
        return $data;
    }

    private static function order_transformation_to_array($order){
        return [
            'externalOrderId' => sanitize_text_field($order->id),
            'externalCustomerId' => !empty($order) && !is_bool($order)? $order->get_user_id() : '',
            'totalCost' => sanitize_text_field($order->total),
            'status' => self::get_order_status($order->status) ? self::get_order_status($order->status) : self::INITIALIZED,
            'email' => (!empty($order) && !is_bool($order) && method_exists($order, 'get_billing_email') && !empty($order->get_billing_email())) ? $order->get_billing_email() : '',
            'date' => ($order && !is_bool($order) && $order->get_meta('yespo_order_time'))? self::get_time_order_created($order->get_meta('order_time')) : (($order && !is_bool($order) && method_exists($order, 'get_date_created') && ($date_created = $order->get_date_created())) ? $date_created->format('Y-m-d\TH:i:s.uP') : null),
            'currency' => sanitize_text_field($order->currency),
            'firstName' => (!empty($order) && !is_bool($order) && method_exists($order, 'get_billing_first_name') && !empty($order->get_billing_first_name())) ? sanitize_text_field($order->get_billing_first_name()) : (!empty($order) && !is_bool($order) && method_exists($order, 'get_shipping_first_name') && !empty($order->get_shipping_first_name()) ? sanitize_text_field($order->get_shipping_first_name()) : ''),
            'lastName' => (!empty($order) && !is_bool($order) && method_exists($order, 'get_billing_last_name') && !empty($order->get_billing_last_name())) ? sanitize_text_field($order->get_billing_last_name()) : (!empty($order) && !is_bool($order) && method_exists($order, 'get_shipping_last_name') && !empty($order->get_shipping_last_name()) ? sanitize_text_field($order->get_shipping_last_name()) : ''),
            'deliveryAddress' => self::get_delivery_address($order, 'shipping') ? sanitize_text_field(self::get_delivery_address($order, 'shipping')) : ( self::get_delivery_address($order, 'billing') ? sanitize_text_field(self::get_delivery_address($order, 'billing')) : ''),
            'phone' => self::get_phone_number($order),
            'shipping' => ($order->shipping_total) ? $order->shipping_total : '',
            'discount' => ($order->discount) ? $order->discount : '',
            'taxes' => !empty($order->total_tax) ? $order->total_tax : ((!empty($order->discount_tax)) ? $order->discount_tax : ((!empty($order->cart_tax)) ? $order->cart_tax : ((!empty($order->shipping_tax)) ? $order->shipping_tax : ''))),
            'source' => ($order->created_via) ? $order->created_via : '',
            'paymentMethod' => ($order->payment_method) ? $order->payment_method : '',
            'country_id' => (!empty($order) && !is_bool($order) && method_exists($order, 'get_billing_country') && !empty($order->get_billing_country())) ? $order->get_billing_country() : (!empty($order) && !is_bool($order) && method_exists($order, 'get_shipping_country') && !empty($order->get_shipping_country()) ? $order->get_shipping_country() : ''),
            'additionalInfo' => (!empty($order) && !is_bool($order) && method_exists($order, 'get_customer_note') && !empty($order->get_customer_note()))? $order->get_customer_note():''
        ];
    }

    private static function get_delivery_address($order, $way){
        $deliveryAddress = '';
        $fields = array('address_2', 'address_1', 'city', 'state', 'postcode', 'country');

        foreach ($fields as $field) {
            $method = 'get_' . $way . '_' . $field;
            $value = (!empty($order) && !is_bool($order) && method_exists($order, $method)) ? $order->$method() : '';
            if($field === 'country') $value = self::get_country_name($value);
            if($field === 'state'){
                $method = 'get_' . $way . '_country';
                $country = (!empty($order) && !is_bool($order) && method_exists($order, $method)) ? $order->$method() : '';
                $value = self::get_state_name($country, $value);
            }

            if (!empty($value)) {
                $deliveryAddress .= $value . ' ';
            }
        }
        return $deliveryAddress;
    }

    private static function get_order_status($order_status){
        switch ($order_status){
            case 'processing':
            case 'on-hold':
                $result = self::IN_PROGRESS;
                break;
            case 'failed':
            case 'cancelled':
            case 'trash':
            case 'refunded':
                $result = self::CANCELLED;
                break;
            case 'completed':
                $result = self::DELIVERED;
                break;
            default:
                $result = self::INITIALIZED;
        }
        return $result;
    }

    private static function get_orders_items($order){
        $data = [];
        $i = 0;
        if (!empty($order) && !is_bool($order) && method_exists($order, 'get_items')) {
            foreach ($order->get_items() as $item_id => $item) {
                $data[$i]['externalItemId'] = $item->get_product_id();
                $data[$i]['name'] = $item->get_name();
                $data[$i]['category'] = self::get_product_category($data[$i]['externalItemId']);
                $data[$i]['quantity'] = $item->get_quantity();
                $data[$i]['cost'] = $item->get_subtotal();
                $data[$i]['url'] = get_permalink( $data[$i]['externalItemId'] );
                $data[$i]['imageUrl'] = self::get_product_thumbnail_url($data[$i]['externalItemId']);
                $data[$i]['description'] = (wc_get_product( $data[$i]['externalItemId']))->get_short_description();
                $i++;
            }
        }
        return $data;
    }

    private static function get_product_category($product_id){
        $terms = wp_get_post_terms( $product_id, 'product_cat' );
        $categories = [];
        if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
            $categories = array_map(function($term) {
                return $term->name;
            }, $terms);
        }
        return !empty($categories) ? implode(", ", $categories) : self::NO_CATEGORY;
    }
    private static function get_product_thumbnail_url($product_id){
        $image_id = get_post_thumbnail_id( $product_id );
        if ( $image_id ) $image_src = wp_get_attachment_image_src( $image_id, 'full' );
        return !empty($image_src) && is_array($image_src) ? $image_src[0] : '';
    }

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

    private static function get_user_id($email){
        return (new \Yespo\Integrations\Esputnik\Yespo_Contact())->get_user_id_by_email($email);
    }

    /* phone */
    private static function get_phone_number($order){
        $phone = (!empty($order) && !is_bool($order) && method_exists($order, 'get_billing_phone') && !empty($order->get_billing_phone())) ? $order->get_billing_phone() : (!empty($order) && !is_bool($order) && method_exists($order, 'get_shipping_phone') && !empty($order->get_shipping_phone()) ? $order->get_shipping_phone() : '');
        if(!empty($phone)){
            $validated_phone = self::phone_validation(trim(sanitize_text_field($phone)));
            if($validated_phone) return $validated_phone;
        }
        return '';
    }

    private static function phone_validation($phone){
        $digits = preg_replace('/\D/', '', $phone);

        $phone_length = strlen($digits);
        if ($phone_length > 4 && $phone_length < 16) {
            return $digits;
        }

        return false;
    }

    /* email */
    private static function get_email($order){
        $email = (!empty($order) && !is_bool($order) && method_exists($order, 'get_billing_email') && !empty($order->get_billing_email())) ? $order->get_billing_email() : '';
        if(!empty($email)){
            $validated_email = self::email_validation(trim(sanitize_email(self::get_user_main_email($email))));
            if($validated_email) return $validated_email;
        }
        return '';
    }

    private static function email_validation($email){
        if(preg_match('/\.$/', $email) || strlen($email) > 50) return '';
        if(!preg_match('/^[a-zA-Z0-9.+_@-]+$/', $email)) return '';
        $new_array = explode("@", $email);
        if(count($new_array) > 2) return '';
        $domen = explode(".", $new_array[1]);
        if(count($domen) < 2 || strlen(end($domen)) < 2 || in_array('', $domen, true)) return '';

        return $email;
    }

    private static function get_user_main_email($billing_email){
        $args = array(
            // phpcs:ignore WordPress.DB.SlowDBQuery
            'meta_query' => array(
                array(
                    'key' => 'billing_email',
                    'value' => $billing_email,
                    'compare' => '='
                )
            )
        );

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();

        $emails = [];

        if (!empty($users)) {
            foreach ($users as $user) {
                $emails[] = $user->user_email;
            }
        }
        $new_mail = $billing_email;
        if (count($emails) > 0 && $new_mail != $emails[0]) $new_mail = $emails[0];

        return $new_mail;
    }

    private static function get_time_order_created($order_time_created){
        if ($order_time_created) {
            $order_time = new DateTime($order_time_created);
            $formatted_order_time = $order_time->format('Y-m-d\TH:i:s.uP');

            return $formatted_order_time;
        }
    }
}