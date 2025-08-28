<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Model\Enum\Survey\Type;
use BackedEnum;
use Cake\Collection\CollectionInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Text;
use Cake\View\View;
use InvalidArgumentException;


/**
 * Survey Entity
 *
 * @property int $id
 * @property \Awyiss\Model\Enum\Survey\Type $type
 * @property string $title
 * @property string $identifier
 * @property string|null $successMessage
 * @property string|null $failureMessage
 * @property \Awyiss\Model\Enum\Survey\NextAction|\BackedEnum $finalAction
 * @property int|null $formId
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Form $form
 * @property \Awyiss\Model\Entity\SurveySurveyQuestion[]|\Cake\Collection\CollectionInterface $surveySurveyQuestions
 * @property \Awyiss\Model\Entity\SurveyEntry[]|\Cake\Collection\CollectionInterface $surveyEntries
 */
class Survey extends Entity {
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'success_message' => 'successMessage',
		'failure_message' => 'failureMessage',
		'final_action' => 'finalAction',
		'form_id' => 'formId',
		'survey_survey_questions' => 'surveySurveyQuestions',
		'survey_entries' => 'surveyEntries',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'type' => true,
		'title' => true,
		'identifier' => true,
		'successMessage' => true,
		'failureMessage' => true,
		'finalAction' => true,
		'formId' => true,
		'active' => true,
	];
	/**
	 * The current step in the survey.
	 *
	 * @var \Awyiss\Model\Entity\SurveySurveyQuestion|(\Awyiss\Model\Enum\Survey\NextAction|\BackedEnum)|false|null
	 */
	protected SurveySurveyQuestion|BackedEnum|false|null $currentAction = null;
	/**
	 * Custom answers for the survey, indexed by question identifier.
	 *
	 * @var array<string, string>
	 */
	protected array $customAnswers = [];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'type' => Type::Linear,
		'finalAction' => NextAction::SaveAndEnd,
	];
	/**
	 * When in preview mode, inactive questions are also loaded
	 *
	 * @var bool
	 */
	protected bool $isPreview = false;
	/**
	 * The next action enum to be used for the survey.
	 *
	 * @var class-string<\Awyiss\Model\Enum\Survey\NextAction&\BackedEnum>
	 */
	protected string $nextActionEnum = '';
	/**
	 * @var array<string, int> $progressData
	 */
	protected array $progressData = [];
	/**
	 * The questions of the survey.
	 *
	 * @var \Cake\Collection\CollectionInterface&array<\Awyiss\Model\Entity\SurveySurveyQuestion>
	 */
	protected CollectionInterface $questions;
	/**
	 * The questions of the survey.
	 *
	 * @var array<string, \Awyiss\Model\Entity\SurveySurveyQuestion>
	 */
	protected array $questionsByIdentifier;
	/**
	 * @varclass-string<\Awyiss\Model\Enum\Survey\QuestionType>
	 */
	protected string $questionTypeEnum = '';
	/**
	 * Whether the survey was submitted.
	 *
	 * @var bool
	 */
	protected bool $submitted = false;
	/**
	 * @var \Awyiss\Model\Entity\Page|null
	 */
	protected ?Page $sourcePage;
	/**
	 * @var \Cake\View\View
	 */
	protected View $view;


	/**
	 * Initialize the survey to be processed
	 *
	 * @param \Cake\View\View $view
	 * @param array $progressData
	 * @param \Awyiss\Model\Entity\Page|null $page
	 * @param bool $isPreview
	 * @return $this
	 */
	public function initialize(View $view, array $progressData = [], ?Page $page = null, bool $isPreview = false): static {
		$this->view = $view;
		$this->isPreview = $isPreview;
		$this->sourcePage = $page;

		$this->loadQuestions()->setProgress($progressData);

		return $this;
	}


	/**
	 * @param string $identifier
	 * @return $this
	 */
	public function goToStep(string $identifier): static {
		if (!$this->progressData) {
			return $this;
		}

		if (
			!isset($this->questionsByIdentifier[ $identifier ]) ||
			!array_key_exists($identifier, $this->progressData)
		) {
			throw new InvalidArgumentException(sprintf('The question with identifier `%s` does not exist in the survey.', $identifier));
		}

		// Remove all progress data after the given identifier
		$la_progress = array_keys($this->progressData);
		$li_index = array_search($identifier, $la_progress, true);
		$la_progress = array_slice($this->progressData, 0, $li_index);

		$this->setProgress($la_progress);

		return $this;
	}


	/**
	 * @return $this
	 */
	protected function loadQuestions(): static {
		if (!$this->id) {
			$this->questions = collection([]);

			return $this;
		}

		/** @var \Awyiss\Model\Table\SurveySurveyQuestionsTable $lo_questionsTable */
		$lo_questionsTable = $this->fetchTable('SurveySurveyQuestions');

		if ($this->isPreview) {
			$lo_query = $lo_questionsTable->find('all');
		}
		else {
			/** @uses \Awyiss\Model\Table::findActive() */
			$lo_query = $lo_questionsTable->find('active');
		}

		$lo_query->contain([
			'SurveyQuestions' => [
				'queryBuilder' => function (SelectQuery $query): SelectQuery {
					$query->find($this->isPreview ? 'all' : 'active');
					return $query->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);
				},
			],
			'SurveySurveyAnswers' => [
				'finder' => $this->isPreview ? 'all' : 'active',
				'SurveyAnswers' => [
					'queryBuilder' => function (SelectQuery $query): SelectQuery {
						$query->find($this->isPreview ? 'all' : 'active');
						return $query->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true);
					},
				],
			],
		]);

		$lo_questions = $lo_query->where([
			'survey_id' => $this->id,
		])->all()->compile();

		if (!$lo_questions->count()) {
			$this->questions = collection([]);

			return $this;
		}

		$lo_questions->each(function (SurveySurveyQuestion $question): void {
			// Order the surveySurveyAnswers by their id
			$question->surveySurveyAnswers = array_column(
				$question->surveySurveyAnswers,
				null,
				'id'
			);
		});

		$this->questions = $lo_questions;

		$this->questionsByIdentifier = array_column(
			$this->questions->toArray(),
			null,
			'identifier'
		);

		return $this;
	}


	/**
	 * Returns the current action in the survey flow.
	 *
	 * @return \Awyiss\Model\Entity\SurveySurveyQuestion|(\Awyiss\Model\Enum\Survey\NextAction|\BackedEnum)|false|null
	 * @noinspection PhpDocSignatureInspection
	 */
	public function getCurrentAction(): SurveySurveyQuestion|BackedEnum|false|null {
		if ($this->currentAction === null) {
			$this->currentAction = isset($this->questions) ? $this->questions->first() : null;
		}

		return $this->currentAction;
	}


	/**
	 * @param \Awyiss\Model\Entity\SurveySurveyQuestion|(\Awyiss\Model\Enum\Survey\NextAction|\BackedEnum)|false|null $action
	 * @return $this
	 */
	public function setCurrentAction(SurveySurveyQuestion|BackedEnum|false|null $action): static {
		if (
			$action instanceof SurveySurveyQuestion &&
			!$this->questions->contains($action)
		) {
			throw new InvalidArgumentException('The provided question is not part of the survey.');
		}

		if (
			$action instanceof BackedEnum &&
			!in_array($action, $this->getNextActionEnum()::cases(), true)
		) {
			throw new InvalidArgumentException(sprintf('The provided action is invalid. `%s` given.', $action->name));
		}

		$this->currentAction = $action;

		return $this;
	}


	/**
	 * Checks if the survey has a next action.
	 * This is used to determine if the survey can continue to the next question or if it should end.
	 *
	 * @return bool
	 */
	public function hasNextAction(): bool {
		return !!$this->getNextAction();
	}


	/**
	 * Gets the next action for the survey based on the given or current question.
	 *
	 * @param \Awyiss\Model\Entity\SurveySurveyQuestion|null $question
	 * @param array|string|int|null $answer
	 * @return \Awyiss\Model\Entity\SurveySurveyQuestion|(\Awyiss\Model\Enum\Survey\NextAction|\BackedEnum)|null
	 */
	public function getNextAction(?SurveySurveyQuestion $question = null, array|string|int|null $answer = null): SurveySurveyQuestion|BackedEnum|false|null {
		$lo_action = $question ?? $this->getCurrentAction();
		$lx_answer = $answer ?? null;

		if ($lx_answer === null && $lo_action instanceof SurveySurveyQuestion) {
			$lx_answer = $this->progressData[ $lo_action->identifier ] ?? null;
		}

		if (!$lo_action) {
			// If no action is given, we cannot determine the next action.
			return false;
		}

		// In case the current action is a NextAction enum,
		// return false as there's no next action.
		if ($lo_action instanceof BackedEnum) {
			return false;
		}

		// For all question types except single choice,
		// the next action is evaluated based on the question itself.
		if ($lo_action->surveyQuestion->type !== $this->getQuestionTypeEnum()::SingleChoice) {
			return $this->evaluateNextAction($lo_action);
		}

		// Find the answer for the given question.
		$lo_answer = $lx_answer ? ($lo_action->surveySurveyAnswers[ $lx_answer ] ?? null) : null;

		// If no answer is given yet, all answers can define the next action.
		if (!$lo_answer) {
			if ($lo_action->allowCustomAnswer) {
				// If the question allows a custom answer, return the next action based on the question itself.
				return $this->evaluateNextAction($lo_action);
			}

			$la_nextActions = [];
			foreach ($lo_action->surveySurveyAnswers as $lo_answer) {
				$lx_nextQuestion = $this->evaluateNextAction($lo_action, $lo_answer);

				if ($lx_nextQuestion instanceof SurveySurveyQuestion) {
					// If the next action is a specific question, return that question.
					return $lx_nextQuestion;
				}

				if ($lx_nextQuestion instanceof BackedEnum) {
					// If the next action is a specific action, return that action.
					return $lx_nextQuestion;
				}

				$la_nextActions[] = $lx_nextQuestion;
			}

			// If the next actions array only contains false, return false.
			// Any null value means that an answer does not define a next action,
			// so the question defines the next action.
			if (count(array_filter($la_nextActions, fn ($x) => $x !== false)) === 0) {
				return false;
			}
		}
		elseif ($lo_answer->nextAction) {
			// If the given answer has a next action, evaluate it
			return $this->evaluateNextAction($lo_action, $lo_action->surveySurveyAnswers[ $lx_answer ]);
		}

		return $this->evaluateNextAction($lo_action);
	}


	/**
	 * Returns a list of custom answers given in the survey.
	 *
	 * @return array<string, string>
	 */
	public function getCustomAnswers(): array {
		return $this->customAnswers;
	}


	/**
	 * @param \Awyiss\Model\Entity\SurveySurveyQuestion|null $question
	 * @param \Awyiss\Model\Entity\SurveySurveyAnswer|null $answer
	 * @return \Awyiss\Model\Entity\Form|null
	 */
	public function getForm(?SurveySurveyQuestion $question = null, ?SurveySurveyAnswer $answer = null): ?Form {
		$lo_formsTable = $this->fetchTable('Forms');

		$la_progress = array_keys($this->progressData);
		$ls_lastIdentifier = array_pop($la_progress);

		$lo_question = $question ?? ($ls_lastIdentifier ? $this->questionsByIdentifier[ $ls_lastIdentifier ] : null);
		// Should not happen, except in tests, but better safe than sorry.
		if (!$lo_question) {
			return null;
		}

		$lx_answer = $answer ?? $this->progressData[ $lo_question->identifier ] ?? null;

		if (
			$lx_answer &&
			(
				$answer instanceof SurveySurveyAnswer ||
				isset($lo_question->surveySurveyAnswers[ $lx_answer ])
			)
		) {
			$lo_answer = $answer instanceof SurveySurveyAnswer ? $answer : $lo_question->surveySurveyAnswers[ $lx_answer ];

			// Check if the selected answer has a next action that requires a form.
			if (
				in_array($lo_answer->nextAction, [
					$this->getNextActionEnum()::ShowForm,
					$this->getNextActionEnum()::SaveAndShowForm,
					$this->getNextActionEnum()::ShowFormAndSave,
				])
			) {
				$lo_query = $lo_formsTable->findById((int)($lo_answer->nextActionTarget ?? $this->formId));

				$lo_query->find($this->isPreview ? 'all' : 'active');

				return $lo_query->first();
			}
		}

		// Check if the question has a next action that is a form.
		if (
			in_array($lo_question->nextAction, [
				$this->getNextActionEnum()::ShowForm,
				$this->getNextActionEnum()::SaveAndShowForm,
				$this->getNextActionEnum()::ShowFormAndSave,
			])
		) {
			$lo_query = $lo_formsTable->findById((int)($lo_question->nextActionTarget ?? $this->formId));

			$lo_query->find($this->isPreview ? 'all' : 'active');

			return $lo_query->first();
		}

		// If the final action is to show a form, return the form.
		if (
			in_array($this->finalAction, [
				$this->getNextActionEnum()::ShowForm,
				$this->getNextActionEnum()::SaveAndShowForm,
				$this->getNextActionEnum()::ShowFormAndSave,
			])
		) {
			$lo_query = $lo_formsTable->findById($this->formId);

			$lo_query->find($this->isPreview ? 'all' : 'active');

			return $lo_query->first();
		}

		return null;
	}


	/**
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function getQuestions(): CollectionInterface {
		if (!isset($this->questions)) {
			$this->loadQuestions();
		}

		return $this->questions;
	}


	/**
	 * Evaluates the next action for the given question or answer.
	 * This method determines the next question based on the next action defined in the question or answer.
	 *
	 * If the next action is the next question or a specific question,
	 * and that question does not exist, it will return null so that the survey can continue with
	 * the final action.
	 *
	 * @param \Awyiss\Model\Entity\SurveySurveyQuestion $question
	 * @param \Awyiss\Model\Entity\SurveySurveyAnswer|null $answer
	 * @return \Awyiss\Model\Entity\SurveySurveyQuestion|(\Awyiss\Model\Enum\Survey\NextAction|\BackedEnum)|false|null
	 * @noinspection PhpDocSignatureInspection
	 */
	protected function evaluateNextAction(SurveySurveyQuestion $question, ?SurveySurveyAnswer $answer = null): SurveySurveyQuestion|BackedEnum|false|null {
		$lo_entity = $answer ?? $question;

		if ($lo_entity->nextAction === $this->getNextActionEnum()::Abort) {
			return false;
		}

		// Any action that doesn't lead to a next question will return null.
		if (
			in_array($lo_entity->nextAction, [
				$this->getNextActionEnum()::SaveAndEnd,
				$this->getNextActionEnum()::ShowForm,
				$this->getNextActionEnum()::SaveAndShowForm,
				$this->getNextActionEnum()::ShowFormAndSave,
			])
		) {
			return $lo_entity->nextAction;
		}

		$lb_tryNext = false;
		if ($lo_entity->nextAction === $this->getNextActionEnum()::SpecificQuestion) {
			if (isset($this->questionsByIdentifier[ $lo_entity->nextActionTarget ])) {
				return $this->questionsByIdentifier[ $lo_entity->nextActionTarget ];
			}

			// If the next action is a specific question, but that question does not exist,
			// we try to continue with the next question in the survey.
			$lb_tryNext = true;
		}

		// If the next action is the next question, return the next question in the survey.
		if ($lb_tryNext || $lo_entity->nextAction === $this->getNextActionEnum()::NextQuestion) {
			$la_questions = $this->questions->toArray();
			$li_currentIndex = array_search($question, $la_questions, true);

			if (isset($la_questions[ $li_currentIndex + 1 ])) {
				return $la_questions[ $li_currentIndex + 1 ];
			}

			// Get the next action from the survey
			return $this->finalAction;
		}

		return null;
	}


	/**
	 * Returns the current progress of the survey.
	 *
	 * @return array<string, int>
	 */
	public function getProgress(): array {
		return $this->progressData;
	}


	/**
	 * Sets the progress of the survey based on the provided data.
	 *
	 * @param array $progressData
	 * @return $this
	 */
	public function setProgress(array $progressData): static {
		$this->progressData = [];
		$this->customAnswers = [];
		$this->currentAction = null;

		$la_customData = $progressData['custom'] ?? [];
		/** @noinspection PhpVariableNamingConventionInspection */
		unset($progressData['custom'], $progressData['action'], $progressData['last_action']);

		foreach ($progressData as $ls_identifier => $lx_answer) {
			$lo_question = $this->questionsByIdentifier[ $ls_identifier ] ?? null;

			if (!$lo_question) {
				break;
			}

			$lx_answer = match ($lo_question->surveyQuestion->type) {
				$this->getQuestionTypeEnum()::SingleChoice => $lx_answer !== 'custom' ? (int)$lx_answer : $lx_answer,
				$this->getQuestionTypeEnum()::MultiChoice => array_map(function (mixed $answer) {
					return $answer !== 'custom' ? (int)$answer : $answer;
				}, (array)$lx_answer),
				$this->getQuestionTypeEnum()::InfoText => null,
				default => $lx_answer,
			};

			if (!$this->validateProgress($ls_identifier, $lx_answer, $this->currentAction)) {
				break;
			}

			if (
				$lx_answer === 'custom' &&
				(
					$lo_question->surveyQuestion->type === $this->getQuestionTypeEnum()::SingleChoice ||
					$lo_question->surveyQuestion->type === $this->getQuestionTypeEnum()::FreeText
				) &&
				isset($la_customData[ $ls_identifier ])
			) {
				$this->customAnswers[ $ls_identifier ] = $la_customData[ $ls_identifier ];
			}

			if (
				$lo_question->surveyQuestion->type === $this->getQuestionTypeEnum()::MultiChoice &&
				in_array('custom', $lx_answer) &&
				isset($la_customData[ $ls_identifier ])
			) {
				// If the multi-choice question has a custom answer,
				// we store it in the custom answers array.
				$this->customAnswers[ $ls_identifier ] = $la_customData[ $ls_identifier ];
			}

			$this->currentAction = $lo_question;
			$this->progressData[ $ls_identifier ] = $lx_answer;
		}

		if ($this->progressData) {
			$this->setCurrentAction($this->getNextAction());
		}

		return $this;
	}


	/**
	 * @param string $identifier
	 * @param array|string|int|null $answer
	 * @param \Awyiss\Model\Entity\SurveySurveyQuestion|null $previousQuestion
	 * @return bool
	 */
	protected function validateProgress(string $identifier, array|string|int|null $answer, ?SurveySurveyQuestion $previousQuestion = null): bool {
		if (!isset($this->questionsByIdentifier[ $identifier ])) {
			return false;
		}

		$lo_question = $this->questionsByIdentifier[ $identifier ];

		if (!$previousQuestion) {
			// If no previous question is given, the identifier must be the first question in the survey.
			if ($this->questions->first()->identifier !== $identifier) {
				return false;
			}
		}
		else {
			// Get the next question from the perspective of the previous question
			$lo_followUpQuestion = $this->getNextAction($previousQuestion, $this->progressData[ $lo_question->identifier ] ?? null);

			// If the follow-up question for the previous question with the given answer
			// is not the current question, return false.
			if (!$lo_followUpQuestion || $lo_followUpQuestion !== $lo_question) {
				return false;
			}
		}

		$lo_question = $this->questionsByIdentifier[ $identifier ];

		if ($lo_question->surveyQuestion->type === $this->getQuestionTypeEnum()::InfoText) {
			// Info text questions do not require an answer.
			return true;
		}

		if ($lo_question->surveyQuestion->type === $this->getQuestionTypeEnum()::FreeText) {
			// Free text questions require an answer that is not empty.
			return !empty($answer);
		}

		if ($lo_question->surveyQuestion->type === $this->getQuestionTypeEnum()::MultiChoice) {
			// Multi-choice questions require an answer that is an array of selected options
			// and all selected options must be valid answers.
			if (!is_array($answer) || empty($answer)) {
				return false;
			}

			$la_possibleAnswers = array_keys($lo_question->surveySurveyAnswers);
			if ($lo_question->allowCustomAnswer) {
				$la_possibleAnswers[] = 'custom';
			}

			return empty(array_diff($answer, $la_possibleAnswers));
		}

		// If the single choice question allows a custom answer,
		// the answer can be null.
		if ($lo_question->allowCustomAnswer && $answer === 'custom') {
			return true;
		}

		return array_key_exists($answer, $lo_question->surveySurveyAnswers);
	}


	/**
	 * Returns whether the survey has a cycle in its questions flow.
	 *
	 * @param array|null $graph
	 * @return bool
	 */
	public function hasCycle(?array $graph = null): bool {
		$la_visited = [];
		$la_stack = [];

		if (!$this->surveySurveyQuestions) {
			return false;
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		$graph ??= $this->buildQuestionsGraph();

		$la_questionsByIdentifier = array_column(
			$this->surveySurveyQuestions,
			null,
			'identifier'
		);

		foreach (array_keys($graph) as $ls_node) {
			$ls_node = (string)$ls_node;
			if ($this->detectCycle($ls_node, $graph, $la_visited, $la_stack)) {
				// Find the question that has the node as nextActionTarget
				$lo_question = collection($la_questionsByIdentifier)
					->filter(function (SurveySurveyQuestion $entity) use ($ls_node): bool {
						if (
							$entity->nextAction === $this->getNextActionEnum()::SpecificQuestion &&
							$entity->nextActionTarget === $ls_node
						) {
							return true;
						}

						if (!$entity->surveySurveyAnswers) {
							return false;
						}

						return collection($entity->surveySurveyAnswers)
							->some(function (SurveySurveyAnswer $answer) use ($ls_node): bool {
								return $answer->nextAction === $this->getNextActionEnum()::SpecificQuestion &&
									$answer->nextActionTarget === $ls_node;
							});
					})
					->first();

				if (!$lo_question) {
					$lo_question = $la_questionsByIdentifier[ $ls_node ];
				}

				$lo_question->setError(
					'nextActionTarget',
					__df('surveys', 'validation', 'error_no_circular_references')
				);

				return true;
			}
		}

		return false;
	}


	/**
	 * Detects if there is a cycle in the survey questions flow.
	 *
	 * @param string $node
	 * @param array $graph
	 * @param array $visited
	 * @param array $stack
	 * @return bool
	 */
	protected function detectCycle(string $node, array $graph, array &$visited, array &$stack): bool {
		if (!empty($stack[ $node ])) {
			return true;
		}

		if (!empty($visited[ $node ])) {
			return false;
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		$visited[ $node ] = $stack[ $node ] = true;

		foreach ($graph[ $node ] ?? [] as $ls_nextNode) {
			/** @noinspection PhpVariableNamingConventionInspection */
			if ($this->detectCycle($ls_nextNode, $graph, $visited, $stack)) {
				return true;
			}
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		unset($stack[ $node ]);

		return false;
	}


	/**
	 * Builds a graph representation of the survey questions and their next actions.
	 * This graph is used to detect cycles in the survey flow.
	 *
	 * @return array<string, array<string>>
	 */
	public function buildQuestionsGraph(): array {
		$la_questionsGraph = [];

		$la_questionsByIdentifier = array_column($this->surveySurveyQuestions, null, 'identifier');

		foreach ($this->surveySurveyQuestions as $li_key => $lo_question) {
			$la_questionsGraph[ $lo_question->identifier ] = [];

			if ($lo_question->surveySurveyAnswers) {
				$la_questionsGraph[ $lo_question->identifier ] = $this->buildAnswersGraph(
					$lo_question->surveySurveyAnswers,
					$this->surveySurveyQuestions,
					$li_key,
					$la_questionsByIdentifier
				);

				/**
				 * If
				 * - the question allows a custom answer or
				 * - any answer has no next action specified
				 * the action of the question will be used to determine the next step.
				 */
				$lb_inheritingAnswer = $lo_question->allowCustomAnswer || collection($lo_question->surveySurveyAnswers)
					->some(function (SurveySurveyAnswer $answer): bool {
						return empty($answer->nextAction);
					});

				// If no answer inherits the next action, the action of the question is never used.
				if (!$lb_inheritingAnswer) {
					continue;
				}
			}

			// If the next action is the next question and a next question exists, add that to the graph.
			if (
				$lo_question->nextAction === $this->getNextActionEnum()::NextQuestion &&
				isset($this->surveySurveyQuestions[ $li_key + 1 ])
			) {
				$la_questionsGraph[ $lo_question->identifier ][] = $this->surveySurveyQuestions[ $li_key + 1 ]->identifier;
			}
			// If the next action is a specific question, add that to the graph.
			elseif (
				$lo_question->nextAction === $this->getNextActionEnum()::SpecificQuestion &&
				isset($la_questionsByIdentifier[ $lo_question->nextActionTarget ])
			) {
				$la_questionsGraph[ $lo_question->identifier ][] = $la_questionsByIdentifier[ $lo_question->nextActionTarget ]->identifier;
			}
		}

		return $la_questionsGraph;
	}


	/**
	 * Builds the answers graph for the current question.
	 *
	 * @param array<\Awyiss\Model\Entity\SurveySurveyAnswer> $answers
	 * @param array<\Awyiss\Model\Entity\SurveySurveyQuestion> $questions
	 * @param int $currentQuestionKey
	 * @param array $questionsByIdentifier
	 * @return array<string>
	 */
	protected function buildAnswersGraph(
		array $answers,
		array $questions,
		int $currentQuestionKey,
		array $questionsByIdentifier
	): array {
		$la_answersGraph = [];

		foreach ($answers as $lo_answer) {
			// If the next action is the next question and a next question exists, add that to the graph.
			if (
				$lo_answer->nextAction === $this->getNextActionEnum()::NextQuestion &&
				isset($questions[ $currentQuestionKey + 1 ])
			) {
				$la_answersGraph[] = $questions[ $currentQuestionKey + 1 ]->identifier;
			}
			// If the next action is a specific question, add that to the graph.
			elseif (
				$lo_answer->nextAction === $this->getNextActionEnum()::SpecificQuestion &&
				isset($questionsByIdentifier[ $lo_answer->nextActionTarget ])
			) {
				$la_answersGraph[] = $questionsByIdentifier[ $lo_answer->nextActionTarget ]->identifier;
			}
		}

		return $la_answersGraph;
	}


	/**
	 * Build an array containing all possible paths in the form of
	 * `[
	 * 	[questionId => answerId, questionId => answerId, ...],
	 * 	[questionId => answerId, questionId => answerId, ...],
	 * 	...,
	 * ]`
	 *
	 * @return array<int, array<int, int>>
	 */
	public function buildResultsArray(): array {
		$la_results = [];

		$this->loadQuestions();

		$lo_first = $this->questions->first();

		if ($lo_first) {
			$this->traverseResultsPaths($lo_first, [], $la_results);
		}

		return $la_results;
	}


	/**
	 * @param array|null $path
	 * @return string
	 */
	public function buildResultPath(?array $path = null): string {
		/** @noinspection PhpVariableNamingConventionInspection */
		$path ??= $this->progressData;

		$ls_result = '';

		foreach ($path as $ls_questionId => $lx_answer) {
			if ($ls_result) {
				$ls_result .= '-';
			}

			$ls_result .= $ls_questionId . ':';
			if (is_array($lx_answer)) {
				// If the answer is an array, join the values with a comma
				$ls_result .= implode(',', array_map(
					fn ($answer) => $answer ?? 'null',
					$lx_answer
				));
			}
			elseif ($lx_answer === null) {
				// Skip null answers. Those are usually InfoText questions and have no
				// influence on the result.
				continue;
			}
			else {
				// Otherwise, just append the answer value
				$ls_result .= $lx_answer;
			}
		}

		return $ls_result;
	}


	/**
	 * Recursively traverses all possible answer paths through the survey,
	 * building an array of all possible question/answer combinations.
	 * Ensures that InfoText questions are not included as the last item in a path.
	 *
	 * @param \Awyiss\Model\Entity\SurveySurveyQuestion $question The current question entity.
	 * @param array $path The current path of question identifier => answer(s).
	 * @param array $results Reference to the results array to collect all paths.
	 * @return void
	 */
	protected function traverseResultsPaths(SurveySurveyQuestion $question, array $path, array &$results): void {
		$ls_type = $question->surveyQuestion->type;
		$la_answers = $question->surveySurveyAnswers ?? [];

		// Handle MultiChoice questions: generate all non-empty combinations of answers
		if ($ls_type === $this->getQuestionTypeEnum()::MultiChoice) {
			$la_answerIds = array_keys($la_answers);
			$la_combinations = $this->getNonEmptyCombinations($la_answerIds, $question->allowCustomAnswer);

			$lo_next = $this->getNextAction($question);

			foreach ($la_combinations as $la_combo) {
				$la_newPath = $path;
				$la_newPath[ $question->identifier ] = $la_combo;

				if ($lo_next instanceof SurveySurveyQuestion) {
					/** @noinspection PhpVariableNamingConventionInspection */
					$this->traverseResultsPaths($lo_next, $la_newPath, $results);
				}
				else {
					/** @noinspection PhpVariableNamingConventionInspection */
					$results[] = $la_newPath;
				}
			}

			return;
		}

		// Handle SingleChoice questions: iterate over all possible answers
		if ($ls_type === $this->getQuestionTypeEnum()::SingleChoice) {
			foreach ($la_answers as $li_answerId => $lo_answer) {
				$la_newPath = $path;
				$la_newPath[ $question->identifier ] = $li_answerId;
				$lo_next = $this->getNextAction($question, $li_answerId);

				if ($lo_next instanceof SurveySurveyQuestion) {
					/** @noinspection PhpVariableNamingConventionInspection */
					$this->traverseResultsPaths($lo_next, $la_newPath, $results);
				}
				else {
					/** @noinspection PhpVariableNamingConventionInspection */
					$results[] = $la_newPath;
				}
			}

			// Handle custom answer if allowed
			if ($question->allowCustomAnswer) {
				$la_newPath = $path;
				$la_newPath[ $question->identifier ] = 'custom';
				/** @noinspection PhpRedundantOptionalArgumentInspection */
				$lo_next = $this->getNextAction($question, null);

				if ($lo_next instanceof SurveySurveyQuestion) {
					/** @noinspection PhpVariableNamingConventionInspection */
					$this->traverseResultsPaths($lo_next, $la_newPath, $results);
					return;
				}

				/** @noinspection PhpVariableNamingConventionInspection */
				$results[] = $la_newPath;
			}

			return;
		}

		// Handle InfoText with null, FreeText with 'custom' as answer
		$la_newPath = $path;
		$la_newPath[ $question->identifier ] = $ls_type === $this->getQuestionTypeEnum()::FreeText ? 'custom' : null;
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$lo_next = $this->getNextAction($question, null);

		if ($lo_next instanceof SurveySurveyQuestion) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$this->traverseResultsPaths($lo_next, $la_newPath, $results);
			return;
		}

		// Remove last entry if this is an InfoText question
		if ($ls_type === $this->getQuestionTypeEnum()::InfoText) {
			unset($la_newPath[ $question->identifier ]);
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		$results[] = $la_newPath;
	}


	/**
	 * Returns all non-empty combinations of the given array values.
	 *
	 * @param array $values
	 * @param bool $withCustomAnswer Whether to include custom answers in the combinations.
	 * @return array
	 */
	protected function getNonEmptyCombinations(array $values, bool $withCustomAnswer = false): array {
		$la_result = [];

		if ($withCustomAnswer) {
			/**
			 * Include a custom answer option
			 *
			 * @noinspection PhpVariableNamingConventionInspection
			 */
			$values[] = 'custom'; // Custom answer represented as 'custom'
		}

		$li_count = count($values);
		$li_combinations = (1 << $li_count);

		for ($li_i = 1; $li_i < $li_combinations; $li_i++) {
			$la_combo = [];

			for ($li_j = 0; $li_j < $li_count; $li_j++) {
				if ($li_i & (1 << $li_j)) {
					$la_combo[] = $values[ $li_j ];
				}
			}

			$la_result[] = $la_combo;
		}

		// Add one entry where the answer is an asterisk, representing "any answer"
		$la_result[] = ['*'];

		return $la_result;
	}


	/**
	 * @return class-string<\Awyiss\Model\Enum\Survey\NextAction>
	 */
	public function getNextActionEnum(): string {
		if ($this->nextActionEnum) {
			return $this->nextActionEnum;
		}

		return $this->nextActionEnum = App::className('NextAction', 'Model/Enum/Survey');
	}


	/**
	 * @return class-string<\Awyiss\Model\Enum\Survey\QuestionType>
	 */
	public function getQuestionTypeEnum(): string {
		if ($this->questionTypeEnum) {
			return $this->questionTypeEnum;
		}

		return $this->questionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');
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

		$ls_identifier = Text::slug($identifier, ['replacement' => '_']);

		return mb_strtolower($ls_identifier);
	}
}
