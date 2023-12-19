<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * PageTemplates Model
 *
 * @property \Awyiss\Model\Table\PageRolesTable&\Cake\ORM\Association\BelongsTo $PageRoles
 *
 * @method PageTemplate newDefaultEntity(array $aa_additionalData = [])
 */
class PageTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'systemOrder' => [
			'relatedColumns' => ['page_role_id'],
		],
		'translate' => [
			'fields' => ['title'],
		],
	];
	public const TABLE = 'page_templates';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setPrimaryKey('id');

		$this->belongsTo('PageRoles', [
			'joinType' => 'INNER',
		]);
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

		$ao_validator->scalar('title')->maxLength('title', 100)->requirePresence('title', 'create')->notEmptyString('title');

		$ao_validator->scalar('filename')->maxLength('filename', 100)->requirePresence('filename', 'create')->notEmptyString('filename');

		$ao_validator->isArray('template_positions')->allowEmptyArray('template_positions');

		$ao_validator->integer('page_role_id')->requirePresence('page_role_id', 'create')->notEmptyString('page_role_id');

		$ao_validator->integer('system_order')->requirePresence('system_order')->notEmptyString('system_order');

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
		$ao_rules->add($ao_rules->isUnique(['filename']), ['errorField' => 'filename']);
		$ao_rules->add($ao_rules->existsIn(['page_role_id'], 'PageRoles', ['authorization' => ['skip' => TRUE]]), ['errorField' => 'page_role_id']);

		return $ao_rules;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _initializeSchema (TableSchemaInterface $ao_schema): TableSchemaInterface {
		$ao_schema->setColumnType('template_positions', 'json');

		return $ao_schema;
	}
}
