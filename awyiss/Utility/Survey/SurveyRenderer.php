<?php declare(strict_types=1);


namespace Awyiss\Utility\Survey;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Entity\Survey;
use Awyiss\Model\Entity\SurveyEntry;
use Awyiss\Model\Entity\SurveySurveyQuestion;
use Awyiss\Model\Table\SurveyEntriesTable;
use Awyiss\Routing\Router;
use Awyiss\Survey\SurveyResultsInterface;
use Awyiss\Utility\Form\FormRenderer;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\Cell\Frontend\Trait\FrontendRenderingTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\FrontendView;
use BackedEnum;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Security;
use RuntimeException;


/**
 * Class SurveyRenderer
 * This class is responsible for rendering and processing surveys.
 */
class SurveyRenderer {
	use FrontendRenderingTrait;
	use LocatorAwareTrait;
	use PreviewTrait;


	/**
	 * @var \Awyiss\Model\Entity\SurveySurveyQuestion|(\Awyiss\Model\Enum\Survey\NextAction|\BackedEnum)|false|null
	 */
	protected SurveySurveyQuestion|BackedEnum|false|null $currentAction;
	/**
	 * @var \Awyiss\Utility\Form\FormRenderer|null
	 */
	protected ?FormRenderer $formRenderer = null;
	/**
	 * @var bool|null
	 */
	protected ?bool $formSent = false;
	/**
	 * The page the survey is on.
	 * A form that might be shown by this survey
	 * will use the current page in placeholder replacements.
	 *
	 * @var \Awyiss\Model\Entity\Page|null
	 */
	protected ?Page $page = null;
	/**
	 * Whether any form was processed.
	 *
	 * @var bool
	 */
	protected bool $processedForm = false;
	/**
	 * @var array
	 */
	protected array $requestData = [];
	/**
	 * @var \Awyiss\Survey\SurveyResultsInterface|null
	 */
	protected ?SurveyResultsInterface $results = null;
	/**
	 * Whether the survey entry was saved successfully.
	 * Null if the survey was not processed yet.
	 *
	 * @var bool|null
	 */
	protected ?bool $savedEntry = null;
	/**
	 * @var \Awyiss\Model\Entity\Survey|null
	 */
	protected ?Survey $survey = null;
	/**
	 * @var \Awyiss\Model\Table\SurveyEntriesTable
	 */
	protected SurveyEntriesTable $surveyEntriesTable;
	/**
	 * @var string|null
	 */
	protected ?string $surveyEntryHash = null;
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

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->surveyEntriesTable = FactoryLocator::get('Table')->get('SurveyEntries');
	}


	/**
	 * @param string|int $identifier
	 * @return \Awyiss\Model\Entity\Survey|null
	 */
	public function getSurveyByIdentifier(string|int $identifier): ?Survey {
		/** @var \Awyiss\Model\Table\SurveysTable $surveysTable */
		$surveysTable = $this->fetchTable('Surveys');

		if ($this->isPreview()) {
			$query = $surveysTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $surveysTable->find('active')->find('published');
		}

		if (is_int($identifier)) {
			$query = $query->where(['Surveys.id' => $identifier]);
		}
		else {
			$query = $query->where(['Surveys.identifier' => $identifier]);
		}

		$query->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);

		return $query->first();
	}


	/**
	 * @param string|int $identifier
	 * @param array $requestData
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @return $this
	 */
	public function initSurvey(string|int $identifier, array $requestData, ?Page $page = null): static {
		$this->page = $page;
		$this->survey = $this->getSurveyByIdentifier($identifier);

		if (!$this->survey) {
			return $this;
		}

		$this->requestData = $requestData;

		$this->survey->initialize(
			$this->View,
			$requestData['survey'][ $this->survey->identifier] ?? [],
			$this->page,
			$this->isPreview(),
		);

		return $this;
	}


	/**
	 * Processes the survey and its form.
	 *
	 * @param string|null $surveyEntryHash
	 * @param string|null $formEntryHash
	 * @return void
	 * @throws \Exception
	 */
	public function process(?string $surveyEntryHash = null, ?string $formEntryHash = null): void {
		if (!$this->survey) {
			throw new RuntimeException('No survey was initialized.');
		}

		$this->currentAction = $this->survey->getCurrentAction();

		if (
			($this->requestData['_survey_identifier'] ?? null) === $this->survey->identifier &&
			!isset($this->requestData['_form_identifier']) &&
			in_array($this->currentAction, [
				$this->survey->getNextActionEnum()::SaveAndEnd,
				$this->survey->getNextActionEnum()::SaveAndShowForm,
			])
		) {
			$this->saveEntryAndRedirect();
			return;
		}

		if ($surveyEntryHash) {
			$this->processSurveyEntryFromHash($surveyEntryHash);
		}
		elseif ($formEntryHash) {
			/**
			 * If a form entry hash is provided, but no survey entry hash,
			 * the form was handled before the survey entry.
			 *
			 * Load the entry and get the survey progress from the saved data.
			 *
			 * @see \Awyiss\Model\Enum\Survey\NextAction::ShowForm
			 * @see \Awyiss\Model\Enum\Survey\NextAction::ShowFormAndSave
			 */
			$formRenderer = $this->getFormRenderer();
			$entry = $formRenderer->loadFormEntryFromHash($formEntryHash);

			if ($entry) {
				$formData = json_decode(gzuncompress(base64_decode($entry->data)), true);

				if (isset($formData['survey'][ $this->survey->identifier ])) {
					$this->processSurveyFromData($formData['survey'][ $this->survey->identifier ]);
				}
			}
		}

		$this->processedForm = $this->processForm();

		if ($formEntryHash) {
			$this->processFormEntryFromHash($formEntryHash);
		}
	}


	/**
	 * @param array $data
	 * @return $this
	 */
	protected function processSurveyFromData(array $data): static {
		if (!$this->survey) {
			return $this;
		}

		$this->survey->setProgress($data);
		$this->currentAction = $this->survey->getCurrentAction();

		return $this;
	}


	/**
	 * @param string $entryHash
	 * @return $this
	 */
	protected function processSurveyEntryFromHash(string $entryHash): static {
		if (!$this->survey) {
			return $this;
		}

		$entry = $this->loadSurveyEntryFromHash($entryHash, $this->survey->id);

		if (!$entry) {
			return $this;
		}

		$this->surveyEntryHash = $entryHash;

		$surveyData = json_decode(gzuncompress(base64_decode($entry->data)), true);

		if (empty($surveyData)) {
			return $this;
		}

		$progress = $surveyData['progress'];
		$progress['custom'] = $surveyData['customAnswers'] ?? [];

		$this->survey->setProgress($progress);
		$this->currentAction = $this->survey->getCurrentAction();

		$this->savedEntry = true;

		return $this;
	}


	/**
	 * @param string $formEntryHash
	 * @return $this
	 * @throws \Exception
	 */
	protected function processFormEntryFromHash(string $formEntryHash): static {
		if (!$this->survey) {
			return $this;
		}

		$formRenderer = $this->getFormRenderer();

		if (!$formRenderer->isSent()) {
			$formRenderer->processFormEntryFromHash($formEntryHash);

			$this->formSent = $formRenderer->isSent();
		}

		return $this;
	}


	/**
	 * @param string $entryHash
	 * @param int $surveyId
	 * @return \Awyiss\Model\Entity\SurveyEntry|null
	 */
	protected function loadSurveyEntryFromHash(string $entryHash, int $surveyId): ?SurveyEntry {
		/** @var \Awyiss\Model\Table\SurveyEntriesTable $surveyEntriesTable */
		$surveyEntriesTable = $this->fetchTable('SurveyEntries');

		$surveyEntryHash = $entryHash;

		/** @var \Awyiss\Model\Entity\SurveyEntry|null $entry */
		$entry = $surveyEntriesTable->find('all')->where([
			'identifier' => $surveyEntryHash,
			'survey_id' => $surveyId,
		])->first();

		return $entry;
	}


	/**
	 * Returns the body of the survey.
	 * If any action displays a form,
	 * the form body will be returned instead.
	 *
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function getSurveyBody(array $options): string {
		if (!$this->survey) {
			return '';
		}

		$this->loadResultsClass();

		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->View->get('fullWidth', 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
			columnWidth: $options['columnWidth'] ?? 100.00,
			singleColumnBreakpoint: $this->View->get('singleColumnBreakpoint'),
		);

		$currentQuestion = '';
		$hasNextAction = false;
		if ($this->currentAction instanceof SurveySurveyQuestion) {
			$currentQuestion = $this->renderQuestion($mediaRenderOptions);

			// Regular questions (single choice, multi choice, free user input) always have a next action.
			// Other question types need to have a specific next action defined.
			$hasNextAction = in_array($this->currentAction->surveyQuestion->type, [
				$this->survey->getQuestionTypeEnum()::SingleChoice,
				$this->survey->getQuestionTypeEnum()::MultiChoice,
				$this->survey->getQuestionTypeEnum()::FreeText,
			]) || $this->survey->hasNextAction();
		}

		if (
			$this->savedEntry === false &&
			in_array($this->currentAction, [
				$this->survey->getNextActionEnum()::SaveAndEnd,
				$this->survey->getNextActionEnum()::SaveAndShowForm,
			])
		) {
			$this->survey->setError('_general', __d('surveys', 'save_entry_failed'));
		}

		if ($this->processedForm) {
			// If the form was processed, we don't need to render the survey body.
			return $this->renderForm($options);
		}

		$mediaRenderOptions = $mediaRenderOptions->withSelector('#Survey-' . Inflector::ucparts($this->survey->identifier));

		// Parse the module
		$this->parseAwyissImageTags($this->survey, $mediaRenderOptions, [
			'successMessage',
			'failureMessage',
		]);

		// Parse the module
		$this->parseModules($this->survey, $mediaRenderOptions, 'failureMessage');

		$successMessage = null;
		if (
			$this->savedEntry && in_array($this->currentAction, [
				$this->survey->getNextActionEnum()::SaveAndEnd,
				$this->survey->getNextActionEnum()::SaveAndShowForm,
			])
		) {
			$this->parseModules($this->survey, $mediaRenderOptions, 'successMessage');
			$successMessage = $this->results?->getFinalResult($this->survey->successMessage, $mediaRenderOptions) ?? $this->survey->successMessage;
		}

		return $this->getView()->element('survey/survey', [
			'survey' => $this->survey,
			'currentQuestion' => $currentQuestion,
			'currentAction' => $this->currentAction,
			'hasNextAction' => $hasNextAction,
			'customAnswers' => $this->survey->getCustomAnswers(),
			'progress' => $this->survey->getProgress(),
			'successMessage' => $successMessage,
			'questionTypeEnum' => $this->survey->getQuestionTypeEnum(),
			'nextActionEnum' => $this->survey->getNextActionEnum(),
			'savedEntry' => $this->savedEntry,
		]);
	}


	/**
	 * @return \Awyiss\Model\Entity\Survey|null
	 */
	public function getSurvey(): ?Survey {
		return $this->survey;
	}


	/**
	 * @return \Awyiss\Utility\Form\FormRenderer|null
	 */
	protected function getFormRenderer(): ?FormRenderer {
		if (!isset($this->formRenderer)) {
			/** @var class-string<\Awyiss\Utility\Form\FormRenderer> $className */
			$className = App::className('FormRenderer', 'Utility/Form');

			$this->formRenderer = new $className($this->getView());
		}

		return $this->formRenderer;
	}


	/**
	 * Process the form if the current action is a form.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function processForm(): bool {
		$form = null;

		if (
			(
				$this->savedEntry === true &&
				in_array($this->currentAction, [
					$this->survey->getNextActionEnum()::SaveAndShowForm,
					$this->survey->getNextActionEnum()::ShowFormAndSave,
				])
			) ||
			(
				$this->savedEntry === null &&
				in_array($this->currentAction, [
					$this->survey->getNextActionEnum()::ShowForm,
					$this->survey->getNextActionEnum()::ShowFormAndSave,
				])
			)
		) {
			$form = $this->survey->getForm();
		}

		if (!$form) {
			return false;
		}

		$this->getFormRenderer()->initForm(
			$form,
			$this->requestData,
			$this->page
		);

		if (!$form->isSubmitted()) {
			return true;
		}

		$form->validate();

		// Validate the form using the form's and form options' validator
		if ($form->isValid()) {
			$formEntryHash = $this->sendForm();

			if (!$formEntryHash) {
				return true;
			}

			if (
				in_array($this->currentAction, [
					$this->survey->getNextActionEnum()::SaveAndShowForm,
					$this->survey->getNextActionEnum()::ShowForm,
				])
			) {
				$this->redirect($this->surveyEntryHash, $formEntryHash);
			}

			if ($this->currentAction === $this->survey->getNextActionEnum()::ShowFormAndSave) {
				$surveyEntryHash = $this->saveEntry();

				if ($surveyEntryHash) {
					$this->redirect($surveyEntryHash, $formEntryHash);
				}
			}

			return true;
		}

		return true;
	}


	/**
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 */
	protected function renderForm(array $options): string {
		$formRenderer = $this->getFormRenderer();
		$formElements = $formRenderer->getForm()->getFormElements()->listNested()->filter(function (FormElement $element): bool {
			return !empty($element->identifier);
		})->indexBy('identifier')->toArray();

		return $this->getView()->element('survey/form', [
			'form' => $formRenderer->getForm(),
			'formBody' => $formRenderer->getFormBody($options),
			'formElements' => $formElements,
			'formSent' => $this->formSent,
			'survey' => $this->survey,
			'currentAction' => $this->currentAction,
			'customAnswers' => $this->survey->getCustomAnswers(),
			'progress' => $this->survey->getProgress(),
			'questionTypeEnum' => $this->survey->getQuestionTypeEnum(),
			'nextActionEnum' => $this->survey->getNextActionEnum(),
			'savedEntry' => $this->savedEntry,
		]);
	}


	/**
	 * Saves the survey entry and redirects the user to the next action.
	 *
	 * @return void
	 */
	protected function saveEntryAndRedirect(): void {
		$entryIdentifier = $this->saveEntry();

		if (!$entryIdentifier) {
			return;
		}

		$this->redirect($entryIdentifier);
	}


	/**
	 * @return string|false
	 */
	protected function saveEntry(): string|false {
		if (!$this->survey) {
			throw new RuntimeException('No survey was initialized.');
		}

		$ipHash = $this->createIpHash();
		$surveyData = $this->getSurveyData();
		$postHash = Security::hash(serialize($surveyData));

		$surveyEntry = $this->surveyEntriesTable->newDefaultEntity();

		$data = [
			'survey_id' => $this->survey->id,
			'page_id' => $this->page?->id ?? null,
			'data' => base64_encode(gzcompress(json_encode($surveyData))),
			'ip_hash' => $ipHash,
			'post_hash' => $postHash,
			'identifier' => md5($ipHash . '|' . $postHash),
		];

		$this->surveyEntriesTable->patchEntity($surveyEntry, $data);

		// Save the survey entry
		if ($this->surveyEntriesTable->save($surveyEntry, ['allowFrontendSave' => true])) {
			$this->savedEntry = true;
			return $surveyEntry->identifier;
		}

		return $this->savedEntry = false;
	}


	/**
	 * @return mixed
	 */
	protected function getSurveyData(): array {
		$progressData = $this->survey->getProgress();
		$customAnswers = $this->survey->getCustomAnswers();
		/** @var array<string, SurveySurveyQuestion> $questionsByIdentifier */
		$questionsByIdentifier = array_column($this->survey->getQuestions()->toArray(), null, 'identifier');

		$userData = [
			'progress' => $progressData,
			'customAnswers' => $customAnswers,
			'readable' => [],
		];
		foreach ($progressData as $questionIdentifier => $answerIds) {
			$question = $questionsByIdentifier[ $questionIdentifier ] ?? null;

			if (!$question) {
				// This should never happen, but just in case
				continue;
			}

			$readableAnswer = '';
			$readableAnswers = [];
			if (is_array($answerIds)) {
				if (count($answerIds) === 1) {
					$readableAnswer = reset($answerIds);
				}
				else {
					foreach ($answerIds as $answerId) {
						if ($answerId === 'custom') {
							$readableAnswers[] = $customAnswers[ $questionIdentifier ] ?? '';
							continue;
						}

						$readableAnswers[] = $question->surveySurveyAnswers[ (int)$answerId ]->surveyAnswer->label;
					}
				}
			}
			elseif (isset($customAnswers[ $questionIdentifier ])) {
				$readableAnswer = $customAnswers[ $questionIdentifier ];
			}
			elseif ($question->surveyQuestion->type === $this->survey->getQuestionTypeEnum()::FreeText) {
				$readableAnswer = $answerIds;
				$answerIds = null;
			}
			else {
				// Single-choice or free-text answers are stored as strings
				$readableAnswer = $question->surveySurveyAnswers[ $answerIds ]->surveyAnswer->label ?? '';
			}

			$userData['readable'][] = [
				'question_id' => $question->id,
				'answer_id' => $answerIds,
				'question' => $question->surveyQuestion->label,
				'answer' => $readableAnswers ?: $readableAnswer ?: null,
			];
		}

		return $userData;
	}


	/**
	 * @return $this
	 */
	protected function loadResultsClass(): static {
		/** @var class-string<\Awyiss\Survey\SurveyResultsInterface> $className */
		$className = App::className(Inflector::camelize($this->survey->identifier), 'Survey', 'SurveyResults');

		if (!$className) {
			return $this;
		}

		if (!is_subclass_of($className, SurveyResultsInterface::class)) {
			throw new RuntimeException(sprintf('The survey results class "%s" must extend "%s".', $className, SurveyResultsInterface::class));
		}

		$this->results = new $className(
			$this->survey,
			$this->View,
			$this->survey->getProgress(),
			$this->survey->getCustomAnswers()
		);

		return $this;
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
	 * @param string|null $surveyEntryIdentifier
	 * @param string|null $formEntryIdentifier
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function redirect(?string $surveyEntryIdentifier = null, ?string $formEntryIdentifier = null): void {
		$url = [
			'surveyEntry' => $surveyEntryIdentifier,
			'formEntry' => $formEntryIdentifier,
			'#' => 'Survey-' . Inflector::ucparts($this->survey->identifier, false),
		];

		$request = Router::getRequest();

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

		throw new RedirectException(Router::url($url, true), 302);
	}


	/**
	 * @return string|false
	 */
	protected function sendForm(): string|false {
		/** @var \Awyiss\Utility\Form\SurveyFormSender $formSenderClass */
		$formSenderClass = App::className('Survey', 'Utility/Form', 'FormSender');

		$formSender = new $formSenderClass($this->getFormRenderer()->getForm(), $this->page);

		$formSender->setSurveyProgress(
			$this->survey,
			$this->survey->getProgress(),
			$this->survey->getCustomAnswers(),
		);

		$this->formSent = $formSender->handle();

		if (!$this->formSent) {
			return false;
		}

		return $formSender->getFormEntryIdentifier();
	}


	/**
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @return string
	 * @throws \Exception
	 */
	protected function renderQuestion(MediaRenderOptions $mediaRenderOptions): string {
		static $templatePath;

		if (!isset($templatePath)) {
			$templatePath = rtrim(Configure::read('App.paths.templates.customer'), DS);
		}

		$fileName = 'Question' . $this->currentAction->identifier;

		$filePath = implode(DS, [
			$templatePath,
			'Frontend',
			'element',
			'survey',
			$fileName . '.twig',
		]);

		$element = $this->currentAction->surveyQuestion->type->value;
		if (file_exists($filePath)) {
			$element = $fileName;
		}

		$mediaRenderOptions = $mediaRenderOptions->withSelector('#SurveyQuestion-' . Inflector::ucparts($this->currentAction->identifier));

		// Parse the module
		$this->parseAwyissImageTags($this->currentAction->surveyQuestion, $mediaRenderOptions);

		$result = null;
		if ($this->currentAction->surveyQuestion->type === $this->survey->getQuestionTypeEnum()::InfoText && !$this->survey->hasNextAction()) {
			$result = $this->results?->getStepResult($this->currentAction->identifier, $mediaRenderOptions);
		}

		// Parse the module
		$this->parseModules($this->currentAction->surveyQuestion, $mediaRenderOptions);

		return $this->getView()->element('survey/' . $element, [
			'survey' => $this->survey,
			'question' => $this->currentAction,
			'result' => $result,
		]);
	}
}
