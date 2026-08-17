<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\View\Cell\Frontend\Trait\FrontendRenderingTrait;
use Awyiss\View\FrontendView;
use Cake\Controller\ComponentRegistry;
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
	 * @noinspection PhpUnused
	 */
	public function antiSpam(): void {
		$this->viewBuilder()->setClassName('Frontend');

		if ($this->request->is('post')) {
			$identifier = $this->request->getData('_formIdentifier');

			if (!$identifier) {
				throw new InvalidArgumentException('No form identifier provided.');
			}

			$this->handleFormSubmission($identifier);
		}
		elseif ($this->request->getParam('formEntry')) {
			$this->handleFormEntry($this->request->getParam('formEntry'));
		}

		$this->viewBuilder()->setTemplate('anti_spam');
	}


	/**
	 * @param string $formEntryHash
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function handleFormEntry(string $formEntryHash): void {
		$options = $this->getOptions();

		/** @var class-string<\Awyiss\Utility\Form\FormRenderer> $className */
		$className = App::className('FormRenderer', 'Utility/Form');
		$formRenderer = new $className($this->createView('Frontend'));

		$formEntry = $formRenderer->loadFormEntryFromHash($formEntryHash);

		if (!$formEntry) {
			throw new NotFoundException('Form entry not found');
		}

		/**
		 * @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted()
		 * @var \Awyiss\Model\Entity\Page $page
		 */
		$page = $this
			->fetchTable('Pages')
			->find('withDeleted', ['skipPageRoleCheck' => true])
			->where(['id' => $formEntry->pageId])
			->first()
		;

		if (!$page) {
			$languageShortcode = $this->request->getParam('lang');

			/**
			 * @var \Awyiss\Model\Entity\Page $page
			 * @uses \Awyiss\Model\Table::findActive()
			 */
			$page = $this
				->getTableLocator()
				->get('Pages')
				->find('active', ['skipPageRoleCheck' => true])
				->where([
					'parentId IS' => null,
					'languageShortcode' => $languageShortcode,
				])
				->first()
			;
		}

		$formRenderer->initForm($formEntry->formId, $this->request->getData(), $page);

		$form = $formRenderer->getForm();

		if (!$form) {
			return;
		}

		$formRenderer->processFormEntry($formEntry, $options);

		// Set the view variables
		$this->set([
			'captcha' => '',
			'contents' => $formRenderer->getFormBody($options),
			'form' => $form,
			'formElements' => $form->getFormElements(),
			'formElementsChecksum' => $form->getFormElementsChecksum(),
			'formData' => $form->getFormData(),
			'formErrors' => [],
			'sent' => $formRenderer->isSent(),
			'submitted' => $form->isSubmitted(),
		]);
	}


	/**
	 * @param string $identifier
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function handleFormSubmission(string $identifier): void {
		$options = $this->getOptions();

		$pageId = $this->request->getData('_pageId');
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $this
			->getTableLocator()
			->get('Pages')
			->find('all', ['skipPageRoleCheck' => true])
			->where(['id' => $pageId])
			->first()
		;

		if (!$page) {
			$languageShortcode = $this->request->getParam('lang');

			/**
			 * @var \Awyiss\Model\Entity\Page $page
			 * @uses \Awyiss\Model\Table::findActive()
			 */
			$page = $this
				->getTableLocator()
				->get('Pages')
				->find('active', ['skipPageRoleCheck' => true])
				->where([
					'parentId IS' => null,
					'languageShortcode' => $languageShortcode,
				])
				->first()
			;
		}

		/** @var class-string<\Awyiss\Utility\Form\FormRenderer> $className */
		$className = App::className('FormRenderer', 'Utility/Form');
		$formRenderer = new $className($this->createView('Frontend'));
		$formRenderer->initForm($identifier, $this->request->getData(), $page);

		$form = $formRenderer->getForm();
		if (!$form) {
			return;
		}

		// Validate the form using the form's and form options' validator
		$form->validate();

		if ($form->isValid()) {
			$validateCaptcha = $this->validateCaptcha($identifier, $this->request->getData());

			if ($validateCaptcha === true) {
				$formRenderer->sendAndRedirect();
				$formErrors = $form->getErrors();
			}
			else {
				$captcha = $this->buildCaptcha($identifier, $page->languageShortcode);

				if (is_string($validateCaptcha)) {
					$formErrors = [
						'_general' => [
							'captcha' => $validateCaptcha,
						],
					];
				}
			}
		}
		else {
			$formErrors = $form->getErrors();
		}

		// Set the view variables
		$this->set([
			'captcha' => $captcha ?? '',
			'contents' => $formRenderer->getFormBody($options),
			'form' => $formRenderer->getForm(),
			'formElements' => $form->getLinearFormElements(),
			'formElementsChecksum' => $formRenderer->getForm()->getFormElementsChecksum(),
			'formData' => $form->getFormData(),
			'formErrors' => $formErrors ?? [],
			'page' => $page,
			'sent' => $formRenderer->isSent(),
			'submitted' => $form->isSubmitted(),
		]);
	}


	/**
	 * @param string $identifier
	 * @param string $languageShortcode
	 * @return string
	 */
	protected function buildCaptcha(string $identifier, string $languageShortcode): string {
		$words = $this->getRandomWikipediaWords($languageShortcode);

		if (!$words || count($words) < 6) {
			// Generate 6 random words
			$words = $this->generateRandomWords(6);
		}

		$session = $this->getRequest()->getSession();
		$session->write('awyiss_captcha.' . $identifier . '.words', $words);

		$ipAddress = $this->getRequest()->clientIp();
		$crossSum = array_sum(array_map('intval', str_split($ipAddress)));
		$randomWord = ($crossSum + time() * mt_rand(0, 5)) % 6;

		$session->write('awyiss_captcha.' . $identifier . '.word', $randomWord);

		$fieldName = md5(json_encode($words));
		$session->write('awyiss_captcha.' . $identifier . '.fieldName', $fieldName);

		return $this->View->element('form/form_captcha', [
			'words' => $words,
			'word' => $randomWord,
			'fieldName' => $fieldName,
		]);
	}


	/**
	 * @param string $identifier
	 * @param array $requestData
	 * @return string|true|null
	 */
	protected function validateCaptcha(string $identifier, array $requestData): string|true|null {
		$session = $this->getRequest()->getSession();
		$words = $session->read('awyiss_captcha.' . $identifier . '.words');
		$word = $session->read('awyiss_captcha.' . $identifier . '.word');
		$fieldName = $session->read('awyiss_captcha.' . $identifier . '.fieldName');

		if (!isset($requestData[ $fieldName ])) {
			return null;
		}

		$input = $requestData[ $fieldName ];

		if (empty($input)) {
			return false;
		}

		if ($input !== $words[ $word ]) {
			return __d('Form', 'error_valid_captcha');
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
		$options = [
			'columnWidth' => 60.00,
			'viewVars' => $this->View->getTwig()->getGlobals(),
		];

		$options['fullWidth'] = $this->findFullWidth($options);
		$options['singleColumnBreakpoint'] = $this->findSingleColumnBreakpoint($options);

		return $options;
	}


	/**
	 * @param int $amount
	 * @return array
	 */
	protected function generateRandomWords(int $amount): array {
		$words = [];

		for ($i = 0; $i < $amount; $i++) {
			$words[] = $this->readableRandomString(rand(6, 10));
		}

		return $words;
	}


	/**
	 * @param int $length
	 * @return string
	 */
	public function readableRandomString(int $length = 6): string {
		static $vowels = 'aeiou';
		static $consonants = 'bcdfghjklmnprstvwxyz';

		$string = '';

		$max = $length / 2;
		for ($i = 1; $i <= $max; $i++) {
			$string .= $consonants[ rand(0, 19) ];
			$string .= $vowels[ rand(0, 4) ];
		}

		return $string;
	}


	/**
	 * @param string $html
	 * @param int $limit
	 * @param int $minLength
	 * @return array
	 */
	protected function extractWordsFromText(string $html, int $limit, int $minLength = 6): array {
		// Remove all style tags
		$html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
		$html = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		$words = preg_split('/\s+/', $html);

		// Filter out all words that are too short or have non-alphabetic characters
		$words = array_filter($words, function (string $word) use ($minLength): bool {
			return strlen($word) >= $minLength && ctype_alpha($word);
		});

		// Remove duplicates
		$words = array_unique($words);

		// Shuffle the words to get a random selection
		shuffle($words);

		// Limit the number of words to the specified limit
		return array_slice($words, 0, $limit);
	}


	/**
	 * @param string $languageShortcode
	 * @return array
	 */
	protected function getRandomWikipediaWords(string $languageShortcode): array {
		$client = new Client([
			'timeout' => 30,
			'http_errors' => false,
		]);

		$randomResponse = $client->get('https://' . $languageShortcode . '.wikipedia.org/w/api.php', [
			'action' => 'query',
			'list' => 'random',
			'rnnamespace' => 0,
			'format' => 'json',
		]);
		$randomData = $randomResponse->getJson();

		$title = $randomData['query']['random'][0]['title'] ?? null;

		if (!$title) {
			return [];
		}

		// Step 2: Get the article content
		$articleResponse = $client->get('https://' . $languageShortcode . '.wikipedia.org/w/api.php', [
			'action' => 'parse',
			'page' => $title,
			'format' => 'json',
			'prop' => 'text',
		]);
		$parseData = $articleResponse->getJson();

		return $this->extractWordsFromText($parseData['parse']['text']['*'] ?? '', 6);
	}
}
