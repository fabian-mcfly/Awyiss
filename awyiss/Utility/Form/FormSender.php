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
use Awyiss\Utility\DebugTimer;
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
	 * Stores the identifier of the saved form entry.
	 * The value is a md5 hash of the form entry id and the post-hash.
	 *
	 * @var string|null $formEntryIdentifier
	 */
	protected ?string $formEntryIdentifier = null;
	/**
	 * @var \Awyiss\Model\Entity\Page|null
	 */
	protected readonly ?Page $page;
	/**
	 * Elements that allows text but not `<table>`.
	 *
	 * @var array<string>
	 */
	protected array $phrasingOnlyTags = [
		// Phrasing-only
		'a',
		'abbr',
		'b',
		'bdi',
		'bdo',
		'cite',
		'code',
		'data',
		'dfn',
		'em',
		'i',
		'kbd',
		'label',
		'mark',
		'output',
		'q',
		'ruby',
		'rp',
		'rt',
		's',
		'samp',
		'small',
		'span',
		'strong',
		'sub',
		'sup',
		'time',
		'u',
		'var',
		'button',
		'p',
		'legend',
		'caption',
		'summary',
	];
	/**
	 * @var \Awyiss\View\FrontendView $view
	 */
	protected FrontendView $view;


	/**
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @throws \Exception
	 */
	public function __construct(Form $form, ?Page $page = null) {
		$this->form = $form;
		$this->page = $page;

		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = FactoryLocator::get('Table')->get('Forms');
		if (!$form->emailTemplate) {
			$formsTable->loadInto($form, ['EmailTemplates']);
		}
		if (!$form->confirmationEmailTemplate) {
			$formsTable->loadInto($form, ['ConfirmationEmailTemplates']);
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
	 * Returns true if the form was handled successfully, false otherwise.
	 *
	 * @return bool
	 */
	public function handle(): bool {
		DebugTimer::start('FormSender::handle', sprintf('FormSender::handle: Handling form "%s"', $this->form->identifier));

		$this->replacePlaceholdersInForm();

		$eventManager = EventManager::instance();

		// Dispatch a new event for the form beforeSend
		$event = new Event('FormSender.beforeSend', $this->form);
		$eventManager->dispatch($event);

		if ($event->isStopped()) {
			DebugTimer::stop('FormSender::handle');

			return false;
		}

		$sentEmail = null;
		if ($this->form->sendEmail) {
			$sentEmail = $this->sendEmail();
		}

		$sentConfirmationEmail = null;
		if ($sentEmail !== false && $this->form->sendConfirmationEmail) {
			$sentConfirmationEmail = $this->sendConfirmationEmail();
		}

		// If either mail was sent and the other did not result in an error, return true.
		if (
			(!$this->form->sendEmail || $sentEmail) && (!$this->form->sendConfirmationEmail || $sentConfirmationEmail)
		) {
			$result = $this->saveFormEntry();
			DebugTimer::stop('FormSender::handle');

			return $result;
		}

		DebugTimer::stop('FormSender::handle');

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
		DebugTimer::start('FormSender::sendEmail', sprintf('FormSender::sendEmail: Sending email for form "%s"', $this->form->identifier));

		// Build the mail body
		$bodyHtml = $this->createBody($this->form->emailTemplate);
		$bodyPlain = $this->createBody($this->form->emailTemplate, 'text');

		if (empty($bodyPlain) && empty($bodyHtml)) {
			DebugTimer::stop('FormSender::sendEmail');

			return false;
		}

		// Dispatch a new event for the form beforeSendEmail
		// If the event is stopped, do not send the email.
		if (
			!$this->sendEvent('FormSender.beforeSendEmail', [
				'bodyHtml' => &$bodyHtml,
				'bodyPlain' => &$bodyPlain,
			])
		) {
			DebugTimer::stop('FormSender::sendEmail');

			return false;
		}

		// Check again if both body parts are empty.
		if (empty($bodyPlain) && empty($bodyHtml)) {
			DebugTimer::stop('FormSender::sendEmail');

			return false;
		}

		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		$mailer = new $className('form');

		$mailer->setTransport($this->form->transportProfile);

		$mailer
			->setSubject(html_entity_decode($this->form->subject))
			->setFrom(html_entity_decode($this->form->userEmail), html_entity_decode($this->form->userName))
			->setTo(
				html_entity_decode($this->form->ownerEmail),
				html_entity_decode($this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix', 'Awyiss CMS'))
			)
		;

		if ($this->form->cc) {
			foreach ($this->form->cc as $cc) {
				$mailer->addCc($cc['email'], $cc['name'] ?: null);
			}
		}

		if ($this->form->bcc) {
			foreach ($this->form->bcc as $bcc) {
				$mailer->addBcc($bcc['email'], $bcc['name'] ?: null);
			}
		}

		$this->setSafeSender(
			$mailer,
			$this->form->userName,
			$this->form->userEmail,
			$this->form->userName
		);

		$this->addFormAttachments($mailer);

		// Dispatch a new event for the form beforeEmailDeliver
		// If the event is stopped, do not send the email.
		if (
			!$this->sendEvent('FormSender.beforeEmailDeliver', [
				'bodyHtml' => &$bodyHtml,
				'bodyPlain' => &$bodyPlain,
				'mailer' => $mailer,
			])
		) {
			DebugTimer::stop('FormSender::sendEmail');

			return false;
		}

		// Check again if both body parts are empty.
		if (empty($bodyPlain) && empty($bodyHtml)) {
			DebugTimer::stop('FormSender::sendEmail');

			return false;
		}

		// `both` should be default, so only set the format if it differs from the default.
		if (empty($bodyHtml) || empty($bodyPlain)) {
			$mailer->setEmailFormat(empty($bodyHtml) ? 'text' : 'html');
		}

		$sent = $this->send($mailer, $bodyHtml, $bodyPlain);

		// Dispatch a new event for the form afterEmailDeliver
		$this->sendEvent('FormSender.afterEmailDeliver', [
			'bodyHtml' => $bodyHtml,
			'bodyPlain' => $bodyPlain,
			'mailer' => $mailer,
			'sent' => $sent,
		]);

		DebugTimer::stop('FormSender::sendEmail');

		return $sent;
	}


	/**
	 * Send the confirmation email to the user
	 *
	 * @return bool
	 */
	protected function sendConfirmationEmail(): bool {
		DebugTimer::start(
			'FormSender::sendConfirmationEmail',
			sprintf('FormSender::sendConfirmationEmail: Sending confirmation email for form "%s"', $this->form->identifier)
		);

		// Build the mail body
		$bodyHtml = $this->createBody($this->form->confirmationEmailTemplate, 'html', 'confirmation');
		$bodyPlain = $this->createBody($this->form->confirmationEmailTemplate, 'text', 'confirmation');

		if (empty($bodyPlain) && empty($bodyHtml)) {
			DebugTimer::stop('FormSender::sendConfirmationEmail');

			return false;
		}

		// Dispatch a new event for the form beforeSendConfirmationEmail
		// If the event is stopped, do not send the email.
		if (
			!$this->sendEvent('FormSender.beforeSendConfirmationEmail', [
				'bodyHtml' => &$bodyHtml,
				'bodyPlain' => &$bodyPlain,
			])
		) {
			DebugTimer::stop('FormSender::sendConfirmationEmail');

			return false;
		}

		// Check again if both body parts are empty.
		if (empty($bodyPlain) && empty($bodyHtml)) {
			DebugTimer::stop('FormSender::sendConfirmationEmail');

			return false;
		}

		/** @var class-string<\Cake\Mailer\Mailer> $mailerClassName */
		$mailerClassName = App::className('Mailer', 'Mailer');
		$mailer = new $mailerClassName('form');

		$mailer->setTransport($this->form->transportProfile);

		$mailer
			->setSubject(html_entity_decode($this->form->subjectConfirmation))
			->setFrom(
				html_entity_decode($this->form->ownerEmail),
				html_entity_decode($this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix'))
			)
			->setTo(html_entity_decode($this->form->userEmail), html_entity_decode($this->form->userName))
		;

		$this->setSafeSender(
			$mailer,
			$this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix'),
			$this->form->ownerEmail,
			$this->form->ownerName ?: Configure::read('Awyiss.System.Frontend.meta.titleAppendix')
		);

		// Dispatch a new event for the form beforeConfirmationEmailDeliver
		// If the event is stopped, do not send the email.
		if (
			!$this->sendEvent('FormSender.beforeConfirmationEmailDeliver', [
				'bodyHtml' => &$bodyHtml,
				'bodyPlain' => &$bodyPlain,
				'mailer' => $mailer,
			])
		) {
			DebugTimer::stop('FormSender::sendConfirmationEmail');

			return false;
		}

		// Check again if both body parts are empty.
		if (empty($bodyPlain) && empty($bodyHtml)) {
			DebugTimer::stop('FormSender::sendConfirmationEmail');

			return false;
		}

		// `both` should be default, so only set the format if it differs from the default.
		if (empty($bodyHtml) || empty($bodyPlain)) {
			$mailer->setEmailFormat(empty($bodyHtml) ? 'text' : 'html');
		}

		$sent = $this->send($mailer, $bodyHtml, $bodyPlain, 'confirmation');

		// Dispatch a new event for the form afterConfirmationEmailDeliver
		$this->sendEvent('FormSender.afterConfirmationEmailDeliver', [
			'bodyHtml' => $bodyHtml,
			'bodyPlain' => $bodyPlain,
			'mailer' => $mailer,
			'sent' => $sent,
		]);

		DebugTimer::stop('FormSender::sendConfirmationEmail');

		return $sent;
	}


	/**
	 * Save the form entry in the database
	 *
	 * @return bool
	 */
	protected function saveFormEntry(): bool {
		DebugTimer::start(
			'FormSender::saveFormEntry',
			sprintf('FormSender::saveFormEntry: Saving form entry for form "%s"', $this->form->identifier)
		);

		$formData = $this->getFormData();
		$formData = array_filter($formData, function (mixed $key): bool {
			return !str_starts_with((string)$key, '_');
		}, ARRAY_FILTER_USE_KEY);

		// Remove hidden input protection field from form data
		if (isset($this->form->getProtectionMethods()['hiddenInput'])) {
			/** @var \Awyiss\Form\Protection\HiddenInputFormProtection $hiddenInputProtection */
			$hiddenInputProtection = $this->form->getProtectionMethods()['hiddenInput'];
			if ($hiddenInputProtection->getFieldName()) {
				unset($formData[ $hiddenInputProtection->getFieldName() ]);
			}
		}

		$ipHash = $this->createIpHash();
		$postHash = Security::hash(serialize($formData));

		$formEntry = $this->formEntriesTable->newDefaultEntity();

		$data = [
			'formId' => $this->form->id,
			'pageId' => $this->page?->id ?? null,
			'languageShortcode' => $this->page?->languageShortcode ?? Router::getRequest()->getParam('languageShortcode') ?? null,
			'subject' => html_entity_decode($this->form->subject ?? ''),
			'subjectConfirmation' => html_entity_decode($this->form->subjectConfirmation ?? ''),
			'body' => $this->emailBody['email'] ? base64_encode(gzcompress($this->emailBody['email'])) : null,
			'bodyConfirmation' => $this->emailBody['confirmation'] ? base64_encode(gzcompress($this->emailBody['confirmation'])) : null,
			'data' => base64_encode(gzcompress(json_encode($formData))),
			'ipHash' => $ipHash,
			'postHash' => $postHash,
			'identifier' => md5($ipHash . '|' . $postHash),
		];

		$this->formEntriesTable->patchEntity($formEntry, $data);

		foreach ($this->form->getProtectionMethods() as $protectionMethod) {
			$formEntry = $protectionMethod->modifyFormEntry($formEntry);

			// If the modify method decides to intervene, return the desired status.
			if (is_bool($formEntry)) {
				DebugTimer::stop('FormSender::saveFormEntry');

				return $formEntry;
			}
		}

		// Save the form entry
		if ($this->formEntriesTable->save($formEntry, ['allowFrontendSave' => true])) {
			$this->formEntryIdentifier = $formEntry->identifier;

			DebugTimer::stop('FormSender::saveFormEntry');

			return true;
		}

		DebugTimer::stop('FormSender::saveFormEntry');

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
		$body = $type === 'html' ? $emailTemplate->textHtml : $emailTemplate->textPlain;

		$formData = [
			'subject' => $this->form->subject ?: null,
			'salutation' => $this->form->salutation ?: null,
		];

		if ($for === 'confirmation') {
			$formData = [
				'subject' => $this->form->subjectConfirmation ?: null,
				'salutation' => $this->form->salutationConfirmation ?: null,
			];
		}

		// Underscored keys since replacePlaceholder() uses underscored keys.
		$formData['base_url'] = Router::url('/', true);

		$data = array_merge(
			$this->getFormData(),
			$formData,
		);

		if ($type === 'html') {
			/**
			 * {{$data}} needs a special treatment: it must never be inside an element
			 * that does not allow `<table>` as child element, because it will be
			 * replaced by the `<table>` containing all data.
			 *
			 * @noinspection RegExpRedundantEscape
			 */
			$body = $this->unwrapDataString($body);
		}

		$body = $this->replacePlaceholders($body, $data, ['data']);

		/**
		 * Only after all placeholders have been replaced, replace the actual data string.
		 *
		 * This is done to prevent users from injecting placeholders into their form data.
		 */
		$data = ['data' => $this->createDataString($type)];

		return $this->replacePlaceholders($body, $data, ['data']);
	}


	/**
	 * @param string $type
	 * @return string
	 */
	protected function createDataString(string $type): string {
		$view = $this->getView();

		$fileName = 'data';
		if ($type === 'text') {
			$fileName .= '_plain';
		}

		return $view->element('email/' . $fileName, $this->templateData());
	}


	/**
	 * @return string
	 */
	protected function createIpHash(): string {
		$request = Router::getRequest();
		$clientIp = $request->clientIp();

		return Security::hash($clientIp . Security::getSalt());
	}


	/**
	 * @return \Awyiss\View\FrontendView
	 */
	protected function getView(): FrontendView {
		if (!isset($this->view)) {
			$className = App::className('Frontend', 'View', 'View');
			$this->view = new $className();
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
		DebugTimer::start(
			'FormSender::replacePlaceholdersInForm',
			sprintf('FormSender::replacePlaceholdersInForm: Replacing placeholders in form "%s"', $this->form->identifier)
		);

		$blocklistedFields = ['active', '_translations', '_publicationData', 'mediaAssignments', 'mediaElementAssignments'];
		$formFields = array_keys(array_filter($this->form->getAccessible()));

		$formData = $this->getFormData();
		// Underscored keys since replacePlaceholder() uses underscored keys.
		$formData['base_url'] = Router::url('/', true);

		foreach ($formFields as $field) {
			if (
				in_array($field, $blocklistedFields)
				|| !$this->form->has($field)
				|| !is_string($this->form->get($field))
			) {
				continue;
			}

			$value = $this->replacePlaceholders((string)$this->form->get($field), $formData);

			$this->form->set($field, $value);
		}

		DebugTimer::stop('FormSender::replacePlaceholdersInForm');
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

		/*
		 * Find all placeholders in the form of `{{$identifier1[ ...$additionalIdentifiers]|Alternative Text}}`
		 * @see https://regex101.com/r/MYhhj2/1
		 */
		$pattern = '/\{\{(?<identifiers>[^\|\}]*?)(?:\|(?<alternative>[^\}]*?))?\}\}/U';
		preg_match_all($pattern, $string, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			$string = preg_replace_callback(
				$pattern,
				fn(array $match) => $this->replacedPlaceholdersOrAlternative($match, $values, $safeList),
				$string
			);
		}

		if (isset($this->page)) {
			// Find all placeholders in the form of `$page.identifier`, with identifier in camelBacked or under_scored format
			$pattern = '/(\$page\.(?<identifier>[a-z][a-zA-Z0-9_]+))/';
			$pageVars = $this->underscoreKeys($this->page->extract());
			$string = preg_replace_callback($pattern, fn(array $match) => $this->replacePlaceholder($match, $pageVars, $safeList), $string);
		}

		// Find all placeholders in the form of `$form.identifier`, with identifier in camelBacked or under_scored format
		$pattern = '/(\$form\.(?<identifier>[a-z][a-zA-Z0-9_]+))/';
		$formVars = $this->underscoreKeys($this->form->extract());
		$string = preg_replace_callback($pattern, fn(array $match) => $this->replacePlaceholder($match, $formVars, $safeList), $string);

		// Find all placeholders in the form of `$identifier`
		$pattern = '/(\$(?!page\.|form\.)(?<identifier>[A-Za-z0-9_]+))/';

		return preg_replace_callback($pattern, fn(array $match) => $this->replacePlaceholder($match, $values, $safeList), $string);
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
		$identifier = $match['identifier'] ?? '';

		if (!$identifier) {
			return '';
		}

		$identifier = Inflector::underscore($identifier);

		$value = $values[ $identifier ] ?? $match[0];

		if ($value === '') {
			return $match[0];
		}

		if (!in_array($identifier, $safeList)) {
			$value = htmlentities($value, ENT_QUOTES, 'UTF-8', false);
		}

		return $value;
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
		$string = $match['identifiers'] ?? '';
		$alternative = $match['alternative'] ?? null;

		if (!$string) {
			return $alternative;
		}

		if (isset($this->page)) {
			// Find all placeholders in the form of `$page.identifier`, with identifier in camelBacked or underscored format
			$pattern = '/(\$page\.(?<identifier>[a-z][a-zA-Z0-9]+))/';
			$pageVars = $this->underscoreKeys($this->page->extract());
			$string = preg_replace_callback($pattern, fn(array $match) => $this->replacePlaceholder($match, $pageVars, $safeList), $string);
		}

		// Find all placeholders in the form of `$form.identifier`, with identifier in camelBacked or underscored format
		$pattern = '/(\$form\.(?<identifier>[a-z][a-zA-Z0-9]+))/';
		$formVars = $this->underscoreKeys($this->form->extract());
		$string = preg_replace_callback($pattern, fn(array $match) => $this->replacePlaceholder($match, $formVars, $safeList), $string);

		$pattern = '/(\$(?!page\.|form\.)(?<identifier>[A-Za-z0-9_]+))/';
		$string = preg_replace_callback($pattern, fn(array $match) => $this->replacePlaceholder($match, $values, $safeList), $string);

		if (str_contains($string, '$') && $alternative !== null) {
			return $alternative;
		}

		return $string;
	}


	/**
	 * @param \Cake\Mailer\Mailer $mailer
	 * @return void
	 */
	protected function addFormAttachments(Mailer $mailer): void {
		$formElements = $this->form->formElements->listNested()->toList();
		foreach ($formElements as $formElement) {
			if ($formElement->type !== 'file' || empty($this->getFormData($formElement->identifier))) {
				continue;
			}

			$file = $this->getFormData($formElement->identifier);
			if ($file instanceof UploadedFile && !$file->getError()) {
				$mailer->addAttachments([
					$file->getClientFilename() => [
						'data' => $file->getStream()->getContents(),
						'mimetype' => $file->getStream()->getMetadata('mime'),
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
		$template = $type === 'email' ? $this->form->emailTemplate : $this->form->confirmationEmailTemplate;

		$mailer->setRenderer(new FormMailRenderer());
		$mailer
			->viewBuilder()
			->setVars([
				'textHtml' => $bodyHtml,
				'textPlain' => $bodyPlain,
				'layout' => 'email/' . $template->layout,
				'form' => $this->form,
				'page' => $this->page,
				'formData' => $this->getFormData(),
			])
			->setTemplate('Frontend/email/' . $template->fileName)
			->setLayout('email/' . str_replace('.twig', '', $template->layout))
		;

		try {
			$sendData = $mailer->deliver();
		}
		catch (Exception $ex) {
			$message = __d('Form', 'error_email_send', $ex->getMessage());
			$this->form->setError('_general', $message);
			$this->errors[] = $message;

			return false;
		}

		/** @noinspection PhpUndefinedMethodInspection */
		$this->emailBody[ $type ] = $mailer->getBodyHtml();

		return !!$sendData;
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
	 * @param string $body
	 * @return string
	 */
	protected function unwrapDataString(string $body): string {
		DebugTimer::start(
			'FormSender::unwrapDataString',
			sprintf('FormSender::unwrapDataString: Unwrapping {{$data}} in form "%s"', $this->form->identifier)
		);

		// Recursively unwrap {{$data}} from phrasingOnlyTags parents
		$phrasingTagsPattern = implode('|', array_map(preg_quote(...), $this->phrasingOnlyTags));
		$previous = '';
		/** @noinspection RegExpRedundantEscape */
		$pattern = '/<(' . $phrasingTagsPattern . ')([^>]*)>(.*?)(\{\{\$data\}\})(.*?)<\/\1>/m';

		while ($previous !== $body) {
			$previous = $body;
			$body = preg_replace_callback(
				$pattern,
				$this->_unwrapDataString(...),
				$body
			);
		}

		DebugTimer::stop('FormSender::unwrapDataString');

		return $body;
	}


	/**
	 * Adjusts and rewraps a string containing HTML content around a specified placeholder to ensure
	 * proper HTML tag closure and maintain a valid structure. This function processes tags before and after
	 * a placeholder to close and reopen those tags, while recursively removing any empty tags.
	 *
	 * @param array $matches An array containing matches from a regular expression. It should include:
	 *  - `$matches[1]`: Tag name (e.g., 'p', 'span', etc.)
	 *  - `$matches[2]`: Attributes for the wrapping tag.
	 *  - `$matches[3]`: Content before the `{{$data}}` placeholder.
	 *  - `$matches[4]`: The `{{$data}}` placeholder itself.
	 *  - `$matches[5]`: Content after the `{{$data}}` placeholder.
	 * @return string The restructured and cleaned HTML string with proper wrapping and placeholder integration.
	 */
	protected function _unwrapDataString(array $matches): string {
		$tagName = $matches[1]; // tag name (e.g., 'p', 'span')
		$attributes = $matches[2]; // tag attributes
		$beforeContent = $matches[3]; // content before {{$data}}
		$dataPlaceholder = $matches[4]; // the {{$data}} placeholder
		$afterContent = $matches[5]; // content after {{$data}}

		// If there's no other content, just return the placeholder
		if (trim($beforeContent) === '' && trim($afterContent) === '') {
			return $dataPlaceholder . PHP_EOL;
		}

		// Find all opened tags that need to be closed before dataPlaceholder
		$openedTags = [];
		preg_match_all('/<([a-z0-9]+)[^>]*>/i', $beforeContent, $openTagMatches);
		preg_match_all('/<\/([a-z0-9]+)>/i', $beforeContent, $closeTagMatches);

		// Count opened and closed tags
		$tagCounts = [];
		foreach ($openTagMatches[1] as $tag) {
			$tagCounts[ $tag ] = isset($tagCounts[ $tag ]) ? $tagCounts[ $tag ] + 1 : 1;
		}

		foreach ($closeTagMatches[1] as $tag) {
			$tagCounts[ $tag ] = isset($tagCounts[ $tag ]) ? $tagCounts[ $tag ] - 1 : -1;
		}

		// Collect tags that remain open
		foreach ($tagCounts as $tag => $count) {
			if ($count > 0) {
				// Add tags that need to be closed, in reverse order (LIFO)
				for ($i = 0; $i < $count; $i++) {
					array_unshift($openedTags, $tag);
				}
			}
		}

		// Build the result
		$result = '<' . $tagName . $attributes . '>' . trim($beforeContent);

		// Close all open tags before the dataPlaceholder
		foreach ($openedTags as $tag) {
			$result .= '</' . $tag . '>';
		}

		$result .= '</' . $tagName . '>' . PHP_EOL . $dataPlaceholder . PHP_EOL . '<' . $tagName . $attributes . '>';

		// Reopen all closed tags in the original order
		foreach (array_reverse($openedTags) as $tag) {
			$result .= '<' . $tag . '>';
		}

		$result .= trim($afterContent) . '</' . $tagName . '>';

		// Filter out empty tags recursively
		$previous = '';
		// Remove all empty tags recursively, including those with attributes (like <span class="foo"></span>)
		while ($previous !== $result) {
			$previous = $result;
			$result = preg_replace('/<([a-z0-9]+)(?:\s[^>]*)?><\/\1>/', '', $result);
		}

		return trim($result) . PHP_EOL;
	}


	/**
	 * @return array
	 */
	protected function templateData(): array {
		$formData = $this->getFormData();
		$formElements = $this->form->formElements->listNested()->toList();

		foreach ($formElements as $formElement) {
			if (
				isset($formData[ $formElement->identifier ])
				&& in_array($formElement->type, ['date', 'time', 'datetime'])
			) {
				$date = new DateTime($formData[ $formElement->identifier ]);

				if ($formElement->type === 'datetime') {
					$date->setTimezone(new DateTimeZone('UTC'));
				}

				$formData[ $formElement->identifier ] = $date;
			}
		}

		return [
			'form' => $this->form,
			'formData' => $formData,
			'formElements' => $formElements,
		];
	}


	/**
	 * @param string $eventName
	 * @param array $data
	 * @return bool
	 */
	protected function sendEvent(string $eventName, array $data): bool {
		$eventManager = EventManager::instance();

		// Dispatch a new event for the form beforeSendEmail
		$event = new Event($eventName, $this->form, $data);
		$eventManager->dispatch($event);

		if ($event->isStopped()) {
			return false;
		}

		$result = $event->getResult();
		if ($result !== null) {
			if (!is_array($result)) {
				throw new InvalidArgumentException(
					sprintf('Expected an array as result of the event "%s", `%s` given', $eventName, gettype($result))
				);
			}

			foreach ($result as $key => $value) {
				if (array_key_exists($key, $data)) {
					$data[ $key ] = $value;
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
		$safeRealSender = $this->getFormOptions()->getSafeRealSender();
		if ($safeRealSender) {
			$mailer
				->setSender($safeRealSender, html_entity_decode($senderName))
				->setReplyTo(html_entity_decode($replyToMail), html_entity_decode($replyToName))
			;
		}

		// Ensure a valid return-path is set
		if (!$mailer->getReturnPath()) {
			// Make sure to only use the main domain (e.g. example.com instead of sub.example.com or www.example.com)
			$domain = $mailer->getDomain();
			if (substr_count($domain, '.') > 1) {
				$domainParts = explode('.', $domain);
				$domain = implode('.', array_slice($domainParts, -2));
			}

			$mailer->setReturnPath('noreply@' . $domain);
		}

		return $this;
	}


	/**
	 * @param array $vars
	 * @return array
	 */
	protected function underscoreKeys(array $vars): array {
		$result = [];
		foreach ($vars as $key => $value) {
			$result[ Inflector::underscore($key) ] = $value;
		}

		return $result;
	}
}
