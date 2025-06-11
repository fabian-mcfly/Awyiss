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
use Awyiss\Utility\Form\FormRenderer;
use Awyiss\Utility\Inflector;
use Awyiss\View\Cell\Frontend\Trait\FrontendRenderingTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\FrontendView;
use BackedEnum;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
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
		/** @var \Awyiss\Model\Table\SurveysTable $lo_surveysTable */
		$lo_surveysTable = $this->fetchTable('Surveys');

		if ($this->isPreview()) {
			$lo_query = $lo_surveysTable->find('all');
		}
		else {
			$lo_query = $lo_surveysTable->find('active')->find('published');
		}

		if (is_int($identifier)) {
			$lo_query = $lo_query->where(['Surveys.id' => $identifier]);
		}
		else {
			$lo_query = $lo_query->where(['Surveys.identifier' => $identifier]);
		}

		$lo_query->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);

		return $lo_query->first();
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
	 * Returns whether the form was sent
	 * or null if the form was not processed yet (no request data).
	 *
	 * @param string|null $surveyEntryHash
	 * @param string|null $formEntryHash
	 * @return void
	 */
	public function process(?string $surveyEntryHash = null, ?string $formEntryHash = null): void {
		if (!$this->survey) {
			throw new RuntimeException('No survey was initialized.');
		}

		$this->currentAction = $this->survey->getCurrentAction();

		if (
			isset($this->requestData['_survey_identifier']) &&
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
			$lo_formRenderer = $this->getFormRenderer();
			$lo_entry = $lo_formRenderer->loadFormEntryFromHash($formEntryHash);
			$la_formData = json_decode(gzuncompress(base64_decode($lo_entry->data)), true);

			if (isset($la_formData['survey'][ $this->survey->identifier ])) {
				$this->processSurveyFromData($la_formData['survey'][ $this->survey->identifier ]);
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
	public function processSurveyFromData(array $data): static {
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
	public function processSurveyEntryFromHash(string $entryHash): static {
		if (!$this->survey) {
			return $this;
		}

		$lo_entry = $this->loadSurveyEntryFromHash($entryHash, $this->survey->id);

		if (!$lo_entry) {
			return $this;
		}

		$this->surveyEntryHash = $entryHash;

		$la_surveyData = json_decode(gzuncompress(base64_decode($lo_entry->data)), true);

		if (empty($la_surveyData)) {
			return $this;
		}

		$la_progress = $la_surveyData['progress'];
		$la_progress['custom'] = $la_surveyData['customAnswers'] ?? [];

		$this->survey->setProgress($la_progress);
		$this->currentAction = $this->survey->getCurrentAction();

		$this->savedEntry = true;

		return $this;
	}


	/**
	 * @param string $formEntryHash
	 * @return $this
	 */
	public function processFormEntryFromHash(string $formEntryHash): static {
		if (!$this->survey) {
			return $this;
		}

		$lo_formRenderer = $this->getFormRenderer();

		if (!$lo_formRenderer->isSent()) {
			$lo_formRenderer->processFormEntryFromHash($formEntryHash);

			$this->formSent = $lo_formRenderer->isSent();
		}

		return $this;
	}


	/**
	 * @param string $entryHash
	 * @param int $surveyId
	 * @return \Awyiss\Model\Entity\SurveyEntry|null
	 */
	public function loadSurveyEntryFromHash(string $entryHash, int $surveyId): ?SurveyEntry {
		/** @var \Awyiss\Model\Table\SurveyEntriesTable $lo_surveyEntriesTable */
		$lo_surveyEntriesTable = $this->fetchTable('SurveyEntries');

		$ls_surveyEntryHash = $entryHash;

		/** @var \Awyiss\Model\Entity\SurveyEntry|null $lo_entry */
		$lo_entry = $lo_surveyEntriesTable->find('all')->where(function (QueryExpression $exp, SelectQuery $query) use ($ls_surveyEntryHash) {
			// The concat of the id and the post_hash must equal the survey entry identifier
			/** @noinspection PhpUndefinedMethodInspection */
			return $exp->eq($query->func()->md5([
				$query->func()->concat([
					'SurveyEntries.id' => 'identifier',
					' | ',
					'SurveyEntries.post_hash' => 'identifier',
				]),
			]), $ls_surveyEntryHash);
		})->where(['survey_id' => $surveyId])->first();

		return $lo_entry;
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
		static $ls_templatePath;

		if (!$this->survey) {
			return '';
		}

		/**
		 * @var \Awyiss\Utility\Media\MediaRenderOptions $lo_mediaRenderOptions
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
			baseWidth: $this->View->get('fullWidth', 1920),
			breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
			columnWidth: $options['columnWidth'] ?? 100.00,
			singleColumnBreakpoint: $this->View->get('singleColumnBreakpoint'),
		);

		$ls_currentQuestion = '';
		$lb_hasNextAction = false;
		if ($this->currentAction instanceof SurveySurveyQuestion) {
			if (!isset($ls_templatePath)) {
				$ls_templatePath = rtrim(Configure::read('App.paths.templates.customer'), DS);
			}

			$ls_fileName = 'Question' . $this->currentAction->identifier;

			$ls_filePath = implode(DS, [
				$ls_templatePath,
				'Frontend',
				'element',
				'survey',
				$ls_fileName . '.twig',
			]);

			$ls_element = $this->currentAction->surveyQuestion->type->value;
			if (file_exists($ls_filePath)) {
				$ls_element = $ls_fileName;
			}

			$lo_mediaRenderOptions = $lo_mediaRenderOptions->withSelector('#SurveyQuestion-' . Inflector::ucparts($this->currentAction->identifier));

			// Parse the module
			$this->parseResponsiveImageTags($this->currentAction->surveyQuestion, $lo_mediaRenderOptions);

			// Parse the module
			$this->parseModule($this->currentAction->surveyQuestion, $lo_mediaRenderOptions);

			$ls_currentQuestion = $this->getView()->element('survey/' . $ls_element, [
				'survey' => $this->survey,
				'question' => $this->currentAction,
			]);

			// Regular questions (single choice, multi choice, free user input) always have a next action.
			// Other question types need to have a specific next action defined.
			$lb_hasNextAction = in_array($this->currentAction->surveyQuestion->type, [
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

		$lo_mediaRenderOptions = $lo_mediaRenderOptions->withSelector('#Survey-' . Inflector::ucparts($this->survey->identifier));

		// Parse the module
		$this->parseResponsiveImageTags($this->survey, $lo_mediaRenderOptions, [
			'successMessage',
			'failureMessage',
		]);

		// Parse the module
		$this->parseModule($this->survey, $lo_mediaRenderOptions, 'successMessage');
		$this->parseModule($this->survey, $lo_mediaRenderOptions, 'failureMessage');

		return $this->getView()->element('survey/survey', [
			'survey' => $this->survey,
			'currentQuestion' => $ls_currentQuestion,
			'currentAction' => $this->currentAction,
			'hasNextAction' => $lb_hasNextAction,
			'customAnswers' => $this->survey->getCustomAnswers(),
			'progress' => $this->survey->getProgress(),
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
	public function getFormRenderer(): ?FormRenderer {
		if (!isset($this->formRenderer)) {
			/** @var class-string<\Awyiss\Utility\Form\FormRenderer> $ls_className */
			$ls_className = App::className('FormRenderer', 'Utility/Form');

			$this->formRenderer = new $ls_className($this->getView());
		}

		return $this->formRenderer;
	}


	/**
	 * Process the form if the current action is a form.
	 *
	 * @return bool
	 */
	protected function processForm(): bool {
		$lo_form = null;

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
			$lo_form = $this->survey->getForm();
		}

		if (!$lo_form) {
			return false;
		}

		$this->getFormRenderer()->initForm(
			$lo_form,
			$this->requestData,
			$this->page
		);

		if (!$lo_form->isSubmitted()) {
			return true;
		}

		$lo_form->validate();

		// Validate the form using the form's and form options' validator
		if ($lo_form->isValid()) {
			$ls_formEntryHash = $this->sendForm();

			if (!$ls_formEntryHash) {
				return true;
			}

			if (
				in_array($this->currentAction, [
					$this->survey->getNextActionEnum()::SaveAndShowForm,
					$this->survey->getNextActionEnum()::ShowForm,
				])
			) {
				$this->redirect($this->surveyEntryHash, $ls_formEntryHash);
			}

			if ($this->currentAction === $this->survey->getNextActionEnum()::ShowFormAndSave) {
				$ls_surveyEntryHash = $this->saveEntry();

				if ($ls_surveyEntryHash) {
					$this->redirect($ls_surveyEntryHash, $ls_formEntryHash);
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
	public function renderForm(array $options): string {
		$lo_formRenderer = $this->getFormRenderer();
		$la_formElements = $lo_formRenderer->getForm()->getFormElements()->listNested()->filter(function (FormElement $element): bool {
			return !empty($element->identifier);
		})->indexBy('identifier')->toArray();

		return $this->getView()->element('survey/form', [
			'form' => $lo_formRenderer->getForm(),
			'formBody' => $lo_formRenderer->getFormBody($options),
			'formElements' => $la_formElements,
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
	public function saveEntryAndRedirect(): void {
		$ls_entryIdentifier = $this->saveEntry();

		if (!$ls_entryIdentifier) {
			return;
		}

		$this->redirect($ls_entryIdentifier);
	}


	/**
	 * @return string|false
	 */
	public function saveEntry(): string|false {
		if (!$this->survey) {
			throw new RuntimeException('No survey was initialized.');
		}

		$ls_ipHash = $this->createIpHash();
		$la_surveyData = $this->getSurveyData();
		$ls_postHash = Security::hash(serialize($la_surveyData));

		$lo_surveyEntry = $this->surveyEntriesTable->newDefaultEntity();

		$la_data = [
			'survey_id' => $this->survey->id,
			'page_id' => $this->page?->id ?? null,
			'data' => base64_encode(gzcompress(json_encode($la_surveyData))),
			'ip_hash' => $ls_ipHash,
			'post_hash' => $ls_postHash,
		];

		$this->surveyEntriesTable->patchEntity($lo_surveyEntry, $la_data);

		// Save the survey entry
		if ($this->surveyEntriesTable->save($lo_surveyEntry, ['allowFrontendSave' => true])) {
			$this->savedEntry = true;
			return md5($lo_surveyEntry->id . ' | ' . $lo_surveyEntry->postHash);
		}

		return $this->savedEntry = false;
	}


	/**
	 * @return mixed
	 */
	protected function getSurveyData(): array {
		$la_progressData = $this->survey->getProgress();
		$la_customAnswers = $this->survey->getCustomAnswers();
		/** @var array<string, SurveySurveyQuestion> $la_questionsByIdentifier */
		$la_questionsByIdentifier = array_column($this->survey->getQuestions()->toArray(), null, 'identifier');

		$la_userData = [
			'progress' => $la_progressData,
			'customAnswers' => $la_customAnswers,
			'readable' => [],
		];
		foreach ($la_progressData as $ls_identifier => $lx_answer) {
			$lo_question = $la_questionsByIdentifier[ $ls_identifier ] ?? null;

			if (!$lo_question) {
				// This should never happen, but just in case
				continue;
			}

			$ls_answer = '';
			$la_answers = [];
			if (is_array($lx_answer)) {
				if (count($lx_answer) === 1) {
					$ls_answer = reset($lx_answer);
				}
				else {
					foreach ($lx_answer as $li_answerId) {
						$la_answers[] = $lo_question->surveySurveyAnswers[ (int)$li_answerId ]->surveyAnswer->label;
					}
				}
			}
			elseif (isset($la_customAnswers[ $ls_identifier ])) {
				$ls_answer = $la_customAnswers[ $ls_identifier ];
			}
			elseif ($lo_question->surveyQuestion->type === $this->survey->getQuestionTypeEnum()::FreeText) {
				$ls_answer = $lx_answer;
				$lx_answer = null;
			}
			else {
				// Single-choice or free-text answers are stored as strings
				$ls_answer = $lo_question->surveySurveyAnswers[ $lx_answer ]->surveyAnswer->label ?? '';
			}

			$la_userData['readable'][] = [
				'question_id' => $lo_question->id,
				'answer_id' => $lx_answer,
				'question' => $lo_question->surveyQuestion->label,
				'answer' => $la_answers ?: $ls_answer ?: null,
			];
		}

		return $la_userData;
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
	 * @param string|null $surveyEntryIdentifier
	 * @param string|null $formEntryIdentifier
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function redirect(?string $surveyEntryIdentifier = null, ?string $formEntryIdentifier = null): void {
		$la_url = [
			'surveyEntry' => $surveyEntryIdentifier,
			'formEntry' => $formEntryIdentifier,
			'#' => 'Survey-' . Inflector::ucparts($this->survey->identifier, false),
		];

		$lo_request = Router::getRequest();

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

		throw new RedirectException(Router::url($la_url, true), 302);
	}


	/**
	 * @return string|false
	 */
	public function sendForm(): string|false {
		/** @var \Awyiss\Utility\Form\SurveyFormSender $ls_formSenderClass */
		$ls_formSenderClass = App::className('Survey', 'Utility/Form', 'FormSender');

		$lo_formSender = new $ls_formSenderClass($this->getFormRenderer()->getForm(), $this->page);

		$lo_formSender->setSurveyProgress(
			$this->survey,
			$this->survey->getProgress(),
			$this->survey->getCustomAnswers(),
		);

		$this->formSent = $lo_formSender->handle();

		if (!$this->formSent) {
			return false;
		}

		return $lo_formSender->getFormEntryIdentifier();
	}
}
