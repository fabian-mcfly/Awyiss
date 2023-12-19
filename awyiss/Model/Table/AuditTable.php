<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Validation\Validator;


/**
 * Audit Model
 *
 * @method \Awyiss\Model\Entity\Audit newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\Audit patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class AuditTable extends \Awyiss\Model\Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'audit';
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'audit' => [
			'enabled' => FALSE,
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('type')->requirePresence('type', 'create')->notEmptyString('type');

		$ao_validator->scalar('scope')->maxLength('model', 50)->requirePresence('scope', 'create')->notEmptyString('scope');

		$ao_validator->allowEmptyArray('data_old');

		$ao_validator->allowEmptyArray('data_new');

		$ao_validator->allowEmptyArray('diff');

		$ao_validator->integer('parent_id')->requirePresence('parent_id')->notEmptyString('parent_id');

		$ao_validator->integer('created_by')->notEmptyString('created_by');

		$ao_validator->dateTime('created_on')->requirePresence('created_on', 'create')->notEmptyDateTime('created_on');

		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _initializeSchema (TableSchemaInterface $ao_schema): TableSchemaInterface {
		$ao_schema->setColumnType('data_old', 'json');
		$ao_schema->setColumnType('data_new', 'json');
		$ao_schema->setColumnType('diff', 'json');

		return $ao_schema;
	}
}
