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
	public const TABLE = 'survey_answers';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'associationName' => 'SurveyQuestions',
		'enabled' => true,
		'identifier' => 'question',
	];
	/**
	 * @inheritDoc
	 */
	protected array $search = [
		'blocklistedColumns' => ['survey_question_id'],
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
			'foreignKey' => 'survey_question_id',
			'joinType' => 'INNER',
		]);

		$this->hasMany('SurveySurveyAnswers', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'survey_answer_id',
			'saveStrategy' => 'replace',
		]);

		$this->belongsTo('SurveySurveyQuestions', [
			'foreignKey' => 'survey_survey_question_id',
			'joinType' => 'INNER',
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
			'surveyQuestionId',
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
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_surveys'),
			]
		);

		return $rules;
	}


	/**
	 * @return array
	 */
	public function getDisabledQuestions(): array {
		$la_disabled = [];

		/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $ls_surveyQuestionTypeEnum */
		$ls_surveyQuestionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');

		/**
		 * @var \Awyiss\Model\Entity\SurveyQuestion $lo_category
		 */
		foreach ($this->getBehavior('Categories')->getCategories(true) as $lo_category) {
			if (
				$lo_category->type === $ls_surveyQuestionTypeEnum::FreeText ||
				$lo_category->type === $ls_surveyQuestionTypeEnum::InfoText
			) {
				$la_disabled[] = $lo_category->id;
			}
		}

		return $la_disabled;
	}
}
