<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_BaseAdminController
 *
 * @author   The Refinery
 * @package  Courier
 * @since    1.0.0
 */
class Courier_BaseAdminController extends BaseController
{
	// Protected Properties
	// =========================================================================

	protected $allowAnonymous = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc
	 */
	public function init()
	{
		// All system setting actions require an admin
		craft()->userSession->requireAdmin();
	}
}
