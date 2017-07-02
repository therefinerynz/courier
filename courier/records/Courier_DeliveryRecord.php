<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_DeliveryRecord
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class Courier_DeliveryRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'courier_deliveries';
	}

	/**
	 * Returns an array of attributes which map back to columns in the database table.
	 *
	 * @access protected
	 * @return array
	 */
   protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), [
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
		]);
	}

	/**
	 * Returns the blueprint that generated this delivery with its trigger event
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'blueprint' => [
				static::BELONGS_TO,
				'Courier_BlueprintRecord',
				'blueprintId',
                'required' => true,
                'onDelete' => self::CASCADE,
                'onUpdate' => self::CASCADE
			],
		];
	}
}
