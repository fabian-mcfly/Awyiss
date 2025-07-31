<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * SurveySurveyQuestions Model
 *
 * @property \Awyiss\Model\Table\SurveysTable&\Awyiss\ORM\Association\BelongsTo $Surveys
 * @property \Awyiss\Model\Table\SurveyQuestionsTable&\Awyiss\ORM\Association\BelongsTo $SurveyQuestions
 * @property \Awyiss\Model\Table\SurveySurveyAnswersTable&\Awyiss\ORM\Association\HasMany $SurveySurveyAnswers
 * @method \Awyiss\Model\Entity\SurveySurveyQuestion newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class SurveySurveyQuestionsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'survey_survey_questions';


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'subtitle',
			'text',
			'customAnswerTitle',
		],
		'realm' => Awyiss::REALM_FRONTEND,
	];

	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Surveys', [
			'foreignKey' => 'survey_id',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('SurveyQuestions', [
			'foreignKey' => 'survey_question_id',
			'joinType' => 'INNER',
		]);

		$this->hasMany('SurveySurveyAnswers', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'survey_survey_question_id',
			'saveStrategy' => 'replace',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Awyiss\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Awyiss\Validation\Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('surveyId');
		$validator->add('surveyId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('surveyQuestionId');
		$validator->add('surveyQuestionId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'exactLength' => [
				'message' => __df($this->getI18nDomain(), 'validation', 'error_exact_length', 8),
				'rule' => function (string $identifier): bool {
					return strlen($identifier) == 8;
				},
			],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->allowEmptyString('subtitle');
		$validator->add('subtitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->allowEmptyString('text');
		$validator->add('text', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('nextAction', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('nextActionTarget', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('allowCustomAnswer', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->allowEmptyString('customAnswerTitle');
		$validator->add('customAnswerTitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
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
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->isUnique(['identifier', 'surveyId']), 'identifierUnique', [
			'errorField' => 'identifier',
			'message' => __df('surveys', 'validation', 'error_identifier_unique'),
		]);

		$rules->add(
			$rules->existsIn('surveyId', 'Surveys'),
			'validSurveyId',
			[
				'errorField' => 'surveyId',
				'message' => __df('surveys', 'validation', 'error_valid_survey_id'),
			]
		);

		$rules->add(
			$rules->existsIn('surveyQuestionId', 'SurveyQuestions'),
			'validSurveyQuestionId',
			[
				'errorField' => 'surveyQuestionId',
				'message' => __df('surveys', 'validation', 'error_valid_survey_question_id'),
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

		$schema->setColumnType('next_action', EnumType::from($ls_surveyNextActionEnum));
	}
}
