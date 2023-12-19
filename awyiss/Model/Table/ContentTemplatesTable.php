<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * ContentTemplates Model
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
	public const TABLE = 'content_templates';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
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
	 */
	public function beforeSave (\Cake\Event\EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): void {
		if ($ao_entity->available_elements === $ao_entity->getOriginal('available_elements')) {
			$ao_entity->setDirty('available_elements', FALSE);
		}

		if ($ao_entity->assigned_template_positions === $ao_entity->getOriginal('assigned_template_positions')) {
			$ao_entity->setDirty('assigned_template_positions', FALSE);
		}

		parent::beforeSave($ao_event, $ao_entity, $ao_options);
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
