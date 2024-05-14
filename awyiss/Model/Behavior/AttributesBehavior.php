<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use BadMethodCallException;
use Cake\Collection\Iterator\MapReduce;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Entity as BaseEntity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;


/**
 * This behavior saves the old and the new values when updating entities into a separate database table.
 * It also sets information when creating, updating or deleting an entity.
 */
class AttributesBehavior extends Behavior {
	use LocatorAwareTrait;


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
		'foreignKey' => null,
		'implementedFinders' => [
			'withMatchingAttributes' => 'findWithMatchingAttributes',
		],
		'implementedEvents' => [
			'buildRules',
			'beforeFind',
			'beforeCopy',
			'beforeSave',
			'afterSave',
		],
		'implementedMethods' => [
			'extractAttributeFields' => 'extractAttributeFields',
			'getAttributes' => 'getAttributes',
			'getAttributesTable' => 'getAttributesTable',
			'getAttributesTableName' => 'getAttributesTableName',
			'hasAttributes' => 'hasAttributes',
		],
		'isAttributesTable' => false,
		'skip' => false,
		'sourceTable' => null,
	];
	/**
	 * A boolean value, indicating if the table has a corresponding attributes table.
	 *
	 * @var bool
	 */
	protected bool $hasAttributes = false;


	/**
	 * @var array<string, string|\Awyiss\Attribute\AttributeOptionsInterface>
	 */
	protected static array $attributeOptions;
	/**
	 * @var array<string, array<string, \Awyiss\Model\Entity\Attribute>>
	 */
	protected static array $attributes;


	/**
	 * Constructor hook method.
	 *
	 * @param array<string, mixed> $aa_config The configuration settings provided to this behavior.
	 * @return void
	 */
	public function initialize(array $aa_config): void {
		if ($this->getConfig('isAttributesTable')) {
			return;
		}

		$this->attributesTable = 'attributes_' . $this->getConfig('sourceTable');

		if (!$this->table()::ATTRIBUTABLE) {
			return;
		}

		$ls_identifier = Inflector::camelize($this->attributesTable);

		if (!App::className($ls_identifier, 'Model\Table', 'Table')) {
			return;
		}

		$this->hasAttributes = true;
		$this->table()->hasOne($ls_identifier, [
			'cascadeCallbacks' => true,
			//'className' => $ls_attributesClass,
			'dependent' => true,
			'foreignKey' => $this->getConfig('foreignKey'),
			'propertyName' => 'attributes',
		]);

		$la_finders = $this->getConfig('implementedFinders');

		foreach ($this->getAttributes() as $lo_attribute) {
			if (!in_array($lo_attribute->type, ['date', 'datetime', 'time'])) {
				continue;
			}

			$ls_name = Inflector::camelize($lo_attribute->identifier);
			$la_finders[ 'future' . $ls_name ] = 'future' . $ls_name;
			$la_finders[ 'past' . $ls_name ] = 'past' . $ls_name;
		}

		$this->setConfig('implementedFinders', $la_finders);
	}


	/**
	 * Get all attribute fields from an array.
	 *
	 * @param array $aa_fields
	 * @param bool $ab_inlcudeBaseFields
	 * @return array
	 */
	public function extractAttributeFields(array $aa_fields, bool $ab_inlcudeBaseFields = false): array {
		$la_columns = [];

		foreach ($aa_fields as $ls_column) {
			if (str_starts_with($ls_column, 'attributes.')) {
				$la_columns[] = substr($ls_column, 11);
			}

			if ($ab_inlcudeBaseFields && !str_contains($ls_column, '.')) {
				$la_columns[] = $ls_column;
			}
		}


		return $la_columns;
	}


	/**
	 * Adds where clauses for the provided list of attribute fields
	 *
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $keys
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findWithMatchingAttributes(SelectQuery $ao_query, Entity $entity, array $keys): SelectQuery {
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();
		$ls_attributesTable = $lo_table->getAttributesTableName(true);
		$la_conditions = [];

		$lo_attributes = $entity->get('attributes');
		if (!$lo_attributes) {
			return $ao_query;
		}

		foreach ($this->extractAttributeFields($keys, true) as $ls_field) {
			if (!$lo_attributes->has($ls_field)) {
				continue;
			}

			$lx_value = $lo_attributes->get($ls_field);

			if ($lx_value === null) {
				$ls_field .= ' IS';
			}

			$la_conditions[ $ls_attributesTable . '.' . $ls_field ] = $lx_value;
		}

		if ($la_conditions) {
			$ao_query->where($la_conditions);
		}


		return $ao_query;
	}


	/**
	 * @param bool $ab_camelized
	 * @return \Awyiss\Model\Table
	 */
	public function getAttributesTable(): Table {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->table()->getAssociation($this->getAttributesTableName(true))?->getTarget();
	}


	/**
	 * @param bool $ab_camelized
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function getAttributesTableName(bool $ab_camelized = false): string {
		return $ab_camelized ? Inflector::camelize($this->attributesTable) : $this->attributesTable;
	}


	/**
	 * @return bool
	 * @noinspection PhpUnused
	 */
	public function hasAttributes(): bool {
		return $this->hasAttributes;
	}


	/**
	 * @return array
	 */
	public function getAttributes(): array {
		if (!$this->getConfig('isAttributesTable')) {
			$ls_assocatiation = Inflector::camelize($this->attributesTable);

			if (!$this->table()->hasAssociation($ls_assocatiation)) {
				return [];
			}

			/** @var \Awyiss\Model\Table $lo_association */
			$lo_association = $this->table()->getAssociation($ls_assocatiation);


			return $lo_association->getAttributes();
		}

		$ls_scope = substr($this->table()->getTable(), 11);

		if (isset(static::$attributes)) {
			return static::$attributes[ $ls_scope ] ?? [];
		}

		/** @var \Awyiss\Model\Table\AttributesTable $lo_attributesTable */
		$lo_attributesTable = FactoryLocator::get('Table')->get('Attributes');
		$lo_attributesQuery = $lo_attributesTable->find('all');

		/**
		 * @noinspection PhpUndefinedMethodInspection
		 */
		$lo_attributesQuery = $lo_attributesQuery->orderByAsc($lo_attributesQuery->newExpr($lo_attributesQuery->func()->FIELD([
			'fieldset' => 'identifier',
			...$lo_attributesTable->getAvailableFieldsets(),
		])));

		static::$attributes = $lo_attributesQuery->all()->groupBy('scope')->map(function ($aa_attributes) {
			return collection($aa_attributes)->indexBy('identifier')->toArray();
		})->toArray();

		return static::$attributes[ $ls_scope ] ?? [];
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 * Adds `validateValue()`-checks for each attribute
	 *
	 * @param Event $ao_event
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @throws \ReflectionException
	 * @see \Awyiss\Attribute\AttributeOptions::validateValue
	 */
	public function buildRules(Event $ao_event, RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		if (!$this->getConfig('isAttributesTable')) {
			return $ao_rules;
		}

		/** @var \Awyiss\Model\Table $lo_subject */
		$lo_subject = $ao_event->getSubject();
		$ls_source = substr($lo_subject->getTable(), 11);

		/** @var AttributeOptionsProvider $ls_attributeOptionsProvider */
		$ls_attributeOptionsProvider = $this->getConfig('attributeOptionsProviderClass');
		$lo_attributeOptions = static::$attributeOptions[ $ls_source ] = $ls_attributeOptionsProvider::getAttributeOptionsFile($ls_source, true);

		/** @var \Awyiss\Model\Entity\Attribute $lo_attribute */
		foreach ($this->getAttributes() as $lo_attribute) {
			if (!isset($lo_attributeOptions[ $lo_attribute->identifier ])) {
				if ($lo_attribute->required) {
					$ao_rules->add(function (Entity $ao_entity/*, array $aa_options*/) use ($lo_attribute): bool|string {
						return !empty($ao_entity->{$lo_attribute->identifier});
					}, 'validValue' . Inflector::camelize($lo_attribute->identifier), [
						'errorField' => $lo_attribute->identifier,
						'message' => __df($ls_source, 'attributes', 'error_valid_value'),
					]);
				}

				continue;
			}

			$ao_rules->add(function (Entity $ao_entity/*, array $aa_options*/) use ($lo_attribute, $lo_attributeOptions): bool|string {
				/**
				 * @noinspection PhpUndefinedMethodInspection
				 * @noinspection PhpPossiblePolymorphicInvocationInspection
				 */
				return $lo_attributeOptions->validateValue($lo_attribute->identifier, $ao_entity->get($lo_attribute->identifier), $ao_entity->getEntity());
			}, 'validValue' . Inflector::camelize($lo_attribute->identifier), [
				'errorField' => $lo_attribute->identifier,
				'message' => __df($ls_source, 'attributes', 'error_valid_value'),
			]);
		}


		return $ao_rules;
	}


	/**
	 * @param EventInterface $ao_event
	 * @param SelectQuery $ao_query
	 * @param ArrayObject $ao_options
	 * @param bool $ab_primary
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options, bool $ab_primary): void {
		if ($this->getConfig('isAttributesTable') || !$this->hasAttributes()) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'attributes'));

		if ($la_options['skip'] === true) {
			return;
		}

		/*if ($ao_query->isEagerLoaded()) {
			throw new RuntimeException('Eager loaded associations should skip the attributes behavior');
		}*/

		$lb_containsI18n = isset($ao_query->getContain()['I18n']);
		if ($lb_containsI18n) {
			$ao_query->contain([
				$this->getAttributesTableName(true) => [
					'finder' => 'translations',
				],
			]);
		}
		else {
			$ao_query->contain([
				$this->getAttributesTableName(true),
			]);
		}

		$ao_query->mapReduce(function (array|Entity $ao_entity, int $ai_key, MapReduce $ao_mapReduce) use ($ao_query): void {
			if (!is_a($ao_entity, Entity::class)) {
				$ao_mapReduce->emit($ao_entity);


				return;
			}

			if (!$ao_entity->attributes) {
				/** @var \Awyiss\ORM\Association\HasOne|\Awyiss\Model\Table $lo_association */
				$lo_association = $this->table()->{$this->getAttributesTableName(true)};

				$ao_entity->attributes = $lo_association->newDefaultEntity();

				/** @var static $ls_associationEntityClass */
				$ls_associationEntityClass = $lo_association->getEntityClass();

				/** @noinspection PhpUndefinedMethodInspection */
				$ls_foreignKey = $ls_associationEntityClass::mapField($lo_association->getForeignKey());

				$ao_entity->initAttributesField($lo_association, $ls_foreignKey);
			}

			/**
			 * @noinspection PhpUndefinedMethodInspection
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			if (isset($ao_entity->attributes) && !$ao_entity->attributes->getEntity()) {
				/**
				 * @noinspection PhpUndefinedMethodInspection
				 * @noinspection PhpPossiblePolymorphicInvocationInspection
				 */
				$ao_entity->attributes->setEntity($ao_entity);
			}

			$ao_mapReduce->emit($ao_entity);
		});
	}


	/**
	 * @param EventInterface $ao_event
	 * @param \Awyiss\Model\Entity|\Cake\ORM\Entity $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeCopy(EventInterface $ao_event, Entity|BaseEntity $ao_entity, ArrayObject $ao_options): void {
		$lo_attributes = $ao_entity->get('attributes');

		if ($this->getConfig('isAttributesTable') || !$lo_attributes) {
			return;
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_attributes->unset((array)$lo_table->getPrimaryKey());
		$lo_attributes->setNew(true);
	}


	/**
	 * @param EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $ao_event, Entity|BaseEntity $ao_entity/*, ArrayObject $ao_options*/): void {
		if (!$this->getConfig('isAttributesTable')) {
			return;
		}

		foreach ($this->getAttributes() as $lo_attribute) {
			if ($lo_attribute->inputType !== 'password') {
				continue;
			}

			if (!$ao_entity->get($lo_attribute->identifier)) {
				$ao_entity->setDirty($lo_attribute->identifier, false);
			}
			else {
				$ao_entity->set(
					$lo_attribute->identifier,
					password_hash($ao_entity->get($lo_attribute->identifier), PASSWORD_BCRYPT, ['cost' => 12])
				);
			}
		}
	}


	/**
	 * @param EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $ao_event, Entity|BaseEntity $ao_entity/*, ArrayObject $ao_options*/): void {
		if (!$this->hasAttributes()) {
			return;
		}

		//If the `attributes`-property was set to false, delete the existings attributes for this entity
		if (!$ao_entity->isNew() && !$ao_entity->get('attributes')) {
			$this->table()->loadInto($ao_entity, [$this->getAttributesTableName(true)]);

			if (!empty($ao_entity->attributes) && !$ao_entity->attributes->isNew()) {
				$this->getAttributesTable()->delete($ao_entity->attributes);
				unset($ao_entity->attributes);
			}
		}
	}


	/**
	 * Dynamic finders for past and future dates, times and datetimes
	 *
	 * Calling `$table->find('pastTestattribute');`
	 * will return all entities where the `testattribute` is in the past.
	 *
	 * Calling `$table->find('futureTestattribute', new \Cake\I18n\DateTime('2028-12-31 23:59:59'));`
	 * will return all entities where the `testattribute` is in the future,
	 * using the provided date as reference.
	 *
	 * @param string $method
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param mixed|null $date
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function _dynamicFinder(string $method, SelectQuery $query, mixed $date = null): SelectQuery {
		$ls_method = Inflector::underscore($method);
		preg_match('/^(future|past)_(\w+)/', $ls_method, $la_matches);

		$lo_now = $date ?? new DateTime('now');

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();
		$ls_attributesTable = $lo_table->getAttributesTableName(true);
		$ls_field = $la_matches[2];

		$ls_comparator = $la_matches[1] === 'future' ? ' >=' : ' <=';

		return $query->where([
			$ls_attributesTable . '.'  . $ls_field . $ls_comparator => $lo_now,
		]);
	}


	/**
	 * @param string $method name of the method to be invoked
	 * @param array $args List of arguments passed to the function
	 * @return SelectQuery
	 * @throws \BadMethodCallException
	 */
	public function __call(string $method, array $args): SelectQuery {
		$la_finders = $this->getConfig('implementedFinders');
		if (isset($la_finders[ $method ])) {
			return $this->_dynamicFinder($method, ...$args);
		}

		throw new BadMethodCallException(
			sprintf('Unknown method `%s` called on `%s`', $method, static::class)
		);
	}
}
