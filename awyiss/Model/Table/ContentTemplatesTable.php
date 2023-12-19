<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * ContentTemplates Model
 *
 * @property \Awyiss\Model\Table\AttributesTable&\Cake\ORM\Association\HasOne $Attributes
 *
 * @method \Awyiss\Model\Entity\ContentTemplate newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\ContentTemplate patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class ContentTemplatesTable extends \Awyiss\Model\Table {
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

		$this->setTable('content_templates');
		$this->setPrimaryKey('id');
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('title')->maxLength('title', 100)->requirePresence('title', 'create')->notEmptyString('title');

		$ao_validator->scalar('filename')->maxLength('filename', 100)->requirePresence('filename', 'create')->notEmptyString('filename');

		$ao_validator->isArray('available_elements')->allowEmptyArray('available_elements');

		$ao_validator->isArray('assigned_template_positions')->allowEmptyArray('assigned_template_positions');

		$ao_validator->integer('system_order')->requirePresence('system_order')->notEmptyString('system_order');

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
		$ao_rules->add($ao_rules->isUnique(['filename']), ['errorField' => 'filename']);

		return $ao_rules;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _initializeSchema (TableSchemaInterface $ao_schema): TableSchemaInterface {
		$ao_schema->setColumnType('available_elements', 'json');
		$ao_schema->setColumnType('assigned_template_positions', 'json');

		return $ao_schema;
	}
}
