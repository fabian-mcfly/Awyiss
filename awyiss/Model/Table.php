<?php declare(strict_types=1);


namespace Awyiss\Model;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Event\EventManager;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Behavior\Translate\EavStrategy;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Trait\SqlTraceTrait;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\BehaviorRegistry;
use Awyiss\ORM\Marshaller;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use Awyiss\Validation\Validator;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Exception\RolledbackTransactionException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table as BaseTable;
use Cake\Utility\Hash;
use Cake\Validation\Validator as BaseValidator;
use Closure;
use Exception;
use RuntimeException;


/**
 * Base Table
 *
 * @method \Cake\ORM\Query\SelectQuery findById(int $id)
 * @method \Cake\ORM\Query\SelectQuery addSystemOrderQueryConditions(?SelectQuery $query, \Cake\Datasource\EntityInterface $entity)
 * @method \Awyiss\Authorization\AuthorizationServiceInterface getAuthorizationService()
 * @method int countAuditCount(\Cake\Datasource\EntityInterface $entity)
 * @method array<\Awyiss\Model\Entity\Audit> getAuditData(\Cake\Datasource\EntityInterface $entity)
 * @method array<string> getAuditHistoryFields()
 * @method int getHighestSystemOrder(\Cake\Datasource\EntityInterface $entity)
 * @method string|\Awyiss\Authorization\Policy\AbstractGenericPolicy|null getPolicyClass()
 * @method array getSystemOrderRelatedColumns(?\Cake\Datasource\EntityInterface $entity = null)
 * @method array hasDirtySystemOrderRelatedColumns(?\Cake\Datasource\EntityInterface $entity = null)
 * @method array extractAttributeFields(array $fields, bool $includeBaseFields = false)
 * @method \Awyiss\Model\Entity\Attribute[] getAttributes()
 * @method \Awyiss\Model\Table getAttributesTable()
 * @method string getAttributesTableName(bool $camelized = false)
 * @method bool hasAttributes()
 * @method \Cake\Datasource\ResultSetInterface|array|null getCategories(bool $returnRaw = false)
 * @method \Awyiss\Model\Entity newDefaultEntity(array $additionalData = [])
 * @method \Cake\Datasource\EntityInterface|array rebuildMediaAssignments(\Cake\Datasource\EntityInterface|array $entity, bool $useMediaEntity = false)
 * @method \Cake\Collection\CollectionInterface listNested(\Cake\ORM\Query\SelectQuery|\Cake\Collection\Iterator\TreeIterator $query, string $nestingKey = 'children', string $direction = 'desc')
 * @method array getPossibleFieldValues(string $column, ?String $type = null)
 * @method SelectQuery searchFilterQuery(SelectQuery $query, ?array $filterColumns = null)
 * @method array<string, \Awyiss\Model\Behavior\Search\FilterColumnSettings> getFilterColumns(array $blocklistedColumns = [], ?array $selectedOperators = null, ?array $selectedValues = null, bool $includePossibleValues = true)
 * @method string normalizeColumnType(string $type)
 * @method bool searchIsActive()
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class Table extends BaseTable {
	use IdentityAwareTrait;
	use InstanceConfigTrait;
	use SqlTraceTrait;


	/**
	 * Allows \Awyiss\Model\Table\AttributesTable to set attributes for this table.
	 *
	 * @var bool
	 * @see \Awyiss\Model\Table\AttributesTable::getAvailableScopes()
	 */
	public const bool ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const string RULES_CLASS = RulesChecker::class;
	/**
	 * Name of the database table. Used in static::initialize() ($this->setTable(static::TABLE)) and in
	 * \Awyiss\Model\Table\AttributesTable::getAvailableScopes()
	 *
	 * @see \Awyiss\Model\Table\AttributesTable::getAvailableScopes()
	 * @var string
	 */
	public const string TABLE = '';


	/**
	 * @var array Settings for the AttributesBehavior
	 */
	protected array $attributes = [];
	/**
	 * @var array Settings for the AuditBehavior
	 */
	protected array $audit = [];
	/**
	 * @var array Settings for the AutoPrefixBehavior
	 */
	protected array $autoPrefix = [];
	/**
	 * @var array Settings for the CategoriesBehavior
	 */
	protected array $categories = [];
	/**
	 * @var array<string> A list of properties that will be merged with values from the database configuration
	 */
	protected array $customConfigProperties = [
		'categories',
		'eventTrigger',
		'nest',
		'publicationData',
		'systemOrder',
	];
	/**
	 * The default values set for this table
	 *
	 * @var array
	 */
	protected array $_defaultConfig = []; // phpcs:ignore
	/**
	 * This array contains all implemented events and their corresponding method names
	 * that will get called when the event is fired.
	 *
	 * @var array
	 */
	protected array $defaultEvents = [
		'beforeMarshal',
		'afterMarshal',
		'beforeFind',
		'buildValidator',
		'beforeRules',
		'afterRules',
		'beforeSave',
		'beforeCreate',
		'beforeUpdate',
		'afterSave',
		'afterSaveCommit',
		'beforeDelete',
		'afterDelete',
		'afterDeleteCommit',
		'beforeSoftDelete',
		'afterSoftDelete',
		'afterSoftDeleteCommit',
	];
	/**
	 * @var array Settings for the DefaultValuesBehavior
	 */
	protected array $defaultValues = [];
	/**
	 * @var array Settings for the EventTriggerBehavior
	 */
	protected array $eventTrigger = [];
	/**
	 * @var array Settings for the PublicationDataBehavior
	 */
	protected array $publicationData = [];
	/**
	 * Validator class.
	 *
	 * @var string
	 */
	protected string $_validatorClass = Validator::class; // phpcs:ignore
	/**
	 * @var string
	 */
	protected string $i18nDomain;
	/**
	 * @var array Settings for the NestBehavior
	 */
	protected array $nest = [];
	/**
	 * @var array Settings for the SearchBehavior
	 */
	protected array $search = [];
	/**
	 * @var array Settings for the SoftDeleteBehavior
	 */
	protected array $softDelete = [];
	/**
	 * @var array Settings for the SystemOrderBehavior
	 */
	protected array $systemOrder = [];
	/**
	 * @var array Settings for the TranslateBehavior
	 */
	protected array $translate = [];


	/**
	 * @inheritDoc
	 */
	public function __construct(array $config = []) {
		if (($this->_defaultConfig['implementedEvents'] ?? null) === null) {
			$this->setConfig('implementedEvents', $this->defaultEvents);
		}

		parent::__construct($config + [
			'behaviors' => new BehaviorRegistry(),
			'eventManager' => new EventManager(),
		]);
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function initialize(array $config): void {
		if (static::TABLE) {
			$this->setTable(static::TABLE);
		}

		if (!$this->getTable()) {
			return;
		}

		$this->setPrimaryKey('id');

		//Do not load behaviors for translation associations
		if (str_ends_with($this->getTable(), '_translation') || str_ends_with($this->getAlias(), '_translation')) {
			return;
		}

		$this->initializeAssociations();

		/** @noinspection PhpStrictTypeCheckingInspection, PhpParamsInspection, PhpUndefinedFieldInspection */
		$sourceTable = isset($this->pageRole) ? Inflector::tableize($this->pageRole->name) : $this->getTable();

		// Merge the config properties with custom configuration from the database
		foreach ($this->customConfigProperties as $property) {
			$path = implode('.', ['Awyiss', Inflector::camelize($sourceTable), Awyiss::REALM_BACKEND, $property]);
			$customConfig = Configure::read($path);

			if ($customConfig && is_array($this->$property ?? null)) {
				/** @noinspection PhpParamsInspection */
				$this->$property = Hash::merge($this->$property, $customConfig);
			}
		}

		$isAttributesTable = str_starts_with($this->getTable(), 'attributes_');

		$schema = $this->getSchema();

		if ($isAttributesTable) {
			$this->addAttributesBehavior();
		}
		else {
			$this->addAttributesBehavior($sourceTable);

			$this->addBehavior('Audit', $this->audit + ['priority' => 999999]);

			if ($schema->getColumn('deleted')) {
				$this->addBehavior('SoftDelete', $this->softDelete);
			}

			$this->addCategoriesBehavior();

			$this->addBehavior('EventTrigger', $this->eventTrigger);

			if ($schema->getColumn('system_order')) {
				$this->addBehavior('SystemOrder', $this->systemOrder);
			}

			if (
				!str_starts_with($this->getTable(), 'media') &&
				!in_array($this->getTable(), [
					'audit',
					'publication_data',
				])
			) {
				$this->addBehavior('MediaAssignment');
				$this->addBehavior('MediaElementAssignment');
			}
		}

		$this->addBehavior('AutoPrefix', $this->autoPrefix + ['priority' => 999999]);
		$this->addBehavior('DefaultValues', $this->defaultValues);

		if ($this->nest) {
			$this->addBehavior('Nest', $this->nest);
		}

		if ($this->getTable() !== 'publication_data') {
			$this->addBehavior('PublicationData', $this->publicationData);
		}

		$this->addBehavior('Search', $this->search);

		if (!empty($this->translate['fields'])) {
			$this->addTranslateBehavior();
		}

		$this->initializeSchema($schema);
	}


	/**
	 * Custom finder method used to retrieve all translations for the found records.
	 * Fetched translations can be filtered by locale by passing the `locales` key
	 * in the options array.
	 *
	 * Translated values will be found for each entity under the property `_translations`,
	 * containing an array indexed by locale name.
	 *
	 * ### Example:
	 * ```
	 * $article = $articles->find('translations', locales: ['eng', 'deu'])->first();
	 * $englishTranslatedFields = $article->get('_translations')['eng'];
	 * ```
	 *
	 * If the `locales` array is not passed, it will bring all translations found
	 * for each record.
	 *
	 * If no translate behavior is attached to the table, this finder will do nothing
	 * to prevent errors.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array $locales
	 * @return \Cake\ORM\Query\SelectQuery
	 * @see \Cake\ORM\Behavior\TranslateBehavior::findTranslations()
	 */
	public function findTranslations(SelectQuery $query, array $locales = []): SelectQuery {
		if ($this->hasBehavior('Translate')) {
			return $this->getBehavior('Translate')->findTranslations($query, $locales);
		}


		return $query;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findActive(SelectQuery $query): SelectQuery {
		if (!$this->getSchema()->getColumn('active')) {
			throw new RuntimeException(sprintf('Cannot use `findActive` on table `%s`', $this->getAlias()));
		}

		$query->where(['active' => true]);


		return $query;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string|false|null $languageShortcode A language shortcode to filter by, null to use the current language, or false to not filter by language.
	 * @param \Awyiss\Model\Entity\MediaFolder|null $entity
	 * @param bool $includeGlobal
	 * @return \Cake\ORM\Query\SelectQuery
	 * @throws \Exception
	 */
	public function findForCurrentLanguage(SelectQuery $query, string|false|null $languageShortcode = null, ?Entity $entity = null, bool $includeGlobal = true): SelectQuery {
		$languageShortcode ??= LocaleMiddleware::getLanguage()->shortcode;

		if ($languageShortcode === false) {
			$languageShortcode = null;
		}

		if ($entity) {
			$languageShortcode = $entity->languageShortcode;
		}

		if ($includeGlobal && $languageShortcode) {
			return $query->where([
				'OR' => [
					'language_shortcode' => $languageShortcode,
					'language_shortcode IS' => null,
				],
			]);
		}


		return $query->where([
			'language_shortcode' . ($languageShortcode ? '' : ' IS') => $languageShortcode,
		]);
	}


	/**
	 * Re-implemented so it'll use `\Awyiss\Core\App::className()` to find the entity class.
	 * Looks for the
	 *
	 * @return class-string<\Cake\Datasource\EntityInterface>
	 * @see \Cake\ORM\Table::getEntityClass()
	 * @see \Awyiss\Core\App::className()
	 */
	public function getEntityClass(): string {
		if (!$this->_entityClass) {
			$default = Inflector::classify($this->_table);
			$parts = explode('\\', static::class);

			if (static::class === self::class || count($parts) < 3) {
				return $this->_entityClass = Entity::class;
			}

			$alias = Inflector::classify(Inflector::underscore(substr(array_pop($parts), 0, -5)));
			$name = '\\' . implode('\\', array_slice($parts, 0, -1)) . '\\Entity\\' . $alias;

			/** @var class-string<\Cake\Datasource\EntityInterface>|null $class */
			$class = App::className($alias, 'Model/Entity');
			if (!$class) {
				$class = App::className($default, 'Model/Entity');
			}

			if (!$class) {
				throw new MissingEntityException([$name]);
			}

			$this->_entityClass = $class;
		}


		return $this->_entityClass;
	}


	/**
	 * Re-implemented 1:1 so it'll use \Awyiss\ORM\Association\BelongsTo
	 *
	 * @inheritDoc
	 * @return \Awyiss\ORM\Association\BelongsTo
	 */
	public function belongsTo(string $associated, array $options = []): BelongsTo {
		$options += ['sourceTable' => $this];

		/** @var \Awyiss\ORM\Association\BelongsTo $association */
		$association = $this->_associations->load(BelongsTo::class, $associated, $options);


		return $association;
	}


	/**
	 * Re-implemented 1:1 so it'll use \Awyiss\ORM\Association\BelongsToMany
	 *
	 * @inheritDoc
	 * @return \Awyiss\ORM\Association\BelongsToMany
	 */
	public function belongsToMany(string $associated, array $options = []): BelongsToMany {
		$options += ['sourceTable' => $this];

		/** @var \Awyiss\ORM\Association\BelongsToMany $association */
		$association = $this->_associations->load(BelongsToMany::class, $associated, $options);


		return $association;
	}


	/**
	 * Re-implemented 1:1 so it'll use \Awyiss\ORM\Association\hasOne
	 *
	 * @inheritDoc
	 * @return \Awyiss\ORM\Association\HasOne
	 */
	public function hasOne(string $associated, array $options = []): HasOne {
		$options += ['sourceTable' => $this];

		/** @var \Awyiss\ORM\Association\HasOne $association */
		$association = $this->_associations->load(HasOne::class, $associated, $options);


		return $association;
	}


	/**
	 * Re-implemented 1:1 so it'll use \Awyiss\ORM\Association\HasMany
	 *
	 * @inheritDoc
	 * @return \Awyiss\ORM\Association\HasMany
	 */
	public function hasMany(string $associated, array $options = []): HasMany {
		$options += ['sourceTable' => $this];

		/** @var \Awyiss\ORM\Association\HasMany $association */
		$association = $this->_associations->load(HasMany::class, $associated, $options);


		return $association;
	}


	/**
	 * Returns true if there is any record in this repository matching the specified conditions.
	 * Does the same as \Cake\ORM\Table::exists but accepts an array of options as the second parameter
	 *
	 * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string|null $conditions
	 * @param array $options
	 * @return bool
	 */
	public function exists(QueryExpression|Closure|array|string|null $conditions, array $options = []): bool {
		$finder = $options['finder'] ?? 'all';
		[$finder, $finderOptions] = $this->_extractFinder($finder);

		$options = array_merge($finderOptions, $options);
		unset($options['finder']);

		$results = $this->find($finder, ...$options)
			->applyOptions($options)
			->select(['existing' => 1])
			->where($conditions)
			->limit(1)
			->disableHydration()
			->toArray();


		return (bool)count($results);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Awyiss\Validation\Validator $validator The validator that can be modified to add some rules to it.
	 * @return \Awyiss\Validation\Validator
	 */
	public function validationDefault(BaseValidator $validator): BaseValidator {
		$validator->setI18nDomain($this->getI18nDomain())->setStopOnFailure();

		return $validator;
	}


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		$eventMap = $this->getConfig('implementedEvents', []);

		if (empty($eventMap)) {
			return [];
		}


		return $this->buildEventMap($this, $eventMap);
	}


	/**
	 * Build a list of events based on the given config
	 *
	 * @param Table|Behavior $instance
	 * @param mixed $eventMap
	 * @param mixed $priority
	 * @return array
	 */
	public function buildEventMap(Table|Behavior $instance, array $eventMap, ?int $priority = null): array {
		$builtEventMap = [];

		$basePriority = $priority;
		foreach ($eventMap as $eventName => $callable) {
			$priority = $basePriority;

			if (is_array($callable)) {
				if (isset($callable['priority'])) {
					$priority = $callable['priority'];
				}

				$callable = $callable['callable'] ?? null;

				if (!$callable) {
					throw new RuntimeException(sprintf('When provided an array, the key `%s` must contain a `callable` key', $eventName));
				}
			}

			if (
				(is_string($callable) && !method_exists($instance, $callable)) ||
				(!is_string($callable) && !is_callable($callable))
			) {
				continue;
			}

			if (is_numeric($eventName)) {
				if (!is_string($callable)) {
					throw new RuntimeException(sprintf('When provided a callable, the key must be a string. `%s` given', gettype($eventName)));
				}
				$eventName = 'Model.' . $callable;
			}

			if ($priority === null) {
				$builtEventMap[ $eventName ] = $callable;
			}
			else {
				$builtEventMap[ $eventName ] = [
					'callable' => $callable,
					'priority' => $priority,
				];
			}
		}


		return $builtEventMap;
	}


	/**
	 * Returns whether the field is one of the attributes
	 *
	 * @param string $field
	 * @return bool
	 */
	public function fieldIsAttribute(string $field): bool {
		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $this->getEntityClass();

		$column = $entityClass::unmapField($field);

		//If the column isn't part of the table, just assume it's part of the attributes table.
		if (!$this->getSchema()->getColumn($column) && $this->hasAttributes()) {
			return true;
		}


		return false;
	}


	/**
	 * @return string
	 */
	public function getI18nDomain(): string {
		if (isset($this->i18nDomain)) {
			return $this->i18nDomain;
		}

		$alias = $this->getAlias();
		if (str_starts_with($alias, 'Attributes') && strlen($alias) > 10) {
			$alias = substr($alias, 10);
		}


		return $this->i18nDomain = Inflector::underscore($alias);
	}


	/**
	 * @param string|null $sourceTable
	 * @return void
	 */
	protected function addAttributesBehavior(?string $sourceTable = null): void {
		if ($sourceTable) {
			$options = ['isAttributesTable' => false] + $this->attributes + [
				'sourceTable' => $sourceTable,
				'foreignKey' => Inflector::singularize($this->getTable()) . '_id',
			];
		}
		else {
			$options = ['isAttributesTable' => true, 'sourceTable' => substr($this->getTable(), 11)] + $this->attributes;
		}

		$this->addBehavior('Attributes', $options);

		if ($sourceTable) {
			return;
		}

		/** @var \Awyiss\Model\Behavior\AttributesBehavior $attributesBehavior */
		$attributesBehavior = $this->getBehavior('Attributes');

		$attributes = $attributesBehavior->getAttributes();

		foreach ($attributes as $attribute) {
			if (!$attribute->translatable) {
				continue;
			}

			$this->translate['fields'][] = $attribute->identifier;
		}
	}


	/**
	 * @return void
	 */
	protected function addCategoriesBehavior(): void {
		$this->addBehavior('Categories', $this->categories);

		$categoriesOptions = $this->getBehavior('Categories')->getConfig();

		if (!$categoriesOptions['enabled'] === true) {
			return;
		}

		$fieldName = $categoriesOptions['field'] ?? $categoriesOptions['identifier'] ?? 'category';

		//Disable the rule check for the NestBehavior if the category field is same as the parent foreign key
		if (Inflector::underscore($fieldName) === Inflector::underscore($this->nest['parent']['foreignKey'] ?? 'parent_id')) {
			$this->nest['buildRules'] = false;
		}

		//Prefix the field with `attributes.` if it's an attribute
		if ($this->fieldIsAttribute($fieldName)) {
			$fieldName = 'attributes.' . $fieldName;
		}

		//Add field to the nested related columns
		if (
			!in_array($fieldName, $this->nest['relatedColumns'] ?? []) &&
			Inflector::underscore($fieldName) !== Inflector::underscore($this->nest['parent']['foreignKey'] ?? 'parent_id')
		) {
			$this->nest['relatedColumns'][] = $fieldName;
		}

		//Add field to the system order related columns
		if (!in_array($fieldName, $this->systemOrder['relatedColumns'] ?? [])) {
			$this->systemOrder['relatedColumns'][] = $fieldName;
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Language|null $translateLanguage
	 * @return void
	 * @throws \Exception
	 */
	public function addTranslateBehavior(?Language $translateLanguage = null): void {
		if (
			!$translateLanguage &&
			$this->getTable() !== 'languages'
		) {
			if (Awyiss::hasRealm()) {
				$translateLanguage = LocaleMiddleware::getLanguage($this->translate['realm'] ?? Awyiss::getRealm());
			}
		}

		if (!$translateLanguage) {
			return;
		}

		$this->addBehavior(
			'Translate',
			$this->translate + [
				'allowEmptyTranslations' => false,
				'defaultLocale' => '',
				'locale' => $translateLanguage->shortcode ?? null,
				'strategyClass' => EavStrategy::class,
			]
		);
	}


	/**
	 * @return \Awyiss\ORM\Marshaller
	 */
	public function marshaller(): Marshaller {
		return new Marshaller($this);
	}


	/**
	 * Handle `isCopy` and `asCopy` options before calling the parent save method.
	 *
	 * @inheritDoc
	 */
	public function save(EntityInterface $entity, array $options = []): EntityInterface|false {
		$options['isCopy'] ??= false;
		$options['asCopy'] ??= false;

		if ($options['asCopy'] === true || $options['isCopy'] === true) {
			$this->prepareForCopy($entity);
		}

		if ($options['asCopy'] === true) {
			$options['isCopy'] = true;
			$this->prepareAssociationsForCopy($entity);
		}

		unset($options['asCopy']);

		return parent::save($entity, $options);
	}


	/**
	 * Persists multiple entities of a table.
	 * The records will be saved in a transaction - if option `transaction` isn't false - which will be rolled back if
	 * any one of the records fails to save due to failed validation or database
	 * error.
	 *
	 * @param iterable<\Cake\Datasource\EntityInterface> $entities Entities to save.
	 * @param array<string, mixed> $options Options used when calling Table::save() for each entity.
	 * @return iterable<\Cake\Datasource\EntityInterface>|false False on failure, entities list on success.
	 * @throws \Exception
	 */
	public function saveMany(iterable $entities, array $options = []): iterable|false {
		$options += ['transaction' => true];

		try {
			return $this->_saveMany($entities, $options);
		}
		catch (PersistenceFailedException $ex) {
			if ($options['transaction'] === false) {
				throw $ex;
			}

			return false;
		}
	}


	/**
	 * Reimplemented to only dispatch the `afterDeleteCommit` event
	 * if no soft delete behavior is enabled or if the soft delete behavior is not enabled.
	 *
	 * @inheritDoc
	 */
	public function delete(EntityInterface $entity, array $options = []): bool {
		$options = new ArrayObject(
			$options + [
				'atomic' => true,
				'checkRules' => true,
				'_primary' => true,
			]
		);

		$success = $this->_executeTransaction(
			fn () => $this->_processDelete($entity, $options),
			$options['atomic'],
		);

		$noSoftDeleteBehavior = !$this->hasBehavior('SoftDelete') || !$this->getBehavior('SoftDelete')->getConfig('enabled');

		if ($success && $noSoftDeleteBehavior && $this->_transactionCommitted($options['atomic'], $options['_primary'])) {
			$this->dispatchEvent('Model.afterDeleteCommit', [
				'entity' => $entity,
				'options' => $options,
			]);
		}

		return $success;
	}


	/**
	 * Implemented nearly 1:1 but honors the `transaction`-option.
	 * If set to false, the save calls will not be handled inside a transaction
	 *
	 * @param iterable<\Cake\Datasource\EntityInterface> $entities Entities to save.
	 * @param array<string, mixed> $options Options used when calling Table::save() for each entity.
	 * @return iterable<\Cake\Datasource\EntityInterface> Entities list.
	 * @throws \Exception If an entity couldn't be saved.
	 * @throws \Cake\ORM\Exception\PersistenceFailedException If an entity couldn't be saved.
	 */
	protected function _saveMany(iterable $entities, array $options = []): iterable {
		$options = new ArrayObject(
			$options + [
				'atomic' => true,
				'checkRules' => true,
				'transaction' => true,
				'_primary' => true,
			]
		);
		$options['_cleanOnSuccess'] = false;

		/** @var array<bool> $isNew */
		$isNew = [];
		$cleanupOnFailure = function ($entities) use (&$isNew): void {
			/** @var iterable<\Cake\Datasource\EntityInterface> $entities */
			foreach ($entities as $key => $entity) {
				if (isset($isNew[ $key ]) && $isNew[ $key ]) {
					$entity->unset($this->getPrimaryKey());
					$entity->setNew(true);
				}
			}
		};

		/** @var \Cake\Datasource\EntityInterface|null $failed */
		$failed = null;
		try {
			$saveMany = function () use ($entities, $options, &$isNew, &$failed): bool {
				// Cache array cast since options are the same for each entity
				$options = (array)$options;
				foreach ($entities as $key => $entity) {
					$isNew[ $key ] = $entity->isNew();
					if ($this->save($entity, $options) === false) {
						$failed = $entity;

						return false;
					}
				}


				return true;
			};

			if ($options['transaction'] !== false) {
				$this->getConnection()->transactional($saveMany);
			}
			else {
				$saveMany();
			}
		}
		catch (Exception $ex) {
			$cleanupOnFailure($entities);

			throw $ex;
		}

		if ($failed !== null) {
			$cleanupOnFailure($entities);

			throw new PersistenceFailedException($failed, ['saveMany']);
		}

		$cleanupOnSuccess = function (EntityInterface $entity) use (&$cleanupOnSuccess): void {
			$entity->clean();
			$entity->setNew(false);

			foreach (array_keys($entity->toArray()) as $field) {
				$value = $entity->get($field);

				if ($value instanceof EntityInterface) {
					$cleanupOnSuccess($value);
				}
				elseif (is_array($value) && current($value) instanceof EntityInterface) {
					foreach ($value as $associated) {
						$cleanupOnSuccess($associated);
					}
				}
			}
		};

		if ($options['transaction'] === false || $this->_transactionCommitted($options['atomic'], $options['_primary'])) {
			foreach ($entities as $entity) {
				$this->dispatchEvent('Model.afterSaveCommit', [
					'entity' => $entity,
					'options' => $options,
				]);

				if ($options['atomic'] || $options['_primary']) {
					$cleanupOnSuccess($entity);
				}
			}
		}


		return $entities;
	}


	/**
	 * Re-implemented 1:1 but checks for the result of the afterSave event.
	 * Returns false, if it was stopped.
	 *
	 * @inheritDoc
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return bool
	 */
	protected function _onSaveSuccess(EntityInterface $entity, ArrayObject $options): bool {
		if ($options['associated'] ?? []) {
			$this->dispatchEvent('Model.beforeSaveAssociations', ['entity' => $entity, 'options' => clone $options]);
		}

		$success = $this->_associations->saveChildren(
			$this,
			$entity,
			$options['associated'],
			['_primary' => false] + $options->getArrayCopy()
		);


		if (!$success && $options['atomic']) {
			return false;
		}

		if ($options['associated'] ?? []) {
			$this->dispatchEvent('Model.afterSaveAssociations', ['entity' => $entity, 'options' => clone $options]);
		}

		$event = $this->dispatchEvent('Model.afterSave', ['entity' => $entity, 'options' => $options]);
		if ($event->isStopped()) {
			$errors = $event->getResult();
			if (!is_array($errors)) {
				$errors = ['_general' => $errors];
			}

			$entity->setErrors($errors);
			return false;
		}

		if ($options['atomic'] && !$this->getConnection()->inTransaction()) {
			throw new RolledbackTransactionException(['table' => static::class]);
		}

		if (!$options['atomic'] && !$options['_primary']) {
			$entity->clean();
			$entity->setNew(false);
			$entity->setSource($this->getRegistryAlias());
		}


		return true;
	}


	/**
	 * Build associations for this table
	 *
	 * @return void
	 */
	public function initializeAssociations(): void {
	}


	/**
	 * Sets specific column types for attributes
	 *
	 * @param TableSchemaInterface $schema
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		if (!str_starts_with($this->getTable(), 'attributes_')) {
			return;
		}

		foreach ($this->getAttributes() as $attribute) {
			$columnData = $schema->getColumn($attribute->identifier);

			if (!$columnData) {
				continue;
			}

			if ($attribute->type === 'json') {
				$schema->setColumnType($attribute->identifier, 'json');
			}

			if (($columnData['default'] ?? null) !== $attribute->defaultValue) {
				$columnData['default'] = $attribute->defaultValue;
				$schema->addColumn($attribute->identifier, $columnData);
			}
		}
	}


	/**
	 * Helper method to infer the requested finder and its options.
	 *
	 * @param array|string $finderData The finder name or an array having the name as key
	 *  and options as value.
	 * @return array
	 * @see \Cake\ORM\Association::_extractFinder()
	 */
	protected function _extractFinder(array|string $finderData): array {
		$finderData = (array)$finderData;

		if (is_numeric(key($finderData))) {
			return [current($finderData), []];
		}

		return [key($finderData), current($finderData)];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeRules(Event $event, Entity $entity, ArrayObject $options): void {
		// Do not clean HTML if this is not the primary entity
		if (($options['_primary'] ?? true) === false) {
			return;
		}

		if ($entity->get('deleted') === true) {
			// If the entity is deleted, we don't want to clean the HTML
			return;
		}

		/**
		 * The html cleaning happens here and not in the `beforeSave`-event
		 * since it could result in empty fields in case the html contained
		 * only empty tags.
		 *
		 * Rules, that check for empty fields, would not fail if the html
		 * cleaning would be done later on.
		 */
		if (Configure::read('Awyiss.System.Backend.htmlCleaning', 'none') !== 'none') {
			/** @var \Awyiss\Utility\Content\HtmlCleaner $className */
			$className = App::className('HtmlCleaner', 'Utility/Content');
			$className::clean($entity, Configure::read('Awyiss.System.Backend.htmlCleaning'));
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, Entity $entity, ArrayObject $options): void {
		// Do not clean HTML if this is not the primary entity
		if ($options['_primary'] === false) {
			return;
		}

		if ($entity->get('deleted') === true) {
			// If the entity is deleted, we don't want to clean the HTML
			return;
		}

		// Convert image tags to the custom format
		if (Configure::read('Awyiss.Media.Backend.handleImagesInHtml')) {
			/** @var \Awyiss\Utility\Content\ImageHandler $className */
			$className = App::className('ImageHandler', 'Utility/Content');
			$className::replaceImageTags($entity);
		}
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	protected function prepareForCopy(EntityInterface $entity): void {
		if (!isset($entity->originalEntity)) {
			/** @noinspection PhpDynamicFieldDeclarationInspection */
			$entity->originalEntity = unserialize(serialize($entity));
			$entity->setVirtual(['originalEntity'], true);

			$entity->originalEntity->patch(
				$entity->extractOriginalChanged(
					$entity->getOriginalFields()
				)
			);

			$entity->originalEntity->clean();
		}

		$primaryKeys = $entity->extractOriginal((array)$this->getPrimaryKey());
		if ($primaryKeys) {
			$entity->originalPrimaryKeyValues ??= $primaryKeys;
			$entity->unset((array)$this->getPrimaryKey());
		}

		$entity->setNew(true);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	protected function prepareAssociationsForCopy(EntityInterface $entity): void {
		/**
		 * Traverse all associations of type HasMany and HasOne,
		 * unset the primary key of the associated entities,
		 * and mark them as new.
		 */
		foreach ($this->associations() as $association) {
			$property = $association->getProperty();

			if (!$entity->has($property) || !$entity->get($property)) {
				continue;
			}

			if ($association instanceof HasMany) {
				$entity->setDirty($property);
				foreach (($entity->get($property) ?? []) as $associated) {
					if (!($associated instanceof EntityInterface)) {
						continue;
					}

					$associated->unset((array)$association->getPrimaryKey());
					$associated->setNew(true);
				}
			}

			if ($association instanceof HasOne) {
				$entity->setDirty($property);
				$associated = $entity->get($property);
				$associated->unset((array)$association->getPrimaryKey());
				$associated->setNew(true);
			}
		}
	}
}
