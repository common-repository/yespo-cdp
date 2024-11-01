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

namespace Yespo\Internals;

use Yespo\Engine\Base;
use \stdClass; // phpcs:ignore

/**
 * Transient used by the plugin
 */
class Transient extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		parent::initialize();
	}

	/**
	 * This method contain an example of caching a transient with an external request.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public function transient_caching_example() { // phpcs:ignore
        return false;
	}

	/**
	 * Print the transient content
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function print_transient_output() {
		return '';
	}

}
