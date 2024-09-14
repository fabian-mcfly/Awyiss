<?php declare(strict_types=1);


namespace Awyiss\Utility\Form;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Form\FormOptionsInterface;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\Validation\Validator;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\FrontendView;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use RuntimeException;


/**
 * Class FormRenderer
 * This class is responsible for rendering and processing forms.
 */
class FormRenderer {
	use ContentElementTrait;
	use LocatorAwareTrait;
	use PreviewTrait;


	/**
	 * @var \Awyiss\Model\Entity\Form|null
	 */
	protected ?Form $form = null;
	/**
	 * @var array $formData
	 */
	protected array $formData = [];
	/**
	 * @var string|null $formElementsChecksum
	 */
	protected ?string $formElementsChecksum = null;
	/**
	 * @var array $formErrors
	 */
	protected array $formErrors = [];
	/**
	 * @var \Awyiss\Form\FormOptionsInterface $formOptions
	 */
	protected FormOptionsInterface $formOptions;
	/**
	 * @var bool|null $formSent
	 */
	protected ?bool $formSent = null;
	/**
	 * @var bool $formSubmitted
	 */
	protected bool $formSubmitted = false;
	/**
	 * @var string $languageShortcode
	 */
	protected string $languageShortcode;
	/**
	 * @var \Awyiss\View\FrontendView
	 * @noinspection PhpPropertyNamingConventionInspection
	 */
	protected FrontendView $View;


	/**
	 * @param \Awyiss\View\FrontendView $view
	 */
	public function __construct(FrontendView $view) {
		$this->View = $view;
	}


	/**
	 * @param string|int $identifier
	 * @return \Awyiss\Model\Entity\Form|null
	 */
	public function getFormByIdentifier(string|int $identifier): ?Form {
		/** @var \Awyiss\Model\Table\FormsTable $lo_formsTable */
		$lo_formsTable = $this->fetchTable('Forms');

		if ($this->isPreview()) {
			$lo_query = $lo_formsTable->find('all');
		}
		else {
			$lo_query = $lo_formsTable->find('active')->find('published');
		}

		if (is_int($identifier)) {
			$lo_query = $lo_query->where(['Forms.id' => $identifier]);
		}
		else {
			$lo_query = $lo_query->where(['Forms.identifier' => $identifier]);
		}

		return $lo_query->first();
	}


	/**
	 * @param string|int $identifier
	 * @param array $requestData
	 * @param string $languageShortcode
	 * @return $this
	 */
	public function initForm(string|int $identifier, array $requestData, string $languageShortcode): static {
		$this->form = $this->getFormByIdentifier($identifier);
		$this->languageShortcode = $languageShortcode;

		if (!$this->form) {
			return $this;
		}

		if ($this->form->identifier === ($requestData['form_identifier'] ?? null)) {
			$this->formSubmitted = true;
			$this->formData = $requestData;
		}

		$this->formOptions = $this->getFormOptions($this->form);

		// Load the form elements into the form
		$this->form->formElements = $this->getFormElements();

		$this->formOptions->modifyForm($this->form, $this->formData, $this->formSubmitted);

		return $this;
	}


	/**
	 * Returns whether the form was sent,
	 * or null if the form was not processed yet (no request data).
	 *
	 * @return bool|null
	 */
	public function process(): ?bool {
		if (!$this->form) {
			throw new RuntimeException('No form was initialized.');
		}

		// Validate the form
		if (!$this->formSubmitted) {
			return null;
		}

		if ($this->isValid()) {
			$this->sendAndRedirect();
		}

		return $this->formSent;
	}


	/**
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 */
	public function buildFormBody(array $options): string {
		if (!$this->form) {
			throw new RuntimeException('No form was initialized.');
		}

		$this->View->set([
			'formData' => $this->formData,
			'formErrors' => $this->formErrors,
			'sent' => $this->formSent,
			'submitted' => $this->formSubmitted,
			'fullWidth' => $options['fullWidth'],
			'singleColumnBreakpoint' => $options['singleColumnBreakpoint'],
			...$options['viewVars'],
		]);

		$lo_formElements = $this->form->formElements;

		$this->prepareEntities($lo_formElements, (float)$options['columnWidth']);

		$la_formElements = $lo_formElements->listNested()->toList();
		foreach ($la_formElements as $lo_formElement) {
			if (!empty($this->formErrors[ $lo_formElement->identifier ])) {
				$lo_formElement->cssClass .= ' FormElement-IsInvalid';
			}
		}

		// If there's at least one input of type file, set the form enctype to multipart/form-data
		$this->form->set(
			'enctype',
			array_reduce($la_formElements, function ($carry, FormElement $element) {
				return $carry || $element->type === 'file';
			}, false) ? 'multipart/form-data' : null
		);

		return $this->buildContents($lo_formElements->toArray());
	}


	/**
	 * @return \Awyiss\Model\Entity\Form|null
	 */
	public function getForm(): ?Form {
		return $this->form;
	}


	/**
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 */
	public function getFormBody(array $options): string {
		if (!$this->form || $this->formSent) {
			return '';
		}

		return $this->buildFormBody($options);
	}


	/**
	 * @return array
	 */
	public function getFormData(): array {
		return $this->formData;
	}


	/**
	 * @return array
	 */
	public function getFormErrors(): array {
		return $this->formErrors;
	}


	/**
	 * @return CollectionInterface
	 */
	public function getFormElements(): CollectionInterface {
		if (!$this->form) {
			return new Collection([]);
		}

		if ($this->form->formElements) {
			return $this->form->formElements;
		}

		/** @var \Awyiss\Model\Table\FormElementsTable $lo_formElementsTable */
		$lo_formElementsTable = $this->fetchTable('FormElements');

		if ($this->isPreview()) {
			$lo_query = $lo_formElementsTable->find('all');
		}
		else {
			$lo_query = $lo_formElementsTable->find('active')->find('published');
		}

		$lo_formElements = $lo_query->find('threaded')->where([
			'form_id' => $this->form->id,
		])->all()->filter(function (FormElement $content) {
			return $content->parentId === null;
		})->compile();

		$la_formElements = $lo_formElements->listNested()->toList();
		foreach ($la_formElements as $lo_formElement) {
			if (in_array($lo_formElement->type, ['checkbox', 'radio', 'select', 'select_multiple'])) {
				$lo_formElement->options = $this->parseOptions($lo_formElement->options, $lo_formElement->type);
			}

			$this->formOptions->modifyFormElement($lo_formElement, $this->form, $this->formData, $this->formSubmitted);
		}

		$this->formElementsChecksum = md5(serialize($la_formElements));

		return $lo_formElements;
	}


	/**
	 * Finds the option file for the form.
	 * If none was found, the default file will be used.
	 *
	 * @param \Awyiss\Model\Entity\Form $form
	 * @return \Awyiss\Form\FormOptionsInterface
	 */
	public function getFormOptions(Form $form): FormOptionsInterface {
		if (isset($this->formOptions)) {
			return $this->formOptions;
		}

		$ls_className = App::className(Inflector::ucparts($form->identifier, false) . 'FormOptions', 'Form');

		if (!$ls_className) {
			$ls_className = App::className('FormOptions', 'Form');
		}

		return $this->formOptions = new $ls_className();
	}


	/**
	 * @return \Awyiss\Validation\Validator
	 */
	public function getValidator(): Validator {
		$lo_validator = new Validator();
		$lo_validator->setI18nDomain('form');

		//$lo_validator->setStopOnFailure();

		return $lo_validator;
	}


	/**
	 * @return bool|null
	 */
	public function isFormSubmitted(): ?bool {
		return $this->formSubmitted;
	}


	/**
	 * @return bool|null
	 */
	public function isFormSent(): ?bool {
		return $this->formSent;
	}


	/**
	 * @return bool
	 */
	public function isValid(): bool {
		if (!$this->form) {
			throw new RuntimeException('No form was initialized.');
		}

		if (empty($this->formData)) {
			throw new RuntimeException('No form data was provided.');
		}

		$lo_validator = $this->formOptions->getValidator($this->getValidator(), $this->form);

		$this->formErrors = $lo_validator->validate($this->formData);

		return !$this->formErrors;
	}


	/**
	 * @param string $formEntryHash
	 * @return \Awyiss\Model\Entity\FormEntry|null
	 */
	public function loadFormDataForEntryHash(string $formEntryHash): ?Entity {
		/** @var \Awyiss\Model\Table\FormEntriesTable $lo_formEntriesTable */
		$lo_formEntriesTable = $this->fetchTable('FormEntries');

		/** @var \Awyiss\Model\Entity\FormEntry|null $lo_entry */
		$lo_entry = $lo_formEntriesTable->find('all')->where(function (QueryExpression $exp, SelectQuery $query) use ($formEntryHash) {
			// The concat of the id and the post_hash must equal the form entry identifier
			/** @noinspection PhpUndefinedMethodInspection */
			return $exp->eq($query->func()->md5([
				$query->func()->concat([
					'FormEntries.id' => 'identifier',
					' | ',
					'FormEntries.post_hash' => 'identifier',
				]),
			]), $formEntryHash);
		})->first();

		return $lo_entry;
	}


	/**
	 * @param \Awyiss\Model\Entity\FormEntry $entry
	 * @return $this
	 */
	public function processFormEntry(FormEntry $entry): static {
		$la_formData = json_decode(gzuncompress(base64_decode($entry->data)), true);

		if (empty($la_formData)) {
			return $this;
		}

		$lo_formHandler = new FormSender($this->form, $la_formData);
		$lo_formHandler->replacePlaceholdersInForm();

		$this->formSent = $this->formSubmitted = true;

		return $this;
	}


	/**
	 * @param string $formEntryHash
	 * @return $this
	 */
	public function processFormDataForEntryHash(string $formEntryHash): static {
		$lo_entry = $this->loadFormDataForEntryHash($formEntryHash);

		if (!$lo_entry || $lo_entry->formId !== $this->form?->id) {
			return $this;
		}

		return $this->processFormEntry($lo_entry);
	}


	/**
	 * @return string|null
	 */
	public function getFormElementsChecksum(): ?string {
		return $this->formElementsChecksum;
	}


	/**
	 * @param array|null $options
	 * @param string $type
	 * @return array
	 */
	protected function parseOptions(?array $options, string $type): array {
		if (!$options) {
			return [];
		}

		$la_options = [];
		foreach ($options as $li_key => $la_option) {
			if (isset($la_option['_translations'][ $this->languageShortcode ])) {
				$ls_value = $la_option['_translations'][ $this->languageShortcode ]['value'];
				$ls_key = $la_option['_translations'][ $this->languageShortcode ]['key'];
			}
			else {
				$ls_key = $la_option['key'];
				$ls_value = $la_option['value'];
			}

			if (empty($ls_key)) {
				$ls_key = $ls_value;
			}
			elseif (empty($ls_value)) {
				$ls_value = $ls_key;
			}

			// If both, key and value are empty, skip this option if the element is not the first one
			if (($li_key !== 0 || in_array($type, ['checkbox', 'radio'])) && empty($ls_key) && empty($ls_value)) {
				continue;
			}

			$la_options[ $ls_key ] = $ls_value;
		}

		return $la_options;
	}


	/**
	 * @inheritDoc
	 * @param \Awyiss\Model\Entity\FormElement $entity
	 * @param string $children
	 * @return string
	 * @throws \ReflectionException
	 */
	protected function renderElement(Entity $entity, string $children): string {
		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $lo_mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->View->get('fullWidth'),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
			columnWidth: $entity->realColumnWidth,
			selector: '#FormElement' . $entity->id,
			singleColumnBreakpoint: $this->View->get('singleColumnBreakpoint'),
		);

		if ($entity->type === 'free_text') {
			// Parse the module
			$this->parseModule($entity, $lo_mediaRenderOptions);
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $this->View->element('form/form_elements', [
			'formElement' => $entity,
			'children' => $children,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Form $form
	 * @return string|false
	 */
	protected function sendForm(Form $form): string|false {
		$lo_formSender = new FormSender($form, $this->formData);
		$this->formSent = $lo_formSender->handle();

		if (!$this->formSent) {
			$this->formErrors['_general'] = $lo_formSender->getErrors();

			return false;
		}

		return $lo_formSender->getFormEntryIdentifier();
	}


	/**
	 * Sends the form using the form handler,
	 * and redirects to a URL with the form entry hash on success.
	 *
	 * @return void
	 */
	public function sendAndRedirect(): void {
		if (!$this->form) {
			throw new RuntimeException('No form was initialized.');
		}

		$ls_responseCode = $this->sendForm($this->form);

		if ($ls_responseCode !== false) {
			$la_url = [
				'formEntry' => $ls_responseCode,
				'#' => 'Form-' . Inflector::ucparts($this->form->identifier, false),
			];

			$lo_request = Router::getRequest();

			if ($lo_request->getParam('_name') === 'FrontendFormAntiSpam') {
				$la_url['_name'] = 'FrontendFormAntiSpam';
			}
			else {
				$ls_languageShortcode = $lo_request->getParam('lang');
				if (!empty($ls_languageShortcode)) {
					$la_url['lang'] = $ls_languageShortcode;
					$la_url['_name'] = Awyiss::REALM_FRONTEND . 'LanguageRoot';
				}

				$ls_slug = $lo_request->getParam('slug');
				if (!empty($ls_slug)) {
					$la_url['slug'] = $ls_slug;
					$la_url['_name'] = Awyiss::REALM_FRONTEND . 'Root';
				}
			}

			throw new RedirectException(Router::url($la_url, true), 302);
		}
	}
}
