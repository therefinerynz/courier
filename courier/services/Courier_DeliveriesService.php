<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_DeliveriesService
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class Courier_DeliveriesService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * @param array|\CDbCriteria $criteria
	 *
	 * @return Delivery[]
	 */
	public function getAllDeliveries($criteria = [])
	{
        $records = Courier_DeliveryRecord::model()
			->with('blueprint')
			->findAll($criteria);

        $models = Courier_DeliveryModel::populateModels($records);

		return $models;
	}

	/**
	 * Create and save a delivery to record and keep track of a blueprint email that was sent
	 *
	 * @param  \CEvent $event
	 *
	 * @return void
	 */
	public function createDelivery(\CEvent $event)
	{
		$params = $event->params;
		$recipients = '';

		// Do we have an Email model?
		if (isset($params['email'])) {
			$toEmail = $params['email']['toEmail'];
			$recipients = is_array($toEmail) ? $this->_convertEmailArrayToString($toEmail) : $toEmail;
		}

		$delivery = new Courier_DeliveryModel();
		$delivery->blueprintId 	 = $params['blueprint']->id;
		$delivery->toEmail 		 = $recipients;
		$delivery->success 		 = isset($params['success']) ? $params['success'] : false;
		$delivery->errorMessages = isset($params['error']) ? $params['error'] : '';


		// Save the delivery record
		$delivery->id = $this->_saveDelivery($delivery);

		$this->enforceDeliveriesLimit();
	}

	/**
	 * @param int $id
	 */
	public function deleteDeliveryById($id)
	{
		return (bool) Courier_DeliveryRecord::model()->deleteByPk($id);
	}

	/**
	 * @param array|\CDbCriteria $criteria
	 *
	 * @return bool $result
	 *
	 * @throws \Exception
	 */
	public function deleteAllDeliveries($criteria = [])
	{
		return (bool) Courier_DeliveryRecord::model()->deleteAll($criteria);
	}

	/**
	 * Ensure and enforce that we never have more Courier_DeliveryModel Records saved to the DB than Courier's set delivery record limit
	 *
	 * @param int $deliveriesLimit
	 *
	 * @return void
	 */
	public function enforceDeliveriesLimit($deliveriesLimit = null)
	{
		if (!$deliveriesLimit) {
			$deliveriesLimit = craft()->plugins->getPlugin('courier')->getSettings()->deliveriesRecordLimit;
		}
		$deliveriesCount = count(Courier_DeliveryRecord::model()->findAll());

		// Proceed only if limit was reached
		if (!($deliveriesCount > $deliveriesLimit)) {
			return;
		}

		// Delete the excess deliveries
		Courier_DeliveryRecord::model()->deleteAll([
			'order' => 'dateCreated ASC',
			'limit' => $deliveriesCount - $deliveriesLimit
		]);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param  Courier_DeliveryModel $deliveryModel
	 *
	 * @return int|null $deliveryId
	 */
	private function _saveDelivery(Courier_DeliveryModel $deliveryModel)
	{
		if (!$deliveryModel->validate()) {
			// Validation errors to string
			$errors = array_column($deliveryModel->getErrors(), 0);
			$errors = implode(' ', $errors);
			$error = Craft::t('Could not create delivery for blueprint “{blueprint}”. Errors: “{errors}”', [
				'blueprint' => $event->blueprint->name,
				'errors' => $errors,
			]);
			Craft::error($error, LogLevel::Error);

			return null;
		}

		$deliveryRecord = new Courier_DeliveryRecord();

		$deliveryRecord->blueprintId  		= $deliveryModel->blueprintId;
		$deliveryRecord->toEmail 			= $deliveryModel->toEmail;
		$deliveryRecord->success 			= $deliveryModel->success;
		$deliveryRecord->errorMessages 		= $deliveryModel->errorMessages;

		// Save the record to the DB
		$deliveryRecord->save(false);

		return $deliveryRecord->id;
	}


	/**
	 * When expecting the Mailer classes's toEmail array format of
	 * [ $email => $name, $email => $name, $email ]
	 * convert these with comma separation to a string in the format of
	 * $name ($email), $name ($email), $email
	 *
	 * @param  array  $emails
	 *
	 * @return string $emailsString
	 */
	private function _convertEmailArrayToString(array $emails)
	{
		$emailsString = '';
		$i = 0;

		foreach ($emails as $key => $val) {
			// Choose correct format depending on whether key is a string or sequentially indexed
			$withName = is_string($key);
			$emailsString .= $withName ?  $val . ' <' . $key . '>' : $val;
			if ($i < count($emails) - 1 && $withName) {
				$emailsString .= ', ';
			}
			$i++;
		}

		return $emailsString;
	}
}
