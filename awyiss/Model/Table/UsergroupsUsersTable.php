<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupsUsers Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Awyiss\ORM\Association\BelongsTo $Usergroups
 * @property \Awyiss\Model\Table\UsersTable&\Awyiss\ORM\Association\BelongsTo $Users
 * @method \Awyiss\Model\Entity\UsergroupsUser newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class UsergroupsUsersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'usergroups_users';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'isPivotTable' => true,
		'leftTable' => 'Users',
		'rightTable' => 'Usergroups',
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Usergroups', [
			'foreignKey' => 'usergroupId',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Users', [
			'foreignKey' => 'userId',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('usergroupId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('userId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn('usergroupId', 'Usergroups'), 'usergroupExists', [
			'errorField' => 'usergroupId',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_usergroup_exists'),
		]);


		$rules->add($rules->existsIn('userId', 'Users'), 'userExists', [
			'errorField' => 'userId',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_user_exists'),
		]);


		return $rules;
	}
}
