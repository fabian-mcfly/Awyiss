<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * AttributesGlobalContents Model
 *
 * @method \Customer\Model\Entity\AttributesGlobalContent newDefaultEntity(array $additionalData = [], array $options = [])
 */
class AttributesGlobalContentsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'attributes_global_contents';


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->belongsTo('GlobalContents', [
			'foreignKey' => 'globalContentId',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator
			->integer('globalContentId')
			->notEmptyString('globalContentId');

		$validator
			->scalar('teaser')
			->allowEmptyString('teaser');

		$validator
			->scalar('freeText')
			->allowEmptyString('freeText');

		$validator
			->scalar('freeTextInactive')
			->allowEmptyString('freeTextInactive');

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['globalContentId'], 'GlobalContent'), 'validGlobalContentId', ['errorField' => 'globalContentId']);

		return $rules;
	}
}
