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

//$yespo_debug = new WPBP_Debug( __( 'Yespo', YESPO_TEXTDOMAIN ) ); 24022024
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Log text inside the debugging plugins.
 *
 * @param string $text The text.
 * @return void
 */
function yespo_log( string $text ) {
	global $yespo_debug;
	$yespo_debug->log( $text );
}
