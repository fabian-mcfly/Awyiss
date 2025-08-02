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
 * @method \Awyiss\Model\Entity\UsergroupPermission newDefaultEntity(array $additionalData = [], array $options = [])
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
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'scope',
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('usergroupId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('scope');
		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('access', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 1]],
		]);


		$validator->allowEmptyArray('settings');
		$validator->add('settings', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($value) {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->existsIn(
				'usergroup_id',
				'Usergroups'
			),
			'usergroupExists',
			[
				'errorField' => 'usergroupId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_usergroup_exists'),
			]
		);


		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		//$schema->setColumnType('access', 'integer');
		$schema->setColumnType('access', EnumType::from(PermissionAccess::class));
		$schema->setColumnType('settings', 'json');
	}
}
