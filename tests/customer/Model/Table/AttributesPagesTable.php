<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Entity;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * AttributesPages Model
 *
 * @method \Customer\Model\Entity\AttributesPage newDefaultEntity(array $additionalData = [], array $options = [])
 */
class AttributesPagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'attributes_pages';


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->belongsTo('Pages', [
			'foreignKey' => 'pageId',
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
			->integer('pageId')
			->notEmptyString('pageId');

		$validator
			->date('date')
			->requirePresence('date', 'create')
			->notEmptyDate('date');

		$validator
			->scalar('teaser')
			->allowEmptyString('teaser');

		$validator
			->scalar('text')
			->allowEmptyString('text');

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(function (Entity $entity, array $options) use ($rules) {
			// Do not care about the pageId when copying a nested entity (isCopy and not primary)
			if (($options['_primary'] ?? false) === false && ($options['isCopy'] ?? false) === true) {
				return true;
			}

			$lo_existsIn = $rules->existsIn(['pageId'], 'Pages');

			return $lo_existsIn($entity, $options);
		}, 'validPageId', ['errorField' => 'pageId']);

		return $rules;
	}
}
