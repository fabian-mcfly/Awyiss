<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\SurveySurveyAnswer;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * SurveySurveyAnswers Model
 *
 * @property \Awyiss\Model\Table\SurveyAnswersTable&\Awyiss\ORM\Association\BelongsTo $SurveyAnswers
 * @property \Awyiss\Model\Table\SurveySurveyQuestionsTable&\Awyiss\ORM\Association\BelongsTo $SurveySurveyQuestions
 * @method \Awyiss\Model\Entity\SurveySurveyAnswer newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class SurveySurveyAnswersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'survey_survey_answers';


	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['surveySurveyQuestionId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'subtitle',
			'text',
		],
		'realm' => Awyiss::REALM_FRONTEND,
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('SurveyAnswers', [
			'foreignKey' => 'surveyAnswerId',
			'joinType' => 'INNER',
			'propertyName' => 'surveyAnswer',
		]);

		$this->belongsTo('SurveySurveyQuestions', [
			'foreignKey' => 'surveySurveyQuestionId',
			'joinType' => 'INNER',
			'propertyName' => 'surveySurveyQuestion',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'surveyAnswerId',
			// No 'surveySurveyQuestionId', since it's gets set automatically as it is a foreign key
			'systemOrder',
		], 'create');

		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('surveyAnswerId');
		$validator->add('surveyAnswerId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('surveySurveyQuestionId');
		$validator->add('surveySurveyQuestionId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->allowEmptyString('title');
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


		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $surveyNextActionEnum */
		$surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');
		$validator->add('nextAction', [
			'enum' => ['rule' => ['enum', $surveyNextActionEnum]],
		]);


		$validator->add('nextActionTarget', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('systemOrder', [
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
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			function (SurveySurveyAnswer $entity/*, array $options*/): string|bool {
				$tableLocator = FactoryLocator::get('Table');
				// Check if the given answer id is part of the question
				$query = $tableLocator
					->get('SurveySurveyQuestions')
					->find()
					->innerJoinWith('SurveyQuestions.SurveyAnswers')
					->where([
						'SurveySurveyQuestions.id' => $entity->surveySurveyQuestionId,
						'SurveyAnswers.id' => $entity->surveyAnswerId,
					])
				;

				return $query->count() > 0;
			},
			'validSurveyAnswerId',
			[
				'errorField' => 'surveyAnswerId',
				'message' => __df('Surveys', 'Validation', 'error_valid_survey_answer_id'),
			]
		);

		$rules->add(
			$rules->existsIn('surveySurveyQuestionId', 'SurveySurveyQuestions'),
			'validSurveySurveyQuestionId',
			[
				'errorField' => 'surveySurveyQuestionId',
				'message' => __df('Surveys', 'Validation', 'error_valid_survey_survey_question_id'),
			]
		);


		$rules->add(
			function (SurveySurveyAnswer $entity): bool {
				if ($entity->nextAction === null) {
					return true;
				}

				/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $surveyNextActionEnum */
				$surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

				return in_array($entity->nextAction, $surveyNextActionEnum::cases());
			},
			'validNextAction',
			[
				'errorField' => 'nextAction',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_next_action'),
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

		$schema->setColumnType('nextAction', EnumType::from($surveyNextActionEnum));
	}
}
