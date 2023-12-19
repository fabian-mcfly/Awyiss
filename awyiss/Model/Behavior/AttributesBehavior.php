<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Attributes\AttributeOptionsInterface;
use Awyiss\Attributes\AttributeOptionsProvider;
use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Table;
use Awyiss\Model\Table\AttributesTable;
use Awyiss\ORM\Association\HasOne;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Cake\Collection\Iterator\MapReduce;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;


/**
 * This behavior saves the old and the new values when updating entities into a separate database table.
 * It also sets information when creating, updating or deleting an entity.
 */
class AttributesBehavior extends Behavior {
	use LocatorAwareTrait;


	/**
	 * @var array<string, string|AttributeOptionsInterface>
	 */
	protected static array $attributeOptions;
	/**
	 * @var array<int, Attribute>
	 */
	protected array $attributes;
	/**
	 * The attributes table is name "attributes_<name>" with <name> being the current table's name.
	 *
	 * @var string
	 */
	protected string $attributesTable;
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'attributeOptionsProviderClass' => AttributeOptionsProvider::class,
		'foreignKey' => NULL,
		'implementedEvents' => [
			'Model.buildRules' => 'buildRules',
			'Model.beforeFind' => 'beforeFind',
			//'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
		],
		'implementedMethods' => [
			'getAttributes' => 'getAttributes',
			'getAttributesTable' => 'getAttributesTable',
			'hasAttributes' => 'hasAttributes',
		],
		'isAttributesTable' => FALSE,
		'sourceTable' => NULL,
	];
	/**
	 * A boolean value, indicating if the table has a corresponding attributes table.
	 *
	 * @var bool
	 */
	protected bool $hasAttributes = FALSE;


	/**
	 * Constructor hook method.
	 *
	 * @param array<string, mixed> $aa_config The configuration settings provided to this behavior.
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection*/
	public function initialize (array $aa_config): void {
		if ($this->getConfig('isAttributesTable')) {
			$this->initializeTranslate();

			return;
		}

		$this->attributesTable = 'attributes_' . $this->getConfig('sourceTable');

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		if ( ! $this->table()::ATTRIBUTABLE) {
			return;
		}

		$ls_identifier = Inflector::camelize($this->attributesTable);

		if ( ! App::className($ls_identifier, 'Model\Table', 'Table')) {
			return;
		}

		$this->hasAttributes = TRUE;
		$this->table()->hasOne($ls_identifier, [
			'cascadeCallbacks' => TRUE,
			//'className' => $ls_attributesClass,
			'dependent' => TRUE,
			'foreignKey' => $this->getConfig('foreignKey'),
			'propertyName' => 'attributes',
		]);
	}


	/**
	 * @param bool $ab_camelized
	 *
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function getAttributesTable (bool $ab_camelized = FALSE): string {
		return $ab_camelized ? Inflector::camelize(Inflector::tableize($this->attributesTable)) : $this->attributesTable;
	}


	/**
	 * @return bool
	 * @noinspection PhpUnused
	 */
	public function hasAttributes (): bool {
		return $this->hasAttributes;
	}


	/**
	 * @return array
	 */
	public function getAttributes (): array {
		if (isset($this->attributes)) {
			return $this->attributes;
		}

		if (!$this->getConfig('isAttributesTable')) {
			$ls_assocatiation = Inflector::camelize($this->attributesTable);

			if (! $this->table()->hasAssociation($ls_assocatiation)) {
				return [];
			}

			/** @var Table $lo_association */
			$lo_association = $this->table()->getAssociation($ls_assocatiation);

			return $lo_association->getAttributes();
		}

		$ls_scope = substr($this->table()->getTable(), 11);

		$lo_tableLocator = FactoryLocator::get('Table');

		/** @var AttributesTable $lo_attributesTable */
		$lo_attributesTable = $lo_tableLocator->get('Attributes');

		$lo_query = $lo_attributesTable->find();
		/** @noinspection PhpUndefinedMethodInspection */
		$this->attributes = $lo_query->where([
			'scope' => $ls_scope,
		])
		->applyOptions([
			'authorize' => [
				'skip' => TRUE,
			],
		])
		->orderByAsc($lo_query->newExpr(
			$lo_query->func()->FIELD(['fieldset' => 'identifier', ...$lo_attributesTable->getAvailableFieldsets()])
		))->all()->indexBy('identifier')->toArray();

		return $this->attributes;
	}


	/**
	 * @return void
	 */
	protected function initializeTranslate (): void {
		/** @var Table $lo_table */
		$lo_table = $this->table();

		$la_translatableFields = $lo_table->getConfig('translate.fields');
		foreach ($this->getAttributes() AS $lo_attribute) {
			if (!$lo_attribute->translatable) {
				continue;
			}

			$la_translatableFields[] = $lo_attribute->identifier;
		}

		$lo_table->setConfig('translate.fields', $la_translatableFields);
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 * Adds `validateValue()`-checks for each attribute
	 *
	 * @param Event $ao_event
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return RulesChecker
	 * @throws \ReflectionException
	 *
	 * @see \Awyiss\Attributes\AttributeOptions::validateValue
	 */
	public function buildRules (Event $ao_event, RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		if (!$this->getConfig('isAttributesTable')) {
			return $ao_rules;
		}

		/** @var Table $lo_subject */
		$lo_subject = $ao_event->getSubject();
		$ls_source = substr($lo_subject->getTable(), 11);

		/** @var AttributeOptionsProvider $ls_attributeOptionsProvider */
		$ls_attributeOptionsProvider = $this->getConfig('attributeOptionsProviderClass');
		$lo_attributeOptions = static::$attributeOptions[ $ls_source ] = $ls_attributeOptionsProvider::getAttributeOptionsFile($ls_source, TRUE);

		if (empty($lo_attributeOptions)) {
			return $ao_rules;
		}

		/** @var Attribute $lo_attribute */
		foreach ($this->getAttributes() as $lo_attribute) {
			if ( ! isset($lo_attributeOptions[ $lo_attribute->identifier ])) {
				continue;
			}

			$ao_rules->add(function(Entity $ao_entity/*, array $aa_options*/) use ($lo_attribute, $lo_attributeOptions): bool|string {
				return $lo_attributeOptions->validateValue($lo_attribute->identifier, $ao_entity->get($lo_attribute->identifier), $ao_entity);
			}, 'validValue', [
				'errorField' => $lo_attribute->identifier,
				'message' => __d('attributes', 'error_valid_value'),
			]);
		}

		return $ao_rules;
	}


	/**
	 * @param EventInterface $ao_event
	 * @param SelectQuery $ao_query
	 * @param \ArrayObject $ao_options
	 * @param bool $ab_primary
	 *
	 * @return void
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind (EventInterface $ao_event, SelectQuery $ao_query, \ArrayObject $ao_options, bool $ab_primary): void {
		if ($this->getConfig('isAttributesTable') || ! $this->hasAttributes()) {
			return;
		}

		$ao_query->contain([
			$this->getAttributesTable(TRUE) => [
				'finder' => [
					'all' => [
						'authorize' => [
							'skip' => TRUE,
						],
					],
				],
			],
		]);

		$ao_query->mapReduce(function(array|Entity $ao_entity, int $ai_key, MapReduce $ao_mapReduce) use ($ao_query): void {
			if (!is_a($ao_entity, Entity::class)) {
				$ao_mapReduce->emit($ao_entity);
				return;
			}

			if ( ! $ao_entity->attributes) {
				/** @var HasOne|Table $lo_association */
				$lo_association = $this->table()->{$this->getAttributesTable(TRUE)};

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$ao_entity->attributes = $lo_association->newDefaultEntity();

				/** @var static $ls_associationEntityClass */
				$ls_associationEntityClass = $lo_association->getEntityClass();

				/** @noinspection PhpUndefinedMethodInspection */
				$ls_foreignKey = $ls_associationEntityClass::mapField($lo_association->getForeignKey());

				$ao_entity->initAttributesField($lo_association, $ls_foreignKey);
			}

			$ao_mapReduce->emit($ao_entity);
		});
	}


	/*
	 * @param EventInterface  $ao_event
	 * @param EntityInterface $ao_entity
	 *
	 * @return void
	 *
	 * @noinspection PhpUnusedParameterInspection
	 *
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if (!$this->getConfig('isAttributesTable')) {
			return;
		}

		if (!$ao_entity->id && !$ao_entity->isNew()) {
			$ao_entity->setNew(TRUE);
		}
	}*/


	/**
	 * @param EventInterface  $ao_event
	 * @param EntityInterface $ao_entity
	 *
	 * @return void
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave (EventInterface $ao_event, Entity|\Cake\ORM\Entity $ao_entity/*, ArrayObject $ao_options*/): void {
		if (!$this->hasAttributes()) {
			return;
		}

		//If the `attributes`-property was set to FALSE, delete the existings attributes for this entity
		if (! $ao_entity->isNew() && ! $ao_entity->get('attributes')) {
			$this->table()->loadInto($ao_entity, [$this->getAttributesTable(TRUE)]);

			if ( ! empty($ao_entity->attributes) && ! $ao_entity->attributes->isNew()) {
				$this->fetchTable($this->getAttributesTable(TRUE))->delete($ao_entity->attributes);
				unset($ao_entity->attributes);
			}
		}
	}
}
