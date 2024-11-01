<?php

namespace Yespo\Integrations\Esputnik;

class Yespo_Export_Service
{
    private $contactClass;
    private $orderClass;
    private $table_name;
    private $wpdb;
    private $export_type;

    public function __construct(){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'yespo_export_status_log';
        $this->contactClass = new Yespo_Export_Users();
        $this->orderClass = new Yespo_Export_Orders();
        $this->export_type = 'allexport';
    }

    public static function get_export_total(){
        $service = new self();
        $total = intval($service->contactClass->get_users_total_count()) + intval($service->orderClass->get_total_orders());
        if($total === 0) $total = 1;
        return $total;
    }

    public static function get_exported_number(){
        $service = new self();
        $total = self::get_export_total();
        $total_for_export = intval($service->contactClass->get_users_export_count()) + intval($service->orderClass->get_export_orders_count());
        return $total - $total_for_export;
    }

}