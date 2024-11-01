<?php

/**
 * Yespo
 *
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @package   Yespo
 * @author    Yespo Omnichannel CDP <yespoplugin@yespo.io>
 * @copyright 2022 Yespo
 * @license   GPL 3.0+
 * @link      https://yespo.io/
 */

// If uninstall not called from WordPress, then exit.
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Loop for uninstall
 *
 * @return void
 */
function yespo_uninstall_multisite() {
	if ( is_multisite() ) {
		/** @var array<\WP_Site> $blogs */
		$blogs = get_sites();

		if ( !empty( $blogs ) ) {
			foreach ( $blogs as $blog ) {
				switch_to_blog( (int) $blog->blog_id );
				y_uninstall();
				restore_current_blog();
			}

			return;
		}
	}

	yespo_uninstall();
}

/**
 * What happens on uninstallation?
 *
 * @global WP_Roles $wp_roles
 * @return void
 */
function yespo_uninstall() { // phpcs:ignore
    global $wpdb;

    $contact_log = $wpdb->prefix . 'yespo_contact_log';
    $export_status_log = $wpdb->prefix . 'yespo_export_status_log';
    $order_log = $wpdb->prefix . 'yespo_order_log';
    $table_yespo_queue = $wpdb->prefix . 'yespo_queue';
    $table_yespo_queue_items = $wpdb->prefix . 'yespo_queue_items';
    $table_yespo_queue_orders = $wpdb->prefix . 'yespo_queue_orders';
    $table_yespo_curl_json = $wpdb->prefix . 'yespo_curl_json'; //logging jsons to yespo
    $table_yespo_auth_log = $wpdb->prefix . 'yespo_auth_log'; //auth logging
    $table_yespo_removed = $wpdb->prefix . 'yespo_removed_users';
    $table_yespo_errors = $wpdb->prefix . 'yespo_errors';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$contact_log));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$export_status_log));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$order_log));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$table_yespo_queue));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$table_yespo_queue_items));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$table_yespo_queue_orders));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$table_yespo_curl_json));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$table_yespo_auth_log));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$table_yespo_removed));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i",$table_yespo_errors));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $wpdb->usermeta WHERE meta_key IN (%s, %s)",
            'yespo_contact_id',
            'yespo_bad_request'
        )
    );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s, %s, %s) AND post_id IN (
            SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('shop_order', 'shop_order_placehold')
        )",
            'sent_order_to_yespo',
            'yespo_order_time',
            'yespo_customer_removed',
            'yespo_bad_request'
        )
    );

    delete_option('yespo_options');
    delete_option('yespo-version');

    
    if (wp_next_scheduled('yespo_export_data_cron')) {
        wp_clear_scheduled_hook('yespo_export_data_cron');
    }

}

yespo_uninstall_multisite();
