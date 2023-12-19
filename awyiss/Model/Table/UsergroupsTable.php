<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Validation\Validator;


/**
 * Usergroups Model
 *
 * @property \Awyiss\Model\Table\UsergroupPermissionsTable&\Cake\ORM\Association\HasMany $UsergroupPermissions
 *
 * @method \Awyiss\Model\Entity\Usergroup newDefaultEntity()
 * @method \Awyiss\Model\Entity\Usergroup newEmptyEntity()
 * @method \Awyiss\Model\Entity\Usergroup newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Usergroup[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Usergroup get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\Usergroup findOrCreate($search, ?callable $callback = NULL, $options = [])
 * @method \Awyiss\Model\Entity\Usergroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Usergroup[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Usergroup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Usergroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Usergroup[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Usergroup[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Usergroup[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Usergroup[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @property \Awyiss\Model\Table\UsergroupsUsersTable&\Cake\ORM\Association\HasMany $UsergroupsUsers
 * @property \Awyiss\Model\Table\UsersTable&\Cake\ORM\Association\BelongsToMany $Users
 */
class UsergroupsTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('usergroups');
		$this->setDisplayField('title');
		$this->setPrimaryKey('id');

		$this->hasMany('UsergroupPermissions')->setSaveStrategy('replace')->setDependent(TRUE);

		$this->belongsToMany('Users');
	}


	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $ao_validator Validator instance.
	 *
	 * @return \Cake\Validation\Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('title')->maxLength('title', 255)->requirePresence('title', 'create')->notEmptyString('title');

		$ao_validator->boolean('active')->notEmptyString('active');

		return $ao_validator;
	}
}
