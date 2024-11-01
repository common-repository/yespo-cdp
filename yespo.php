<?php

/**
 * @package   Yespo
 * @author    Yespo
 * @copyright 2024 Yespo
 * @license   GPL-2.0-or-later
 *
 * Plugin Name:     Yespo CDP - Marketing Automation, Omnichannel, Segmentation & Personalization
 * Description:     Get CDP power for your business: improve customer conversion and retention with better personalization and omnichannel campaigns
 * Version:         1.0.1
 * Author:          Yespo
 * Author URI:      https://yespo.io/
 * License:         GPLv2 or later
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:     /languages
 * Requires PHP:    7.4
 * Requires at least: 6.5.5
 * Requires Plugins: woocommerce
 */

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

define( 'YESPO_VERSION', '1.0.1' );
define( 'YESPO_TEXTDOMAIN', 'yespo-cdp' );
define( 'YESPO_MAIN_PLUGIN_FOLDER', 'yespo-cdp' );
define( 'YESPO_NAME', 'Yespo' );
define( 'YESPO_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
define( 'YESPO_PLUGIN_ABSOLUTE', __FILE__ );
define( 'YESPO_MIN_PHP_VERSION', '7.4' );
define( 'YESPO_WP_VERSION', '6.5.5' );
define( 'YESPO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'YESPO_CLIENT_ID', 'd048ab4ffd96be4ee0c17510d8a42486' );
define( 'YESPO_CALLBACK', '9bf62295abbb565a5f4e248f30e00b741d3dd713d7cea79c737f14a5ed775486' );


add_action(
	'init',
	static function () {
		load_plugin_textdomain( YESPO_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	);

if ( version_compare( PHP_VERSION, YESPO_MIN_PHP_VERSION, '<=' ) ) {
	add_action(
		'admin_init',
		static function() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	);
	add_action(
		'admin_notices',
		static function() {
			echo wp_kses_post(
			sprintf(
				'<div class="notice notice-error"><p>%s</p></div>',
				__( '"Yespo" requires PHP 7.4 or newer.', 'yespo-cdp' )
			)
			);
		}
	);

	// Return early to prevent loading the plugin.
	return;
}

$yespo_libraries = require YESPO_PLUGIN_ROOT . 'vendor/autoload.php'; //phpcs:ignore

require_once YESPO_PLUGIN_ROOT . 'functions/functions.php';
require_once YESPO_PLUGIN_ROOT . 'functions/debug.php';

// Add your new plugin on the wiki: https://github.com/WPBP/WordPress-Plugin-Boilerplate-Powered/wiki/Plugin-made-with-this-Boilerplate

$requirements = new \Micropackage\Requirements\Requirements(
	'Yespo',
	array(
		'php'            => YESPO_MIN_PHP_VERSION,
		'php_extensions' => array( 'mbstring' ),
		'wp'             => YESPO_WP_VERSION,
	)
);

if ( ! $requirements->satisfied() ) {
	$requirements->print_notice();

	return;
}

if ( ! wp_installing() ) {
	register_activation_hook( YESPO_MAIN_PLUGIN_FOLDER . '/' . 'yespo' . '.php', array( new \Yespo\Backend\ActDeact, 'activate' ) );
	register_deactivation_hook( YESPO_MAIN_PLUGIN_FOLDER . '/' . 'yespo' . '.php', array( new \Yespo\Backend\ActDeact, 'deactivate' ) );
	add_action(
		'plugins_loaded',
		static function () use ( $yespo_libraries ) {
			new \Yespo\Engine\Initialize( $yespo_libraries );
		}
	);
}

function yespo_export_data_activation(){
    if (!wp_next_scheduled('yespo_export_data_cron')) {
        wp_schedule_event(time(), 'every_minute', 'yespo_export_data_cron');
    }

}

yespo_export_data_activation();