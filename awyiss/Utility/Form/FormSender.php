<?php declare(strict_types=1);


namespace Awyiss\Utility\Form;


use Awyiss\Core\App;
use Awyiss\Event\EventManager;
use Awyiss\Form\FormOptionsInterface;
use Awyiss\Model\Entity\EmailTemplate;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\FormEntriesTable;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\View\FrontendView;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\I18n\DateTime;
use Cake\Mailer\Mailer;
use Cake\Utility\Security;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;


/**
 * FormHandler class
 *
 * Works with the form data and sends the email(s) for a specific form
 */
class FormSender {
	/**
	 * Save both the email body and the confirmation email body
	 * to save the data in the database.
	 *
	 * @var array $emailBody
	 */
	protected array $emailBody = ['email' => '', 'confirmation' => ''];
	/**
	 * Stores the identifier of the saved form entry.
	 * The value is a md5 hash of the form entry id and the post-hash.
	 *
	 * @var string|null $formEntryIdentifier
	 */
	protected ?string $formEntryIdentifier = null;
	/**
	 * @var array $errors
	 */
	protected array $errors = [];
	/**
	 * @var \Awyiss\Model\Entity\Form
	 */
	protected readonly Form $form;
	/**
	 * @var \Awyiss\Model\Table\FormEntriesTable $formEntriesTable
	 */
	protected FormEntriesTable $formEntriesTable;
	/**
	 * @var \Awyiss\Model\Entity\Page|null
	 */
	protected readonly ?Page $page;
	/**
	 * @var \Awyiss\View\FrontendView $view
	 */
	protected FrontendView $view;


	/**
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param \Awyiss\Model\Entity\Page|null $page
	 */
	public function __construct(Form $form, ?Page $page = null) {
		$this->form = $form;
		$this->page = $page;

		/** @var \Awyiss\Model\Table\FormsTable $lo_formsTable */
		$lo_formsTable = FactoryLocator::get('Table')->get('Forms');
		if (!$form->emailTemplate) {
			$lo_formsTable->loadInto($form, ['EmailTemplates']);
		}
		if (!$form->confirmationEmailTemplate) {
			$lo_formsTable->loadInto($form, ['ConfirmationEmailTemplates']);
		}
		if (!$form->formElements) {
			$form->loadFormOptions()->loadFormElements();
		}

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->formEntriesTable = FactoryLocator::get('Table')->get('FormEntries');
	}


	/**
	 * Handle the form.
	 *
	 * Replaces all placeholders in the form fields with
	 * the actual form data.

	 * If neither `sendEmail` nor `sendConfirmationEmail` are enabled,
	 * just save the form data in the database and return true,
	 * otherwise save the form data in the database and send the email(s).
	 *
	 * Returns
	 *
	 * @return bool
	 */
	public function handle(): bool {
		$this->replacePlaceholdersInForm();

		$lo_eventManager = EventManager::instance();

		// Dispatch a new event for the form beforeSend
		$lo_event = new Event('FormSender.beforeSend', $this->form);
		$lo_eventManager->dispatch($lo_event);

		if ($lo_event->isStopped()) {
			return false;
		}

		$lb_sentEmail = null;
		if ($this->form->sendEmail) {
			$lb_sentEmail = $this->sendEmail();
		}

		$lb_sentConfirmationEmail = null;
		if ($lb_sentEmail !== false && $this->form->sendConfirmationEmail) {
			$lb_sentConfirmationEmail = $this->sendConfirmationEmail();
		}

		// If either mail was sent and the other did not result in an error, return true.
		if (
			(!$this->form->sendEmail || $lb_sentEmail) &&
			(!$this->form->sendConfirmationEmail || $lb_sentConfirmationEmail)
		) {
			return $this->saveFormEntry();
		}

		return false;
	}


	/**
	 * Returns the identifier of the saved form entry.
	 * The value is a md5 hash of the form entry id and the post-hash.
	 *
	 * @return string|null
	 */
	public function getFormEntryIdentifier(): ?string {
		return $this->formEntryIdentifier;
	}


	/**
	 * Send the email to the site owner
	 *
	 * @return bool
	 */
	protected function sendEmail(): bool {
		// Build the mail body
		$ls_bodyHtml = $this->createBody($this->form->emailTemplate);
		$ls_bodyPlain = $this->createBody($this->form->emailTemplate, 'text');

		if (empty($ls_bodyPlain) && empty($ls_bodyHtml)) {
			return false;
		}

		// Dispatch a new event for the form beforeSendEmail
		// If the event is stopped, do not send the email.
		if (
			!$this->sendEvent('FormSender.beforeSendEmail', [
				'bodyHtml' => &$ls_bodyHtml,
				'bodyPlain' => &$ls_bodyPlain,
			])
		) {
			return false;
		}

		// Check again if both body parts are empty.
		if (empty($ls_bodyPlain) && empty($ls_bodyHtml)) {
			return false;
		}

		/** @var class-string<\Cake\Mailer\Mailer> $ls_className */
		$ls_className = App::className('Mailer', 'Mailer');
		$lo_mailer = new $ls_className('form');

		$lo_mailer->setTransport($this->form->transportProfile);

		$lo_mailer->setSubject(html_entity_decode($this->form->subject))
		->setFrom(html_entity_decode($this->form->userEmail), html_entity_decode($this->form->userName))
		->setTo(html_entity_decode($this->form->ownerEmail), html_entity_decode($this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix', 'Awyiss CMS')));

		if ($this->form->cc) {
			foreach ($this->form->cc as $la_cc) {
				$lo_mailer->addCc($la_cc['email'], $la_cc['name'] ?: null);
			}
		}

		if ($this->form->bcc) {
			foreach ($this->form->bcc as $la_bcc) {
				$lo_mailer->addBcc($la_bcc['email'], $la_bcc['name'] ?: null);
			}
		}

		$this->setSafeSender(
			$lo_mailer,
			$this->form->userName,
			$this->form->userEmail,
			$this->form->userName
		);

		$this->addFormAttachments($lo_mailer);

		// Dispatch a new event for the form beforeEmailDeliver
		// If the event is stopped, do not send the email.
		if (
			!$this->sendEvent('FormSender.beforeEmailDeliver', [
				'bodyHtml' => &$ls_bodyHtml,
				'bodyPlain' => &$ls_bodyPlain,
				'mailer' => $lo_mailer,
			])
		) {
			return false;
		}

		// Check again if both body parts are empty.
		if (empty($ls_bodyPlain) && empty($ls_bodyHtml)) {
			return false;
		}

		// `both` should be default, so only set the format if it differs from the default.
		if (empty($ls_bodyHtml) || empty($ls_bodyPlain)) {
			$lo_mailer->setEmailFormat(empty($ls_bodyHtml) ? 'text' : 'html');
		}

		$lb_sent = $this->send($lo_mailer, $ls_bodyHtml, $ls_bodyPlain);

		// Dispatch a new event for the form afterEmailDeliver
		$this->sendEvent('FormSender.afterEmailDeliver', [
			'bodyHtml' => $ls_bodyHtml,
			'bodyPlain' => $ls_bodyPlain,
			'mailer' => $lo_mailer,
			'sent' => $lb_sent,
		]);

		return $lb_sent;
	}


	/**
	 * Send the confirmation email to the user
	 *
	 * @return bool
	 */
	protected function sendConfirmationEmail(): bool {
		// Build the mail body
		$ls_bodyHtml = $this->createBody($this->form->confirmationEmailTemplate, 'html', 'confirmation');
		$ls_bodyPlain = $this->createBody($this->form->confirmationEmailTemplate, 'text', 'confirmation');

		if (empty($ls_bodyPlain) && empty($ls_bodyHtml)) {
			return false;
		}

		// Dispatch a new event for the form beforeSendConfirmationEmail
		// If the event is stopped, do not send the email.
		if (
			!$this->sendEvent('FormSender.beforeSendConfirmationEmail', [
				'bodyHtml' => &$ls_bodyHtml,
				'bodyPlain' => &$ls_bodyPlain,
			])
		) {
			return false;
		}

		// Check again if both body parts are empty.
		if (empty($ls_bodyPlain) && empty($ls_bodyHtml)) {
			return false;
		}

		/** @var class-string<\Cake\Mailer\Mailer> $ls_className */
		$ls_className = App::className('Mailer', 'Mailer');
		$lo_mailer = new $ls_className('form');

		$lo_mailer->setTransport($this->form->transportProfile);

		$lo_mailer->setSubject(html_entity_decode($this->form->subjectConfirmation))
		->setFrom(html_entity_decode($this->form->ownerEmail), html_entity_decode($this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix')))
		->setTo(html_entity_decode($this->form->userEmail), html_entity_decode($this->form->userName));

		$this->setSafeSender(
			$lo_mailer,
			$this->form->userName,
			$this->form->ownerEmail,
			$this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix')
		);

		// Dispatch a new event for the form beforeConfirmationEmailDeliver
		// If the event is stopped, do not send the email.
		if (
			!$this->sendEvent('FormSender.beforeConfirmationEmailDeliver', [
				'bodyHtml' => &$ls_bodyHtml,
				'bodyPlain' => &$ls_bodyPlain,
				'mailer' => $lo_mailer,
			])
		) {
			return false;
		}

		// Check again if both body parts are empty.
		if (empty($ls_bodyPlain) && empty($ls_bodyHtml)) {
			return false;
		}

		// `both` should be default, so only set the format if it differs from the default.
		if (empty($ls_bodyHtml) || empty($ls_bodyPlain)) {
			$lo_mailer->setEmailFormat(empty($ls_bodyHtml) ? 'text' : 'html');
		}

		$lb_sent = $this->send($lo_mailer, $ls_bodyHtml, $ls_bodyPlain, 'confirmation');

		// Dispatch a new event for the form afterConfirmationEmailDeliver
		$this->sendEvent('FormSender.afterConfirmationEmailDeliver', [
			'bodyHtml' => $ls_bodyHtml,
			'bodyPlain' => $ls_bodyPlain,
			'mailer' => $lo_mailer,
			'sent' => $lb_sent,
		]);

		return $lb_sent;
	}


	/**
	 * Save the form entry in the database
	 *
	 * @return bool
	 */
	protected function saveFormEntry(): bool {
		$ls_ipHash = $this->createIpHash();
		$ls_postHash = Security::hash(serialize($this->getFormData()));

		$lo_formEntry = $this->formEntriesTable->newDefaultEntity();

		$la_data = [
			'form_id' => $this->form->id,
			'page_id' => $this->page?->id ?? null,
			'subject' => html_entity_decode($this->form->subject ?? ''),
			'subject_confirmation' => html_entity_decode($this->form->subjectConfirmation ?? ''),
			'body' => $this->emailBody['email'] ? base64_encode(gzcompress($this->emailBody['email'])) : null,
			'body_confirmation' => $this->emailBody['confirmation'] ? base64_encode(gzcompress($this->emailBody['confirmation'])) : null,
			'data' => base64_encode(gzcompress(json_encode($this->getFormData()))),
			'ip_hash' => $ls_ipHash,
			'post_hash' => $ls_postHash,
			'identifier' => md5($ls_ipHash . '|' . $ls_postHash),
		];

		$this->formEntriesTable->patchEntity($lo_formEntry, $la_data);

		foreach ($this->form->getProtectionMethods() as $lo_protectionMethod) {
			$lo_formEntry = $lo_protectionMethod->modifyFormEntry($lo_formEntry);

			// If the modify method decides to intervene, return the desired status.
			if (is_bool($lo_formEntry)) {
				return $lo_formEntry;
			}
		}

		// Save the form entry
		if ($this->formEntriesTable->save($lo_formEntry, ['allowFrontendSave' => true])) {
			$this->formEntryIdentifier = $lo_formEntry->identifier;

			return true;
		}

		return false;
	}


	/**
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}


	/**
	 * @param \Awyiss\Model\Entity\EmailTemplate $emailTemplate
	 * @param string $type
	 * @param string $for
	 * @return string|null
	 */
	protected function createBody(EmailTemplate $emailTemplate, string $type = 'html', string $for = 'email'): ?string {
		$ls_body = $type === 'html' ? $emailTemplate->textHtml : $emailTemplate->textPlain;

		$la_formData = [
			'subject' => $this->form->subject ?: null,
			'salutation' => $this->form->salutation ?: null,
		];

		if ($for === 'confirmation') {
			$la_formData = [
				'subject' => $this->form->subjectConfirmation ?: null,
				'salutation' => $this->form->salutationConfirmation ?: null,
			];
		}

		$la_formData['data'] = $this->createDataString($type);
		$la_formData['base_url'] = Router::url('/', true);

		$la_data = array_merge(
			$this->getFormData(),
			$la_formData,
		);

		if ($type === 'html') {
			/**
			 * {{$data}} needs a special treatment: it must never be inside a <p> tag
			 * because it will be replaced by the `<table>` containing all data.
			 *
			 * @noinspection RegExpRedundantEscape
			 */
			$ls_body = preg_replace_callback('/<p([^>]*)>(.*?)(\{\{\$data\}\})(.*?)<\/p>/Um', $this->unwrapDataString(...), $ls_body);
		}

		return $this->replacePlaceholders($ls_body, $la_data, ['data']);
	}


	/**
	 * @param string $type
	 * @return string
	 */
	protected function createDataString(string $type): string {
		$lo_view = $this->getView();

		$ls_fileName = 'data';
		if ($type === 'text') {
			$ls_fileName .= '_plain';
		}

		return $lo_view->element('email/' . $ls_fileName, $this->templateData());
	}


	/**
	 * @return string
	 */
	protected function createIpHash(): string {
		$lo_request = Router::getRequest();
		$ls_clientIp = $lo_request->clientIp();

		return Security::hash($ls_clientIp . Security::getSalt());
	}


	/**
	 * @return \Awyiss\View\FrontendView
	 */
	protected function getView(): FrontendView {
		if (!isset($this->view)) {
			$ls_className = App::className('Frontend', 'View', 'View');
			$this->view = new $ls_className();
		}

		return $this->view;
	}


	/**
	 * Replaces all placeholders in the form of
	 *
	 * - `$identifier`
	 * - `{{$identifier}}`
	 * - `{{$identifier|Alternative Text}}`
	 * - `{{$identifier1 $identifier 2|Alternative Text}}`
	 *
	 * in all accessible form fields with the actual form data.
	 *
	 * @return void
	 */
	public function replacePlaceholdersInForm(): void {
		$la_blocklistedFields = ['active', '_translations', '_publicationData', 'mediaAssignments', 'mediaElementAssignments'];
		$la_formFields = array_keys(array_filter($this->form->getAccessible()));

		$la_formData = $this->getFormData();
		$la_formData['base_url'] = Router::url('/', true);

		foreach ($la_formFields as $ls_field) {
			if (
				in_array($ls_field, $la_blocklistedFields) ||
				!$this->form->has($ls_field) ||
				!is_string($this->form->get($ls_field))
			) {
				continue;
			}

			$ls_value = $this->replacePlaceholders((string)$this->form->get($ls_field), $la_formData);

			$this->form->set($ls_field, $ls_value);
		}
	}


	/**
	 * Replaces all placeholders in the form of
	 * - `$identifier`
	 * - `{{$identifier}}`
	 * - `{{$identifier|Alternative Text}}`
	 * - `{{$identifier1 $identifier 2|Alternative Text}}`
	 * in the given string with the provided values.
	 *
	 * @param string $string
	 * @param array $values
	 * @param array $safeList List of identifiers that will not be escaped
	 * @return string
	 */
	public function replacePlaceholders(string $string, array $values, array $safeList = []): string {
		// If no `$` is found, return the string as is.
		if (!str_contains($string, '$')) {
			return $string;
		}

		$ls_string = $string;

		/*
		 * Find all placeholders in the form of `{{$identifier1[ ...$additionalIdentifiers]|Alternative Text}}`
		 * @see https://regex101.com/r/MYhhj2/1
		 */
		$ls_pattern = '/\{\{(?<identifiers>[^\|\}]*?)(?:\|(?<alternative>[^\}]*?))?\}\}/U';
		preg_match_all($ls_pattern, $string, $la_matches, PREG_SET_ORDER);
		if (!empty($la_matches)) {
			$ls_string = preg_replace_callback($ls_pattern, fn (array $match) => $this->replacedPlaceholdersOrAlternative($match, $values, $safeList), $ls_string);
		}

		// Find all placeholders in the form of `$identifier`
		$ls_pattern = '/(\$(?<identifier>[A-Za-z0-9_]+))/';

		return preg_replace_callback($ls_pattern, fn (array $match) => $this->replacePlaceholder($match, $values, $safeList), $ls_string);
	}


	/**
	 * Replaces all placeholders in the form of
	 * - `$identifier`
	 * in the given string with the provided values.
	 *
	 * @param array $match
	 * @param array $values
	 * @param array $safeList
	 * @return string
	 */
	protected function replacePlaceholder(array $match, array $values, array $safeList = []): string {
		$ls_identifier = $match['identifier'] ?? '';

		if (!$ls_identifier) {
			return '';
		}

		$ls_identifier = Inflector::underscore($ls_identifier);

		$lx_value = $values[ $ls_identifier ] ?? $match[0];

		if ($lx_value === '') {
			return $match[0];
		}

		if (!in_array($ls_identifier, $safeList)) {
			$lx_value = htmlentities($lx_value, ENT_QUOTES, 'UTF-8', false);
		}

		return $lx_value;
	}


	/**
	 * Replaces all placeholders in the form of
	 * - `$identifier`
	 * in the given string with the provided values.
	 *
	 * If the result still contains placeholders,
	 * return the alternative text.
	 *
	 * @param array $match
	 * @param array $values
	 * @param array $safeList
	 * @return string
	 */
	protected function replacedPlaceholdersOrAlternative(array $match, array $values, array $safeList = []): string {
		$ls_string = $match['identifiers'] ?? '';
		$ls_alternative = $match['alternative'] ?? null;

		if (!$ls_string) {
			return $ls_alternative;
		}

		$ls_pattern = '/(\$(?<identifier>[A-Za-z0-9_]+))/';
		$ls_string = preg_replace_callback($ls_pattern, fn (array $match) => $this->replacePlaceholder($match, $values, $safeList), $ls_string);

		if (str_contains($ls_string, '$') && $ls_alternative !== null) {
			return $ls_alternative;
		}

		return $ls_string;
	}


	/**
	 * @param \Cake\Mailer\Mailer $mailer
	 * @return void
	 */
	protected function addFormAttachments(Mailer $mailer): void {
		$la_formElements = $this->form->formElements->listNested()->toList();
		foreach ($la_formElements as $lo_formElement) {
			if ($lo_formElement->type !== 'file' || empty($this->getFormData($lo_formElement->identifier))) {
				continue;
			}

			$lo_file = $this->getFormData($lo_formElement->identifier);
			if ($lo_file instanceof UploadedFile && !$lo_file->getError()) {
				$mailer->addAttachments([
					$lo_file->getClientFilename() => [
						'data' => $lo_file->getStream()->getContents(),
						'mimetype' => $lo_file->getStream()->getMetadata('mime'),
					],
				]);
			}
		}
	}


	/**
	 * @param \Cake\Mailer\Mailer $mailer
	 * @param string|null $bodyHtml
	 * @param string|null $bodyPlain
	 * @param string $type
	 * @return bool
	 */
	protected function send(Mailer $mailer, ?string $bodyHtml, ?string $bodyPlain, string $type = 'email'): bool {
		$lo_template = $type === 'email' ? $this->form->emailTemplate : $this->form->confirmationEmailTemplate;

		$mailer->setRenderer(new FormMailRenderer());
		$mailer->viewBuilder()->setVars([
			'textHtml' => $bodyHtml,
			'textPlain' => $bodyPlain,
			'layout' => 'email/' . $lo_template->layout,
		])
		->setTemplate('Frontend/email/' . $lo_template->fileName)
		->setLayout('email/' . str_replace('.twig', '', $lo_template->layout));

		try {
			$la_sendData = $mailer->deliver();
		}
		catch (Exception $ex) {
			$ls_message = __d('form', 'error_email_send', $ex->getMessage());
			$this->form->setError('_general', $ls_message);
			$this->errors[] = $ls_message;

			return false;
		}

		/** @noinspection PhpUndefinedMethodInspection */
		$this->emailBody[ $type ] = $mailer->getBodyHtml();

		return !!$la_sendData;
	}


	/**
	 * @param string|null $identifier
	 * @return mixed
	 */
	protected function getFormData(?string $identifier = null): mixed {
		return $this->form->getFormData($identifier);
	}


	/**
	 * @return \Awyiss\Form\FormOptionsInterface
	 */
	protected function getFormOptions(): FormOptionsInterface {
		return $this->form->getFormOptions();
	}


	/**
	 * Adjusts and rewraps a string containing HTML content around a specified placeholder to ensure
	 * proper HTML tag closure and maintain a valid structure. This function processes tags before and after
	 * a placeholder to close and reopen those tags, while recursively removing any empty tags.
	 *
	 * @param array $matches An array containing matches from a regular expression. It should include:
	 *                       - `$matches[1]`: Attributes for a wrapping `<p>` tag.
	 *                       - `$matches[2]`: Content before the `{{$data}}` placeholder.
	 *                       - `$matches[3]`: The `{{$data}}` placeholder itself.
	 *                       - `$matches[4]`: Content after the `{{$data}}` placeholder.
	 * @return string The restructured and cleaned HTML string with proper wrapping and placeholder integration.
	 */
	protected function unwrapDataString(array $matches): string {
		$la_attributes = $matches[1]; // p tag attributes
		$ls_beforeContent = $matches[2]; // content before {{$data}}
		$ls_dataPlaceholder = $matches[3]; // the {{$data}} placeholder
		$ls_afterContent = $matches[4]; // content after {{$data}}

		// If there's no other content, just return the placeholder
		if (trim($ls_beforeContent) === '' && trim($ls_afterContent) === '') {
			return $ls_dataPlaceholder;
		}

		// Find all opened tags that need to be closed before dataPlaceholder
		$la_openedTags = [];
		preg_match_all('/<([a-z0-9]+)[^>]*>/i', $ls_beforeContent, $la_openTagMatches);
		preg_match_all('/<\/([a-z0-9]+)>/i', $ls_beforeContent, $la_closeTagMatches);

		// Count opened and closed tags
		$la_tagCounts = [];
		foreach ($la_openTagMatches[1] as $ls_tag) {
			$la_tagCounts[ $ls_tag ] = isset($la_tagCounts[ $ls_tag ]) ? $la_tagCounts[ $ls_tag ] + 1 : 1;
		}

		foreach ($la_closeTagMatches[1] as $ls_tag) {
			$la_tagCounts[ $ls_tag ] = isset($la_tagCounts[ $ls_tag ]) ? $la_tagCounts[ $ls_tag ] - 1 : -1;
		}

		// Collect tags that remain open
		foreach ($la_tagCounts as $ls_tag => $li_count) {
			if ($li_count > 0) {
				// Add tags that need to be closed, in reverse order (LIFO)
				for ($li_i = 0; $li_i < $li_count; $li_i++) {
					array_unshift($la_openedTags, $ls_tag);
				}
			}
		}

		// Build the result
		$ls_result = '<p' . $la_attributes . '>' . $ls_beforeContent;

		// Close all open tags before the dataPlaceholder
		foreach ($la_openedTags as $ls_tag) {
			$ls_result .= '</' . $ls_tag . '>';
		}

		$ls_result .= '</p>' . $ls_dataPlaceholder . ' <p' . $la_attributes . '>';

		// Reopen all closed tags in the original order
		foreach (array_reverse($la_openedTags) as $ls_tag) {
			$ls_result .= '<' . $ls_tag . '>';
		}

		$ls_result .= $ls_afterContent . '</p>';

		// Filter out empty tags recursively
		$ls_previous = '';
		// Remove all empty tags recursively, including those with attributes (like <span class="foo"></span>)
		while ($ls_previous !== $ls_result) {
			$ls_previous = $ls_result;
			$ls_result = preg_replace('/<([a-z0-9]+)(?:\s[^>]*)?><\/\1>/', '', $ls_result);
		}

		return $ls_result;
	}


	/**
	 * @return array
	 */
	protected function templateData(): array {
		$la_formData = $this->getFormData();
		$la_formElements = $this->form->formElements->listNested()->toList();

		foreach ($la_formElements as $lo_formElement) {
			if (
				isset($la_formData[ $lo_formElement->identifier ]) &&
				in_array($lo_formElement->type, ['date', 'time', 'datetime'])
			) {
				$lo_date = new DateTime($la_formData[ $lo_formElement->identifier ]);

				if ($lo_formElement->type === 'datetime') {
					$lo_date->setTimezone(new DateTimeZone('UTC'));
				}

				$la_formData[ $lo_formElement->identifier ] = $lo_date;
			}
		}

		return [
			'form' => $this->form,
			'formData' => $la_formData,
			'formElements' => $la_formElements,
		];
	}


	/**
	 * @param string $eventName
	 * @param array $data
	 * @return bool
	 */
	protected function sendEvent(string $eventName, array $data): bool {
		$lo_eventManager = EventManager::instance();

		// Dispatch a new event for the form beforeSendEmail
		$lo_event = new Event($eventName, $this->form, $data);
		$lo_eventManager->dispatch($lo_event);

		if ($lo_event->isStopped()) {
			return false;
		}

		$la_result = $lo_event->getResult();
		if ($la_result !== null) {
			if (!is_array($la_result)) {
				throw new InvalidArgumentException(sprintf('Expected an array as result of the event "%s", `%s` given', $eventName, gettype($la_result)));
			}

			foreach ($la_result as $ls_key => $lx_value) {
				if (array_key_exists($ls_key, $data)) {
					/** @noinspection PhpVariableNamingConventionInspection */
					$data[ $ls_key ] = $lx_value;
				}
			}
		}

		return true;
	}


	/**
	 * It's usually not permitted to send emails from a different domain than the site's domain.
	 * To ensure that the email is sent, the real sender should be part of the site's domain.
	 *
	 * And to make sure the recipient can reply to the email, the real sender should be set
	 * as the reply-to address.
	 *
	 * @param \Cake\Mailer\Mailer $mailer
	 * @param string|null $senderName
	 * @param string $replyToMail
	 * @param string|null $replyToName
	 * @return static
	 */
	protected function setSafeSender(Mailer $mailer, ?string $senderName, string $replyToMail, ?string $replyToName): static {
		$ls_safeRealSender = $this->getFormOptions()->getSafeRealSender();
		if ($ls_safeRealSender) {
			$mailer
				->setSender($ls_safeRealSender, html_entity_decode($senderName))
				->setReplyTo(html_entity_decode($replyToMail), html_entity_decode($replyToName));
		}

		return $this;
	}
}
