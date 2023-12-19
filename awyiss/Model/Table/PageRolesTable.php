<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * PageRoles Model
 *
 * @method PageRole newDefaultEntity(array $aa_additionalData = [])
 */
class PageRolesTable extends Table {
	protected array $_defaultConfig = [
		'translate' => [
			'fields' => ['title'],
		],
	];
	/**
	 * @var array|string[] A list of identifiers a page role can't have, since they're used by the system
	 * or because they are template folder names
	 */
	protected array $blacklistedIdentifiers = [
		'cell',
		'element',
		'generic_pages',
		'layout',
	];
	public const TABLE = 'page_roles';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setPrimaryKey('id');
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return \Cake\Validation\Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('title')->maxLength('title', 32)->requirePresence('title', 'create')->notEmptyString('title');

		$ao_validator->scalar('identifier')->maxLength('identifier', 32)->requirePresence('identifier', 'create')->notEmptyString('identifier');

		$ao_validator->boolean('include_in_linklist')->notEmptyString('include_in_linklist');

		$ao_validator->integer('system_order')->requirePresence('system_order')->notEmptyString('system_order');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		return $ao_validator;
	}


	/**
	 * @todo add a rule for the identifier. it must not be a blacklisted one
	 *
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return \Awyiss\ORM\RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {



		return $ao_rules;
	}
}
