<?php

namespace Yespo\Integrations\Esputnik;

class Yespo_Localization
{
    public static function localize_template(){
        return wp_localize_script( YESPO_TEXTDOMAIN . '-settings-admin', 'yespoVars', array(
            'h1' => esc_html__( 'Synchronization progress', 'yespo-cdp' ),
            'outSideText' => esc_html__( 'Synchronize contacts and orders for subsequent analysis and efficient data utilization using Yespo marketing automation tools', 'yespo-cdp' ),
            'h4' => esc_html__( 'The first data export will take some time; it will happen in the background, and it is not necessary to stay on the page', 'yespo-cdp' ),
            'resume' => esc_html__( 'The synchronization process has been paused; you can resume it from the moment of pausing without losing the previous progress', 'yespo-cdp' ),
            'error' => esc_html__( 'Some error have occurred. Try to resume synchronization. If it doesn’t help, contact Support', 'yespo-cdp' ),
            'error401' => esc_html__( 'Invalid API key. Please delete the plugin and start the configuration from scratch using a valid API key. No data will be lost.', 'yespo-cdp' ),
            'error555' => esc_html__( 'Outgoing activity on the server is blocked. Please contact your provider to resolve this issue. Data synchronization will automatically be resumed without any data loss once the issue is resolved.', 'yespo-cdp' ),
            'success' => esc_html__( 'Data is successfully synchronized', 'yespo-cdp' ),
            'synhStarted' => esc_html__( 'Data synchronization has started', 'yespo-cdp' ),
            'pluginUrl' => esc_url( YESPO_PLUGIN_URL ),
            'pauseButton' => esc_html__( 'Pause', 'yespo-cdp' ),
            'resumeButton' => esc_html__( 'Resume', 'yespo-cdp' ),
            'contactSupportButton' => esc_html__( 'Contact Support', 'yespo-cdp' ),
            'ajaxUrl' => esc_url( admin_url( 'admin-ajax.php' ) ),
            //'nonceApiKeyForm' => wp_create_nonce( 'yespo_api_key_nonce' ),
            'nonceApiKeyForm' => wp_nonce_field('yespo_plugin_settings_save', 'yespo_plugin_settings_nonce', true, false),
            'apiKeyValue' => isset( $yespo_api_key ) ? esc_js( $yespo_api_key ) : '',
            'apiKeyText' => esc_html__( 'The API key to connect the account can be received by the', 'yespo-cdp' ),
            'yespoLink' => 'https://my.yespo.io/settings-ui/#/api-keys-list',
            'yespoLinkText' => esc_html__( 'link', 'yespo-cdp' ),
            'yespoApiKey' => esc_js(__( 'API Key', 'yespo-cdp' )),
            'synchronize' =>  esc_js(__('Synchronize', 'yespo-cdp')),
            'startExportUsersNonce' => wp_create_nonce('yespo_export_user_data_to_esputnik'),
            'startExportOrdersNonce' => wp_create_nonce('yespo_export_order_data_to_esputnik'),

            'yespoGetAccountYespoNameNonce' => wp_create_nonce('yespo_get_account_yespo_name'),
            'yespoCheckApiAuthorizationYespoNonce' => wp_create_nonce('yespo_check_api_authorization_yespo'),
            'yespoGetUsersTotalNonce' => wp_create_nonce('get_users_total'),
            'yespoGetUsersTotalExportNonce' => wp_create_nonce('yespo_get_users_total_export'),
            'yespoGetProcessExportUsersDataToEsputnikNonce' => wp_create_nonce('yespo_get_process_export_users_data_to_esputnik'),

            'yespoGetOrdersTotalNonce' => wp_create_nonce('get_orders_total'),
            'yespoGetOrdersTotalExportNonce' => wp_create_nonce('yespo_get_orders_total_export'),
            'yespoGetProcessExportOrdersDataToEsputnikNonce' => wp_create_nonce('yespo_get_process_export_orders_data_to_esputnik'),
            'yespoStopExportDataToYespoNonce' => wp_create_nonce('yespo_stop_export_data_to_yespo'),
            'yespoResumeExportDataNonce' => wp_create_nonce('yespo_resume_export_data')

        ));
    }
}