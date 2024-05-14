<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupPermissions Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Awyiss\ORM\Association\BelongsTo $Usergroups
 * @method \Awyiss\Model\Entity\UsergroupPermission newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class UsergroupPermissionsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'usergroup_permissions';


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Usergroups', [
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


		$ao_validator->requirePresence([
			'scope',
			'identifier',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('usergroupId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('scope');
		$ao_validator->add('scope', [
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


		$ao_validator->add('access', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 1]],
		]);


		$ao_validator->allowEmptyArray('settings');
		$ao_validator->add('settings', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($ax_value) {
					return strlen(json_encode($ax_value)) <= 65535;
				},
			],
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
		$ao_rules->add(
			$ao_rules->existsIn(
				'usergroup_id',
				'Usergroups'
			),
			'usergroupExists',
			[
				'errorField' => 'usergroupId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_usergroup_exists'),
			]
		);


		return $ao_rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		//$ao_schema->setColumnType('access', 'integer');
		$ao_schema->setColumnType('access', EnumType::from(PermissionAccess::class));
		$ao_schema->setColumnType('settings', 'json');
	}
}
