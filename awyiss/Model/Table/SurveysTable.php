<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Survey;
use Awyiss\Model\Enum\SurveyType;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Surveys Model
 *
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\BelongsTo $Forms
 * @property \Awyiss\Model\Table\SurveySurveyQuestionsTable&\Awyiss\ORM\Association\HasMany $SurveySurveyQuestions
 * @method \Awyiss\Model\Entity\Survey newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
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
		],
		'realm' => Awyiss::REALM_FRONTEND,
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Forms', [
			'foreignKey' => 'formId',
		]);

		$this->hasMany('SurveySurveyQuestions', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'survey_id',
			'saveStrategy' => 'replace',
		]);
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
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
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
		$rules->add(
			$rules->existsIn('formId', 'Forms', ['allowNullableNulls' => true]),
			'validFormId',
			[
				'errorField' => 'formId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_form_id'),
			]
		);


		$rules->add(
			function (Survey $entity, array $options) {
				/** @var class-string<\Awyiss\Model\Enum\SurveyType> $ls_surveyTypeEnum */
				$ls_surveyTypeEnum = App::className('SurveyType', 'Model/Enum');

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


		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\SurveyType> $ls_surveyTypeEnum */
		$ls_surveyTypeEnum = App::className('SurveyType', 'Model/Enum');

		$schema->setColumnType('type', EnumType::from($ls_surveyTypeEnum));
	}
}
