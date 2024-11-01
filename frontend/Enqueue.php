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

namespace Yespo\Frontend;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Yespo\Engine\Base;

/**
 * Enqueue stuff on the frontend
 */
class Enqueue extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		parent::initialize();

		\add_action( AssetManager::ACTION_SETUP, array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets with Inpyside library https://inpsyde.github.io/assets
	 *
	 * @param \Inpsyde\Assets\AssetManager $asset_manager The class.
	 * @return void
	 */
	public function enqueue_assets( AssetManager $asset_manager ) {
		// Load public-facing style sheet and JavaScript.
		$assets = $this->enqueue_styles();

		if ( !empty( $assets ) ) {
			foreach ( $assets as $asset ) {
				$asset_manager->register( $asset );
			}
		}

		$assets = $this->enqueue_scripts();

		if ( !empty( $assets ) ) {
			foreach ( $assets as $asset ) {
				$asset_manager->register( $asset );
			}
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function enqueue_styles() {
		$styles = array();
		$styles[0] = new Style( YESPO_TEXTDOMAIN . '-plugin-styles', \plugins_url( 'assets/build/plugin-public.css', YESPO_PLUGIN_ABSOLUTE ) );
		$styles[0]->forLocation( Asset::FRONTEND )->useAsyncFilter()->withVersion( YESPO_VERSION );
		$styles[0]->dependencies();

		return $styles;
	}


	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function enqueue_scripts() {
		$scripts = array();
		$scripts[0] = new Script( YESPO_TEXTDOMAIN . '-plugin-script', \plugins_url( 'assets/build/plugin-public.js', YESPO_PLUGIN_ABSOLUTE ) );
		$scripts[0]->forLocation( Asset::FRONTEND )->useAsyncFilter()->withVersion( YESPO_VERSION );
		$scripts[0]->dependencies();
		$scripts[0]->withLocalize(
			'exampleDemo',
			array(
				'alert'   => \__( 'Error!', 'yespo-cdp' ),
				'nonce'   => \wp_create_nonce( 'demo_example' ),
				'wp_rest' => \wp_create_nonce( 'wp_rest' ),
			)
		);


		return $scripts;
	}


}
