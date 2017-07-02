<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_EventsService
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class Courier_EventsService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * Get array of available events as determined in Courier's settings
	 *
	 * @return array
	 */
	public function getAvailableEvents()
	{
		$courierSettings = craft()->plugins->getPlugin('courier')->getSettings();
		$availableEvents = [];
		foreach ($courierSettings->availableEvents as $eventOption) {
			if ($eventOption['enabled']) {
				$availableEvents[$eventOption['event']] = $eventOption['event'];
			}
		}

		return $availableEvents;
	}

	/**
	 * Setup event listeners of all blueprints
	 *
	 * @return void
	 */
	public function setupEventListeners()
	{
		$blueprints = craft()->courier_blueprints->getAllBlueprints();
		$availableEvents = $this->getAvailableEvents();

		// Setup event listeners for each blueprint
		foreach ($blueprints as $blueprint) {
			if (!$blueprint->eventTriggers) {
				continue;
			}
			foreach ($blueprint->eventTriggers as $event) {
				// Is event currently enabled?
				if (!isset($availableEvents[$event])) {
					continue;
				}
				craft()->on($event, function(Event $event) use ($blueprint) {
					craft()->courier_blueprints->checkEventConditions($event, $blueprint);
				});
			}
		}

		// On the event that an email is sent, create a successful delivery record
		craft()->on('courier_emails.onAfterBlueprintEmailSent', [
			craft()->courier_deliveries,
			'createDelivery'
		]);

		// On the event that an email fails to send, create a failed delivery record
		craft()->on('courier_emails.onAfterBlueprintEmailFailed', [
			craft()->courier_deliveries,
			'createDelivery',
		]);
	}
}
