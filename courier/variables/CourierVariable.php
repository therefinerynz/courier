<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * CourierVariable
 *
 * @author   The Refinery
 * @package  Courier
 * @since    1.0.0
 */
class CourierVariable
{
	/**
	 * Get array of available events as determined in Courier's settings
	 *
	 * @return array
	 */
	public function getAvailableEvents()
	{
		return craft()->courier_events->getAvailableEvents();
	}
}
