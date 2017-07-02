<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_BlueprintsService
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class Courier_BlueprintsService extends BaseApplicationComponent
{

	// Public Methods
	// =========================================================================

	/**
	 * @param Blueprint $model
	 *
	 * @return bool
	 */
	public function saveBlueprint(Courier_BlueprintModel $model)
	{
		// Load existing record from id, or create a new one
		if ($model->id)
			$record = Courier_BlueprintRecord::model()->findById($model->id);
		else {
			$record = new Courier_BlueprintRecord();
		}

		// Populate the blueprint record
		$record->name 					= $model->name;
		$record->description 			= $model->description;
		$record->htmlEmailTemplatePath 	= $model->htmlEmailTemplatePath;
		$record->textEmailTemplatePath 	= $model->textEmailTemplatePath;
		$record->enabled 				= $model->enabled;
		$record->emailSubject 			= $model->emailSubject;
		$record->toEmail 				= $model->toEmail;
		$record->toName 				= $model->toName;
		$record->fromEmail 				= $model->fromEmail;
		$record->fromName 				= $model->fromName;
		$record->replyToEmail			= $model->replyToEmail;
		$record->ccEmail 				= $model->ccEmail;
		$record->bccEmail 				= $model->bccEmail;
		$record->eventTriggers 			= $model->eventTriggers;
		$record->eventTriggerConditions = $model->eventTriggerConditions;

		$record->validate();
		$model->addErrors($record->getErrors());
		// Fail validation if there were errors on the model or reocrd
		if ($model->hasErrors()) {
			return false;
		}

		// Save the record to the DB
		$record->save(false);
		// Save the record id to the model
		$model->id = $record->id;

		return true;
	}

	/**
	 * @param int $id
	 *
	 * @return Courier_BlueprintModel|null
	 */
	public function getBlueprintById($id)
	{
		$record = Courier_BlueprintRecord::model()->findById($id);

		if (!$record) {
			return null;
		}
		$model = Courier_BlueprintModel::populateModel($record);

		return $model;
	}

	/**
	 * @param array|\CDbCriteria $criteria
	 *
	 * @return Courier_BlueprintModel[]
	 */
	public function getAllBlueprints($criteria = [])
	{
		$records = Courier_BlueprintRecord::model()->findAll($criteria);

		$models = Courier_BlueprintModel::populateModels($records);

		return $models;
	}

	/**
	 * @param int $id
	 */
	public function deleteBlueprintById($id)
	{
		return (bool) Courier_BlueprintRecord::model()->deleteByPk($id);
	}

	/**
	 * @param Event $event
	 * @param Courier_BlueprintModel $blueprint
	 *
	 * @return void
	 */
	public function checkEventConditions(Event $event, Courier_BlueprintModel $blueprint)
	{
		// Prep render variables
		$renderVariables = array_merge(compact('blueprint'), $event->params);
		$globalSets = craft()->globals->getAllSets();

		foreach ($globalSets as $globalSet) {
			$renderVariables[$globalSet->handle] = $globalSet;
		}

		try {
			// Render the string with Twig
			$eventTriggerConditions = craft()->templates->renderString($blueprint->eventTriggerConditions, $renderVariables);
		}
		// Template parse error
		catch (\Exception $e) {
			$errorMessage = $e->getMessage();
			$error = Craft::t("Template parse error encountered while parsing field “Event Trigger Conditions” for the blueprint named “{blueprint}”:\r\n{error}", [
				'blueprint' => $blueprint->name,
				'error' => $errorMessage
			]);
			CourierPlugin::log($error, LogLevel::Error, true);

			throw new Exception($error);
		}

		// Event trigger conditions were not met
		if (trim($eventTriggerConditions) !== 'true') {
			return;
		}

		// If everything looks all good, send the email
		craft()->courier_emails->sendBlueprintEmail($blueprint, $renderVariables);
	}
}
