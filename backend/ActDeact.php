<?php

/**
 * Yespo
 *
 * @package   Yespo
 * @author    Yespo Omnichannel CDP <yespoplugin@yespo.io>
 * @copyright 2022 Yespo
 * @license   GPL 3.0+
 * @link      https://yespo.io/
 */

namespace Yespo\Backend;

use Yespo\Engine\Base;

/**
 * Activate and deactive method of the plugin and relates.
 */
class ActDeact extends Base {

    /**
     * Initialize the class.
     *
     * @return void|bool
     */

    private static $wpdb;

    public function __construct() {
        global $wpdb;
        self::$wpdb = $wpdb;
    }

    public function initialize() {
        if ( !parent::initialize() ) {
            return;
        }

        // Activate plugin when new blog is added
        \add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

        \add_action( 'admin_init', array( $this, 'upgrade_procedure' ) );
    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @param int $blog_id ID of the new blog.
     * @since 1.0.0
     * @return void
     */
    public function activate_new_site( int $blog_id ) {
        if ( 1 !== \did_action( 'wpmu_new_blog' ) ) {
            return;
        }

        \switch_to_blog( $blog_id );
        self::single_activate();
        \restore_current_blog();

    }

    /**
     * Fired when the plugin is activated.
     *
     * @param bool|null $network_wide True if active in a multiste, false if classic site.
     * @since 1.0.0
     * @return void
     */
    public static function activate( $network_wide ) {
        if ( \function_exists( 'is_multisite' ) && \is_multisite() ) {
            if ( $network_wide ) {
                // Get all blog ids
                /** @var array<\WP_Site> $blogs */
                $blogs = \get_sites();

                foreach ( $blogs as $blog ) {
                    \switch_to_blog( (int) $blog->blog_id );
                    self::single_activate();
                    \restore_current_blog();
                }

                return;
            }
        }

        self::single_activate();
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @param bool $network_wide True if WPMU superadmin uses
     * "Network Deactivate" action, false if
     * WPMU is disabled or plugin is
     * deactivated on an individual blog.
     * @since 1.0.0
     * @return void
     */
    public static function deactivate( bool $network_wide ) {
        if ( \function_exists( 'is_multisite' ) && \is_multisite() ) {
            if ( $network_wide ) {
                // Get all blog ids
                /** @var array<\WP_Site> $blogs */
                $blogs = \get_sites();

                foreach ( $blogs as $blog ) {
                    \switch_to_blog( (int) $blog->blog_id );
                    self::single_deactivate();
                    \restore_current_blog();
                }

                return;
            }
        }

        self::single_deactivate();
        //self::yespo_crone_deactivate();
    }

    /**
     * Add admin capabilities
     *
     * @return void
     */
    public static function add_capabilities() {
        // Add the capabilites to all the roles
        $caps  = array(
            'create_plugins',
            'read_demo',
            'read_private_demoes',
            'edit_demo',
            'edit_demoes',
            'edit_private_demoes',
            'edit_published_demoes',
            'edit_others_demoes',
            'publish_demoes',
            'delete_demo',
            'delete_demoes',
            'delete_private_demoes',
            'delete_published_demoes',
            'delete_others_demoes',
            'manage_demoes',
        );
        $roles = array(
            \get_role( 'administrator' ),
            \get_role( 'editor' ),
            \get_role( 'author' ),
            \get_role( 'contributor' ),
            \get_role( 'subscriber' ),
        );

        foreach ( $roles as $role ) {
            foreach ( $caps as $cap ) {
                if ( \is_null( $role ) ) {
                    continue;
                }

                $role->add_cap( $cap );
            }
        }
    }

    /**
     * Remove capabilities to specific roles
     *
     * @return void
     */
    public static function remove_capabilities() {
        // Remove capabilities to specific roles
        $bad_caps = array(
            'create_demoes',
            'read_private_demoes',
            'edit_demo',
            'edit_demoes',
            'edit_private_demoes',
            'edit_published_demoes',
            'edit_others_demoes',
            'publish_demoes',
            'delete_demo',
            'delete_demoes',
            'delete_private_demoes',
            'delete_published_demoes',
            'delete_others_demoes',
            'manage_demoes',
        );
        $roles    = array(
            \get_role( 'author' ),
            \get_role( 'contributor' ),
            \get_role( 'subscriber' ),
        );

        foreach ( $roles as $role ) {
            foreach ( $bad_caps as $cap ) {
                if ( \is_null( $role ) ) {
                    continue;
                }

                $role->remove_cap( $cap );
            }
        }
    }

    /**
     * Upgrade procedure
     *
     * @return void
     */
    public static function upgrade_procedure() {
        if ( !\is_admin() ) {
            return;
        }

        $version = \strval( \get_option( 'yespo-version' ) );

        if ( !\version_compare( YESPO_VERSION, $version, '>' ) ) {
            return;
        }

        \update_option( 'yespo-version', YESPO_VERSION );
        \delete_option( YESPO_TEXTDOMAIN . '_fake-meta' );
    }

    /**
     * Fired for each blog when the plugin is activated.
     *
     * @since 1.0.0
     * @return void
     */
    private static function single_activate() {
        // @TODO: Define activation functionality here
        // add_role( 'advanced', __( 'Advanced' ) ); //Add a custom roles
        self::create_databases(self::$wpdb);
        self::add_capabilities();
        self::upgrade_procedure();
        (new \Yespo\Integrations\Esputnik\Yespo_Export_Users())->update_after_activation();
        (new \Yespo\Integrations\Esputnik\Yespo_Export_Orders())->update_after_activation();

        // Clear the permalinks
        \flush_rewrite_rules();

    }

    /**
     * Fired for each blog when the plugin is deactivated.
     *
     * @since 1.0.0
     * @return void
     */
    private static function single_deactivate() {
        // @TODO: Define deactivation functionality here
        self::remove_capabilities();
        // Clear the permalinks
        \flush_rewrite_rules();
    }

    /** this code creates new table in database **/
    public static function create_databases($wpdb){

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'yespo_contact_log';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id varchar(255) NOT NULL,
            contact_id varchar(255) NOT NULL,
            action varchar(255) NOT NULL,
            yespo INT NULL,
            log_date datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);


        $table_name_order_log = $wpdb->prefix . 'yespo_order_log';
        $sql_order_log = "CREATE TABLE IF NOT EXISTS $table_name_order_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id varchar(255) NOT NULL,
            action varchar(255) NOT NULL,
            status varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime default NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_order_log);


        $table_export_name = $wpdb->prefix . 'yespo_export_status_log';
        $sqlExport = "CREATE TABLE IF NOT EXISTS $table_export_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            export_type varchar(255) NOT NULL,
            total varchar(255) NOT NULL,
            exported varchar(255) NOT NULL,
            status varchar(255) NOT NULL,
            code varchar(255) default NULL,
            updated_at datetime default NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sqlExport);


        $table_yespo_queue = $wpdb->prefix . 'yespo_queue';
        $sqlQueue = "CREATE TABLE IF NOT EXISTS $table_yespo_queue (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            export_status varchar(255) default NULL,
            local_status varchar(255) default NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sqlQueue);


        $table_yespo_queue_items = $wpdb->prefix . 'yespo_queue_items';
        $sqlQueueItems = "CREATE TABLE IF NOT EXISTS $table_yespo_queue_items (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) default NULL,
            contact_id varchar(255) NOT NULL,
            yespo_id varchar(255) default NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sqlQueueItems);


        $table_yespo_queue_orders = $wpdb->prefix . 'yespo_queue_orders';
        $sqlQueueOrders = "CREATE TABLE IF NOT EXISTS $table_yespo_queue_orders (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            yespo_status varchar(255) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sqlQueueOrders);


        $table_yespo_curl_json = $wpdb->prefix . 'yespo_curl_json';
        $sqlOrdersJson = "CREATE TABLE IF NOT EXISTS $table_yespo_curl_json (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            text longtext default NULL,
            created_at datetime default NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sqlOrdersJson);


        $table_yespo_auth = $wpdb->prefix . 'yespo_auth_log';
        $sqlYespoAuth = "CREATE TABLE IF NOT EXISTS $table_yespo_auth (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            api_key varchar(255) default NULL,
            response varchar(255) default NULL,
            time datetime default NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sqlYespoAuth);


        $table_yespo_removed = $wpdb->prefix . 'yespo_removed_users';
        $sqlYespoRemoved = "CREATE TABLE IF NOT EXISTS $table_yespo_removed (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(255) default NULL,
            time datetime default NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sqlYespoRemoved);


        $table_yespo_errors = $wpdb->prefix . 'yespo_errors';
        $sqlYespoErrors = "CREATE TABLE IF NOT EXISTS $table_yespo_errors (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            error varchar(255) default NULL,
            time datetime default NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sqlYespoErrors);


    }

    public static function yespo_crone_deactivate(){

        register_deactivation_hook (__FILE__, function(){

            $timestamp = wp_next_scheduled ('yespo_export_data_cron');
            wp_unschedule_event ($timestamp, 'yespo_export_data_cron');

        });
    }

}
