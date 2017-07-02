<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier plugin for Craft CMS
 *
 * Manage the automated deliveries of custom emails, triggered by certain pre-determined events.
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class CourierPlugin extends BasePlugin
{
	/**
	 * @return mixed
	 */
	public function init()
	{
		parent::init();

		craft()->courier_events->setupEventListeners();
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		 return Craft::t('Courier');
	}

	/**
	 * @return mixed
	 */
	public function getDescription()
	{
		return Craft::t('Manage the automated deliveries of custom emails, triggered by certain pre-determined events.');
	}

	/**
	 * @return string
	 */
	public function getDocumentationUrl()
	{
		return 'https://github.com/therefinerynz/courier/blob/master/README.md';
	}

	/**
	 * @return string
	 */
	public function getReleaseFeedUrl()
	{
		return 'https://raw.githubusercontent.com/therefinerynz/courier/master/releases.json';
	}

	/**
	 * Returns the version number.
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return '1.0.0';
	}

	/**
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return '1.0.0';
	}

	/**
	 * Returns the developer’s name.
	 *
	 * @return string
	 */
	public function getDeveloper()
	{
		return 'The Refinery';
	}

	/**
	 * Returns the developer’s website URL.
	 *
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'http://therefinery.co.nz';
	}

	/**
	 * Returns whether the plugin should get its own tab in the CP header.
	 *
	 * @return bool
	 */
	public function hasCpSection()
	{
		return true;
	}

	/**
	 * Control Panel routes.
	 *
	 * @return mixed
	 */
	public function registerCpRoutes()
	{
		return require(__DIR__ . '/etc/routes.php');
	}

	/**
	 * Make sure requirements are met before installation.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function onBeforeInstall()
	{
		if (version_compare(craft()->getVersion(), '2.6', '<')) {
			throw new Exception('Courier requires Craft CMS 2.6+ in order to run.');
		}

		if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50400) {
			Craft::delivery('Courier requires PHP 5.4+ in order to run.', LogLevel::Error);
			return false;
		}
	}

	/**
	 */
	public function onAfterInstall()
	{
	}

	/**
	 */
	public function onBeforeUninstall()
	{
	}

	/**
	 */
	public function onAfterUninstall()
	{
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return [
			'deliveriesRecordLimit' => [
				'type' => AttributeType::Number,
				'label' => 'Deliveries Record Limit',
				'default' => 50,
				'max' => 200,
			],
			'availableEvents' => [
				'type' => AttributeType::Mixed,
				'default' => [
					[
						'event' => 'entries.onSaveEntry',
						'enabled' => true
					]
				]
			]
		];
	}

	/**
	 * Returns the HTML that displays your plugin’s settings.
	 *
	 * @return mixed
	 */
	public function getSettingsHtml()
	{
	   return craft()->templates->render('courier/settings', [
		   'settings' => $this->getSettings()
	   ]);
	}

	/**
	 * Apply settings after saving them
	 *
	 * @param array $settings
	 *
	 * @return array $settings
	 */
	public function prepSettings($settings)
	{
		craft()->courier_deliveries->enforceDeliveriesLimit($settings['deliveriesRecordLimit']);

		return $settings;
	}
}
