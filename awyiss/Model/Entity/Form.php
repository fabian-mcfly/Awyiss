<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Core\App;
use Awyiss\Form\FormOptionsInterface;
use Awyiss\Form\Protection\FormProtectionProvider;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Awyiss\Validation\Validator;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use Cake\View\View;


/**
 * Form Entity
 *
 * @property int $id
 * @property string $title
 * @property string $identifier
 * @property bool $sendEmail
 * @property int|null $emailTemplateId
 * @property bool $sendConfirmationEmail
 * @property int|null $confirmationEmailTemplateId
 * @property string|null $ownerEmail
 * @property string|null $ownerName
 * @property string|null $userEmail
 * @property string|null $userName
 * @property string|null $cc
 * @property string|null $bcc
 * @property string|null $subject
 * @property string|null $subjectConfirmation
 * @property string|null $salutation
 * @property string|null $salutationConfirmation
 * @property bool $summarizeErrors
 * @property string|null $successMessage
 * @property bool $multistep
 * @property string $conditionalRecipientsStrategy
 * @property string $transportProfile
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\EmailTemplate $emailTemplate
 * @property \Awyiss\Model\Entity\EmailTemplate $confirmationEmailTemplate
 * @property \Awyiss\Model\Entity\FormElement[]|\Cake\Collection\CollectionInterface $formElements
 * @property \Awyiss\Model\Entity\FormEntry[]|\Cake\Collection\CollectionInterface $formEntries
 * @property \Awyiss\Model\Entity\FormConditionalRecipient[]|\Cake\Collection\CollectionInterface $formConditionalRecipients
 */
class Form extends Entity {
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'send_email' => 'sendEmail',
		'email_template_id' => 'emailTemplateId',
		'send_confirmation_email' => 'sendConfirmationEmail',
		'confirmation_email_template_id' => 'confirmationEmailTemplateId',
		'owner_email' => 'ownerEmail',
		'owner_name' => 'ownerName',
		'user_email' => 'userEmail',
		'user_name' => 'userName',
		'subject_confirmation' => 'subjectConfirmation',
		'salutation_confirmation' => 'salutationConfirmation',
		'summarize_errors' => 'summarizeErrors',
		'success_message' => 'successMessage',
		'conditional_recipients_strategy' => 'conditionalRecipientsStrategy',
		'transport_profile' => 'transportProfile',
		'email_template' => 'emailTemplate',
		'confirmation_email_template' => 'confirmationEmailTemplate',
		'form_elements' => 'formElements',
		'form_entries' => 'formEntries',
		'form_conditional_recipients' => 'formConditionalRecipients',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'title' => true,
		'identifier' => true,
		'sendEmail' => true,
		'emailTemplateId' => true,
		'sendConfirmationEmail' => true,
		'confirmationEmailTemplateId' => true,
		'ownerEmail' => true,
		'ownerName' => true,
		'userEmail' => true,
		'userName' => true,
		'cc' => true,
		'bcc' => true,
		'subject' => true,
		'subjectConfirmation' => true,
		'salutation' => true,
		'salutationConfirmation' => true,
		'summarizeErrors' => true,
		'successMessage' => true,
		'multistep' => true,
		'conditionalRecipientsStrategy' => true,
		'transportProfile' => true,
		'active' => true,
		'formConditionalRecipients' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'transportProfile' => 'default',
	];
	/**
	 * @var array
	 */
	protected array $formData = [];
	/**
	 * @var string|null
	 */
	protected ?string $formElementsChecksum = null;
	/**
	 * @var \Awyiss\Form\FormOptionsInterface|null
	 */
	protected ?FormOptionsInterface $formOptions = null;
	/**
	 * @var bool
	 */
	protected bool $formSubmitted = false;
	/**
	 * @var bool
	 */
	protected bool $isPreview = false;
	/**
	 * @var array<string, \Awyiss\Form\Protection\FormProtectionInterface>
	 */
	protected array $protectionMethods;
	/**
	 * @var \Awyiss\Model\Entity\Page|null
	 */
	protected ?Page $sourcePage = null;
	/**
	 * @var \Cake\View\View
	 */
	protected View $view;


	/**
	 * Initialize the form to be processed
	 * and used to send emails and/or save data
	 *
	 * @param \Cake\View\View $view
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @param bool $isPreview
	 * @return $this
	 * @throws \Exception
	 */
	public function initialize(View $view, ?Page $page = null, bool $isPreview = false): static {
		$this->view = $view;
		$this->isPreview = $isPreview;
		$this->sourcePage = $page;

		$this
			->loadFormOptions()
			->loadFormElements()
			->initProtectionMethods();

		return $this;
	}


	/**
	 * @param string|null $identifier
	 * @return array
	 */
	public function getFormData(?string $identifier = null): mixed {
		if ($identifier) {
			return $this->formData[ $identifier ] ?? null;
		}

		return $this->formData;
	}


	/**
	 * @param array $formData
	 * @return static
	 */
	public function setFormData(array $formData): static {
		$this->formData = $formData;

		return $this;
	}


	/**
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	public function getFormElements(): ?CollectionInterface {
		if (isset($this->formElements)) {
			return $this->formElements;
		}

		return null;
	}


	/**
	 * @return $this
	 * @throws \Exception
	 */
	public function loadFormElements(): static {
		/** @var \Awyiss\Model\Table\FormElementsTable $formElementsTable */
		$formElementsTable = $this->fetchTable('FormElements');

		if ($this->isPreview) {
			$query = $formElementsTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $formElementsTable->find('active')->find('published');
		}

		$formElements = $query->find('threaded')->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true)->where([
			'form_id' => $this->id,
		])->all()->filter(function (FormElement $content) {
			return $content->parentId === null;
		})->compile();

		if (!$formElements->count()) {
			return $this;
		}

		/** @var array<\Awyiss\Model\Entity\FormElement> $listedFormElements */
		$listedFormElements = $formElements->listNested()->toList();
		foreach ($listedFormElements as $formElement) {
			if (in_array($formElement->type, ['checkbox', 'radio', 'select', 'select_multiple'])) {
				$formElement->options = $formElement->parseOptions(
					$formElement->options,
					$formElement->type,
					$this->sourcePage?->languageShortcode ?? LocaleMiddleware::getLanguage()?->shortcode ?? null
				);
			}

			$this->getFormOptions()->modifyFormElement($formElement);
		}

		$this->formElementsChecksum = md5(serialize($listedFormElements));

		$this->formElements = $formElements;

		return $this;
	}


	/**
	 * Get all form elements in a linear array, indexed by their identifier
	 *
	 * @return array
	 */
	public function getLinearFormElements(): array {
		return $this->getFormElements()->listNested()->filter(function (FormElement $element): bool {
			return !empty($element->identifier);
		})->indexBy('identifier')->toArray();
	}


	/**
	 * @return string|null
	 */
	public function getFormElementsChecksum(): ?string {
		return $this->formElementsChecksum;
	}


	/**
	 * @return \Awyiss\Form\FormOptionsInterface|null
	 */
	public function getFormOptions(): ?FormOptionsInterface {
		return $this->formOptions;
	}


	/**
	 * @return static
	 */
	public function loadFormOptions(): static {
		if (!isset($this->formOptions)) {
			$className = App::className(Inflector::ucparts($this->identifier, false) . 'FormOptions', 'Form');

			if (!$className) {
				$className = App::className('FormOptions', 'Form');
			}

			$this->formOptions = new $className($this, $this->sourcePage);
		}

		return $this;
	}


	/**
	 * @param bool $submitted
	 * @return static
	 */
	public function submitted(bool $submitted = true): static {
		$this->formSubmitted = $submitted;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isSubmitted(): bool {
		return $this->formSubmitted;
	}


	/**
	 * @return \Awyiss\Validation\Validator
	 */
	public function getValidator(): Validator {
		$validator = new Validator();
		$validator->setI18nDomain('form');

		return $validator;
	}


	/**
	 * @param array|null $formData
	 * @param \Awyiss\Validation\Validator|null $validator
	 * @param bool|null $validateProtection
	 * @return bool
	 */
	public function validate(?array $formData = null, ?Validator $validator = null, ?bool $validateProtection = null): bool {
		$validator ??= $this->getFormOptions()->setValidationRules($this->getValidator());

		$this->setErrors($validator->validate($formData ?? $this->getFormData()));

		// Validate the protection methods if forced (true)
		// or if no other validation errors are present (null)
		if (
			$validateProtection === true ||
			(
				$validateProtection === null &&
				!$this->getErrors()
			)
		) {
			$this->validateProtection($formData ?? $this->getFormData());
		}

		// Modify the form using the protection methods
		foreach ($this->getProtectionMethods() as $protectionMethod) {
			$protectionMethod->modifyForm($this);
		}

		return !$this->getErrors();
	}


	/**
	 * @return bool
	 */
	public function isValid(): bool {
		return !$this->getErrors();
	}


	/**
	 * @return static
	 */
	protected function initProtectionMethods(): static {
		$protectionMethods = Configure::read('Awyiss.Forms.Frontend.protection.methods');

		if (!$protectionMethods) {
			$this->protectionMethods = [];
			return $this;
		}

		$formElements = $this->formElements ?? [];
		if (!is_array($formElements)) {
			$filteredFormElements = $formElements->listNested()->filter(fn (FormElement $element) => $element->identifier !== null);
			$formElements = $filteredFormElements->indexBy('identifier')->toArray();
		}

		$this->protectionMethods = [];

		foreach ($protectionMethods as $identifier) {
			$class = FormProtectionProvider::getFormProtectionFile($identifier);

			if ($class === null) {
				continue;
			}

			$protection = new $class();

			$protection->initialize($this, $formElements, $this->formOptions, $this->view);

			$parts = explode('\\', $protection::class);
			$identifier = array_pop($parts);
			$identifier = FormProtectionProvider::sanitizeIdentifier(substr($identifier, 0, -14));

			$this->protectionMethods[ $identifier ] = $protection;
		}

		return $this;
	}

	/**
	 * @return array<\Awyiss\Form\Protection\FormProtectionInterface>
	 */
	public function getProtectionMethods(): array {
		if (!isset($this->protectionMethods)) {
			$this->protectionMethods = [];
		}

		return $this->protectionMethods;
	}


	/**
	 * @param array $formData
	 * @return \Awyiss\Model\Entity\Form
	 */
	public function validateProtection(array $formData): static {
		if (!$this->getProtectionMethods()) {
			return $this;
		}

		foreach ($this->getProtectionMethods() as $identifier => $protection) {
			$error = $protection->validateData($formData);

			if ($error !== true) {
				$errors = $this->getError('_general') ?? [];
				$errors[ $identifier ] = $error;
				$this->setError('_general', $errors);
			}
		}

		return $this;
	}


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Form::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		$identifier = Text::slug($identifier, ['replacement' => '_']);

		return mb_strtolower($identifier);
	}
}
