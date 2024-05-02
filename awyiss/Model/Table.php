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
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\Marshaller;
use Awyiss\ORM\RulesChecker;
use Awyiss\Validation\Validator;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Exception\RolledbackTransactionException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table as BaseTable;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Validation\Validator as BaseValidator;
use Closure;
use Exception;
use RuntimeException;


/**
 * Base Table
 *
 * @method \Cake\ORM\Query\SelectQuery findById(int $ai_id)
 * @method \Cake\ORM\Query\SelectQuery addSystemOrderQueryConditions(?SelectQuery $ao_query, \Cake\Datasource\EntityInterface $ao_entity)
 * @method \Awyiss\Authorization\AuthorizationServiceInterface getAuthorizationService()
 * @method int getHighestSystemOrder(\Cake\Datasource\EntityInterface $ao_entity)
 * @method string|\Awyiss\Authorization\Policy\AbstractGenericPolicy|null getPolicyClass()
 * @method array getSystemOrderRelatedColumns(?\Cake\Datasource\EntityInterface $ao_entity = null)
 * @method array extractAttributeFields(array $aa_fields, bool $ab_inlcudeBaseFields = false)
 * @method array getAttributes()
 * @method \Awyiss\Model\Table getAttributesTable()
 * @method string getAttributesTableName(bool $ab_camelized = false)
 * @method bool hasAttributes()
 * @method \Cake\Datasource\ResultSetInterface|array|null getCategories(bool $ab_returnRaw = false)
 * @method \Awyiss\Model\Entity newDefaultEntity(array $aa_additionalData = [])
 * @method \Cake\Collection\CollectionInterface listNested(\Cake\ORM\Query\SelectQuery $ao_query)
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class Table extends BaseTable {
	use IdentityAwareTrait;
	use InstanceConfigTrait;


	/**
	 * Allows \Awyiss\Model\Table\AttributesTable to set attributes for this table.
	 *
	 * @var bool
	 */
	public const ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const RULES_CLASS = RulesChecker::class;
	/**
	 * Name of the database table. Used in static::initialize() ($this->setTable(static::TABLE)) and in
	 * \Awyiss\Model\Table\AttributesTable::getAvailableScopes()
	 *
	 * @see \Awyiss\Model\Table\AttributesTable::getAvailableScopes();
	 * @var string
	 */
	public const TABLE = '';


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
	protected array $_defaultConfig = [];
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
	protected string $_validatorClass = Validator::class;
	/**
	 * @var string
	 */
	protected string $i18nDomain;
	/**
	 * @var array Settings for the NestBehavior
	 */
	protected array $nest = [];
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
	 * @var array Custom configuration, set by the current user, for the current user
	 */
	protected array $userConfiguration;


	/**
	 * @inheritDoc
	 */
	public function __construct(array $aa_config = []) {
		if (($this->_defaultConfig['implementedEvents'] ?? null) === null) {
			$this->setConfig('implementedEvents', $this->defaultEvents);
		}

		parent::__construct($aa_config + ['eventManager' => new EventManager()]);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize(array $aa_config): void {
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

		/**
		 * @noinspection PhpStrictTypeCheckingInspection
		 * @noinspection PhpParamsInspection
		 */
		$ls_sourceTable = isset($this->pageRole) ? Inflector::tableize($this->pageRole->name) : $this->getTable();

		//Merge the config properties with custom configuration from the database
		foreach ($this->customConfigProperties as $ls_property) {
			$ls_path = implode('.', ['Awyiss', Inflector::camelize($ls_sourceTable), Awyiss::REALM_BACKEND, $ls_property]);
			$la_customConfig = Configure::read($ls_path);

			if ($la_customConfig && is_array($this->$ls_property ?? null)) {
				/** @noinspection PhpParamsInspection */
				$this->$ls_property = Hash::merge($this->$ls_property, $la_customConfig);
			}
		}

		$lb_isAttributesTable = str_starts_with($this->getTable(), 'attributes_');

		$lo_schema = $this->getSchema();

		if ($lb_isAttributesTable) {
			$this->addAttributesBehavior();
		}
		else {
			$this->addAttributesBehavior($ls_sourceTable);

			$this->addBehavior('Audit', $this->audit + ['priority' => 999999]);

			if ($lo_schema->getColumn('deleted')) {
				$this->addBehavior('SoftDelete', $this->softDelete);
			}

			$this->addCategoriesBehavior();

			$this->addBehavior('EventTrigger', $this->eventTrigger);

			if ($lo_schema->getColumn('system_order')) {
				$this->addBehavior('SystemOrder', $this->systemOrder);
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

		if (!empty($aa_config['translateLanguage']) && !empty($this->translate['fields'])) {
			$this->addTranslateBehavior($aa_config['translateLanguage']);
		}

		$this->initializeSchema($lo_schema);
	}


	/**
	 * @param SelectQuery $ao_query
	 * @return SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findTranslations(SelectQuery $ao_query): SelectQuery {
		if ($this->hasBehavior('Translate')) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return $this->getBehavior('Translate')->findTranslations($ao_query);
		}


		return $ao_query;
	}


	/**
	 * @param SelectQuery $ao_query
	 * @param array $aa_options
	 * @return SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findActive(SelectQuery $ao_query): SelectQuery {
		if (!$this->getSchema()->getColumn('active')) {
			throw new RuntimeException(sprintf('Cannot use `findActive` on table `%s` ', $this->getAlias()));
		}

		$ao_query->where(['active' => true]);


		return $ao_query;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param string|null $languageShortcode
	 * @param \Awyiss\Model\Entity\MediaFolder|null $entity
	 * @param bool $includeGlobal
	 * @return \Cake\ORM\Query\SelectQuery
	 * @throws \Exception
	 */
	public function findForCurrentLanguage(SelectQuery $ao_query, ?string $languageShortcode = null, ?Entity $entity = null, bool $includeGlobal = true): SelectQuery {
		$ls_languageShortcode = $languageShortcode ?? LocaleMiddleware::getLanguage()->shortcode;

		if ($ls_languageShortcode === '_global') {
			$ls_languageShortcode = null;
		}

		if ($entity) {
			$ls_languageShortcode = $entity->languageShortcode;
		}

		if ($includeGlobal && $ls_languageShortcode) {
			return $ao_query->where([
				'OR' => [
					'language_shortcode' => $ls_languageShortcode,
					'language_shortcode IS' => null,
				],
			]);
		}


		return $ao_query->where([
			'language_shortcode' . ($ls_languageShortcode ? '' : ' IS') => $ls_languageShortcode,
		]);
	}


	/**
	 * @inheritDoc
	 */
	public function getEntityClass(): string {
		if (!$this->_entityClass) {
			$ls_default = Entity::class;
			$ls_self = static::class;
			$la_parts = explode('\\', $ls_self);

			if ($ls_self === self::class || count($la_parts) < 3) {
				return $this->_entityClass = $ls_default;
			}

			$ls_alias = Inflector::classify(Inflector::underscore(substr(array_pop($la_parts), 0, -5)));
			$ls_name = implode('\\', array_slice($la_parts, 0, -1)) . '\\Entity\\' . $ls_alias;

			/** @var class-string<\Cake\Datasource\EntityInterface>|null $ls_class */
			$ls_class = App::className($ls_name, 'Model/Entity');
			if (!$ls_class) {
				$ls_class = App::className($ls_alias, 'Model/Entity');
			}

			if (!$ls_class) {
				throw new MissingEntityException([$ls_name]);
			}

			$this->_entityClass = $ls_class;
		}


		return $this->_entityClass;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Re-implemented 1:1 so it'll use \Awyiss\ORM\Association\BelongsTo
	 *
	 * @param string $as_associated
	 * @param array $aa_options
	 * @return BelongsTo
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function belongsTo(string $as_associated, array $aa_options = []): BelongsTo {
		$aa_options += ['sourceTable' => $this];

		/** @var BelongsTo $lo_association */
		$lo_association = $this->_associations->load(BelongsTo::class, $as_associated, $aa_options);


		return $lo_association;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Re-implemented 1:1 so it'll use \Awyiss\ORM\Association\hasOne
	 *
	 * @param string $as_associated
	 * @param array $aa_options
	 * @return HasOne
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function hasOne(string $as_associated, array $aa_options = []): HasOne {
		$aa_options += ['sourceTable' => $this];

		/** @var HasOne $lo_association */
		$lo_association = $this->_associations->load(HasOne::class, $as_associated, $aa_options);


		return $lo_association;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Re-implemented 1:1 so it'll use \Awyiss\ORM\Association\HasMany
	 *
	 * @param string $as_associated
	 * @param array $aa_options
	 * @return HasOne
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function hasMany(string $as_associated, array $aa_options = []): HasMany {
		$aa_options += ['sourceTable' => $this];

		/** @var HasMany $lo_association */
		$lo_association = $this->_associations->load(HasMany::class, $as_associated, $aa_options);


		return $lo_association;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Re-implemented 1:1 so it'll use \Awyiss\ORM\Association\BelongsToMany
	 *
	 * @param string $as_associated
	 * @param array $aa_options
	 * @return HasOne
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function belongsToMany(string $as_associated, array $aa_options = []): BelongsToMany {
		$aa_options += ['sourceTable' => $this];

		/** @var BelongsToMany $lo_association */
		$lo_association = $this->_associations->load(BelongsToMany::class, $as_associated, $aa_options);


		return $lo_association;
	}


	/**
	 * Returns true if there is any record in this repository matching the specified conditions.
	 * Does the same as \Cake\ORM\Table::exists but accepts an array of options as the second parameter
	 *
	 * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string|null $aa_conditions
	 * @param array $aa_options
	 * @return bool
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function exists(QueryExpression|Closure|array|string|null $aa_conditions, array $aa_options = []): bool {
		$lx_finder = $aa_options['finder'] ?? 'all';
		[$lx_finder, $la_options] = $this->_extractFinder($lx_finder);

		$la_options = array_merge($la_options, $aa_options);
		unset($la_options['finder']);

		$lo_results = $this->find($lx_finder)
			->applyOptions($la_options)
			->select(['existing' => 1])
			->where($aa_conditions)
			->limit(1)
			->disableHydration()
			->toArray();


		return (bool)count($lo_results);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to add some rules to it.
	 * @return Validator
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault(BaseValidator $ao_validator): BaseValidator {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$ao_validator->setI18nDomain($this->getI18nDomain());
		$ao_validator->setStopOnFailure();


		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		$la_eventMap = $this->getConfig('implementedEvents', []);

		if (empty($la_eventMap)) {
			return [];
		}


		return $this->buildEventMap($this, $la_eventMap);
	}


	/**
	 * Build a list of events based on the given config
	 *
	 * @param Table|Behavior $ao_instance
	 * @param mixed $aa_eventMap
	 * @param mixed $ai_priority
	 * @return array
	 */
	public function buildEventMap(Table|Behavior $ao_instance, array $aa_eventMap, ?int $ai_priority = null): array {
		$la_eventMap = [];
		$li_priority = $ai_priority;

		foreach ($aa_eventMap as $ls_event => $lx_callable) {
			if (is_array($lx_callable)) {
				if (isset($lx_callable['priority'])) {
					$li_priority = $lx_callable['priority'];
				}

				$lx_callable = $lx_callable['callable'] ?? null;
			}

			if ((is_string($lx_callable) && !method_exists($ao_instance, $lx_callable)) || (!is_string($lx_callable) && !is_callable($lx_callable))) {
				continue;
			}

			if (is_numeric($ls_event)) {
				if (!is_string($lx_callable)) {
					throw new RuntimeException(sprintf('When provided a callable, the key must be a string. `%s` given', gettype($ls_event)));
				}
				$ls_event = 'Model.' . $lx_callable;
			}

			if ($li_priority === null) {
				$la_eventMap[ $ls_event ] = $lx_callable;
			}
			else {
				$la_eventMap[ $ls_event ] = [
					'callable' => $lx_callable,
					'priority' => $li_priority,
				];
			}
		}


		return $la_eventMap;
	}


	/**
	 * Returns whether the field is one of the attributes
	 *
	 * @param string $as_field
	 * @return bool
	 */
	public function fieldIsAttribute(string $as_field): bool {
		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $this->getEntityClass();

		$ls_column = $ls_entityClass::unmapField($as_field);

		//If the column isn't part of the table, just assume it's part of the attributes table.
		if (!$this->getSchema()->getColumn($ls_column) && $this->hasAttributes()) {
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

		$ls_alias = $this->getAlias();
		if (str_starts_with($ls_alias, 'Attributes') && strlen($ls_alias) > 10) {
			$ls_alias = substr($ls_alias, 10);
		}


		return $this->i18nDomain = Inflector::underscore($ls_alias);
	}


	/**
	 * @return void
	 */
	protected function addCategoriesBehavior(): void {
		$this->addBehavior('Categories', $this->categories);

		$la_categoriesOptions = $this->getBehavior('Categories')->getConfig();

		if ($la_categoriesOptions['enabled'] === true) {
			$ls_fieldName = $la_categoriesOptions['field'] ?? $la_categoriesOptions['identifier'] ?? 'category';

			//Disable the rule check for the NestBehavior if the category field is same as the parent foreign key
			if (Inflector::underscore($ls_fieldName) === Inflector::underscore($this->nest['parent']['foreignKey'] ?? 'parent_id')) {
				$this->nest['buildRules'] = false;
			}

			//Prefix the field with `attributes.` if it's an attribute
			if ($this->fieldIsAttribute($ls_fieldName)) {
				$ls_fieldName = 'attributes.' . $ls_fieldName;
			}

			//Add field to the nested related columns
			if (
				!in_array($ls_fieldName, $this->nest['relatedColumns'] ?? []) &&
				Inflector::underscore($ls_fieldName) !== Inflector::underscore($this->nest['parent']['foreignKey'] ?? 'parent_id')
			) {
				$this->nest['relatedColumns'][] = $ls_fieldName;
			}

			//Add field to the system order related columns
			if (!in_array($ls_fieldName, $this->systemOrder['relatedColumns'] ?? [])) {
				$this->systemOrder['relatedColumns'][] = $ls_fieldName;
			}
		}
	}


	/**
	 * @return \Awyiss\ORM\Marshaller
	 */
	public function marshaller(): Marshaller {
		return new Marshaller($this);
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param array $aa_options
	 * @return \Cake\Datasource\EntityInterface|false
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function save(EntityInterface $ao_entity, array $aa_options = []): EntityInterface|false {
		$la_options = $aa_options;
		$la_options['isCopy'] ??= false;
		$la_options['asCopy'] ??= false;

		if ($la_options['asCopy'] === true || $la_options['isCopy'] === true) {
			/** @noinspection PhpDynamicFieldDeclarationInspection */
			$ao_entity->originalEntity = clone $ao_entity;
			$ao_entity->setVirtual(['originalEntity']);

			/** @noinspection PhpUndefinedFieldInspection */
			if ($ao_entity->originalPrimaryKeys) {
				/** @noinspection PhpUndefinedFieldInspection */
				$ao_entity->originalEntity->set($ao_entity->originalPrimaryKeys, ['guard' => false]);
				$ao_entity->unset('originalPrimaryKeys');
			}

			if ($ao_entity->originalEntity->isDirty()) {
				$ao_entity->originalEntity->set(
					$ao_entity->originalEntity->extractOriginalChanged(
						$ao_entity->originalEntity->getOriginalFields()
					)
				);

				$ao_entity->originalEntity->clean();
			}
		}

		if ($la_options['asCopy'] === true) {
			$ao_entity->unset((array)$this->getPrimaryKey());
			$ao_entity->setNew(true);

			$la_options['isCopy'] = true;
		}

		unset($la_options['asCopy']);


		return parent::save($ao_entity, $la_options);
	}


	/**
	 * Persists multiple entities of a table.
	 * The records will be saved in a transaction - if option `transaction` isn't false - which will be rolled back if
	 * any one of the records fails to save due to failed validation or database
	 * error.
	 *
	 * @param iterable<\Cake\Datasource\EntityInterface> $ax_entities Entities to save.
	 * @param array<string, mixed> $aa_options Options used when calling Table::save() for each entity.
	 * @return iterable<\Cake\Datasource\EntityInterface>|false False on failure, entities list on success.
	 * @throws \Exception
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function saveMany(iterable $ax_entities, array $aa_options = []): iterable|false {
		$la_options = $aa_options + ['transaction' => true];

		try {
			return $this->_saveMany($ax_entities, $la_options);
		}
		catch (PersistenceFailedException $ex) {
			if ($la_options['transaction'] === false) {
				throw $ex;
			}

			return false;
		}
	}


	/**
	 * Implemented nearly 1:1 but honors the `transaction`-option.
	 * If set to false, the save calls will not be handled inside a transaction
	 *
	 * @param iterable<\Cake\Datasource\EntityInterface> $ax_entities Entities to save.
	 * @param array<string, mixed> $aa_options Options used when calling Table::save() for each entity.
	 * @return iterable<\Cake\Datasource\EntityInterface> Entities list.
	 * @throws \Exception If an entity couldn't be saved.
	 * @throws \Cake\ORM\Exception\PersistenceFailedException If an entity couldn't be saved.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _saveMany(iterable $ax_entities, array $aa_options = []): iterable {
		$la_options = new ArrayObject(
			$aa_options + [
				'atomic' => true,
				'checkRules' => true,
				'_primary' => true,
			]
		);
		$la_options['_cleanOnSuccess'] = false;

		/** @var array<bool> $la_isNew */
		$la_isNew = [];
		$lc_cleanupOnFailure = function ($entities) use (&$la_isNew): void {
			/** @var iterable<\Cake\Datasource\EntityInterface> $entities */
			foreach ($entities as $lx_key => $lo_entity) {
				if (isset($la_isNew[ $lx_key ]) && $la_isNew[ $lx_key ]) {
					$lo_entity->unset($this->getPrimaryKey());
					$lo_entity->setNew(true);
				}
			}
		};

		/** @var \Cake\Datasource\EntityInterface|null $lo_failed */
		$lo_failed = null;
		try {
			$lc_saveMany = function () use ($ax_entities, $la_options, &$la_isNew, &$lo_failed): bool {
				// Cache array cast since options are the same for each entity
				$la_options = (array)$la_options;
				foreach ($ax_entities as $lx_key => $lo_entity) {
					$la_isNew[ $lx_key ] = $lo_entity->isNew();
					if ($this->save($lo_entity, $la_options) === false) {
						$lo_failed = $lo_entity;


						return false;
					}
				}


				return true;
			};

			if ($la_options['transaction'] !== false) {
				$this->getConnection()->transactional($lc_saveMany);
			}
			else {
				$lc_saveMany();
			}
		}
		catch (Exception $ex) {
			$lc_cleanupOnFailure($ax_entities);

			throw $ex;
		}

		if ($lo_failed !== null) {
			$lc_cleanupOnFailure($ax_entities);

			throw new PersistenceFailedException($lo_failed, ['saveMany']);
		}

		$lc_cleanupOnSuccess = function (EntityInterface $ao_entity) use (&$lc_cleanupOnSuccess): void {
			$ao_entity->clean();
			$ao_entity->setNew(false);

			foreach (array_keys($ao_entity->toArray()) as $ls_field) {
				$lx_value = $ao_entity->get($ls_field);

				if ($lx_value instanceof EntityInterface) {
					$lc_cleanupOnSuccess($lx_value);
				}
				elseif (is_array($lx_value) && current($lx_value) instanceof EntityInterface) {
					foreach ($lx_value as $lo_associated) {
						$lc_cleanupOnSuccess($lo_associated);
					}
				}
			}
		};

		if ($this->_transactionCommitted($la_options['atomic'], $la_options['_primary'])) {
			foreach ($ax_entities as $lo_entity) {
				$this->dispatchEvent('Model.afterSaveCommit', [
					'entity' => $lo_entity,
					'options' => $la_options,
				]);

				if ($la_options['atomic'] || $la_options['_primary']) {
					$lc_cleanupOnSuccess($lo_entity);
				}
			}
		}


		return $ax_entities;
	}


	/**
	 * Re-implemented 1:1 but checks for the result of the afterSave event.
	 * Returns false, if it was stopped.
	 *
	 * @inheritDoc
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return bool
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _onSaveSuccess(EntityInterface $ao_entity, ArrayObject $ao_options): bool {
		$lx_success = $this->_associations->saveChildren(
			$this,
			$ao_entity,
			$ao_options['associated'],
			['_primary' => false] + $ao_options->getArrayCopy()
		);


		if (!$lx_success && $ao_options['atomic']) {
			return false;
		}

		$lo_event = $this->dispatchEvent('Model.afterSave', ['entity' => $ao_entity, 'options' => $ao_options]);
		if ($lo_event->isStopped()) {
			$lx_errors = $lo_event->getResult();
			if (!is_array($lx_errors)) {
				$lx_errors = ['_general' => $lx_errors];
			}

			$ao_entity->setErrors($lx_errors);
			return false;
		}

		if ($ao_options['atomic'] && !$this->getConnection()->inTransaction()) {
			throw new RolledbackTransactionException(['table' => static::class]);
		}

		if (!$ao_options['atomic'] && !$ao_options['_primary']) {
			$ao_entity->clean();
			$ao_entity->setNew(false);
			$ao_entity->setSource($this->getRegistryAlias());
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
	 * @param TableSchemaInterface $ao_schema
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		if (str_starts_with($this->getTable(), 'attributes_')) {
			foreach ($this->getAttributes() as $lo_attribute) {
				$la_column = $ao_schema->getColumn($lo_attribute->identifier);
				if ($lo_attribute->type === 'json') {
					$ao_schema->setColumnType($lo_attribute->identifier, 'json');
				}

				if ($la_column['default'] !== $lo_attribute->defaultValue) {
					$la_column['default'] = $lo_attribute->defaultValue;
					$ao_schema->addColumn($lo_attribute->identifier, $la_column);
				}
			}
		}
	}


	/**
	 * @param string|null $as_sourceTable
	 * @return void
	 */
	protected function addAttributesBehavior(?string $as_sourceTable = null): void {
		if ($as_sourceTable) {
			$la_options = ['isAttributesTable' => false] + $this->attributes + [
				'sourceTable' => $as_sourceTable,
				'foreignKey' => Inflector::singularize($this->getTable()) . '_id',
			];
		}
		else {
			$la_options = ['isAttributesTable' => true] + $this->attributes;
		}

		$this->addBehavior('Attributes', $la_options);

		if ($as_sourceTable) {
			return;
		}

		/** @var \Awyiss\Model\Behavior\AttributesBehavior $lo_attributes */
		$lo_attributes = $this->getBehavior('Attributes');

		$la_attributes = $lo_attributes->getAttributes();

		foreach ($la_attributes as $lo_attribute) {
			if (!$lo_attribute->translatable) {
				continue;
			}

			$this->translate['fields'][] = $lo_attribute->identifier;
		}
	}


	/**
	 * Helper method to infer the requested finder and its options.
	 * Returns the inferred options from the finder $finderData.
	 *
	 * ### Examples:
	 * Given you're using the Muffin/TrashBehavior
	 *
	 * The following will call the finder 'withTrashed' with the value of the finder as its options:
	 *
	 * ```
	 * $table->Articles->exists(['id' => 1], 'withTrashed');
	 * $table->Articles->exists(['id' => 1], ['finder' => ['withTrashed' => []]]);
	 * //this is the same as
	 * $table->Articles->exists(['id' => 1], ['finder' => ['all' => ['skipAddTrashCondition' => true]]]);
	 * ```
	 *
	 *
	 * Only return true if an article with `en` and `es` locales exist
	 *
	 * ```
	 * $table->Articles->exists(['id' => 1], ['finder' => ['translations' => ['locales' => ['en', 'es']]]]
	 *
	 * ```
	 * The following will call the finder 'published' with additional options. Those options will be available
	 * inside the attached behaviors (resp. their beforeFind-events):
	 * ```
	 *
	 * $table->Articles->exists(['id' => 1], [
	 * 	'finder' => [
	 *  	'published' => [
	 *    		'published_before' => '2010-01-01 00:00:00',
	 *      	'skipAddTrashCondition' => true,
	 *  	]
	 * 	]
	 * ]);
	 * ```
	 *
	 * @param array|string $ax_finderData The finder name or an array having the name as key
	 * and options as value.
	 * @return array
	 */
	protected function _extractFinder(array|string $ax_finderData): array {
		$la_finderData = (array)$ax_finderData;

		if (is_numeric(key($la_finderData))) {
			return [current($la_finderData), []];
		}

		return [key($la_finderData), current($la_finderData)];
	}


	/**
	 * @param \Awyiss\Model\Entity\Language $translateLanguage
	 * @return void
	 */
	public function addTranslateBehavior(Language $translateLanguage): void {
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
}
