<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

		<div id="tabs-2" class="wrap">
			<?php
			$cmb = new_cmb2_box(
				array(
					'id'         => 'yespo-cdp' . '_options-second',
					'hookup'     => false,
					'show_on'    => array( 'key' => 'options-page', 'value' => array( 'yespo-cdp' ) ),
					'show_names' => true,
					)
			);
			$cmb->add_field(
				array(
					'name'    => __( 'Text', 'yespo-cdp' ),
					'desc'    => __( 'field description (optional)', 'yespo-cdp' ),
					'id'      => '_text-second',
					'type'    => 'text',
					'default' => 'Default Text',
			)
			);
			$cmb->add_field(
				array(
					'name'    => __( 'Color Picker', 'yespo-cdp' ),
					'desc'    => __( 'field description (optional)', 'yespo-cdp' ),
					'id'      => '_colorpicker-second',
					'type'    => 'colorpicker',
					'default' => '#bada55',
			)
			);

			cmb2_metabox_form( 'yespo-cdp' . '_options-second', 'yespo-cdp' . '-settings-second' );
			?>

			<!-- @TODO: Provide other markup for your options page here. -->
		</div>
