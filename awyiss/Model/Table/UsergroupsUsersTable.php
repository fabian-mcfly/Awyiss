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
 * @method \Awyiss\Model\Entity\UsergroupsUser newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class UsergroupsUsersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'usergroups_users';


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Usergroups', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Users', [
			'joinType' => 'INNER',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('usergroupId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('userId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
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
		$ao_rules->add($ao_rules->existsIn('usergroupId', 'Usergroups'), 'usergroupExists', [
			'errorField' => 'usergroupId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_usergroup_exists'),
		]);


		$ao_rules->add($ao_rules->existsIn('userId', 'Users'), 'userExists', [
			'errorField' => 'userId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_user_exists'),
		]);


		return $ao_rules;
	}
}
