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
	public const ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'surveys';


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'success_message',
			'failure_message',
		],
		'realm' => Awyiss::REALM_FRONTEND,
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('Contents');

		$this->belongsTo('Forms', [
			'foreignKey' => 'formId',
		]);

		$this->hasMany('Pages');

		$this->hasMany('SurveyEntries', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'survey_id',
		]);

		$this->hasMany('SurveySurveyQuestions', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'survey_id',
			'saveStrategy' => 'replace',
		]);

		$this->hasMany('Widgets');
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
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
		$validator->add('finalAction', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
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
			'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique'),
		]);

		$rules->add(
			$rules->existsIn('formId', 'Forms', ['allowNullableNulls' => true]),
			'validFormId',
			[
				'errorField' => 'formId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_form_id'),
			]
		);


		$rules->add(
			function (Survey $entity): bool {
				return array_key_exists($entity->finalAction->value, $this->availableFinalActions());
			},
			'validFinalAction',
			[
				'errorField' => 'finalAction',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_final_action'),
			]
		);


		$rules->add(
			function (Survey $entity): bool {
				if ($entity->formId) {
					return true;
				}

				/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $ls_surveyNextActionEnum */
				$ls_surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

				if (
					in_array($entity->finalAction, [
						$ls_surveyNextActionEnum::ShowForm,
						$ls_surveyNextActionEnum::SaveAndShowForm,
						$ls_surveyNextActionEnum::ShowFormAndSave,
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
				return !collection($entity->surveySurveyQuestions)->some(function (SurveySurveyQuestion $question) use ($ls_surveyNextActionEnum): bool {
					if (
						in_array($question->nextAction, [
							$ls_surveyNextActionEnum::ShowForm,
							$ls_surveyNextActionEnum::SaveAndShowForm,
							$ls_surveyNextActionEnum::ShowFormAndSave,
						]) &&
						!$question->nextActionTarget
					) {
						return true;
					}

					if (!$question->surveySurveyAnswers) {
						return false;
					}

					return collection($question->surveySurveyAnswers)->some(function (SurveySurveyAnswer $answer) use ($ls_surveyNextActionEnum): bool {
						return $answer->nextAction && in_array($answer->nextAction, [
							$ls_surveyNextActionEnum::ShowForm,
							$ls_surveyNextActionEnum::SaveAndShowForm,
							$ls_surveyNextActionEnum::ShowFormAndSave,
						]) && !$answer->nextActionTarget;
					});
				});
			},
			'formIdSetWhenRequired',
			[
				'errorField' => 'formId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_form_id_set_when_required'),
			]
		);


		$rules->add(
			function (Survey $entity): bool {
				return !$entity->hasCycle();
			},
			'noCircularReferences',
			[
				'errorField' => 'surveySurveyQuestions',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_no_circular_references'),
			]
		);


		$rules->add(
			function (Survey $entity): bool {
				/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $ls_surveyTypeEnum */
				$ls_surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');

				if (
					$entity->type !== $ls_surveyTypeEnum::Linear ||
					!$entity->surveySurveyQuestions
				) {
					return true;
				}

				$la_questionIds = array_column($entity->surveySurveyQuestions, 'surveyQuestionId');

				return count($la_questionIds) === count(array_unique($la_questionIds));
			},
			'noRepeatedQuestionsInLinearSurvey',
			[
				'errorField' => 'surveySurveyQuestions',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_no_repeated_questions_in_linear_survey'),
			]
		);

		$rules->add(
			function (Survey $entity): bool {
				/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $ls_surveyTypeEnum */
				$ls_surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');

				if (!$entity->surveySurveyQuestions) {
					return true;
				}

				foreach ($entity->surveySurveyQuestions as $lo_question) {
					// Linear surveys should not have next actions set in any question
					if ($entity->type === $ls_surveyTypeEnum::Linear) {
						// Questions must not have next actions set in linear surveys
						if (!empty($lo_question->nextAction)) {
							return false;
						}

						// If the question has no answers, skip the next part
						if (!$lo_question->surveySurveyAnswers) {
							continue;
						}

						foreach ($lo_question->surveySurveyAnswers as $lo_answer) {
							// Answers must not have next actions set in linear surveys
							if (!empty($lo_answer->nextAction)) {
								return false;
							}
						}

						continue;
					}

					// For non-linear surveys, we need to check if the next action is valid
					if (
						// Empty next action is not allowed
						!$lo_question->nextAction ||
						// Neither is an unknown next action
						!array_key_exists($lo_question->nextAction->value, $this->availableNextActions())
					) {
						return false;
					}

					// If the question has no answers, skip the next part
					if (!$lo_question->surveySurveyAnswers) {
						continue;
					}

					foreach ($lo_question->surveySurveyAnswers as $lo_answer) {
						// For answers, empty next action is allowed but
						// unknown next action is not
						if (
							$lo_answer->nextAction &&
							(
								!$lo_answer->nextAction instanceof BackedEnum ||
								!array_key_exists($lo_answer->nextAction->value, $this->availableNextActions())
							)
						) {
							return false;
						}
					}
				}

				return true;
			},
			'noInvalidNextActions',
			[
				'errorField' => 'surveySurveyQuestions',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_no_invalid_next_actions'),
			]
		);


		$rules->addDelete(
			$rules->isNotLinkedTo('Contents', 'contents'),
			'noLinkedContents',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_contents'),
			]
		);

		$rules->addDelete(
			$rules->isNotLinkedTo('Pages', 'pages'),
			'noLinkedPages',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_pages'),
			]
		);


		$rules->addDelete(
			$rules->isNotLinkedTo('Widgets', 'widgets'),
			'noLinkedWidgets',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_widgets'),
			]
		);


		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $ls_surveyNextActionEnum */
		$ls_surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		$schema->setColumnType('final_action', EnumType::from($ls_surveyNextActionEnum));

		/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $ls_surveyTypeEnum */
		$ls_surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');

		$schema->setColumnType('type', EnumType::from($ls_surveyTypeEnum));
	}


	/**
	 * @return array
	 */
	public function availableFinalActions(): array {
		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $ls_surveyNextActionEnum */
		$ls_surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		return [
			$ls_surveyNextActionEnum::SaveAndEnd->value => $ls_surveyNextActionEnum::SaveAndEnd->label(),
			$ls_surveyNextActionEnum::ShowForm->value => $ls_surveyNextActionEnum::ShowForm->label(),
			$ls_surveyNextActionEnum::SaveAndShowForm->value => $ls_surveyNextActionEnum::SaveAndShowForm->label(),
			$ls_surveyNextActionEnum::ShowFormAndSave->value => $ls_surveyNextActionEnum::ShowFormAndSave->label(),
		];
	}


	/**
	 * @return array
	 */
	public function availableNextActions(): array {
		$la_nextActions = [];

		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $ls_surveyNextActionEnum */
		$ls_surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		foreach ($ls_surveyNextActionEnum::cases() as $le_nextAction) {
			$la_nextActions[ $le_nextAction->value ] = $le_nextAction->label();
		}

		return $la_nextActions;
	}
}
