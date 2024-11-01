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

use I18n_Notice_WordPressOrg;
use Yespo\Engine\Base;

/**
 * Everything that involves notification on the WordPress dashboard
 */
class Notices extends Base {

	/**
	 * Initialize the class
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( !parent::initialize() ) {
			return;
		}

		//\wpdesk_wp_notice( \__( 'Updated Messages', YESPO_TEXTDOMAIN ), 'updated' );

		/*
		 * Review plugin notice.
		 */
        /*
		new WP_Review_Me(
			array(
				'days_after' => 15,
				'type'       => 'plugin',
				'slug'       => YESPO_TEXTDOMAIN,
				'rating'     => 5,
				'message'    => \__( 'Review me!', YESPO_TEXTDOMAIN ),
				'link_label' => \__( 'Click here to review', YESPO_TEXTDOMAIN ),
			)
		);
        */

		/*
		 * Alert after few days to suggest to contribute to the localization if it is incomplete
		 * on translate.wordpress.org, the filter enables to remove globally.
		 */
		if ( \apply_filters( 'yespo_alert_localization', true ) ) {
			new I18n_Notice_WordPressOrg(
			array(
				'textdomain'  => YESPO_TEXTDOMAIN,
				'yespo' => YESPO_NAME,
				'hook'        => 'admin_notices',
			),
			true
			);
		}

	}

}
