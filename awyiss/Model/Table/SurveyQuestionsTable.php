<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\Validation\Validator;


/**
 * SurveyQuestions Model
 *
 * @property \Awyiss\Model\Table\SurveyAnswersTable&\Awyiss\ORM\Association\HasMany $SurveyAnswers
 * @property \Awyiss\Model\Table\SurveySurveyQuestionsTable&\Awyiss\ORM\Association\HasMany $SurveySurveyQuestions
 * @method \Awyiss\Model\Entity\SurveyQuestion newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class SurveyQuestionsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'survey_questions';


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
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'title',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('type');
		$validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->allowEmptyString('subtitle');
		$validator->add('subtitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->allowEmptyString('text');
		$validator->add('text', [
			'isScalar' => ['rule' => 'isScalar'],
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
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $ls_surveyQuestionTypeEnum */
		$ls_surveyQuestionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');

		$schema->setColumnType('type', EnumType::from($ls_surveyQuestionTypeEnum));
	}
}
