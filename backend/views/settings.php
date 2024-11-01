<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( get_option( 'yespo_options' ) !== false ){
    $options = get_option('yespo_options', array());
    if(isset($options['yespo_username'])) $yespo_username = $options['yespo_username'];
    if(isset($options['yespo_api_key'])) $yespo_api_key = $options['yespo_api_key'];
}
?>
<div class="yespo-settings-page">
    <section class="topPanel">
        <div class="contentPart panelBox">
            <img src="<?php echo esc_url(YESPO_PLUGIN_URL);?>assets/images/yespologosmall.svg" width="33" height="33" alt="<?php echo esc_attr(YESPO_NAME); ?>" title="<?php echo esc_attr(YESPO_NAME); ?>">
            <div class="panelUser">
                <?php
                if(isset($yespo_username)) echo esc_html($yespo_username);
                ?>
            </div>
        </div>
    </section>
    <section class="userPanel">
        <div class="contentPart">
            <h1><?php echo esc_html__('Data synchronization', 'yespo-cdp'); ?></h1>
            <p><?php echo esc_html__('Synchronize contacts and orders for subsequent analysis and efficient data utilization using Yespo marketing automation tools','yespo-cdp') ?></p>
            <div class="settingsSection">
            </div>
        </div>
    </section>
</div>
