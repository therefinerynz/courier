<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_DeliveriesController
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class Courier_DeliveriesController extends Courier_BaseAdminController
{
	/**
	 * View all deliveries
	 *
     * @return Response
	 * @throws HttpException
	 */
	public function actionIndex()
	{
		$deliveries = craft()->courier_deliveries->getAllDeliveries([ 'order' => 't.dateCreated DESC' ]);

		return $this->renderTemplate('courier/deliveries', compact('deliveries'));
	}

	/**
	 * Delete a delivery by id
	 * (only by JSON accepting POST requests)
	 *
	 * @return Response
	 * @throws HttpException
	 */
	public function actionDelete()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$id = craft()->request->getRequiredPost('id');

		$result = craft()->courier_deliveries->deleteDeliveryById($id);

		return $this->returnJson([ 'success' => $result ]);
	}

	/**
	 * Delete all deliveries
	 *
	 * @return Response|null
	 */
	public function actionDeleteAll()
	{
		// Try to delete deliveries
		if (!craft()->courier_deliveries->deleteAllDeliveries()) {
			craft()->userSession->setError(Craft::t('Something went wrong! Could not clear the delivery records.'));
			return null;
		}
		craft()->userSession->setNotice(Craft::t('Successfully cleared the delivery records.'));

		return $this->redirectToPostedUrl();
	}
}
