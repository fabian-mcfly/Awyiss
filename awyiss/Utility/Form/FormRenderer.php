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
use Awyiss\Utility\Inflector;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\FrontendView;
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
		/** @var \Awyiss\Model\Table\FormsTable $lo_formsTable */
		$lo_formsTable = $this->fetchTable('Forms');

		if ($this->isPreview()) {
			$lo_query = $lo_formsTable->find('all');
		}
		else {
			$lo_query = $lo_formsTable->find('active')->find('published');
		}

		$lo_query = $lo_query->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);

		if (is_int($identifier)) {
			$lo_query = $lo_query->where(['Forms.id' => $identifier]);
		}
		else {
			$lo_query = $lo_query->where(['Forms.identifier' => $identifier]);
		}

		return $lo_query->first();
	}


	/**
	 * @param string|int Form
	 * @param array $requestData
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @return $this
	 */
	public function initForm(Form|string|int $form, array $requestData, ?Page $page = null): static {
		$this->form = $form instanceof Form ? $form : $this->getFormByIdentifier($form);
		$this->page = $page;

		if (!$this->form) {
			return $this;
		}

		$this->form->initialize(
			$this->View,
			$this->page,
			$this->isPreview(),
		);

		if ($this->form->identifier === ($requestData['_form_identifier'] ?? null)) {
			$this->form->submitted();

			$this->form->setFormData($requestData);
		}

		$this->form->getFormOptions()->modifyForm($this->form, $this->page);

		if ($this->form->isSubmitted()) {
			$this->form->getFormOptions()->setConditionalRecipient($this->form, $this->page);
		}

		return $this;
	}


	/**
	 * Returns whether the form was sent
	 * or null if the form was not processed yet (no request data).
	 *
	 * @return bool|null
	 */
	public function process(): ?bool {
		if (!$this->form) {
			throw new RuntimeException('No form was initialized.');
		}

		// Validate the form
		if (!$this->form->isSubmitted()) {
			return null;
		}

		// Validate the form using the form's and form options' validator
		$this->form->validate();

		if ($this->form->isValid()) {
			$this->sendAndRedirect();
		}

		return $this->formSent;
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

		$this->View->set([
			'formData' => $this->form->getFormData(),
			'formErrors' => $this->form->getErrors(),
			'sent' => $this->formSent,
			'submitted' => $this->form->isSubmitted(),
			'fullWidth' => $options['fullWidth'],
			'singleColumnBreakpoint' => $options['singleColumnBreakpoint'],
			...$options['viewVars'],
		]);

		$lo_formElements = $this->form->getFormElements();

		if (!$lo_formElements) {
			return '';
		}

		$this->prepareEntities($lo_formElements, (float)$options['columnWidth']);

		$la_formElements = $lo_formElements->listNested()->toList();
		foreach ($la_formElements as $lo_formElement) {
			if (!$lo_formElement->identifier) {
				continue;
			}

			if ($this->form->getError($lo_formElement->identifier)) {
				$lo_formElement->cssClass .= ' FormElement-IsInvalid';
			}
		}

		// If there's at least one input of inputtype `file`, set the form enctype to multipart/form-data
		$this->form->set(
			'enctype',
			array_reduce($la_formElements, function ($carry, FormElement $element) {
				return $carry || $element->type === 'file';
			}, false) ? 'multipart/form-data' : null
		);

		return $this->buildContents($lo_formElements->toArray());
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
		/** @var \Awyiss\Model\Table\FormEntriesTable $lo_formEntriesTable */
		$lo_formEntriesTable = $this->fetchTable('FormEntries');

		$ls_formEntryHash = $entryHash;

		/** @var \Awyiss\Model\Entity\FormEntry|null $lo_entry */
		$lo_entry = $lo_formEntriesTable->find('all')->where(function (QueryExpression $exp, SelectQuery $query) use ($ls_formEntryHash) {
			// The concat of the id and the post_hash must equal the form entry identifier
			/** @noinspection PhpUndefinedMethodInspection */
			return $exp->eq($query->func()->md5([
				$query->func()->concat([
					'FormEntries.id' => 'identifier',
					' | ',
					'FormEntries.post_hash' => 'identifier',
				]),
			]), $ls_formEntryHash);
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

		$this->form?->setFormData($la_formData);

		/** @var \Awyiss\Utility\Form\FormSender $ls_formSenderClass */
		$ls_formSenderClass = App::className('FormSender', 'Utility/Form');

		$lo_formSender = new $ls_formSenderClass($this->form, $this->page);
		$lo_formSender->replacePlaceholdersInForm();

		$this->formSent = true;

		return $this;
	}


	/**
	 * @param string $entryHash
	 * @return $this
	 */
	public function processFormEntryFromHash(string $entryHash): static {
		$lo_entry = $this->loadFormEntryFromHash($entryHash);

		if (!$lo_entry || $lo_entry->formId !== $this->form?->id) {
			return $this;
		}

		return $this->processFormEntry($lo_entry);
	}


	/**
	 * @inheritDoc
	 * @param \Awyiss\Model\Entity\FormElement $entity
	 * @param string $children
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function renderElement(Entity $entity, string $children): string {
		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $lo_mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->View->get('fullWidth', 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
			columnWidth: $entity->realColumnWidth,
			selector: '#FormElement' . $entity->id,
			singleColumnBreakpoint: $this->View->get('singleColumnBreakpoint'),
		);

		if ($entity->type === 'free_text') {
			// Parse the custom image tags
			$this->parseAwyissImageTags($entity, $lo_mediaRenderOptions);

			// Parse the module
			$this->parseModule($entity, $lo_mediaRenderOptions);
		}

		$ls_fullWidthMissingWarning = '';
		if (!$this->View->get('fullWidth')) {
			$ls_fullWidthMissingWarning = '<!-- Full width is missing. Please add the `fullWidth`-option to the form cell. -->';
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $ls_fullWidthMissingWarning . $this->View->element('form/form_elements', [
			'form' => $this->form,
			'formData' => $this->form->getFormData(),
			'formElement' => $entity,
			'formErrors' => $this->form->getErrors(),
			'children' => $children,
			'mediaRenderOptions' => $lo_mediaRenderOptions,
		]);
	}


	/**
	 * @return string|false
	 */
	public function sendForm(): string|false {
		/** @var \Awyiss\Utility\Form\FormSender $ls_formSenderClass */
		$ls_formSenderClass = App::className('FormSender', 'Utility/Form');

		$lo_formSender = new $ls_formSenderClass($this->form, $this->page);
		$this->formSent = $lo_formSender->handle();

		if (!$this->formSent) {
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

		$ls_responseCode = $this->sendForm();

		if ($ls_responseCode !== false) {
			$la_url = [
				'formEntry' => $ls_responseCode,
				'#' => 'Form-' . Inflector::ucparts($this->form->identifier, false),
			];

			$lo_request = Router::getRequest();

			if ($lo_request->getParam('_name') === 'FrontendFormAntiSpamPost') {
				$la_url['_name'] = 'FrontendFormAntiSpamGet';
			}
			else {
				$ls_languageShortcode = $lo_request->getParam('lang');
				if (empty($ls_languageShortcode)) {
					$la_url['_name'] = Awyiss::REALM_FRONTEND . 'Root';
				}
				else {
					$la_url['lang'] = trim($ls_languageShortcode, '/');

					$ls_slug = $lo_request->getParam('slug');
					if (empty($ls_slug)) {
						$la_url['_name'] = Awyiss::REALM_FRONTEND . 'LanguageRoot';
					}
					else {
						$la_url['slug'] = trim($ls_slug, '/');
					}
				}
			}

			throw new RedirectException(Router::url($la_url, true), 302);
		}
	}
}
