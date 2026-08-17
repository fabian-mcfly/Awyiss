<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * SurveyAnswers Model
 *
 * @property \Awyiss\Model\Table\SurveyQuestionsTable&\Awyiss\ORM\Association\BelongsTo $SurveyQuestions
 * @property \Awyiss\Model\Table\SurveySurveyAnswersTable&\Awyiss\ORM\Association\HasMany $SurveySurveyAnswers
 * @property \Awyiss\Model\Table\SurveySurveyQuestionsTable&\Awyiss\ORM\Association\BelongsTo $SurveySurveyQuestions
 * @method \Awyiss\Model\Entity\SurveyAnswer newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class SurveyAnswersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'survey_answers';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'associationName' => 'SurveyQuestions',
		'enabled' => true,
		'identifier' => 'question',
		'threaded' => false,
	];
	/**
	 * @inheritDoc
	 */
	protected array $search = [
		'blocklistedColumns' => ['surveyQuestionId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['surveyQuestionId'],
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
		$this->belongsTo('SurveyQuestions', [
			'foreignKey' => 'surveyQuestionId',
			'joinType' => 'INNER',
			'propertyName' => 'surveyQuestion',
		]);

		$this->hasMany('SurveySurveyAnswers', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'surveyAnswerId',
			'propertyName' => 'surveySurveyAnswers',
			'saveStrategy' => 'replace',
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
			'title',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('surveyQuestionId');
		$validator->add('surveyQuestionId', [
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
		$rules->addDelete(
			$rules->isNotLinkedTo('SurveySurveyAnswers', 'surveys'),
			'noLinkedSurveys',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_linked_surveys'),
			]
		);

		return $rules;
	}


	/**
	 * @return array
	 */
	public function getDisabledQuestions(): array {
		$disabled = [];

		/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $surveyQuestionTypeEnum */
		$surveyQuestionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');

		/**
		 * @var \Awyiss\Model\Entity\SurveyQuestion $category
		 */
		foreach ($this->getBehavior('Categories')->getCategories(true) as $category) {
			if (
				$category->type === $surveyQuestionTypeEnum::FreeText
				|| $category->type === $surveyQuestionTypeEnum::InfoText
			) {
				$disabled[] = $category->id;
			}
		}

		return $disabled;
	}
}
