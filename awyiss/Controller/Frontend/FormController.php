<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\Model\Entity\FormElement;
use Awyiss\View\Cell\Frontend\Trait\FrontendRenderingTrait;
use Awyiss\View\FrontendView;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\EventManagerInterface;
use Cake\Http\Client;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;


/**
 * The Form Controller handles forms
 * that where submitted with the wrong action.
 */
class FormController extends AppController {
	use FrontendRenderingTrait;
	use LocatorAwareTrait;


	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $View;


	/**
	 * @inheritDoc
	 */
	public function __construct(
		ServerRequest $request,
		?string $name = null,
		?EventManagerInterface $eventManager = null,
		?ComponentRegistry $components = null
	) {
		parent::__construct($request, $name, $eventManager, $components);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->View = $this->createView('Frontend');
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

		if ($this->request->is('post')) {
			$ls_identifier = $this->request->getData('_form_identifier');

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
	 * @throws \Exception
	 */
	protected function handleFormEntry(string $formEntryHash): void {
		$la_options = $this->getOptions();

		/** @var class-string<\Awyiss\Utility\Form\FormRenderer> $ls_className */
		$ls_className = App::className('FormRenderer', 'Utility/Form');
		$lo_formRenderer = new $ls_className($this->createView('Frontend'));

		$lo_formEntry = $lo_formRenderer->loadFormEntryFromHash($formEntryHash);

		if (!$lo_formEntry) {
			throw new NotFoundException('Form entry not found');
		}

		/**
		 * @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted()
		 * @var \Awyiss\Model\Entity\Page $lo_page
		 */
		$lo_page = $this->getTableLocator()->get('Pages')->find('withDeleted', ['skipPageRoleCheck' => true])
		->where(['id' => $lo_formEntry->pageId])->first();

		if (!$lo_page) {
			$ls_languageShortcode = $this->request->getParam('lang');

			/**
			 * @var \Awyiss\Model\Entity\Page $lo_page
			 * @uses \Awyiss\Model\Table::findActive()
			 */
			$lo_page = $this->getTableLocator()->get('Pages')->find('active', ['skipPageRoleCheck' => true])->where([
				'parent_id IS' => null,
				'language_shortcode' => $ls_languageShortcode,
			])->first();
		}

		$lo_formRenderer->initForm($lo_formEntry->formId, $this->request->getData(), $lo_page);

		$lo_form = $lo_formRenderer->getForm();

		if (!$lo_form) {
			return;
		}

		$lo_formRenderer->processFormEntry($lo_formEntry);

		if ($lo_formRenderer->isSent()) {
			/**
			 * @var \Awyiss\Utility\Media\MediaRenderOptions $lo_mediaRenderOptions
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$lo_mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
				baseWidth: $la_options['fullWidth'],
				breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
				columnWidth: $la_options['columnWidth'],
				selector: '#Form' . $lo_form->id,
				singleColumnBreakpoint: $la_options['singleColumnBreakpoint'],
			);

			// Parse the custom image tag
			$this->parseAwyissImageTags($lo_form, $lo_mediaRenderOptions);

			// Parse the module
			$this->parseModule($lo_form, $lo_mediaRenderOptions);
		}

		// Set the view variables
		$this->set([
			'captcha' => '',
			'contents' => $lo_formRenderer->getFormBody($la_options),
			'form' => $lo_form,
			'formElements' => $lo_form->getFormElements(),
			'formElementsChecksum' => $lo_form->getFormElementsChecksum(),
			'formData' => $lo_form->getFormData(),
			'formErrors' => [],
			'sent' => $lo_formRenderer->isSent(),
			'submitted' => $lo_form->isSubmitted(),
		]);
	}


	/**
	 * @param string $identifier
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function handleFormSubmission(string $identifier): void {
		$la_options = $this->getOptions();

		$li_pageId = $this->request->getData('_page_id');
		/** @var \Awyiss\Model\Entity\Page $lo_page */
		$lo_page = $this->getTableLocator()->get('Pages')->find('all', ['skipPageRoleCheck' => true])->where(['id' => $li_pageId])->first();

		if (!$lo_page) {
			$ls_languageShortcode = $this->request->getParam('lang');

			/**
			 * @var \Awyiss\Model\Entity\Page $lo_page
			 * @uses \Awyiss\Model\Table::findActive()
			 */
			$lo_page = $this->getTableLocator()->get('Pages')->find('active', ['skipPageRoleCheck' => true])->where([
				'parent_id IS' => null,
				'language_shortcode' => $ls_languageShortcode,
			])->first();
		}

		/** @var class-string<\Awyiss\Utility\Form\FormRenderer> $ls_className */
		$ls_className = App::className('FormRenderer', 'Utility/Form');
		$lo_formRenderer = new $ls_className($this->createView('Frontend'));
		$lo_formRenderer->initForm($identifier, $this->request->getData(), $lo_page);

		$lo_form = $lo_formRenderer->getForm();
		if (!$lo_form) {
			return;
		}

		// Validate the form using the form's and form options' validator
		$lo_form->validate();

		if ($lo_form->isValid()) {
			$lx_validateCaptcha = $this->validateCaptcha($identifier, $this->request->getData());

			if ($lx_validateCaptcha === true) {
				$lo_formRenderer->sendAndRedirect();
				$la_formErrors = $lo_form->getErrors();
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
			$la_formErrors = $lo_form->getErrors();
		}

		$la_formElements = $lo_formRenderer->getForm()->getFormElements()->listNested()->filter(function (FormElement $element): bool {
			return !empty($element->identifier);
		})->indexBy('identifier')->toArray();

		// Set the view variables
		$this->set([
			'captcha' => $ls_captcha ?? '',
			'contents' => $lo_formRenderer->getFormBody($la_options),
			'form' => $lo_formRenderer->getForm(),
			'formElements' => $la_formElements,
			'formElementsChecksum' => $lo_formRenderer->getForm()->getFormElementsChecksum(),
			'formData' => $lo_form->getFormData(),
			'formErrors' => $la_formErrors ?? [],
			'page' => $lo_page,
			'sent' => $lo_formRenderer->isSent(),
			'submitted' => $lo_form->isSubmitted(),
		]);
	}


	/**
	 * @param string $identifier
	 * @param string $languageShortcode
	 * @return string
	 */
	protected function buildCaptcha(string $identifier, string $languageShortcode): string {
		$la_words = $this->getRandomWikipediaWords($languageShortcode);

		if (!$la_words || count($la_words) < 6) {
			// Generate 6 random words
			$la_words = $this->generateRandomWords(6);
		}

		$lo_session = $this->getRequest()->getSession();
		$lo_session->write('awyiss_captcha.' . $identifier . '.words', $la_words);

		$ls_ipAddress = $this->getRequest()->clientIp();
		$li_crossSum = array_sum(array_map('intval', str_split($ls_ipAddress)));
		$li_randomWord = ($li_crossSum + time() * mt_rand(0, 5)) % 6;

		$lo_session->write('awyiss_captcha.' . $identifier . '.word', $li_randomWord);

		$ls_fieldName = md5(json_encode($la_words));
		$lo_session->write('awyiss_captcha.' . $identifier . '.fieldName', $ls_fieldName);

		return $this->View->element('form/form_captcha', [
			'words' => $la_words,
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
			'columnWidth' => 60.00,
			'viewVars' => $this->View->getTwig()->getGlobals(),
		];

		$la_options['fullWidth'] = $this->findFullWidth($la_options);
		$la_options['singleColumnBreakpoint'] = $this->findSingleColumnBreakpoint($la_options);

		return $la_options;
	}


	/**
	 * @param int $amount
	 * @return array
	 */
	protected function generateRandomWords(int $amount): array {
		$la_words = [];

		for ($li_i = 0; $li_i < $amount; $li_i++) {
			$la_words[] = $this->readableRandomString(rand(6, 10));
		}

		return $la_words;
	}


	/**
	 * @param int $length
	 * @return string
	 */
	public function readableRandomString(int $length = 6): string {
		static $la_vowels = ['a', 'e', 'i', 'o', 'u'];
		static $la_consonants = [
			'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm',
			'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z',
		];

		$ls_string = '';

		$la_max = $length / 2;
		for ($li_i = 1; $li_i <= $la_max; $li_i++) {
			$ls_string .= $la_consonants[ rand(0, 19) ];
			$ls_string .= $la_vowels[ rand(0, 4) ];
		}

		return $ls_string;
	}


	/**
	 * @param string $html
	 * @param int $limit
	 * @param int $minLength
	 * @return array
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function extractWordsFromText(string $html, int $limit, int $minLength = 6): array {
		// Remove all style tags
		$ls_html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
		$ls_html = html_entity_decode(strip_tags($ls_html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		$la_words = preg_split('/\s+/', $ls_html);

		// Filter out all words that are too short or have non-alphabetic characters
		$la_words = array_filter($la_words, function (string $word) use ($minLength): bool {
			return strlen($word) >= $minLength && ctype_alpha($word);
		});

		// Remove duplicates
		$la_words = array_unique($la_words);

		// Shuffle the words to get a random selection
		shuffle($la_words);

		// Limit the number of words to the specified limit
		return array_slice($la_words, 0, $limit);
	}


	/**
	 * @param string $languageShortcode
	 * @return array
	 */
	protected function getRandomWikipediaWords(string $languageShortcode): array {
		$lo_client = new Client([
			'timeout' => 30,
			'http_errors' => false,
		]);

		$lo_randomResponse = $lo_client->get('https://' . $languageShortcode . '.wikipedia.org/w/api.php', [
			'action' => 'query',
			'list' => 'random',
			'rnnamespace' => 0,
			'format' => 'json',
		]);
		$la_randomData = $lo_randomResponse->getJson();

		$ls_title = $la_randomData['query']['random'][0]['title'] ?? null;

		if (!$ls_title) {
			return [];
		}

		// Step 2: Get the article content
		$lo_articleResponse = $lo_client->get('https://' . $languageShortcode . '.wikipedia.org/w/api.php', [
			'action' => 'parse',
			'page' => $ls_title,
			'format' => 'json',
			'prop' => 'text',
		]);
		$la_parseData = $lo_articleResponse->getJson();

		return $this->extractWordsFromText($la_parseData['parse']['text']['*'] ?? '', 6);
	}
}
