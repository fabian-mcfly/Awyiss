<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Model\Enum\Survey\Type;
use Cake\Utility\Text;


/**
 * Survey Entity
 *
 * @property int $id
 * @property \Awyiss\Model\Enum\Survey\Type $type
 * @property string $title
 * @property string $identifier
 * @property \Awyiss\Model\Enum\Survey\NextAction $finalAction
 * @property string|null $successMessage
 * @property string|null $failureMessage
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
 */
class Survey extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'success_message' => 'successMessage',
		'failure_message' => 'failureMessage',
		'final_action' => 'finalAction',
		'form_id' => 'formId',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'survey_survey_questions' => 'surveySurveyQuestions',
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
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'type' => Type::Linear,
		'finalAction' => NextAction::SaveAndEnd,
	];


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

		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $ls_surveyNextActionEnum */
		$ls_surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		$la_questionsByIdentifier = array_column(
			$this->surveySurveyQuestions,
			null,
			'identifier'
		);

		foreach (array_keys($graph) as $ls_node) {
			if ($this->detectCycle($ls_node, $graph, $la_visited, $la_stack)) {
				// Find the question that has the node as nextActionTarget
				$lo_question = collection($la_questionsByIdentifier)
					->filter(function (SurveySurveyQuestion $entity) use ($ls_node, $ls_surveyNextActionEnum): bool {
						if (
							$entity->nextAction === $ls_surveyNextActionEnum::SpecificQuestion &&
							$entity->nextActionTarget === $ls_node
						) {
							return true;
						}

						if (!$entity->surveySurveyAnswers) {
							return false;
						}

						return collection($entity->surveySurveyAnswers)
							->some(function (SurveySurveyAnswer $answer) use ($ls_node, $ls_surveyNextActionEnum): bool {
								return $answer->nextAction === $ls_surveyNextActionEnum::SpecificQuestion &&
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
	public function detectCycle(string $node, array $graph, array &$visited, array &$stack): bool {
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
		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $ls_surveyNextActionEnum */
		$ls_surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		$la_questionsByIdentifier = array_column($this->surveySurveyQuestions, null, 'identifier');

		foreach ($this->surveySurveyQuestions as $li_key => $lo_question) {
			$la_questionsGraph[ $lo_question->identifier ] = [];

			if ($lo_question->surveySurveyAnswers) {
				$la_questionsGraph[ $lo_question->identifier ] = $this->buildAnswersGraph(
					$lo_question->surveySurveyAnswers,
					$this->surveySurveyQuestions,
					$li_key,
					$la_questionsByIdentifier,
					$ls_surveyNextActionEnum
				);

				/**
				 * If
				 * - the question allows a custom answer or
				 * - any answer has no next action specified
				 * the action of the question will be used to determine the next step.
				 */
				$lb_inheritingAnswer = $lo_question->allowCustomAnswer || collection($lo_question->surveySurveyAnswers)
					->some(function (SurveySurveyAnswer $answer) use ($ls_surveyNextActionEnum): bool {
						return empty($answer->nextAction);
					});

				// If no answer inherits the next action, the action of the question is never used.
				if (!$lb_inheritingAnswer) {
					continue;
				}
			}

			// If the next action is the next question and a next question exists, add that to the graph.
			if (
				$lo_question->nextAction === $ls_surveyNextActionEnum::NextQuestion &&
				isset($this->surveySurveyQuestions[ $li_key + 1 ])
			) {
				$la_questionsGraph[ $lo_question->identifier ][] = $this->surveySurveyQuestions[ $li_key + 1 ]->identifier;
			}
			// If the next action is a specific question, add that to the graph.
			elseif (
				$lo_question->nextAction === $ls_surveyNextActionEnum::SpecificQuestion &&
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
	 * @param class-string<\Awyiss\Model\Enum\Survey\NextAction> $surveyNextActionEnum
	 * @return array<string>
	 */
	protected function buildAnswersGraph(
		array $answers,
		array $questions,
		int $currentQuestionKey,
		array $questionsByIdentifier,
		string $surveyNextActionEnum
	): array {
		$la_answersGraph = [];

		foreach ($answers as $lo_answer) {
			// If the next action is the next question and a next question exists, add that to the graph.
			if (
				$lo_answer->nextAction === $surveyNextActionEnum::NextQuestion &&
				isset($questions[ $currentQuestionKey + 1 ])
			) {
				$la_answersGraph[] = $questions[ $currentQuestionKey + 1 ]->identifier;
			}
			// If the next action is a specific question, add that to the graph.
			elseif (
				$lo_answer->nextAction === $surveyNextActionEnum::SpecificQuestion &&
				isset($questionsByIdentifier[ $lo_answer->nextActionTarget ])
			) {
				$la_answersGraph[] = $questionsByIdentifier[ $lo_answer->nextActionTarget ]->identifier;
			}
		}

		return $la_answersGraph;
	}
}
