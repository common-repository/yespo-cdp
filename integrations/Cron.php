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
 * The various Cron of this plugin
 */
class Cron extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		/*
		 * Load CronPlus
		 */
		$args = array(
			'recurrence'       => 'hourly',
			'schedule'         => 'schedule',
			'name'             => 'hourly_cron',
			'cb'               => array( $this, 'hourly_cron' ),
			'plugin_root_file' => 'yespo.php',
		);

		$cronplus = new \CronPlus( $args );
		// Schedule the event
		$cronplus->schedule_event();
		// Remove the event by the schedule
		// $cronplus->clear_schedule_by_hook();
		// Jump the scheduled event
		// $cronplus->unschedule_specific_event();
	}

	/**
	 * Cron Hourly example
	 *
	 * @since 1.0.0
	 * @param int $id The ID.
	 * @return void
	 */
	public function hourly_cron( int $id ) {
		echo \esc_html( (string) $id );
	}

}
