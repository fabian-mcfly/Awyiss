<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Menus Model
 *
 * @property MenuEntriesTable&\Awyiss\ORM\Association\HasMany $AllMenuEntries
 * @property MenuEntriesTable&\Awyiss\ORM\Association\HasMany $MenuEntries
 * @method \Awyiss\Model\Entity\Menu newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 */
class MenusTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'menus';


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		//Used only internally to delete all entries, no matter the current language
		$this->hasMany('AllMenuEntries', [
			'cascadeCallbacks' => true,
			'className' => 'MenuEntries',
			'dependent' => true,
			'foreignKey' => 'menu_id',
		]);

		$this->hasMany('MenuEntries', [
			'finder' => 'forCurrentLanguage',
			'foreignKey' => 'menu_id',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'title',
			'identifier',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('identifier');
		$ao_validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['identifier']), 'identifierUnique', [
			'errorField' => 'identifier',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'menus', 'error_identifier_unique'),
		]);


		return $ao_rules;
	}


	/**
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function beforeSoftDelete(): void {
		$this->AllMenuEntries->disableCascadeCallbacks();
	}


	/**
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function beforeDelete(): void {
		$this->AllMenuEntries->disableCascadeCallbacks();
	}


	/**
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function afterSoftDelete(): void {
		$this->AllMenuEntries->enableCascadeCallbacks();
	}


	/**
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function afterDelete(): void {
		$this->AllMenuEntries->enableCascadeCallbacks();
	}
}
