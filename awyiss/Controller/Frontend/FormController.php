<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Awyiss\Utility\Form\FormRenderer;
use Awyiss\View\Cell\Frontend\FormCell;
use Awyiss\View\FrontendView;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;


/**
 * The Form Controller handles forms
 * that where submitted with the wrong action.
 */
class FormController extends AppController {
	use LocatorAwareTrait;


	/**
	 * @var \Awyiss\View\Cell\Frontend\FormCell
	 */
	protected FormCell $formCell;
	/**
	 * @var bool|null $formSent
	 */
	protected ?bool $formSent = null;
	/**
	 * @var bool $formSubmitted
	 */
	protected bool $formSubmitted = false;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $view;


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function initialize(): void {
		parent::initialize();

		$this->formCell = new FormCell(
			$this->getRequest(),
			$this->getResponse(),
			$this->getEventManager(),
			['action' => 'display'],
		);
	}


	/**
	 * This method is working with the request data.
	 * It fetches the form with the identifier that should be
	 * present in the request data, retrieves and re-renders the form
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	public function antiSpam(): void {
		$this->viewBuilder()->setClassName('Frontend');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->view = $this->viewBuilder()->build();

		if ($this->request->is('post')) {
			$ls_identifier = $this->request->getData('form_identifier');

			if (!$ls_identifier) {
				throw new InvalidArgumentException('No form identifier provided.');
			}

			$this->handleFormSubmission($ls_identifier);
		}
		elseif ($this->request->getParam('formEntry')) {
			$this->handleFormEntry($this->request->getParam('formEntry'));
		}
	}


	/**
	 * @param string $formEntryHash
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function handleFormEntry(string $formEntryHash): void {
		$la_options = $this->getOptions();

		/** @noinspection PhpParamsInspection */
		$lo_formRenderer = new FormRenderer($this->createView('Frontend'));
		$lo_formEntry = $lo_formRenderer->loadFormDataForEntryHash($formEntryHash);

		if (!$lo_formEntry) {
			throw new NotFoundException('Form entry not found');
		}

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		$lo_page = $this->getTableLocator()->get('Pages')->find('withDeleted', ['skipPageRoleCheck' => true])
		->where(['id' => $lo_formEntry->pageId])->first();

		if (!$lo_page) {
			$ls_languageShortcode = $this->request->getParam('lang');

			/** @var \Awyiss\Model\Entity\Page $lo_page */
			$lo_page = $this->getTableLocator()->get('Pages')->find('active', ['skipPageRoleCheck' => true])->where([
				'parent_id IS' => null,
				'language_shortcode' => $ls_languageShortcode,
			])->first();
		}

		$lo_formRenderer->initForm($lo_formEntry->formId, $this->request->getData(), $lo_page)
		->processFormEntry($lo_formEntry);

		// Set the view variables
		$this->set([
			'captcha' => '',
			'contents' => $lo_formRenderer->getFormBody($la_options),
			'form' => $lo_formRenderer->getForm(),
			'formElements' => $lo_formRenderer->getFormElements(),
			'formElementsChecksum' => $lo_formRenderer->getFormElementsChecksum(),
			'formData' => $lo_formRenderer->getFormData(),
			'formErrors' => [],
			'sent' => $lo_formRenderer->isFormSent(),
			'submitted' => $lo_formRenderer->isFormSubmitted(),
		]);
	}


	/**
	 * @param string $identifier
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function handleFormSubmission(string $identifier): void {
		$la_options = $this->getOptions();

		$li_pageId = $this->request->getData('page_id');
		/** @var \Awyiss\Model\Entity\Page $lo_page */
		$lo_page = $this->getTableLocator()->get('Pages')->find('all', ['skipPageRoleCheck' => true])->where(['id' => $li_pageId])->first();

		if (!$lo_page) {
			$ls_languageShortcode = $this->request->getParam('lang');

			/** @var \Awyiss\Model\Entity\Page $lo_page */
			$lo_page = $this->getTableLocator()->get('Pages')->find('active', ['skipPageRoleCheck' => true])->where([
				'parent_id IS' => null,
				'language_shortcode' => $ls_languageShortcode,
			])->first();
		}

		/** @noinspection PhpParamsInspection */
		$lo_formRenderer = new FormRenderer($this->createView('Frontend'));
		$lo_formRenderer->initForm($identifier, $this->request->getData(), $lo_page);

		if ($lo_formRenderer->isValid()) {
			$lx_validateCaptcha = $this->validateCaptcha($identifier, $this->request->getData());

			if ($lx_validateCaptcha === true) {
				$lo_formRenderer->sendAndRedirect();
				$la_formErrors = $lo_formRenderer->getFormErrors();
			}
			else {
				$ls_captcha = $this->buildCaptcha($identifier, $lo_page->languageShortcode);

				if (is_string($lx_validateCaptcha)) {
					$la_formErrors = [
						'_general' => [
							'captcha' => $lx_validateCaptcha,
						],
					];
				}
			}
		}
		else {
			$la_formErrors = $lo_formRenderer->getFormErrors();
		}

		// Set the view variables
		$this->set([
			'captcha' => $ls_captcha ?? '',
			'contents' => $lo_formRenderer->getFormBody($la_options),
			'form' => $lo_formRenderer->getForm(),
			'formElements' => $lo_formRenderer->getFormElements(),
			'formElementsChecksum' => $lo_formRenderer->getFormElementsChecksum(),
			'formData' => $lo_formRenderer->getFormData(),
			'formErrors' => $la_formErrors ?? [],
			'page' => $lo_page,
			'sent' => $lo_formRenderer->isFormSent(),
			'submitted' => $lo_formRenderer->isFormSubmitted(),
		]);
	}


	/**
	 * @param string $identifier
	 * @param string $languageShortcode
	 * @param array $requestData
	 * @return string
	 */
	protected function buildCaptcha(string $identifier, string $languageShortcode): string {
		$la_params = ['number' => 6];

		if (in_array($languageShortcode, ['de', 'it', 'fr', 'es'])) {
			$la_params['lang'] = $languageShortcode;
		}

		// Fetch random words from https://random-word-api.herokuapp.com/word
		$lo_curlHandle = curl_init();
		curl_setopt($lo_curlHandle, CURLOPT_HTTPHEADER, ['Accept: application/json']);
		curl_setopt($lo_curlHandle, CURLOPT_URL, 'https://random-word-api.herokuapp.com/word?' . http_build_query($la_params));
		curl_setopt($lo_curlHandle, CURLOPT_RETURNTRANSFER, 1);
		$lx_result = curl_exec($lo_curlHandle);
		curl_close($lo_curlHandle);

		if (!$lx_result) {
			throw new NotFoundException('Could not fetch captcha words');
		}

		$lo_session = $this->getRequest()->getSession();
		$lo_session->write('awyiss_captcha.' . $identifier . '.words', json_decode($lx_result, true));

		$ls_ipAddress = $this->getRequest()->clientIp();
		$li_crossSum = array_sum(array_map('intval', str_split($ls_ipAddress)));
		$li_randomWord = ($li_crossSum + time() * mt_rand(0, 5)) % 6;

		$lo_session->write('awyiss_captcha.' . $identifier . '.word', $li_randomWord);

		$ls_fieldName = md5(json_encode($lx_result));
		$lo_session->write('awyiss_captcha.' . $identifier . '.fieldName', $ls_fieldName);

		return $this->view->element('form/form_captcha', [
			'words' => json_decode($lx_result, true),
			'word' => $li_randomWord,
			'fieldName' => $ls_fieldName,
		]);
	}


	/**
	 * @param string $identifier
	 * @param array $requestData
	 * @return string|true|null
	 */
	protected function validateCaptcha(string $identifier, array $requestData): string|true|null {
		$lo_session = $this->getRequest()->getSession();
		$la_words = $lo_session->read('awyiss_captcha.' . $identifier . '.words');
		$li_word = $lo_session->read('awyiss_captcha.' . $identifier . '.word');
		$ls_fieldName = $lo_session->read('awyiss_captcha.' . $identifier . '.fieldName');

		if (!isset($requestData[ $ls_fieldName ])) {
			return null;
		}

		$ls_input = $requestData[ $ls_fieldName ];

		if (empty($ls_input)) {
			return false;
		}

		if ($ls_input !== $la_words[ $li_word ]) {
			return __d('form', 'error_valid_captcha');
		}

		return true;
	}


	/**
	 * Attempt to find the width of the page by
	 * - checking the view vars for a page width
	 *
	 * @param array $options
	 * @return float|null
	 */
	protected function findFullWidth(array $options): ?float {
		if (isset($options['viewVars']['fullWidth'])) {
			return (float)$options['viewVars']['fullWidth'];
		}

		if (isset($options['viewVars']['designSettings']['pageWidth'])) {
			return (float)$options['viewVars']['designSettings']['pageWidth'];
		}

		return null;
	}


	/**
	 * Attempt to find the single column breakpoint by
	 * - checking the view vars for a single column breakpoint setting
	 *
	 * @param array $options
	 * @return float|null
	 */
	protected function findSingleColumnBreakpoint(array $options): ?float {
		if (isset($options['viewVars']['singleColumnBreakpoint'])) {
			return (float)$options['viewVars']['singleColumnBreakpoint'];
		}

		if (isset($options['viewVars']['designSettings']['singleColumnBreakpoint'])) {
			return (float)$options['viewVars']['designSettings']['singleColumnBreakpoint'];
		}

		return null;
	}


	/**
	 * @return array
	 */
	protected function getOptions(): array {
		$la_options = [
			'columnWidth' => 100.00,
			'viewVars' => $this->view->getTwig()->getGlobals(),
		];

		$la_options['fullWidth'] = $this->findFullWidth($la_options);
		$la_options['singleColumnBreakpoint'] = $this->findSingleColumnBreakpoint($la_options);

		return $la_options;
	}
}
