<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_DeliveryModel
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class Courier_DeliveryModel extends BaseModel
{
	/**
	 * Defines this model's attributes.
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), [
			'id' => AttributeType::Number,
			'blueprintId' => [
				AttributeType::Number,
				'required' => true,
			],
			'toEmail' => [
				AttributeType::String,
				'default' => '',
			],
			'errorMessages' => [
				AttributeType::String,
				'maxLength' => 1020,
				'default' => '',
			],
			'success' => AttributeType::Bool,
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,
		]);
	}

	/**
	 * @return Courier_BlueprintModel|null
	 */
	public function getBlueprint()
	{
		return craft()->courier_blueprints->getBlueprintById($this->blueprintId);
	}
}
