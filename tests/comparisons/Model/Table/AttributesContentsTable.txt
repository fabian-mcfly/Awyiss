<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * AttributesContents Model
 *
 * @method \Customer\Model\Entity\AttributesContent newDefaultEntity(array $additionalData = [], array $options = [])
 */
class AttributesContentsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'attributes_contents';


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->belongsTo('Contents', [
			'foreignKey' => 'contentId',
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
			->integer('contentId')
			->notEmptyString('contentId');

		$validator
			->scalar('teaser')
			->allowEmptyString('teaser');

		$validator
			->scalar('freeText')
			->allowEmptyString('freeText');

		$validator
			->scalar('backgroundColor')
			->maxLength('backgroundColor', 50)
			->allowEmptyString('backgroundColor');

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['contentId'], 'Contents'), 'validContentId', ['errorField' => 'contentId']);

		return $rules;
	}
}
