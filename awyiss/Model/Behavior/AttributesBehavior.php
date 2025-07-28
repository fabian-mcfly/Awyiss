<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use BadMethodCallException;
use Cake\Collection\Iterator\MapReduce;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\SqliteSchemaDialect;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Hash;


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
			'beforeMarshal',
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
	 * @param array<string, mixed> $config The configuration settings provided to this behavior.
	 * @return void
	 */
	public function initialize(array $config): void {
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
	 * @param array $fields
	 * @param bool $inlcudeBaseFields
	 * @return array
	 */
	public function extractAttributeFields(array $fields, bool $inlcudeBaseFields = false): array {
		$la_columns = [];

		foreach ($fields as $ls_column) {
			if (str_starts_with($ls_column, 'attributes.')) {
				$la_columns[] = substr($ls_column, 11);
			}

			if ($inlcudeBaseFields && !str_contains($ls_column, '.')) {
				$la_columns[] = $ls_column;
			}
		}


		return $la_columns;
	}


	/**
	 * Adds where clauses for the provided list of attribute fields
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $keys
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findWithMatchingAttributes(SelectQuery $query, Entity $entity, array $keys): SelectQuery {
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();
		$ls_attributesTable = $lo_table->getAttributesTableName(true);
		$la_conditions = [];

		$lo_attributes = $entity->get('attributes');
		if (!$lo_attributes) {
			return $query;
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
			$query->where($la_conditions);
		}


		return $query;
	}


	/**
	 * @return \Awyiss\Model\Table
	 */
	public function getAttributesTable(): Table {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->table()->getAssociation($this->getAttributesTableName(true))?->getTarget();
	}


	/**
	 * @param bool $camelized
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function getAttributesTableName(bool $camelized = false): string {
		return $camelized ? Inflector::camelize($this->attributesTable) : $this->attributesTable;
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

		$lo_attributesQuery = $this->addOrderByFieldset($lo_attributesQuery, $lo_attributesTable);

		static::$attributes = $lo_attributesQuery->all()->groupBy('scope')->map(function ($attributes) {
			return collection($attributes)->indexBy('identifier')->toArray();
		})->toArray();

		return static::$attributes[ $ls_scope ] ?? [];
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		if (!$this->getConfig('isAttributesTable')) {
			return;
		}

		foreach ($this->getAttributes() as $lo_attribute) {
			if (!in_array($lo_attribute->inputType, ['input_list', 'input_key_value_list'])) {
				continue;
			}

			// If the attribute is not set, skip it
			if (!isset($data[ $lo_attribute->identifier ]) || !is_array($data[ $lo_attribute->identifier ])) {
				continue;
			}

			if ($lo_attribute->inputType === 'input_list') {
				/**
				 * Filter out all empty values
				 *
				 * @noinspection PhpVariableNamingConventionInspection
				 */
				$data[ $lo_attribute->identifier ] = array_filter($data[ $lo_attribute->identifier ], function ($lx_value) {
					return !empty($lx_value);
				});

				continue;
			}

			/**
			 * For key-value-list items, empty values are allowed
			 * if the key is set. And empty keys are allowed
			 * if the value is set.
			 *
			 * @noinspection PhpVariableNamingConventionInspection
			 */
			$data[ $lo_attribute->identifier ] = array_filter($data[ $lo_attribute->identifier ], function ($value) {
				return !empty($value['key']) || !empty($value['value']);
			});

			// If the data isn't empty, combine the key and value into a single array
			if ($data[ $lo_attribute->identifier ]) {
				/** @noinspection PhpVariableNamingConventionInspection */
				$data[ $lo_attribute->identifier ] = array_column($data[ $lo_attribute->identifier ], 'value', 'key');
			}
		}
	}


	/**
	 * Adds `validateValue()`-checks for each attribute
	 *
	 * @param Event $event
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return void
	 * @throws \ReflectionException
	 * @see \Awyiss\Attribute\AttributeOptions::validateValue
	 */
	public function buildRules(Event $event, RulesChecker|BaseRulesChecker $rules): void {
		if (!$this->getConfig('isAttributesTable')) {
			return;
		}

		/** @var \Awyiss\Model\Table $lo_subject */
		$lo_subject = $event->getSubject();
		$ls_source = substr($lo_subject->getTable(), 11);

		/** @var AttributeOptionsProvider $ls_attributeOptionsProvider */
		$ls_attributeOptionsProvider = $this->getConfig('attributeOptionsProviderClass');
		$lo_attributeOptions = static::$attributeOptions[ $ls_source ] = $ls_attributeOptionsProvider::getAttributeOptionsFile($ls_source, true);

		/** @var \Awyiss\Model\Entity\Attribute $lo_attribute */
		foreach ($this->getAttributes() as $lo_attribute) {
			if (!isset($lo_attributeOptions[ $lo_attribute->identifier ])) {
				if ($lo_attribute->required) {
					$rules->add(function (Entity $entity/*, array $options*/) use ($lo_attribute): bool|string {
						return !empty($entity->{$lo_attribute->identifier});
					}, 'validValue' . Inflector::camelize($lo_attribute->identifier), [
						'errorField' => $lo_attribute->identifier,
						'message' => __df($ls_source, 'attributes', 'error_valid_value'),
					]);
				}

				continue;
			}

			$rules->add(function (Entity $entity/*, array $options*/) use ($lo_attribute, $lo_attributeOptions): bool|string {
				/**
				 * @noinspection PhpUndefinedMethodInspection
				 * @noinspection PhpPossiblePolymorphicInvocationInspection
				 */
				return $lo_attributeOptions->validateValue($lo_attribute->identifier, $entity->get($lo_attribute->identifier), $entity->getEntity());
			}, 'validValue' . Inflector::camelize($lo_attribute->identifier), [
				'errorField' => $lo_attribute->identifier,
				'message' => __df($ls_source, 'attributes', 'error_valid_value'),
			]);
		}
	}


	/**
	 * @param EventInterface $event
	 * @param SelectQuery $query
	 * @param ArrayObject $options
	 * @param bool $primary
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void {
		if ($this->getConfig('isAttributesTable') || !$this->hasAttributes()) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($options, 'attributes'));

		if ($la_options['skip'] === true) {
			return;
		}

		/*if ($query->isEagerLoaded()) {
			throw new RuntimeException('Eager loaded associations should skip the attributes behavior');
		}*/

		$lb_containsI18n = isset($query->getContain()['I18n']);
		if ($lb_containsI18n) {
			$query->contain([
				$this->getAttributesTableName(true) => [
					'finder' => 'translations',
				],
			]);
		}
		else {
			$query->contain([
				$this->getAttributesTableName(true),
			]);
		}

		$query->mapReduce(function (array|Entity $entity, int $key, MapReduce $mapReduce): void {
			if (!is_a($entity, Entity::class)) {
				$mapReduce->emit($entity);


				return;
			}

			if (!$entity->attributes) {
				/** @var \Awyiss\ORM\Association\HasOne|\Awyiss\Model\Table $lo_association */
				$lo_association = $this->table()->{$this->getAttributesTableName(true)};

				$entity->attributes = $lo_association->newDefaultEntity();
			}

			/**
			 * @noinspection PhpUndefinedMethodInspection
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			if (isset($entity->attributes) && !$entity->attributes->getEntity()) {
				/**
				 * @noinspection PhpUndefinedMethodInspection
				 * @noinspection PhpPossiblePolymorphicInvocationInspection
				 */
				$entity->attributes->setEntity($entity);
			}

			$mapReduce->emit($entity);
		});
	}


	/**
	 * @param EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpUnused
	 */
	public function beforeCopy(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$lo_attributes = $entity->get('attributes');

		if ($this->getConfig('isAttributesTable') || !$lo_attributes) {
			return;
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $event->getSubject();

		$lo_attributes->unset((array)$lo_table->getPrimaryKey());
		$lo_attributes->setNew(true);
	}


	/**
	 * @param EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity/*, ArrayObject $options*/): void {
		if (!$this->getConfig('isAttributesTable')) {
			if ($this->getAttributes() && $entity->get('attributes')) {
				if ($entity->get('attributes')->isDirty()) {
					$entity->setDirty('attributes');
				}
			}

			return;
		}

		foreach ($this->getAttributes() as $lo_attribute) {
			if ($lo_attribute->inputType !== 'password') {
				continue;
			}

			if (!$entity->get($lo_attribute->identifier)) {
				$entity->setDirty($lo_attribute->identifier, false);
			}
			else {
				$entity->set(
					$lo_attribute->identifier,
					password_hash($entity->get($lo_attribute->identifier), PASSWORD_BCRYPT, ['cost' => 12])
				);
			}
		}
	}


	/**
	 * @param EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $event, EntityInterface $entity/*, ArrayObject $options*/): void {
		if (!$this->hasAttributes()) {
			return;
		}

		//If the `attributes`-property was set to false, delete the existings attributes for this entity
		if (!$entity->isNew() && !$entity->get('attributes')) {
			$this->table()->loadInto($entity, [$this->getAttributesTableName(true)]);

			if (!empty($entity->attributes) && !$entity->attributes->isNew()) {
				$this->getAttributesTable()->delete($entity->attributes);
				unset($entity->attributes);
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


	/**
	 * @param \Cake\ORM\Query\SelectQuery $attributesQuery
	 * @param \Awyiss\Model\Table\AttributesTable $attributesTable
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function addOrderByFieldset(SelectQuery $attributesQuery, Table\AttributesTable $attributesTable): SelectQuery {
		$lo_dialect = $attributesQuery->getConnection()->getDriver()->schemaDialect();

		/**
		 * SQLite does not support FIELD(),
		 * so ordering using CASE WHEN is used instead
		 */
		if ($lo_dialect instanceof SqliteSchemaDialect) {
			$la_fieldsets = $attributesTable->getAvailableFieldsets();

			$attributesQuery->orderBy(function (QueryExpression $exp) use ($la_fieldsets) {
				$li_index = 0;

				$lo_case = $exp->case();
				foreach ($la_fieldsets as $ls_fieldset) {
					$lo_case->when(['fieldset' => $ls_fieldset])->then($li_index, 'integer');

					$li_index++;
				}

				$lo_case->else(999, 'integer');

				return $lo_case;
			});

			return $attributesQuery;
		}

		/** @noinspection PhpUndefinedMethodInspection */
		$attributesQuery->orderByAsc($attributesQuery->newExpr($attributesQuery->func()->FIELD([
			'fieldset' => 'identifier',
			...$attributesTable->getAvailableFieldsets(),
		])));

		return $attributesQuery;
	}
}
