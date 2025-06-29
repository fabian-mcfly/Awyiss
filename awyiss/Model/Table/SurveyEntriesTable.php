<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * SurveyEntries Model
 *
 * @property \Awyiss\Model\Table\SurveysTable&\Awyiss\ORM\Association\BelongsTo $Survey
 * @method \Awyiss\Model\Entity\SurveyEntry newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class SurveyEntriesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'survey_entries';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'associationName' => 'Surveys',
		'enabled' => true,
		'identifier' => 'survey',
	];
	/**
	 * @inheritDoc
	 */
	protected array $search = [
		'blocklistedColumns' => ['survey_id', 'page_id'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Surveys');

		$this->belongsTo('Pages', [
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
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
			'surveyId',
			'ipHash',
			'postHash',
			'identifier',
		], 'create');


		$validator->notEmptyString('surveyId');
		$validator->add('surveyId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->allowEmptyString('pageId');
		$validator->add('pageId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('data', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->notEmptyString('ipHash');
		$validator->add('ipHash', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 40]],
		]);


		$validator->notEmptyString('postHash');
		$validator->add('postHash', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 40]],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 40]],
		]);


		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->existsIn('surveyId', 'Surveys'),
			'surveyExists',
			[
				'errorField' => 'surveyId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_survey_exists'),
			]
		);

		$rules->add(
			$rules->existsIn('pageId', 'Pages', ['allowNullableNulls' => true]),
			'pageExists',
			[
				'errorField' => 'pageId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_page_exists'),
			]
		);

		return $rules;
	}
}
