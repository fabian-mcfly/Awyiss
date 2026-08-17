<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use BadMethodCallException;
use Cake\Collection\Iterator\MapReduce;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Hash;
use Cake\Utility\Security;


/**
 * This behavior saves the old and the new values when updating entities into a separate database table.
 * It also sets information when creating, updating or deleting an entity.
 */
class AttributesBehavior extends Behavior {
	use LocatorAwareTrait;


	/**
	 * @var array<string, string|\Awyiss\Attribute\AttributeOptionsCollectionInterface>
	 */
	protected static array $attributeOptions;
	/**
	 * @var array<string, array<string, \Awyiss\Model\Entity\Attribute>>
	 */
	protected static array $attributes;


	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
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
	 * The attributes table is name "attributes_<name>" with <name> being the current table's name.
	 *
	 * @var string
	 */
	protected string $attributesTable;
	/**
	 * A boolean value, indicating if the table has a corresponding attributes table.
	 *
	 * @var bool
	 */
	protected bool $hasAttributes = false;


	/**
	 * Constructor hook method.
	 *
	 * @param array<string, mixed> $config The configuration settings provided to this behavior.
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->attributesTable = 'attributes_' . $this->getConfig('sourceTable');

		if ($this->getConfig('isAttributesTable') || !$this->table()::ATTRIBUTABLE) {
			return;
		}

		$identifier = Inflector::camelize($this->attributesTable);

		if (!App::className($identifier, 'Model\Table', 'Table')) {
			return;
		}

		$this->hasAttributes = true;
		$this
			->table()
			->hasOne($identifier, [
				'cascadeCallbacks' => true,
				//'className' => $attributesClass,
				'dependent' => true,
				'foreignKey' => $this->getConfig('foreignKey'),
				'propertyName' => 'attributes',
			])
		;

		$finders = $this->getConfig('implementedFinders');

		foreach ($this->getAttributes() as $attribute) {
			if (!in_array($attribute->type, ['date', 'datetime', 'time'])) {
				continue;
			}

			$name = Inflector::camelize($attribute->identifier);
			$finders[ 'future' . $name ] = 'future' . $name;
			$finders[ 'past' . $name ] = 'past' . $name;
		}

		$this->setConfig('implementedFinders', $finders);
	}


	/**
	 * Get all attribute fields from an array.
	 *
	 * @param array $fields
	 * @param bool $inlcudeBaseFields
	 * @return array
	 */
	public function extractAttributeFields(array $fields, bool $inlcudeBaseFields = false): array {
		$columns = [];

		foreach ($fields as $column) {
			if (str_starts_with($column, 'attributes.')) {
				$columns[] = substr($column, 11);
			}

			if ($inlcudeBaseFields && !str_contains($column, '.')) {
				$columns[] = $column;
			}
		}


		return $columns;
	}


	/**
	 * Adds where clauses for the provided list of attribute fields
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \Awyiss\Model\Entity $entity
	 * @param array $fields
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findWithMatchingAttributes(SelectQuery $query, Entity $entity, array $fields): SelectQuery {
		$attributesTableName = $this->table()->getAttributesTableName(true);
		$conditions = [];

		$attributes = $entity->get('attributes');
		if (!$attributes) {
			return $query;
		}

		foreach ($this->extractAttributeFields($fields, true) as $field) {
			if (!$attributes->has($field)) {
				continue;
			}

			$value = $attributes->get($field);

			if ($value === null) {
				$field .= ' IS';
			}

			$conditions[ $attributesTableName . '.' . $field ] = $value;
		}

		if ($conditions) {
			$query->where($conditions);
		}


		return $query;
	}


	/**
	 * @return \Awyiss\Model\Table
	 */
	public function getAttributesTable(): Table {
		if ($this->getConfig('isAttributesTable')) {
			return $this->table();
		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this
			->table()
			->getAssociation($this->getAttributesTableName(true))
			?->getTarget()
		;
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
			$assocatiationName = Inflector::camelize($this->attributesTable);

			if (!$this->table()->hasAssociation($assocatiationName)) {
				return [];
			}

			/** @var \Awyiss\Model\Table $association */
			$association = $this->table()->getAssociation($assocatiationName);


			return $association->getAttributes();
		}

		$scope = Inflector::camelize(substr($this->table()->getTable(), 11));

		if (isset(static::$attributes)) {
			return static::$attributes[ $scope ] ?? [];
		}

		/** @var \Awyiss\Model\Table\AttributesTable $attributesTable */
		$attributesTable = FactoryLocator::get('Table')->get('Attributes');
		$attributesQuery = $attributesTable->find('all');

		static::$attributes = $attributesQuery
			->all()
			->groupBy('scope')
			->map(function (array $attributes): array {
				return collection($attributes)->indexBy('identifier')->toArray();
			})
			->toArray()
		;

		return static::$attributes[ $scope ] ?? [];
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

		$unmappedData = $data->getArrayCopy();

		foreach ($this->getAttributes() as $attribute) {
			if (!in_array($attribute->inputType, ['inputList', 'inputKeyValueList'])) {
				continue;
			}
			// If the attribute is not set, skip it
			if (!isset($unmappedData[ $attribute->identifier ]) || !is_array($unmappedData[ $attribute->identifier ])) {
				continue;
			}

			/**
			 * Unset any mapped fields as the identifier will be used
			 * and is already an underscored version of the field name.
			 */
			unset($data[ $attribute->identifier ]);

			if ($attribute->inputType === 'inputList') {
				/**
				 * Filter out all empty values
				 */
				$data[ $attribute->identifier ] = array_values(
					array_filter($unmappedData[ $attribute->identifier ], function (mixed $value): bool {
						return !empty($value) || $value === '0' || $value === 0;
					})
				);

				continue;
			}

			/**
			 * For key-value-list items, empty values are allowed
			 * if the key is set. And empty keys are allowed
			 * if the value is set.
			 */
			$data[ $attribute->identifier ] = array_filter($unmappedData[ $attribute->identifier ], function (array $value): bool {
				return (
						!empty($value['key'])
						|| $value['key'] === '0'
						|| $value['key'] === 0
					)
					|| (
						!empty($value['value'])
						|| $value['value'] === '0'
						|| $value['value'] === 0
					);
			});

			// If the data isn't empty, combine the key and value into a single array
			if ($data[ $attribute->identifier ]) {
				$data[ $attribute->identifier ] = array_column($data[ $attribute->identifier ], 'value', 'key');
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
	 * @see \Awyiss\Attribute\AttributeOption::validateValue
	 */
	public function buildRules(Event $event, RulesChecker|BaseRulesChecker $rules): void {
		if (!$this->getConfig('isAttributesTable')) {
			return;
		}

		/** @var \Awyiss\Model\Table $subject */
		$subject = $event->getSubject();
		$source = substr($subject->getTable(), 11);

		/** @var AttributeOptionsProvider $attributeOptionsProvider */
		$attributeOptionsProvider = $this->getConfig('attributeOptionsProviderClass');
		/** @var \Awyiss\Attribute\AttributeOptionsCollectionInterface $attributeOptions */
		$attributeOptions = static::$attributeOptions[ $source ] = $attributeOptionsProvider::getAttributeOptionsFile($source, true);

		/** @var \Awyiss\Model\Entity\Attribute $attribute */
		foreach ($this->getAttributes() as $attribute) {
			$identifier = AttributeOptionsProvider::sanitizeIdentifier($attribute->identifier);

			if (!isset($attributeOptions[ $identifier ])) {
				if ($attribute->required) {
					$rules->add(function (Entity $entity/*, array $options*/) use ($attribute, $identifier): bool|string {
						return !empty($entity->{$identifier});
					}, 'validValue' . Inflector::camelize($identifier), [
						'errorField' => $identifier,
						'message' => __df($source, 'Validation', 'error_valid_value'),
					]);
				}

				continue;
			}

			$rules->add(function (Entity $entity/*, array $options*/) use ($attribute, $attributeOptions, $identifier): bool|string {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				return $attributeOptions->validateValue($identifier, $entity->get($identifier), $entity->getEntity());
			}, 'validValue' . Inflector::camelize($identifier), [
				'errorField' => $attribute->identifier,
				'message' => __df($source, 'Validation', 'error_valid_value'),
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

		$queryOptions = Hash::merge($this->getConfig(), Hash::get($options, 'attributes'));

		if ($queryOptions['skip'] === true) {
			return;
		}

		$containsI18n = isset($query->getContain()['I18n']);
		if ($containsI18n) {
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
				$this->findAttributes($entity);
			}

			/**
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			if ($entity->attributes && !$entity->attributes->getEntity()) {
				/**
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
		$attributes = $entity->get('attributes');

		if ($this->getConfig('isAttributesTable') || !$attributes) {
			return;
		}

		/** @var \Awyiss\Model\Table $table */
		$table = $event->getSubject();

		$attributes->unset((array)$table->getPrimaryKey());
		$attributes->setNew(true);
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

		foreach ($this->getAttributes() as $attribute) {
			if ($attribute->inputType !== 'password') {
				continue;
			}

			if (!$entity->get($attribute->identifier)) {
				$entity->setDirty($attribute->identifier, false);
				continue;
			}

			$password = $entity->get($attribute->identifier);

			$passwordHasher = new DefaultPasswordHasher();
			$passwordHasher->setConfig('hashOptions', [
				'cost' => 14,
			]);

			if (Configure::read('Security.prehashPassword', false) && Security::getSalt()) {
				$password = hash_hmac('sha256', $password, Security::getSalt());
			}

			$entity->set($attribute->identifier, $passwordHasher->hash($password));
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

		//If the `attributes`-property was set to false, delete the existing attributes for this entity
		if (!$entity->isNew() && !$entity->get('attributes')) {
			$this->table()->loadInto($entity, [$this->getAttributesTableName(true)]);

			if (!empty($entity->attributes) && !$entity->attributes->isNew()) {
				$this->getAttributesTable()->delete($entity->attributes);
				unset($entity->attributes);
			}
		}
	}


	/**
	 * Finds and sets the attributes entity for the provided entity.
	 *
	 * If the entity is a page and not of the current page role,
	 * the attributes entity of the page role will be fetched before
	 * setting a default attributes entity.
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @return void
	 */
	protected function findAttributes(Entity $entity): void {
		$table = $this->table();

		// If the entity is not a Page, just return the default attributes entity
		if (!$entity instanceof Page) {
			/** @var \Awyiss\Model\Table $attributesTable */
			$attributesTable = $table->{$table->getAttributesTableName(true)};
			$entity->attributes = $attributesTable->newDefaultEntity();

			return;
		}

		$alias = $this->table()->getAlias();

		if (str_starts_with($alias, 'Child')) {
			$alias = substr($alias, 5);
		}
		elseif (str_starts_with($alias, 'Parent')) {
			$alias = substr($alias, 6);
		}

		$pageRole = $entity->pageRoleId ? Inflector::pluralize($entity->pageRoleId->name) : $alias;
		// If the page role matches the current table, return the default attributes entity
		if ($alias === $pageRole) {
			/** @var \Awyiss\Model\Table $attributesTable */
			$attributesTable = $table->{$table->getAttributesTableName(true)};
			$entity->attributes = $attributesTable->newDefaultEntity();

			return;
		}

		/** @var \Awyiss\Model\Table\PagesTable $pageRoleTable */
		$pageRoleTable = $this->fetchTable($pageRole);

		/** @var \Awyiss\Model\Table|null $table */
		if (!$pageRoleTable->hasAttributes()) {
			return;
		}

		// No id? Just return the default attributes entity
		if (!$entity->id) {
			/** @var \Awyiss\Model\Table $attributesTable */
			$attributesTable = $pageRoleTable->{$pageRoleTable->getAttributesTableName(true)};
			$entity->attributes = $attributesTable->newDefaultEntity();

			return;
		}

		/** @var \Awyiss\Model\Table|\Awyiss\ORM\Association\HasOne $attributesTable */
		$attributesTable = $pageRoleTable->{$pageRoleTable->getAttributesTableName(true)};

		$attributes = $attributesTable
			->find('all')
			->where([
				$attributesTable->getForeignKey() => $entity->id,
			])
			->first()
		;

		$entity->attributes = $attributes ?? $attributesTable->newDefaultEntity();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity->attributes->setEntity($entity);
	}


	/**
	 * Dynamic finders for past and future date, time and datetime attributes.
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
		$method = Inflector::underscore($method);
		preg_match('/^(future|past)_(\w+)/', $method, $matches);

		$now = $date ?? new DateTime('now');

		$attributesTable = $this->table()->getAttributesTableName(true);
		$field = $matches[2];

		$comparator = $matches[1] === 'future' ? ' >=' : ' <=';

		return $query->where([
			$attributesTable . '.' . $field . $comparator => $now,
		]);
	}


	/**
	 * @param string $method name of the method to be invoked
	 * @param array $args List of arguments passed to the function
	 * @return SelectQuery
	 * @throws \BadMethodCallException
	 */
	public function __call(string $method, array $args): SelectQuery {
		$finders = $this->getConfig('implementedFinders');
		if (isset($finders[ $method ])) {
			return $this->_dynamicFinder($method, ...$args);
		}

		throw new BadMethodCallException(
			sprintf('Unknown method `%s` called on `%s`', $method, static::class)
		);
	}
}
