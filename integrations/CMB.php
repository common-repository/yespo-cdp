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

namespace Yespo\Integrations;

use Yespo\Engine\Base;

/**
 * All the CMB related code.
 */
class CMB extends Base {

	/**
	 * Initialize class.
	 *
	 * @since 1.0.0
	 * @return void|bool
	 */
	public function initialize() {
		parent::initialize();

		require_once YESPO_PLUGIN_ROOT . 'vendor/cmb2/init.php';
		require_once YESPO_PLUGIN_ROOT . 'vendor/cmb2-grid/Cmb2GridPluginLoad.php';
		\add_action( 'cmb2_init', array( $this, 'cmb_demo_metaboxes' ) );
	}

	/**
	 * Your metabox on Demo CPT
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function cmb_demo_metaboxes() { // phpcs:ignore
		// Start with an underscore to hide fields from custom fields list
		$prefix   = '_demo_';
		$cmb_demo = \new_cmb2_box(
			array(
				'id'           => $prefix . 'metabox',
				'title'        => \__( 'Demo Metabox', 'yespo-cdp' ),
				'object_types' => array( 'demo' ),
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true, // Show field names on the left
		)
			);
		$cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid( $cmb_demo ); //phpcs:ignore WordPress.NamingConventions
		$row      = $cmb2Grid->addRow(); //phpcs:ignore WordPress.NamingConventions
		$field1 = $cmb_demo->add_field(
			array(
				'name' => \__( 'Text', 'yespo-cdp' ),
				'desc' => \__( 'field description (optional)', 'yespo-cdp' ),
				'id'   => $prefix . 'yespo-cdp' . '_text',
				'type' => 'text',
				)
			);
		$field2 = $cmb_demo->add_field(
			array(
				'name' => \__( 'Text 2', 'yespo-cdp' ),
				'desc' => \__( 'field description (optional)', 'yespo-cdp' ),
				'id'   => $prefix . 'yespo-cdp' . '_text2',
				'type' => 'text',
				)
			);

		$field3 = $cmb_demo->add_field(
			array(
				'name' => \__( 'Text Small', 'yespo-cdp' ),
				'desc' => \__( 'field description (optional)', 'yespo-cdp' ),
				'id'   => $prefix . 'yespo-cdp' . '_textsmall',
				'type' => 'text_small',
				)
			);
		$field4 = $cmb_demo->add_field(
			array(
				'name' => \__( 'Text Small 2', 'yespo-cdp' ),
				'desc' => \__( 'field description (optional)', 'yespo-cdp' ),
				'id'   => $prefix . 'yespo-cdp' . '_textsmall2',
				'type' => 'text_small',
		)
			);
		$row->addColumns( array( $field1, $field2 ) );
		$row = $cmb2Grid->addRow(); //phpcs:ignore WordPress.NamingConventions
		$row->addColumns( array( $field3, $field4 ) );
	}

}
