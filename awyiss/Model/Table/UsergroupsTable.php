<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Usergroups Model
 *
 * @property \Awyiss\Model\Table\UsergroupPermissionsTable&\Cake\ORM\Association\HasMany $UsergroupPermissions
 *
 * @method \Awyiss\Model\Entity\Usergroup newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\Usergroup patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class UsergroupsTable extends \Awyiss\Model\Table {
	protected array $_defaultConfig = [
		'translate' => [
			'fields' => ['title'],
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('usergroups');
		$this->setPrimaryKey('id');

		$this->hasMany('UsergroupPermissions')->setSaveStrategy('replace')->setDependent(TRUE);

		$this->belongsToMany('Users');
	}


	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['title']), ['errorField' => 'title']);

		return $ao_rules;
	}
}
