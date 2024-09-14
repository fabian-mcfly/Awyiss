<?php declare(strict_types=1);


namespace Awyiss\Utility\Form;


use Awyiss\Core\App;
use Awyiss\Model\Entity\EmailTemplate;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Table\FormEntriesTable;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\View\FrontendView;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\Mailer\Mailer;
use Cake\Utility\Security;
use DateTimeZone;
use Exception;
use Laminas\Diactoros\UploadedFile;


/**
 * FormHandler class
 *
 * Works with the form data and sends the email(s) for a specific form
 */
class FormSender {
	/**
	 * The timeout in seconds for checking if the user
	 * can send the same form with the same data.
	 *
	 * @var int $duplicateCheckTimeout
	 */
	protected int $duplicateCheckTimeout = 86400;
	/**
	 * Save both the email body and the confirmation email body
	 * to save the data in the database.
	 *
	 * @var array $emailBody
	 */
	protected array $emailBody = ['email' => '', 'confirmation' => ''];
	/**
	 * Stores the identifier of the saved form entry.
	 * The value is a md5 hash of the form entry id and the post hash.
	 *
	 * @var string $formEntryIdentifier
	 */
	protected string $formEntryIdentifier;
	/**
	 * @var array $errors
	 */
	protected array $errors = [];
	/**
	 * @var \Awyiss\Model\Entity\Form
	 */
	protected readonly Form $form;
	/**
	 * @var array $formData
	 */
	protected readonly array $formData;
	/**
	 * @var \Awyiss\Model\Table\FormEntriesTable $formEntriesTable
	 */
	protected FormEntriesTable $formEntriesTable;
	/**
	 * The timeout in seconds for checking if the user
	 * can send another form with the same ip.
	 *
	 * @var int $ipTimeout
	 */
	protected int $ipTimeout = 300;
	/**
	 * Indicates whether the real sender should be used as the sender (= empty value),
	 * or if the site owner's email should be used as the sender (= safe email address).
	 *
	 * This should ensure that no mailserver denies the email
	 * due to the sender not having the same origin as the site.
	 *
	 * @var string $safeRealSender
	 */
	protected string $safeRealSender;
	/**
	 * @var \Awyiss\View\FrontendView $view
	 */
	protected FrontendView $view;


	/**
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param array $formData
	 */
	public function __construct(Form $form, array $formData) {
		$lo_form = $form;
		$this->formData = $formData;

		/** @var \Awyiss\Model\Table\FormsTable $lo_formsTable */
		$lo_formsTable = FactoryLocator::get('Table')->get('Forms');
		if (!$lo_form->emailTemplate) {
			$lo_form = $lo_formsTable->loadInto($lo_form, ['EmailTemplates']);
		}
		if (!$lo_form->confirmationEmailTemplate) {
			$lo_form = $lo_formsTable->loadInto($lo_form, ['ConfirmationEmailTemplates']);
		}

		$this->form = $lo_form;

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->formEntriesTable = FactoryLocator::get('Table')->get('FormEntries');

		$lo_mailer = new Mailer('default');
		$this->safeRealSender = 'noreply@' . $lo_mailer->getMessage()->getDomain();
	}


	/**
	 * Handle the form.
	 * Check if the user can send the form (ip timeout, duplicate check timeout).
	 * If the check fails, set the error message and return false.
	 * If neither `sendEmail`, nor `sendConfirmationEmail` are enabled
	 * just save the form data in the database and return true,
	 * otherwise save the form data in the database and send the email(s).
	 *
	 * Returns
	 *
	 * @return bool
	 */
	public function handle(): bool {
		if (!$this->canSend()) {
			return false;
		}

		$this->replacePlaceholdersInForm();

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
			$this->saveFormEntry();

			return true;
		}

		return false;
	}


	/**
	 * Check if the user can send the form (ip timeout, duplicate check timeout).
	 * If the check fails, set the error message and return false.
	 *
	 * @return bool
	 */
	public function canSend(): bool {
		// Check if the user can send the form (ip timeout).
		if (!$this->canSendIp()) {
			$ls_message = __d('form', 'error_ip_timeout', $this->ipTimeout);
			$this->form->setError('_general', $ls_message);
			$this->errors[] = $ls_message;

			return false;
		}

		// Check if the user can send the form (duplicate check timeout).
		if (!$this->canSendDuplicate()) {
			$ls_message = __d('form', 'error_duplicate_timeout', $this->duplicateCheckTimeout);
			$this->form->setError('_general', $ls_message);
			$this->errors[] = $ls_message;

			return false;
		}

		return true;
	}


	/**
	 * Check if the user can send the form (duplicate check timeout).
	 *
	 * @return bool
	 */
	public function canSendDuplicate(): bool {
		$ls_postHash = Security::hash(serialize($this->formData));

		$lo_formEntry = $this->formEntriesTable->find()
		->where([
			'form_id' => $this->form->id,
			'post_hash' => $ls_postHash,
			'created_on >' => time() - $this->duplicateCheckTimeout,
		])
		->first();

		return !$lo_formEntry;
	}


	/**
	 * Check if the user can send the form (ip timeout).
	 *
	 * @return bool
	 */
	public function canSendIp(): bool {
		$ls_ipHash = $this->createIpHash();

		$lo_formEntry = $this->formEntriesTable->find()
		->where([
			'form_id' => $this->form->id,
			'ip_hash' => $ls_ipHash,
			'created_on >' => time() - $this->ipTimeout,
		])
		->first();

		return !$lo_formEntry;
	}


	/**
	 * Returns the identifier of the saved form entry.
	 * The value is a md5 hash of the form entry id and the post hash.
	 *
	 * @return string
	 */
	public function getFormEntryIdentifier(): string {
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

		$lo_mailer = new Mailer('default');

		$lo_mailer->setSubject($this->form->subject)
		->setFrom($this->form->userEmail, $this->form->userName)
		->setTo($this->form->ownerEmail, $this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix'));

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

		/*
		 * It's usually not permitted to send emails from a different domain than the site's domain.
		 * To ensure that the email is sent, the real sender should be part of the site's domain.
		 *
		 * And to make sure the recipient can reply to the email,
		 * the real sender should be set as the reply-to address.
		 */
		if ($this->safeRealSender) {
			$lo_mailer->setSender($this->safeRealSender, $this->form->userName)
			->setReplyTo($this->form->userEmail, $this->form->userName);
		}

		// `both` should be default, so only set the format if it differs from the default.
		if (empty($ls_bodyHtml) || empty($ls_bodyPlain)) {
			$lo_mailer->setEmailFormat(empty($ls_bodyHtml) ? 'text' : 'html');
		}

		$this->addFormAttachments($lo_mailer);

		return $this->_send($lo_mailer, $ls_bodyHtml, $ls_bodyPlain);
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

		$lo_mailer = new Mailer('default');

		$lo_mailer->setSubject($this->form->subjectConfirmation)
		->setFrom($this->form->ownerEmail, $this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix'))
		->setTo($this->form->userEmail, $this->form->userName);

		/*
		 * It's usually not permitted to send emails from a different domain than the site's domain.
		 * To ensure that the email is sent, the real sender should be part of the site's domain.
		 *
		 * And to make sure the recipient can reply to the email,
		 * the real sender should be set as the reply-to address.
		 */
		if ($this->safeRealSender) {
			$lo_mailer->setSender($this->safeRealSender, $this->form->userName)
			->setReplyTo($this->form->ownerEmail, $this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix'));
		}

		// `both` should be default, so only set the format if it differs from the default.
		if (empty($ls_bodyHtml) || empty($ls_bodyPlain)) {
			$lo_mailer->setEmailFormat(empty($ls_bodyHtml) ? 'text' : 'html');
		}

		return $this->_send($lo_mailer, $ls_bodyHtml, $ls_bodyPlain, 'confirmation');
	}


	/**
	 * Save the form entry in the database
	 *
	 * @return void
	 */
	protected function saveFormEntry(): void {
		$ls_ipHash = $this->createIpHash();
		$ls_postHash = Security::hash(serialize($this->formData));

		$lo_formEntry = $this->formEntriesTable->newDefaultEntity();

		$la_data = [
			'form_id' => $this->form->id,
			'subject' => $this->form->subject,
			'subject_confirmation' => $this->form->subjectConfirmation,
			'body' => $this->emailBody['email'] ? base64_encode(gzcompress($this->emailBody['email'])) : null,
			'body_confirmation' => $this->emailBody['confirmation'] ? base64_encode(gzcompress($this->emailBody['confirmation'])) : null,
			'data' => base64_encode(gzcompress(json_encode($this->formData))),
			'ip_hash' => $ls_ipHash,
			'post_hash' => $ls_postHash,
		];

		$this->formEntriesTable->patchEntity($lo_formEntry, $la_data);

		if ($this->formEntriesTable->save($lo_formEntry, ['allowFrontendSave' => true])) {
			$this->formEntryIdentifier = md5($lo_formEntry->id . ' | ' . $lo_formEntry->postHash);
		}
	}


	/**
	 * @return int
	 */
	public function getDuplicateCheckTimeout(): int {
		return $this->duplicateCheckTimeout;
	}


	/**
	 * @param int $duplicateCheckTimeout
	 * @return $this
	 */
	public function setDuplicateCheckTimeout(int $duplicateCheckTimeout): static {
		$this->duplicateCheckTimeout = $duplicateCheckTimeout;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}


	/**
	 * @return int
	 */
	public function getIpTimeout(): int {
		return $this->ipTimeout;
	}


	/**
	 * @param int $ipTimeout
	 * @return $this
	 */
	public function setIpTimeout(int $ipTimeout): static {
		$this->ipTimeout = $ipTimeout;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSafeRealSender(): string {
		return $this->safeRealSender;
	}


	/**
	 * @param string $safeRealSender
	 * @return $this
	 */
	public function setSafeRealSender(string $safeRealSender): static {
		$this->safeRealSender = $safeRealSender;

		return $this;
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
			$this->formData,
			$la_formData,
		);

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

		$la_formData = $this->formData;
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

		return $lo_view->element('email/' . $ls_fileName, [
			'form' => $this->form,
			'formData' => $la_formData,
			'formElements' => $la_formElements,
		]);
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

		$la_formData = $this->formData;
		$la_formData['base_url'] = Router::url('/', true);

		foreach ($la_formFields as $ls_field) {
			if (
				in_array($ls_field, $la_blocklistedFields) ||
				!$this->form->get($ls_field) ||
				!is_string($this->form->get($ls_field))
			) {
				continue;
			}

			$ls_value = $this->replacePlaceholders($this->form->get($ls_field), $la_formData);

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
	 * @return void
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
			if ($lo_formElement->type !== 'file' || empty($this->formData[ $lo_formElement->identifier ])) {
				continue;
			}

			$lo_file = $this->formData[ $lo_formElement->identifier ];
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
	protected function _send(Mailer $mailer, ?string $bodyHtml, ?string $bodyPlain, string $type = 'email'): bool {
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
}
