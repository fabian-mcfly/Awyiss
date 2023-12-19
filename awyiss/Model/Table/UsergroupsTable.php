<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Usergroup;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Usergroups Model
 *
 * @property \Awyiss\Model\Table\UsergroupPermissionsTable&\Cake\ORM\Association\HasMany $UsergroupPermissions
 *
 * @method Usergroup newDefaultEntity(array $aa_additionalData = [])
 */
class UsergroupsTable extends Table {
	protected array $_defaultConfig = [
		'translate' => [
			'fields' => ['title'],
		],
	];
	public const TABLE = 'usergroups';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setPrimaryKey('id');

		$this->hasMany('UsergroupPermissions')->setSaveStrategy('replace')->setDependent(TRUE);

		$this->belongsToMany('Users');
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

		$ao_validator->scalar('title')->maxLength('title', 255)->requirePresence('title', 'create')->notEmptyString('title');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return \Awyiss\ORM\RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['title']), ['errorField' => 'title']);

		return $ao_rules;
	}
}
