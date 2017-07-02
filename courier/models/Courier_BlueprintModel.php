<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_BlueprintModel
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class Courier_BlueprintModel extends BaseModel
{
	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), [
			'id' => [
				AttributeType::Number,
				'required' => true,
			],
			'name' => [
				AttributeType::Name,
				'required'	=> true,
			],
			'fromName' => [
				AttributeType::String,
				'default' => '',
			],
			'htmlEmailTemplatePath' => [
				AttributeType::String,
				'required' => true,
				'maxLength' => 510,
			],
			'toEmail' => [
				AttributeType::String,
				'required' => true,
				'maxLength' => 510,
			],
			'fromEmail' => [
				AttributeType::String,
				'required' => true,
			],
			'emailSubject' => [
				AttributeType::String,
				'required' => true,
				'maxLength' => 510,
			],
			'toName' => [
				AttributeType::String,
				'default' => '',
			],
			'replyToEmail' => [
				AttributeType::String,
				'default' => '',
			],
			'ccEmail' => [
				AttributeType::String,
				'default' => '',
				'maxLength' => 510,
			],
			'bccEmail' => [
				AttributeType::String,
				'default' => '',
				'maxLength' => 510,
			],
			'textEmailTemplatePath' => [
				AttributeType::String,
				'default' => '',
				'maxLength' => 510,
			],
			'description' => [
				AttributeType::Template,
				'default' => '',
				'maxLength' => 1020,
			],
			'eventTriggerConditions' => [
				AttributeType::Template,
				'default' => 'false',
				'maxLength' => 1020,
				'required' => true
			],
			'eventTriggers' => [ AttributeType::Mixed ],
			'enabled' => [
				AttributeType::Bool,
				'required' => true,
				'default' => 1
			],
		]);
	}
}
