<?php declare(strict_types=1);


namespace Awyiss\Utility\Form;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Awyiss\Utility\DebugTimer;
use Awyiss\Utility\Inflector;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\FrontendView;
use Cake\Core\Configure;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Locator\LocatorAwareTrait;
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
	 * @var bool|null $formSent
	 */
	protected ?bool $formSent = null;
	/**
	 * @var \Awyiss\Model\Entity\Page|null $page
	 */
	protected ?Page $page = null;
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
		DebugTimer::start('FormRenderer::getFormByIdentifier', sprintf('FormRenderer::getFormByIdentifier: Fetching form "%s"', $identifier));

		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = $this->fetchTable('Forms');

		if ($this->isPreview()) {
			$query = $formsTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $formsTable->find('active')->find('published');
		}

		$query = $query->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);

		if (is_int($identifier)) {
			$query = $query->where(['Forms.id' => $identifier]);
		}
		else {
			$query = $query->where(['Forms.identifier' => $identifier]);
		}

		/** @var \Awyiss\Model\Entity\Form|null $form */
		$form = $query->first();

		DebugTimer::stop('FormRenderer::getFormByIdentifier');

		return $form;
	}


	/**
	 * @param \Awyiss\Model\Entity\Form|string|int $form Form
	 * @param array $requestData
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @return $this
	 * @throws \Exception
	 */
	public function initForm(Form|string|int $form, array $requestData, ?Page $page = null): static {
		DebugTimer::start('FormRenderer::initForm', sprintf('FormRenderer::initForm: Initializing form "%s"', $form instanceof Form ? $form->identifier : $form));

		$this->form = $form instanceof Form ? $form : $this->getFormByIdentifier($form);
		$this->page = $page;

		if (!$this->form) {
			DebugTimer::stop('FormRenderer::initForm');
			return $this;
		}

		if ($this->form->identifier === ($requestData['_formIdentifier'] ?? null)) {
			$this->form->submitted();

			$this->form->setFormData($requestData);
		}

		$this->form->initialize(
			$this->View,
			$this->page,
			$this->isPreview(),
		);

		$this->form->getFormOptions()->modifyForm();

		if ($this->form->isSubmitted()) {
			$this->form->getFormOptions()->setConditionalRecipient();
		}

		DebugTimer::stop('FormRenderer::initForm');
		return $this;
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
		DebugTimer::start('FormRenderer::getFormBody', sprintf('FormRenderer::getFormBody: Building body for form "%s"', $this->form?->identifier ?? 'unknown'));

		if (!$this->form || $this->formSent) {
			DebugTimer::stop('FormRenderer::getFormBody');
			return '';
		}

		$this->View->set([
			'formData' => $this->form->getFormData(),
			'formErrors' => $this->form->getErrors(),
			'sent' => $this->formSent,
			'submitted' => $this->form->isSubmitted(),
			'fullWidth' => $options['fullWidth'] ?? $this->View->get('fullWidth', 1920),
			'singleColumnBreakpoint' => $options['singleColumnBreakpoint'] ?? $this->View->get('singleColumnBreakpoint', 860),
			...($options['viewVars'] ?? []),
		]);

		$formElements = $this->form->getFormElements();

		if (!$formElements) {
			DebugTimer::stop('FormRenderer::getFormBody');
			return '';
		}

		$this->prepareEntities($formElements, (float)($options['columnWidth'] ?? $this->View->get('columnWidth', 100.0)));

		$listedFormElements = $formElements->listNested()->toList();
		foreach ($listedFormElements as $formElement) {
			if (!$formElement->identifier) {
				continue;
			}

			if ($this->form->getError($formElement->identifier)) {
				$formElement->cssClass .= ' FormElement-IsInvalid';
			}
		}

		// If there's at least one input of input-type `file`, set the form enctype to multipart/form-data
		$this->form->set(
			'enctype',
			array_reduce($listedFormElements, function (bool $multipart, FormElement $element) {
				return $multipart || $element->type === 'file';
			}, false) ? 'multipart/form-data' : null
		);

		$formBody = $this->buildContents($formElements->toArray());

		DebugTimer::stop('FormRenderer::getFormBody');

		return $formBody;
	}


	/**
	 * @return bool|null
	 */
	public function isSent(): ?bool {
		return $this->formSent;
	}


	/**
	 * @param string $entryHash
	 * @return \Awyiss\Model\Entity\FormEntry|null
	 */
	public function loadFormEntryFromHash(string $entryHash): ?Entity {
		/** @var \Awyiss\Model\Table\FormEntriesTable $formEntriesTable */
		$formEntriesTable = $this->fetchTable('FormEntries');

		/** @var \Awyiss\Model\Entity\FormEntry|null $entry */
		$entry = $formEntriesTable->find('all')->where(['identifier' => $entryHash])->first();

		return $entry;
	}


	/**
	 * Returns whether the form was sent
	 * or null if the form was not processed yet (no request data).
	 *
	 * @param string|null $entryHash
	 * @param array $options
	 * @return void
	 * @throws \Exception
	 */
	public function process(?string $entryHash = null, array $options = []): void {
		DebugTimer::start('FormRenderer::process', sprintf('FormRenderer::process: Processing form "%s"', $this->form?->identifier ?? 'unknown'));

		if (!$this->form) {
			DebugTimer::stop('FormRenderer::process');
			throw new RuntimeException('No form was initialized.');
		}

		// If the form is not submitted, but there's an entry hash, try to load the entry
		if (!$this->form->isSubmitted()) {
			if ($entryHash && !$this->isSent()) {
				$this->processFormEntryFromHash($entryHash, $options);
			}

			DebugTimer::stop('FormRenderer::process');
			return;
		}

		// Validate the form using the form's and form options' validator
		$this->form->validate();

		if ($this->form->isValid()) {
			$this->sendAndRedirect();
		}

		DebugTimer::stop('FormRenderer::process');
	}


	/**
	 * @param \Awyiss\Model\Entity\FormEntry $entry
	 * @param array $options
	 * @return $this
	 * @throws \Exception
	 */
	public function processFormEntry(FormEntry $entry, array $options = []): static {
		$formData = json_decode(gzuncompress(base64_decode($entry->data)), true);

		if (empty($formData)) {
			return $this;
		}

		$this->form?->setFormData($formData);

		/** @var \Awyiss\Utility\Form\FormSender $formSenderClass */
		$formSenderClass = App::className('FormSender', 'Utility/Form');

		$formSender = new $formSenderClass($this->form, $this->page);
		$formSender->replacePlaceholdersInForm();

		$this->formSent = true;

		$this->parseTagsInForm($options);

		return $this;
	}


	/**
	 * @param string $entryHash
	 * @param array $options
	 * @return $this
	 * @throws \Exception
	 */
	public function processFormEntryFromHash(string $entryHash, array $options = []): static {
		$entry = $this->loadFormEntryFromHash($entryHash);

		if (!$entry || $entry->formId !== $this->form?->id) {
			return $this;
		}

		return $this->processFormEntry($entry, $options);
	}


	/**
	 * @inheritDoc
	 * @param \Awyiss\Model\Entity\FormElement $entity
	 * @param string $children
	 * @return string
	 * @throws \Exception
	 */
	protected function renderElement(Entity $entity, string $children): string {
		DebugTimer::start('FormRenderer::renderElement' . $entity->id, sprintf('FormRenderer::renderElement: Rendering form element #%d type "%s"', $entity->id, $entity->type));

		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->View->get('fullWidth', 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
			columnWidth: $entity->realColumnWidth,
			selector: '#FormElement' . $entity->id,
			singleColumnBreakpoint: $this->View->get('singleColumnBreakpoint'),
		);

		if ($entity->type === 'freeText') {
			// Parse the Awyiss image tags
			$this->parseAwyissImageTags($entity, $mediaRenderOptions);

			// Parse the widgets
			$this->parseWidgets($entity, $mediaRenderOptions);
		}

		$fullWidthMissingWarning = '';
		if (!$this->View->get('fullWidth')) {
			$fullWidthMissingWarning = '<!-- Full width is missing. Please add the `fullWidth`-option to the form cell. -->';
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$renderedElement = $fullWidthMissingWarning . $this->View->element('form/form_elements', [
			'form' => $this->form,
			'formData' => $this->form->getFormData(),
			'formElement' => $entity,
			'formErrors' => $this->form->getErrors(),
			'children' => $children,
			'mediaRenderOptions' => $mediaRenderOptions,
		]);

		DebugTimer::stop('FormRenderer::renderElement' . $entity->id);

		return $renderedElement;
	}


	/**
	 * @return string|false
	 */
	public function sendForm(): string|false {
		DebugTimer::start('FormRenderer::sendForm', sprintf('FormRenderer::sendForm: Sending form "%s"', $this->form?->identifier ?? 'unknown'));

		if (!$this->form) {
			DebugTimer::stop('FormRenderer::sendForm');
			throw new RuntimeException('No form was initialized.');
		}

		// Make sure the form is submitted
		if (!$this->form->isSubmitted()) {
			DebugTimer::stop('FormRenderer::sendForm');
			return false;
		}

		/** @var \Awyiss\Utility\Form\FormSender $formSenderClass */
		$formSenderClass = App::className('FormSender', 'Utility/Form');

		$formSender = new $formSenderClass($this->form, $this->page);
		$this->formSent = $formSender->handle();

		if (!$this->formSent) {
			DebugTimer::stop('FormRenderer::sendForm');
			return false;
		}

		$formEntryIdentifier = $formSender->getFormEntryIdentifier();

		DebugTimer::stop('FormRenderer::sendForm');

		return $formEntryIdentifier;
	}


	/**
	 * Sends the form using the form handler,
	 * and redirects to a URL with the form entry hash on success.
	 *
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	public function sendAndRedirect(): void {
		if (!$this->form) {
			throw new RuntimeException('No form was initialized.');
		}

		// Make sure the form is submitted
		if (!$this->form->isSubmitted()) {
			return;
		}

		$responseCode = $this->sendForm();

		if ($responseCode !== false) {
			$url = [
				'formEntry' => $responseCode,
				'#' => 'Form-' . Inflector::ucparts($this->form->identifier, false),
			];

			$request = Router::getRequest();

			if ($request->getParam('_name') === 'FrontendFormAntiSpamPost') {
				$url['_name'] = 'FrontendFormAntiSpamGet';
			}
			else {
				$languageShortcode = $request->getParam('lang');
				if (empty($languageShortcode)) {
					$url['_name'] = Awyiss::REALM_FRONTEND . 'Root';
				}
				else {
					$url['lang'] = trim($languageShortcode, '/');

					$slug = $request->getParam('slug');
					if (empty($slug)) {
						$url['_name'] = Awyiss::REALM_FRONTEND . 'LanguageRoot';
					}
					else {
						$url['slug'] = trim($slug, '/');
					}
				}
			}

			throw new RedirectException(Router::url($url, true), 302);
		}
	}


	/**
	 * @param array $options
	 * @return $this
	 * @throws \Exception
	 */
	public function parseTagsInForm(array $options = []): static {
		$options['mediaRenderOptions'] ??= [];

		// Determine the full width
		$options['mediaRenderOptions']['fullWidth'] ??= $options['fullWidth'] ?? $this->View->get('fullWidth', 1920);

		// Set the breakpoints to the default if not set
		$options['mediaRenderOptions']['breakpoints'] ??= Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []);

		// Determine the column width
		$options['mediaRenderOptions']['columnWidth'] ??= $options['columnWidth'] ?? $this->View->get('columnWidth', 100.0);

		// Set the selector to the form if not set
		$options['mediaRenderOptions']['selector'] ??= '#Form' . $this->form->id;

		// Determine the single column breakpoint
		$options['mediaRenderOptions']['singleColumnBreakpoint'] ??= $options['singleColumnBreakpoint'] ?? $this->View->get('singleColumnBreakpoint');

		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $options['mediaRenderOptions']['fullWidth'],
			breakpoints: $options['mediaRenderOptions']['breakpoints'],
			columnWidth: $options['mediaRenderOptions']['columnWidth'],
			selector: $options['mediaRenderOptions']['selector'],
			singleColumnBreakpoint: $options['mediaRenderOptions']['singleColumnBreakpoint']
		);

		// Parse the Awyiss image tags
		$this->parseAwyissImageTags($this->form, $mediaRenderOptions);

		// Parse the widgets
		$this->parseWidgets($this->form, $mediaRenderOptions);

		return $this;
	}
}
