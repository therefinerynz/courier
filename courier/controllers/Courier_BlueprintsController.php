<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_BlueprintsController
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class Courier_BlueprintsController extends Courier_BaseAdminController
{
	// Public Methods
	// =========================================================================

	/**
	 * View all blueprints
	 *
     * @return Response
	 * @throws HttpException
	 */
	public function actionIndex()
	{
		$blueprints = craft()->courier_blueprints->getAllBlueprints();

		return $this->renderTemplate('courier/blueprints', compact('blueprints'));
	}

	/**
	 * Create a blueprint
	 *
     * @return Response
	 * @throws HttpException
	 */
	public function actionCreate()
	{
		$variables = craft()->urlManager->getRouteParams()['variables'];
		// Create a fresh Blueprint, or get the invalid one that was not saved yet
		if (empty($variables['blueprint'])) {
			$variables['blueprint'] = new Courier_BlueprintModel();
		}

		$variables['title'] = Craft::t('Create new blueprint');

		return $this->renderTemplate('courier/_blueprint', $variables);
	}

	/**
	 * Edit a blueprint
	 *
     * @return Response
	 * @throws HttpException
	 */
	public function actionEdit()
	{
		$variables = craft()->urlManager->getRouteParams()['variables'];

		// Get blueprint by id if it is not loaded already
		if (empty($variables['blueprint'])) {
			$variables['blueprint'] = craft()->courier_blueprints->getBlueprintById($variables['id']);
		}

		// Could not find requested Blueprint
		if ($variables['blueprint'] === null) {
			throw new HttpException(404);
		}

		$variables['title'] = $variables['blueprint']->name;

		return $this->renderTemplate('courier/_blueprint', $variables);
	}

	/**
	 * Save a blueprint
	 *
     * @return Response
	 * @throws HttpException
	 */
	public function actionSave()
	{
		$this->requirePostRequest();

		$blueprint = new Courier_BlueprintModel();
		$request = craft()->getRequest();

		$blueprint->id 						= $request->getParam('blueprintId');
		$blueprint->name 					= $request->getParam('name');
		$blueprint->description 			= $request->getParam('description');
		$blueprint->htmlEmailTemplatePath 	= $request->getParam('htmlEmailTemplatePath');
		$blueprint->textEmailTemplatePath 	= $request->getParam('textEmailTemplatePath');
		$blueprint->enabled 				= $request->getParam('enabled');
		$blueprint->emailSubject 			= $request->getParam('emailSubject');
		$blueprint->toEmail 				= $request->getParam('toEmail');
		$blueprint->toName 					= $request->getParam('toName');
		$blueprint->fromEmail 				= $request->getParam('fromEmail');
		$blueprint->fromName 				= $request->getParam('fromName');
		$blueprint->replyToEmail			= $request->getParam('replyToEmail');
		$blueprint->ccEmail 				= $request->getParam('ccEmail');
		$blueprint->bccEmail 				= $request->getParam('bccEmail');
		$blueprint->eventTriggers 			= $request->getParam('eventTriggers');
		$blueprint->eventTriggerConditions 	= $request->getParam('eventTriggerConditions');

		// Validate and save the blueprint
		if (!craft()->courier_blueprints->saveBlueprint($blueprint)) {
			craft()->userSession->setError(Craft::t('Couldn’t save blueprint.'));

			// Send the invalid blueprint back to the template
			return craft()->urlManager->setRouteVariables(['blueprint' => $blueprint]);
		}

		craft()->userSession->setNotice(Craft::t('Blueprint saved.'));

		// Saved, success!
		return $this->redirectToPostedUrl($blueprint);
	}

	/**
	 * Delete a blueprint by id
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

		$success = craft()->courier_blueprints->deleteBlueprintById($id);

		return $this->returnJson(['success' => $result]);
	}
}
