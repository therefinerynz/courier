<?php
/**
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 */

namespace Craft;

/**
 * Courier_EmailsService
 *
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
class Courier_EmailsService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * @param Blueprint $blueprint
	 * @param array $renderVariables
	 *
	 * @return bool $success
	 */
	public function sendBlueprintEmail(Courier_BlueprintModel $blueprint, array $renderVariables)
	{
		$resultEventParams = [
			'blueprint' => $blueprint,
			'success' => false,
		];

		$email = $this->_createBlueprintEmail($blueprint, $renderVariables);
		$resultEventParams['email'] = $email;

		// Something went wrong creating the email...
		if (!$email) {
			return false;
		}

		// Now try to send the email
		try {
			$success = craft()->email->sendEmail($email);
			$resultEventParams['success'] = $success;
		} catch (\Exception $e) {
			$error = Craft::t("Could not send email for the blueprint named “{blueprint}”.\r\n{error}", [
				'blueprint' => $blueprint->name,
				'error' => $e->getMessage(),
			]);
			CourierPlugin::log($error, LogLevel::Error, true);

			$resultEventParams['error'] = $error;

			// Fire a new onBlueprintEmailFailedEvent
			$event = new Event($this, $resultEventParams);
			$this->onAfterBlueprintEmailFailed($event);

			return false;
		}

		if ($success) {
			$message = Craft::t('Successfully sent email for “{blueprint}”.', [
				'blueprint' => $blueprint->name,
			]);
			CourierPlugin::log($message, LogLevel::Info);

			// Fire a new onBlueprintEmailSentEvent
			$event = new Event($this, $resultEventParams);
			$this->onAfterBlueprintEmailSent($event);
		} else {
			$error = Craft::t("Unknown error occurred when attempting to send email for “{blueprint}”.\r\nCheck your Craft log for more details.", [
				'blueprint' => $blueprint->name,
			]);
			CourierPlugin::log($error, LogLevel::Error);
			$resultEventParams['error'] = $error;

			// Fire a new onBlueprintEmailFailedEvent
			$event = new Event($this, $resultEventParams);
			$this->onAfterBlueprintEmailFailed($event);
		}

		return true;
	}

	/**
	 * Fire the event "onAfterBlueprintEmailSent"
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onAfterBlueprintEmailSent(Event $event)
	{
		$this->raiseEvent('onAfterBlueprintEmailSent', $event);
	}

	/**
	 * Fire the event "onAfterBlueprintEmailFailed"
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onAfterBlueprintEmailFailed(Event $event)
	{
		$this->raiseEvent('onAfterBlueprintEmailFailed', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param Blueprint $blueprint
	 * @param array $renderVariables
	 *
	 * @return Email|null
	 */
	private function _createBlueprintEmail(Courier_BlueprintModel $blueprint, array $renderVariables)
	{
		$resultEventParams = compact('blueprint');

		// Try to render the blueprint templates and settings
		try {
			$blueprint = $this->_renderBlueprintSettings($blueprint, $renderVariables);

			// Blueprint model should only be available in the email body templates
			$emailVariables = array_merge($renderVariables, compact('blueprint'));

			$emailTemplates = $this->_renderEmailTemplates($blueprint, $emailVariables);
		} catch (Exception $e) {
			$error = Craft::t("Could not create email for the blueprint named “{blueprint}”.\r\n{error}", [
				'blueprint' => $blueprint->name,
				'error' => $e->getMessage(),
			]);
			CourierPlugin::log($error, LogLevel::Error, true);

			$resultEventParams['error'] = $error;

			// Fire a new onBlueprintEmailFailedEvent
			$event = new Event($this, $resultEventParams);
			$this->onAfterBlueprintEmailFailed($event);

			return null;
		}

		$email = new EmailModel();

		// Set required fields on the email
		$email->toFirstName = $blueprint->toName;
		$email->toEmail = $blueprint->toEmail;
		$email->fromEmail = $blueprint->fromEmail;
		$email->fromName = $blueprint->fromName;
		$email->subject = $blueprint->emailSubject;
		$email->htmlBody = $emailTemplates['HTML Email Template'];

		// Set optional fields on the email
		if (!empty($blueprint->replyToEmail)) {
			$email->replyTo =$blueprint->replyToEmail;
		}

		if (!empty($blueprint->ccEmail)) {
			$email->cc = $blueprint->ccEmail;
		}

		if (!empty($blueprint->bccEmail)) {
			$email->bcc = $blueprint->bccEmail;
		}

		// Set optional text email template if it exists
		if (isset($emailTemplates['Text Email Template'])) {
			$email->body = $emailTemplates['Text Email Template'];
		}

		return $email;
	}

	/**
	 * @param Courier_BlueprintModel $blueprint
	 * @param array $renderVariables
	 *
	 * @return array|null $result
	 */
	private function _renderEmailTemplates(Courier_BlueprintModel $blueprint, array $renderVariables)
	{
		$oldTemplateMode = craft()->templates->getTemplateMode();

		// Switch template modes to allow us to locate the template paths
		craft()->templates->setTemplateMode(TemplateMode::Site);

		$emailTemplates = ['HTML Email Template' => $blueprint->htmlEmailTemplatePath];

		if ($blueprint->textEmailTemplatePath) {
			$emailTemplates = array_merge($emailTemplates, ['Text Email Template' => $blueprint->textEmailTemplatePath]);
		}

		foreach ($emailTemplates as $attributeHandle => $attributeValue) {
			$renderableString = trim($attributeValue);
			// Skip empty value
			if (!$renderableString) {
				continue;
			}
			do {
				// Try to render dynamic path
				try {
					$lastRenderedResult = $renderableString;
					$renderableString = craft()->templates->renderString($renderableString, $renderVariables);
				}
				// Template path parse error
				catch (\Exception $e) {
					$errorMessage = $e->getMessage();
					$error = Craft::t("Template parse error encountered while parsing field “{attributeHandle}” for the blueprint named “{blueprint}.”:\r\n{error}", [
						'blueprint' => $blueprint->name,
						'attributeHandle' => $attributeHandle,
						'error' => $errorMessage,
					]);
					CourierPlugin::log($error, LogLevel::Error, true);

					throw new Exception($error);
				}
			} while ($this->_hasTwigBrackets($renderableString) && $lastRenderedResult !== $renderableString);

			$templatePath = $this->_stripTwigBrackets($renderableString);

			// Try to resolve the rendered template path
			if (!craft()->templates->doesTemplateExist($templatePath)) {
				$error = Craft::t('Email template does not exist at path “{templatePath}” for blueprint “{blueprint}”.', [
					'templatePath' => $templatePath,
					'blueprint' => $blueprint->name,
				]);
				CourierPlugin::log($error, LogLevel::Error, true);

				throw new Exception($error);
			}
			$renderableTemplate = null;
			do {
				try {
					$lastRenderedResult = $renderableTemplate;
					// Render template that dynamic path points to
					$renderableTemplate = craft()->templates->render($templatePath, $renderVariables);
				}
				// Template file parse error
				catch (\Exception $e) {
					$errorMessage = $e->getMessage();
					$error = Craft::t("Template parse error encountered while parsing the {templateName} file located at path “{templatePath}” for the blueprint named “{blueprint}”:\r\n{error}", [
						'blueprint' => $blueprint->name,
						'templateName' => $attributeHandle,
						'templatePath' => $templatePath,
						'error' => $errorMessage,
					]);
					CourierPlugin::log($error, LogLevel::Error, true);

					throw new Exception($error);
				}
			} while ($this->_hasTwigBrackets($renderableTemplate) && $lastRenderedResult !== $renderableTemplate);

			$renderedTemplate = $this->_stripTwigBrackets($renderableTemplate);

			$emailTemplates[$attributeHandle] = $this->_stripEntities($renderedTemplate);
		}

		// Return to the original template mode
		craft()->templates->setTemplateMode($oldTemplateMode);

		return $emailTemplates;
	}

	/**
	 * Render a given Blueprint's fields with Twig
	 *
	 * @param Courier_BlueprintModel $blueprint
	 * @param array $renderVariables
	 *
	 * @return Courier_BlueprintModel - Blueprint with its settings rendered
	 */
	private function _renderBlueprintSettings(Courier_BlueprintModel $blueprint, array $renderVariables)
	{
		$renderableSettings = [
			'emailSubject' => $blueprint->emailSubject,
			'toName' => $blueprint->toName,
			'toEmail' => $blueprint->toEmail,
			'fromName' => $blueprint->fromName,
			'fromEmail' => $blueprint->fromEmail,
			'replyToEmail' => $blueprint->replyToEmail,
			'ccEmail' => $blueprint->ccEmail,
			'bccEmail' => $blueprint->bccEmail,
		];

		$multipleItemFields = [
			'ccEmail',
			'bccEmail'
		];

		// Render all settings on the Blueprint with Twig
		foreach ($renderableSettings as $attributeHandle => $attributeValue) {
			$renderableString = trim($attributeValue);
			// Skip empty value
			if (!$renderableString) {
				continue;
			}
			do {
				try {
					$lastRenderedResult = $renderableString;
					// Render the string with Twig
					$renderableString = craft()->templates->renderString($renderableString, $renderVariables);
					$renderableString = $this->_stripEntities($renderableString);
				}
				// Template parse error
				catch (\Exception $e) {
					$errorMessage = $e->getMessage();
					$error = Craft::t("Template parse error encountered while parsing field “{attributeName}” for the blueprint named “{blueprint}”:\r\n{error}", [
						'blueprint' => $blueprint->name,
						'attributeName' => $this->_camelCaseToTitle($attributeHandle),
						'error' => $errorMessage
					]);
					CourierPlugin::log($error, LogLevel::Error, true);

					throw new Exception($error);
				}
			} while ($this->_hasTwigBrackets($renderableString) && $lastRenderedResult !== $renderableString);

			$renderedString = $this->_stripTwigBrackets($renderableString);

			// Commas and semicolons can separate multiple item fields. Explode these items into an array.
			if (in_array($attributeHandle, $multipleItemFields)) {
				$emails = str_replace(';', ',', $renderedString);
				$emails = explode(',', $emails);
				$emailsArr = [];
				foreach ($emails as $email) {
					$emailsArr[] = ['email' => trim($email)];
				}
				$blueprint[$attributeHandle] = $emailsArr;
			} else {
				$blueprint[$attributeHandle] = $renderedString;
			}
		}

		return $blueprint;
	}

	/**
	 * Convert camelCase string to Title Case
	 * From https://gist.github.com/justjkk/1402061
	 *
	 * @param str
	 *
	 * @return str $result
	 */
	private function _camelCaseToTitle($camelStr)
	{
		$intermediate = preg_replace('/(?!^)([[:upper:]][[:lower:]]+)/', ' $0', $camelStr);
		$titleStr = preg_replace('/(?!^)([[:lower:]])([[:upper:]])/', '$1 $2', $intermediate);

		return ucwords($titleStr);
	}

	/**
	 * Strip HTML entities from string and trim
	 * From http://php.net/manual/en/function.html-entity-decode.php#104617
	 *
	 * @param str $string
	 *
	 * @return str $result
	 */
	private function _stripEntities($string)
	{
		 return trim(preg_replace_callback("/(&#[0-9]+;)/", function($m) {
			 return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
		}, $string));
	}

	/**
	 * Check for Twig tags within a template string
	 *
	 * @param str $template
	 *
	 * @return bool
	 */
	private function _hasTwigBrackets($template)
	{
		return preg_match('/\{{2}.*?\}{2}|\{\%.*?\%\}/', $template);
	}

	/**
	 * Strip Twig tags from string (used in case of inability to render)
	 *
	 * @param str $template
	 *
	 * @return str
	 */
	private function _stripTwigBrackets($template)
	{
		return preg_replace('/\{{2}.*?\}{2}|\{\%.*?\%\}/', '', $template);
	}
}
