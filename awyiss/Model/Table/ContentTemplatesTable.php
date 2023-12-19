<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * ContentTemplates Model
 *
 * @method ContentTemplate newDefaultEntity(array $aa_additionalData = [])
 */
class ContentTemplatesTable extends Table {
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

		$ao_validator->isArray('available_elements')->allowEmptyArray('available_elements');

		$ao_validator->isArray('assigned_template_positions')->allowEmptyArray('assigned_template_positions');

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

		return $ao_rules;
	}


	/**
	 * Before saving an entity, make sure the `available_elements` and `assigned_template_positions`-properties really have
	 * changed, since CakePHP will always mark a property containing an array as dirty.
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\ContentTemplate|\Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 *
	 * @return void
	 */
	public function beforeSave (EventInterface $ao_event, ContentTemplate|EntityInterface $ao_entity, ArrayObject $ao_options): void {
		parent::beforeSave($ao_event, $ao_entity, $ao_options);

		if ($ao_entity->available_elements === $ao_entity->getOriginal('available_elements')) {
			$ao_entity->setDirty('available_elements', FALSE);
		}

		if ($ao_entity->assigned_template_positions === $ao_entity->getOriginal('assigned_template_positions')) {
			$ao_entity->setDirty('assigned_template_positions', FALSE);
		}
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
