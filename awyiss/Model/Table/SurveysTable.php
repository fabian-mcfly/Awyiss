<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Survey;
use Awyiss\Model\Entity\SurveySurveyAnswer;
use Awyiss\Model\Entity\SurveySurveyQuestion;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use BackedEnum;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Surveys Model
 *
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\BelongsTo $Forms
 * @property \Awyiss\Model\Table\SurveySurveyQuestionsTable&\Awyiss\ORM\Association\HasMany $SurveySurveyQuestions
 * @property \Awyiss\Model\Table\SurveyEntriesTable&\Awyiss\ORM\Association\HasMany $SurveyEntries
 * @method \Awyiss\Model\Entity\Survey newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class SurveysTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'surveys';


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'successMessage',
			'failureMessage',
		],
		'realm' => Awyiss::REALM_FRONTEND,
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('Contents', [
			'foreignKey' => 'surveyId',
		]);

		$this->belongsTo('Forms', [
			'foreignKey' => 'formId',
		]);

		$this->hasMany('GlobalContents', [
			'foreignKey' => 'surveyId',
			'propertyName' => 'globalContents',
		]);

		$this->hasMany('Pages', [
			'foreignKey' => 'surveyId',
		]);

		$this->hasMany('SurveyEntries', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'surveyId',
			'propertyName' => 'surveyEntries',
		]);

		$this->hasMany('SurveySurveyQuestions', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'surveyId',
			'saveStrategy' => 'replace',
			'propertyName' => 'surveySurveyQuestions',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'title',
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('type');
		/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $surveyTypeEnum */
		$surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');
		$validator->add('type', [
			'enum' => ['rule' => ['enum', $surveyTypeEnum]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('successMessage', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('failureMessage', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->notEmptyString('finalAction');
		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $surveyNextActionEnum */
		$surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');
		$validator->add('finalAction', [
			'enum' => ['rule' => ['enum', $surveyNextActionEnum]],
		]);


		$validator->add('formId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->isUnique(['identifier']), 'identifierUnique', [
			'errorField' => 'identifier',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_identifier_unique'),
		]);

		$rules->add(
			$rules->existsIn('formId', 'Forms', ['allowNullableNulls' => true]),
			'validFormId',
			[
				'errorField' => 'formId',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_form_id'),
			]
		);


		$rules->add(
			function (Survey $entity): bool {
				/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $surveyTypeEnum */
				$surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');

				return in_array($entity->type, $surveyTypeEnum::cases());
			},
			'validType',
			[
				'errorField' => 'type',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_type'),
			]
		);


		$rules->add(
			function (Survey $entity): bool {
				if (!$entity->finalAction instanceof BackedEnum) {
					return false;
				}

				return array_key_exists($entity->finalAction->value, $this->availableFinalActions());
			},
			'validFinalAction',
			[
				'errorField' => 'finalAction',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_final_action'),
			]
		);


		$rules->add(
			function (Survey $entity): bool {
				if ($entity->formId) {
					return true;
				}

				/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $surveyNextActionEnum */
				$surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

				if (
					in_array($entity->finalAction, [
						$surveyNextActionEnum::ShowForm,
						$surveyNextActionEnum::SaveAndShowForm,
						$surveyNextActionEnum::ShowFormAndSave,
					])
				) {
					return false;
				}

				if (!$entity->surveySurveyQuestions) {
					return true;
				}

				/**
				 * If any question has a next action that is of type "Form",
				 * or if any answer has a next action that is of type "Form",
				 * then the survey must have a formId set.
				 */
				return !collection($entity->surveySurveyQuestions)->some(
					function (SurveySurveyQuestion $question) use ($surveyNextActionEnum): bool {
						if (
							in_array($question->nextAction, [
								$surveyNextActionEnum::ShowForm,
								$surveyNextActionEnum::SaveAndShowForm,
								$surveyNextActionEnum::ShowFormAndSave,
							])
							&& !$question->nextActionTarget
						) {
							return true;
						}

						if (!$question->surveySurveyAnswers) {
							return false;
						}

						return collection($question->surveySurveyAnswers)->some(
							function (SurveySurveyAnswer $answer) use ($surveyNextActionEnum): bool {
								return $answer->nextAction
									&& in_array($answer->nextAction, [
										$surveyNextActionEnum::ShowForm,
										$surveyNextActionEnum::SaveAndShowForm,
										$surveyNextActionEnum::ShowFormAndSave,
									])
									&& !$answer->nextActionTarget;
							}
						);
					}
				);
			},
			'formIdSetWhenRequired',
			[
				'errorField' => 'formId',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_form_id_set_when_required'),
			]
		);


		$rules->add(
			function (Survey $entity): bool {
				return !$entity->hasCycle();
			},
			'noCircularReferences',
			[
				'errorField' => 'surveySurveyQuestions',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_no_circular_references'),
			]
		);


		$rules->add(
			function (Survey $entity): bool {
				/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $surveyTypeEnum */
				$surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');

				if (
					$entity->type !== $surveyTypeEnum::Linear || !$entity->surveySurveyQuestions
				) {
					return true;
				}

				$questionIds = array_column($entity->surveySurveyQuestions, 'surveyQuestionId');

				return count($questionIds) === count(array_unique($questionIds));
			},
			'noRepeatedQuestionsInLinearSurvey',
			[
				'errorField' => 'surveySurveyQuestions',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_no_repeated_questions_in_linear_survey'),
			]
		);

		$rules->add(
			function (Survey $entity): bool {
				/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $surveyTypeEnum */
				$surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');

				if (!$entity->surveySurveyQuestions) {
					return true;
				}

				foreach ($entity->surveySurveyQuestions as $question) {
					// Linear surveys should not have next actions set in any question
					if ($entity->type === $surveyTypeEnum::Linear) {
						// Questions must not have next actions set in linear surveys
						if (!empty($question->nextAction)) {
							return false;
						}

						// If the question has no answers, skip the next part
						if (!$question->surveySurveyAnswers) {
							continue;
						}

						// Answers must not have next actions set in linear surveys
						if (array_any($question->surveySurveyAnswers, fn($answer) => !empty($answer->nextAction))) {
							return false;
						}

						continue;
					}

					// For non-linear surveys, we need to check if the next action is valid
					if (
						// Empty next action is not allowed
						!$question->nextAction
						// Neither is an unknown next action
						|| !array_key_exists($question->nextAction->value, $this->availableNextActions())
					) {
						return false;
					}

					// If the question has no answers, skip the next part
					if (!$question->surveySurveyAnswers) {
						continue;
					}

					// For answers, empty next action is allowed but unknown next action is not
					if (
						array_any(
							$question->surveySurveyAnswers,
							fn($answer) => $answer->nextAction
								&& (
									!$answer->nextAction instanceof BackedEnum
									|| !array_key_exists(
										$answer->nextAction->value,
										$this->availableNextActions()
									)
								)
						)
					) {
						return false;
					}
				}

				return true;
			},
			'noInvalidNextActions',
			[
				'errorField' => 'surveySurveyQuestions',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_no_invalid_next_actions'),
			]
		);


		$rules->addDelete(
			$rules->isNotLinkedTo('Contents', 'contents'),
			'noLinkedContents',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_linked_contents'),
			]
		);

		$rules->addDelete(
			$rules->isNotLinkedTo('Pages', 'pages'),
			'noLinkedPages',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_linked_pages'),
			]
		);


		$rules->addDelete(
			$rules->isNotLinkedTo('GlobalContents', 'globalContents'),
			'noLinkedGlobalContents',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_linked_global_contents'),
			]
		);


		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $surveyNextActionEnum */
		$surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		$schema->setColumnType('finalAction', EnumType::from($surveyNextActionEnum));

		/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $surveyTypeEnum */
		$surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');

		$schema->setColumnType('type', EnumType::from($surveyTypeEnum));
	}


	/**
	 * @return array
	 */
	public function availableFinalActions(): array {
		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $surveyNextActionEnum */
		$surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		return [
			$surveyNextActionEnum::SaveAndEnd->value => $surveyNextActionEnum::SaveAndEnd->label(),
			$surveyNextActionEnum::ShowForm->value => $surveyNextActionEnum::ShowForm->label(),
			$surveyNextActionEnum::SaveAndShowForm->value => $surveyNextActionEnum::SaveAndShowForm->label(),
			$surveyNextActionEnum::ShowFormAndSave->value => $surveyNextActionEnum::ShowFormAndSave->label(),
		];
	}


	/**
	 * @return array
	 */
	public function availableNextActions(): array {
		$nextActions = [];

		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $surveyNextActionEnum */
		$surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		foreach ($surveyNextActionEnum::cases() as $nextAction) {
			$nextActions[ $nextAction->value ] = $nextAction->label();
		}

		return $nextActions;
	}
}
