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
 * Create the settings page in the backend
 */
class Settings_Page extends Base {

    /**
     * Initialize the class.
     *
     * @return void|bool
     */
    public function initialize() {
        if ( !parent::initialize() ) {
            return;
        }

        // Add the options page and menu item.
        \add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

        $realpath        = (string) \realpath( __DIR__ );
        $plugin_basename = \plugin_basename( \plugin_dir_path( $realpath ) . 'yespo' . '.php' );
        \add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_plugin_admin_menu() {
        /*
         * Add a settings page for this plugin to the Settings menu
         *
         * @TODO:
         *
         * - Change 'manage_options' to the capability you see fit
         *   For reference: http://codex.wordpress.org/Roles_and_Capabilities

        add_options_page( __( 'Page Title', YESPO_TEXTDOMAIN ), YESPO_NAME, 'manage_options', YESPO_TEXTDOMAIN, array( $this, 'display_plugin_admin_page' ) );
         *
         */
        /*
         * Add a settings page for this plugin to the main menu
         *
         */
        \add_menu_page( \__( 'Yespo Settings', 'yespo-cdp' ), 'Yespo', 'manage_options', 'yespo-cdp', array( $this, 'display_plugin_admin_page' ), 'dashicons-rest-api', 90 );
/*
        add_submenu_page(
            YESPO_TEXTDOMAIN,
            __('Settings', YESPO_TEXTDOMAIN),
            __('Settings', YESPO_TEXTDOMAIN),
            'manage_options',
            'yespo_settings',
            array($this, 'display_plugin_settings_page')
        );
*/
        /*
        add_filter('parent_file', function($parent_file) {
            global $submenu_file;
            global $current_screen;

            if ($current_screen->base === 'toplevel_page_' . YESPO_TEXTDOMAIN) {
                $submenu_file = 'yespo_settings';
            }

            return $parent_file;
        });
        */
/*
        add_action('admin_menu', function() {
            global $submenu;

            if (isset($submenu[YESPO_TEXTDOMAIN])) {
                unset($submenu[YESPO_TEXTDOMAIN][0]);
            }
        }, 999);
*/
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since 1.0.0
     * @return void
     */
    public function display_plugin_admin_page() {
        //include_once YESPO_PLUGIN_ROOT . 'backend/views/admin.php';
        include_once YESPO_PLUGIN_ROOT . 'backend/views/settings.php';
    }

    public function display_plugin_settings_page() {
        include_once YESPO_PLUGIN_ROOT . 'backend/views/settings.php';
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since 1.0.0
     * @param array $links Array of links.
     * @return array
     */
    public function add_action_links( array $links ) {
        return \array_merge(
            array(
                //'settings' => '<a href="' . \admin_url( 'admin.php?page=' . YESPO_TEXTDOMAIN . '_settings' ) . '">' . \__( 'Settings', YESPO_TEXTDOMAIN ) . '</a>',
                'settings' => '<a href="' . \admin_url( 'admin.php?page=' . 'yespo-cdp' ) . '">' . \__( 'Settings', 'yespo-cdp' ) . '</a>',
            ),
            $links
        );
    }

}
