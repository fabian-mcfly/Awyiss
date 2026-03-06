<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Model\Enum\Survey\Type;
use Awyiss\Utility\Inflector;
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
	protected array $_accessible = [ // phpcs:ignore
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
	 * @var class-string<\Awyiss\Model\Enum\Survey\QuestionType>
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
		$progress = array_keys($this->progressData);
		$index = array_search($identifier, $progress, true);
		$progress = array_slice($this->progressData, 0, $index);

		$this->setProgress($progress);

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

		/** @var \Awyiss\Model\Table\SurveySurveyQuestionsTable $questionsTable */
		$questionsTable = $this->fetchTable('SurveySurveyQuestions');

		if ($this->isPreview) {
			$query = $questionsTable->find('all');
		}
		else {
			/** @uses \Awyiss\Model\Table::findActive() */
			$query = $questionsTable->find('active');
		}

		$query->contain([
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

		$questions = $query->where([
			'surveyId' => $this->id,
		])->all()->compile();

		if (!$questions->count()) {
			$this->questions = collection([]);

			return $this;
		}

		$questions->each(function (SurveySurveyQuestion $question): void {
			// Order the surveySurveyAnswers by their id
			$question->surveySurveyAnswers = array_column(
				$question->surveySurveyAnswers,
				null,
				'id'
			);
		});

		$this->questions = $questions;

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
		$action = $question ?? $this->getCurrentAction();

		if ($answer === null && $action instanceof SurveySurveyQuestion) {
			$answer = $this->progressData[ $action->identifier ] ?? null;
		}

		if (!$action) {
			// If no action is given, we cannot determine the next action.
			return false;
		}

		// In case the current action is a NextAction enum,
		// return false as there's no next action.
		if ($action instanceof BackedEnum) {
			return false;
		}

		// For all question types except single choice,
		// the next action is evaluated based on the question itself.
		if ($action->surveyQuestion->type !== $this->getQuestionTypeEnum()::SingleChoice) {
			return $this->evaluateNextAction($action);
		}

		// Find the answer for the given question.
		$surveyAnswer = $answer ? ($action->surveySurveyAnswers[ $answer ] ?? null) : null;

		// If no answer is given yet, all answers can define the next action.
		if (!$surveyAnswer) {
			if ($action->allowCustomAnswer) {
				// If the question allows a custom answer, return the next action based on the question itself.
				return $this->evaluateNextAction($action);
			}

			$nextActions = [];
			foreach ($action->surveySurveyAnswers as $surveySurveyAnswer) {
				$nextQuestion = $this->evaluateNextAction($action, $surveySurveyAnswer);

				if ($nextQuestion instanceof SurveySurveyQuestion) {
					// If the next action is a specific question, return that question.
					return $nextQuestion;
				}

				if ($nextQuestion instanceof BackedEnum) {
					// If the next action is a specific action, return that action.
					return $nextQuestion;
				}

				$nextActions[] = $nextQuestion;
			}

			// If the next actions array only contains false, return false.
			// Any null value means that an answer does not define a next action,
			// so the question defines the next action.
			if (count(array_filter($nextActions, fn ($x) => $x !== false)) === 0) {
				return false;
			}
		}
		elseif ($surveyAnswer->nextAction) {
			// If the given answer has a next action, evaluate it
			return $this->evaluateNextAction($action, $action->surveySurveyAnswers[ $answer ]);
		}

		return $this->evaluateNextAction($action);
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
		$formsTable = $this->fetchTable('Forms');

		$progress = array_keys($this->progressData);
		$lastIdentifier = array_pop($progress);

		$question ??= ($lastIdentifier ? $this->questionsByIdentifier[ $lastIdentifier ] : null);
		// Should not happen, except in tests, but better safe than sorry.
		if (!$question) {
			return null;
		}

		$givenAnswer = $answer ?? $this->progressData[ $question->identifier ] ?? null;

		if (
			$givenAnswer &&
			(
				$answer instanceof SurveySurveyAnswer ||
				isset($question->surveySurveyAnswers[ $givenAnswer ])
			)
		) {
			$surveySurveyAnswer = $answer instanceof SurveySurveyAnswer ? $answer : $question->surveySurveyAnswers[ $givenAnswer ];

			// Check if the selected answer has a next action that requires a form.
			if (
				in_array($surveySurveyAnswer->nextAction, [
					$this->getNextActionEnum()::ShowForm,
					$this->getNextActionEnum()::SaveAndShowForm,
					$this->getNextActionEnum()::ShowFormAndSave,
				])
			) {
				$query = $formsTable->findById((int)($surveySurveyAnswer->nextActionTarget ?? $this->formId));

				$query->find($this->isPreview ? 'all' : 'active');

				return $query->first();
			}
		}

		// Check if the question has a next action that is a form.
		if (
			in_array($question->nextAction, [
				$this->getNextActionEnum()::ShowForm,
				$this->getNextActionEnum()::SaveAndShowForm,
				$this->getNextActionEnum()::ShowFormAndSave,
			])
		) {
			$query = $formsTable->findById((int)($question->nextActionTarget ?? $this->formId));

			$query->find($this->isPreview ? 'all' : 'active');

			return $query->first();
		}

		// If the final action is to show a form, return the form.
		if (
			in_array($this->finalAction, [
				$this->getNextActionEnum()::ShowForm,
				$this->getNextActionEnum()::SaveAndShowForm,
				$this->getNextActionEnum()::ShowFormAndSave,
			])
		) {
			$query = $formsTable->findById($this->formId);

			$query->find($this->isPreview ? 'all' : 'active');

			return $query->first();
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
		$entity = $answer ?? $question;

		if ($entity->nextAction === $this->getNextActionEnum()::Abort) {
			return false;
		}

		// Any action that doesn't lead to a next question will return null.
		if (
			in_array($entity->nextAction, [
				$this->getNextActionEnum()::SaveAndEnd,
				$this->getNextActionEnum()::ShowForm,
				$this->getNextActionEnum()::SaveAndShowForm,
				$this->getNextActionEnum()::ShowFormAndSave,
			])
		) {
			return $entity->nextAction;
		}

		$tryNext = false;
		if ($entity->nextAction === $this->getNextActionEnum()::SpecificQuestion) {
			if (isset($this->questionsByIdentifier[ $entity->nextActionTarget ])) {
				return $this->questionsByIdentifier[ $entity->nextActionTarget ];
			}

			// If the next action is a specific question, but that question does not exist,
			// we try to continue with the next question in the survey.
			$tryNext = true;
		}

		// If the next action is the next question, return the next question in the survey.
		if ($tryNext || $entity->nextAction === $this->getNextActionEnum()::NextQuestion) {
			$questions = $this->questions->toArray();
			$currentIndex = array_search($question, $questions, true);

			if (isset($questions[ $currentIndex + 1 ])) {
				return $questions[ $currentIndex + 1 ];
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

		$customData = $progressData['custom'] ?? [];
		unset($progressData['custom'], $progressData['action'], $progressData['lastAction']);

		foreach ($progressData as $identifier => $answer) {
			$question = $this->questionsByIdentifier[ $identifier ] ?? null;

			if (!$question) {
				break;
			}

			$answer = match ($question->surveyQuestion->type) {
				$this->getQuestionTypeEnum()::SingleChoice => $answer !== 'custom' ? (int)$answer : $answer,
				$this->getQuestionTypeEnum()::MultiChoice => array_map(function (mixed $answer) {
					return $answer !== 'custom' ? (int)$answer : $answer;
				}, (array)$answer),
				$this->getQuestionTypeEnum()::InfoText => null,
				default => $answer,
			};

			if (!$this->validateProgress($identifier, $answer, $this->currentAction)) {
				break;
			}

			if (
				$answer === 'custom' &&
				(
					$question->surveyQuestion->type === $this->getQuestionTypeEnum()::SingleChoice ||
					$question->surveyQuestion->type === $this->getQuestionTypeEnum()::FreeText
				) &&
				isset($customData[ $identifier ])
			) {
				$this->customAnswers[ $identifier ] = $customData[ $identifier ];
			}

			if (
				$question->surveyQuestion->type === $this->getQuestionTypeEnum()::MultiChoice &&
				in_array('custom', $answer) &&
				isset($customData[ $identifier ])
			) {
				// If the multi-choice question has a custom answer,
				// we store it in the custom answers array.
				$this->customAnswers[ $identifier ] = $customData[ $identifier ];
			}

			$this->currentAction = $question;
			$this->progressData[ $identifier ] = $answer;
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

		$question = $this->questionsByIdentifier[ $identifier ];

		if (!$previousQuestion) {
			// If no previous question is given, the identifier must be the first question in the survey.
			if ($this->questions->first()->identifier !== $identifier) {
				return false;
			}
		}
		else {
			// Get the next question from the perspective of the previous question
			$followUpQuestion = $this->getNextAction($previousQuestion, $this->progressData[ $question->identifier ] ?? null);

			// If the follow-up question for the previous question with the given answer
			// is not the current question, return false.
			if (!$followUpQuestion || $followUpQuestion !== $question) {
				return false;
			}
		}

		$question = $this->questionsByIdentifier[ $identifier ];

		if ($question->surveyQuestion->type === $this->getQuestionTypeEnum()::InfoText) {
			// Info text questions do not require an answer.
			return true;
		}

		if ($question->surveyQuestion->type === $this->getQuestionTypeEnum()::FreeText) {
			// Free text questions require an answer that is not empty.
			return !empty($answer);
		}

		if ($question->surveyQuestion->type === $this->getQuestionTypeEnum()::MultiChoice) {
			// Multi-choice questions require an answer that is an array of selected options
			// and all selected options must be valid answers.
			if (!is_array($answer) || empty($answer)) {
				return false;
			}

			$possibleAnswers = array_keys($question->surveySurveyAnswers);
			if ($question->allowCustomAnswer) {
				$possibleAnswers[] = 'custom';
			}

			return empty(array_diff($answer, $possibleAnswers));
		}

		// If the single choice question allows a custom answer,
		// the answer can be null.
		if ($question->allowCustomAnswer && $answer === 'custom') {
			return true;
		}

		return array_key_exists($answer, $question->surveySurveyAnswers);
	}


	/**
	 * Returns whether the survey has a cycle in its questions flow.
	 *
	 * @param array|null $graph
	 * @return bool
	 */
	public function hasCycle(?array $graph = null): bool {
		$visited = [];
		$stack = [];

		if (!$this->surveySurveyQuestions) {
			return false;
		}

		$graph ??= $this->buildQuestionsGraph();

		$questionsByIdentifier = array_column(
			$this->surveySurveyQuestions,
			null,
			'identifier'
		);

		foreach (array_keys($graph) as $node) {
			$node = (string)$node;
			if ($this->detectCycle($node, $graph, $visited, $stack)) {
				// Find the question that has the node as nextActionTarget
				$question = collection($questionsByIdentifier)
					->filter(function (SurveySurveyQuestion $entity) use ($node): bool {
						if (
							$entity->nextAction === $this->getNextActionEnum()::SpecificQuestion &&
							$entity->nextActionTarget === $node
						) {
							return true;
						}

						if (!$entity->surveySurveyAnswers) {
							return false;
						}

						return collection($entity->surveySurveyAnswers)
							->some(function (SurveySurveyAnswer $answer) use ($node): bool {
								return $answer->nextAction === $this->getNextActionEnum()::SpecificQuestion &&
									$answer->nextActionTarget === $node;
							});
					})
					->first();

				if (!$question) {
					$question = $questionsByIdentifier[ $node ];
				}

				$question->setError(
					'nextActionTarget',
					__df('Surveys', 'Validation', 'error_no_circular_references')
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

		$visited[ $node ] = $stack[ $node ] = true;

		foreach ($graph[ $node ] ?? [] as $nextNode) {
			if ($this->detectCycle($nextNode, $graph, $visited, $stack)) {
				return true;
			}
		}

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
		$questionsGraph = [];

		$questionsByIdentifier = array_column($this->surveySurveyQuestions, null, 'identifier');

		foreach ($this->surveySurveyQuestions as $key => $question) {
			$questionsGraph[ $question->identifier ] = [];

			if ($question->surveySurveyAnswers) {
				$questionsGraph[ $question->identifier ] = $this->buildAnswersGraph(
					$question->surveySurveyAnswers,
					$this->surveySurveyQuestions,
					$key,
					$questionsByIdentifier
				);

				/**
				 * If
				 * - the question allows a custom answer or
				 * - any answer has no next action specified
				 * the action of the question will be used to determine the next step.
				 */
				$inheritingAnswer = $question->allowCustomAnswer || collection($question->surveySurveyAnswers)
					->some(function (SurveySurveyAnswer $answer): bool {
						return empty($answer->nextAction);
					});

				// If no answer inherits the next action, the action of the question is never used.
				if (!$inheritingAnswer) {
					continue;
				}
			}

			// If the next action is the next question and a next question exists, add that to the graph.
			if (
				$question->nextAction === $this->getNextActionEnum()::NextQuestion &&
				isset($this->surveySurveyQuestions[ $key + 1 ])
			) {
				$questionsGraph[ $question->identifier ][] = $this->surveySurveyQuestions[ $key + 1 ]->identifier;
			}
			// If the next action is a specific question, add that to the graph.
			elseif (
				$question->nextAction === $this->getNextActionEnum()::SpecificQuestion &&
				isset($questionsByIdentifier[ $question->nextActionTarget ])
			) {
				$questionsGraph[ $question->identifier ][] = $questionsByIdentifier[ $question->nextActionTarget ]->identifier;
			}
		}

		return $questionsGraph;
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
		$answersGraph = [];

		foreach ($answers as $answer) {
			// If the next action is the next question and a next question exists, add that to the graph.
			if (
				$answer->nextAction === $this->getNextActionEnum()::NextQuestion &&
				isset($questions[ $currentQuestionKey + 1 ])
			) {
				$answersGraph[] = $questions[ $currentQuestionKey + 1 ]->identifier;
			}
			// If the next action is a specific question, add that to the graph.
			elseif (
				$answer->nextAction === $this->getNextActionEnum()::SpecificQuestion &&
				isset($questionsByIdentifier[ $answer->nextActionTarget ])
			) {
				$answersGraph[] = $questionsByIdentifier[ $answer->nextActionTarget ]->identifier;
			}
		}

		return $answersGraph;
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
		$results = [];

		$this->loadQuestions();

		$first = $this->questions->first();

		if ($first) {
			$this->traverseResultsPaths($first, [], $results);
		}

		return $results;
	}


	/**
	 * @param array|null $path
	 * @return string
	 */
	public function buildResultPath(?array $path = null): string {
		$path ??= $this->progressData;

		$result = '';

		foreach ($path as $questionId => $answer) {
			if ($result) {
				$result .= '-';
			}

			$result .= $questionId . ':';
			if (is_array($answer)) {
				// If the answer is an array, join the values with a comma
				$result .= implode(',', array_map(
					fn ($answer) => $answer ?? 'null',
					$answer
				));
			}
			elseif ($answer === null) {
				// Skip null answers. Those are usually InfoText questions and have no
				// influence on the result.
				continue;
			}
			else {
				// Otherwise, just append the answer value
				$result .= $answer;
			}
		}

		return $result;
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
		$type = $question->surveyQuestion->type;
		$answers = $question->surveySurveyAnswers ?? [];

		// Handle MultiChoice questions: generate all non-empty combinations of answers
		if ($type === $this->getQuestionTypeEnum()::MultiChoice) {
			$answerIds = array_keys($answers);
			$combinations = $this->getNonEmptyCombinations($answerIds, $question->allowCustomAnswer);

			$next = $this->getNextAction($question);

			foreach ($combinations as $combo) {
				$newPath = $path;
				$newPath[ $question->identifier ] = $combo;

				if ($next instanceof SurveySurveyQuestion) {
					$this->traverseResultsPaths($next, $newPath, $results);
				}
				else {
					$results[] = $newPath;
				}
			}

			return;
		}

		// Handle SingleChoice questions: iterate over all possible answers
		if ($type === $this->getQuestionTypeEnum()::SingleChoice) {
			foreach ($answers as $answerId => $answer) {
				$newPath = $path;
				$newPath[ $question->identifier ] = $answerId;
				$next = $this->getNextAction($question, $answerId);

				if ($next instanceof SurveySurveyQuestion) {
					$this->traverseResultsPaths($next, $newPath, $results);
				}
				else {
					$results[] = $newPath;
				}
			}

			// Handle custom answer if allowed
			if ($question->allowCustomAnswer) {
				$newPath = $path;
				$newPath[ $question->identifier ] = 'custom';
				/** @noinspection PhpRedundantOptionalArgumentInspection */
				$next = $this->getNextAction($question, null);

				if ($next instanceof SurveySurveyQuestion) {
					$this->traverseResultsPaths($next, $newPath, $results);
					return;
				}

				$results[] = $newPath;
			}

			return;
		}

		// Handle InfoText with null, FreeText with 'custom' as answer
		$newPath = $path;
		$newPath[ $question->identifier ] = $type === $this->getQuestionTypeEnum()::FreeText ? 'custom' : null;
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$next = $this->getNextAction($question, null);

		if ($next instanceof SurveySurveyQuestion) {
			$this->traverseResultsPaths($next, $newPath, $results);
			return;
		}

		// Remove last entry if this is an InfoText question
		if ($type === $this->getQuestionTypeEnum()::InfoText) {
			unset($newPath[ $question->identifier ]);
		}

		$results[] = $newPath;
	}


	/**
	 * Returns all non-empty combinations of the given array values.
	 *
	 * @param array $values
	 * @param bool $withCustomAnswer Whether to include custom answers in the combinations.
	 * @return array
	 */
	protected function getNonEmptyCombinations(array $values, bool $withCustomAnswer = false): array {
		$result = [];

		if ($withCustomAnswer) {
			/**
			 * Include a custom answer option
			 */
			$values[] = 'custom'; // Custom answer represented as 'custom'
		}

		$count = count($values);
		$combinations = (1 << $count);

		for ($i = 1; $i < $combinations; $i++) {
			$combo = [];

			for ($j = 0; $j < $count; $j++) {
				if ($i & (1 << $j)) {
					$combo[] = $values[ $j ];
				}
			}

			$result[] = $combo;
		}

		// Add one entry where the answer is an asterisk, representing "any answer"
		$result[] = ['*'];

		return $result;
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

		$identifier = Text::slug($identifier, ['replacement' => '_']);

		return Inflector::variable($identifier);
	}
}
