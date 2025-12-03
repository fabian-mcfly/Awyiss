<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\SurveyQuestion;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * SurveyQuestions Model
 *
 * @property \Awyiss\Model\Table\SurveyAnswersTable&\Awyiss\ORM\Association\HasMany $SurveyAnswers
 * @property \Awyiss\Model\Table\SurveySurveyQuestionsTable&\Awyiss\ORM\Association\HasMany $SurveySurveyQuestions
 * @method \Awyiss\Model\Entity\SurveyQuestion newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class SurveyQuestionsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'survey_questions';


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
		$this->hasMany('SurveyAnswers', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'survey_question_id',
			'saveStrategy' => 'replace',
		]);

		$this->hasMany('SurveySurveyQuestions', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'survey_question_id',
			'saveStrategy' => 'replace',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'type',
			'title',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('type');
		/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $surveyQuestionTypeEnum */
		$surveyQuestionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');
		$validator->add('type', [
			'enum' => ['rule' => ['enum', $surveyQuestionTypeEnum]],
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
			function (SurveyQuestion $entity): bool {
				/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $surveyQuestionTypeEnum */
				$surveyQuestionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');

				return in_array($entity->type, $surveyQuestionTypeEnum::cases());
			},
			'validType',
			[
				'errorField' => 'type',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_type'),
			]
		);


		$rules->addDelete(
			$rules->isNotLinkedTo('SurveySurveyQuestions', 'surveys'),
			'noLinkedSurveys',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_surveys'),
			]
		);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $surveyQuestionTypeEnum */
		$surveyQuestionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');

		$schema->setColumnType('type', EnumType::from($surveyQuestionTypeEnum));
	}
}
