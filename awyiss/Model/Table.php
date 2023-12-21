<?php declare(strict_types=1);


namespace Awyiss\Model;


use Awyiss\Core\App;
use Awyiss\Model\Behavior\Translate\EavStrategy;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Awyiss\Validation\Validator;
use Cake\Core\InstanceConfigTrait;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table as BaseTable;
use Cake\Utility\Inflector;
use Cake\Validation\Validator as BaseValidator;
use Closure;
use RuntimeException;


/**
 * Base Table
 *
 * @method SelectQuery findById(int $ai_id)
 * @method SelectQuery addSystemOrderQueryConditions(?SelectQuery $ao_query, \Cake\Datasource\EntityInterface $ao_entity)
 * @method \Awyiss\Authorization\AuthorizationServiceInterface getAuthorizationService()
 * @method bool|int getHighestSystemOrder(\Cake\Datasource\EntityInterface $ao_entity)
 * @method string|\Awyiss\Authorization\Policy\GenericPagePolicy|null getPolicyClass()
 * @method array getSystemOrderRelatedColumns(?\Cake\Datasource\EntityInterface $ao_entity = null)
 * @method array getAttributes()
 * @method string getAttributesTable(bool $ab_camelized = false)
 * @method bool hasAttributes()
 * @method \Awyiss\Model\Behavior\AuthorizeBehavior setAuthorizationService(\Awyiss\Authorization\AuthorizationServiceInterface $ao_authorizationService)
 * @method \Awyiss\Model\Behavior\AuthorizeBehavior setPolicyClass(string|\Awyiss\Authorization\Policy\GenericPagePolicy|null $ax_policyClass)
 * @method \Awyiss\Model\Behavior\AuthorizeBehavior skipAuthorizationCheck(bool $ab_skip = true)
 * @method \Awyiss\Model\Behavior\AuthorizeBehavior skipAuthorizationCheckOnce(bool $ab_skip = true)
 * @method \Awyiss\Model\Behavior\DefaultValuesBehavior newDefaultEntity(array $aa_additionalData = [])
 */
class Table extends BaseTable {
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
	 * \Awyiss\Model\Table\AttributesTable::getScopes()
	 *
	 * @see \Awyiss\Model\Table\AttributesTable::getAvailableScopes();
	 * @var string
	 */
	public const TABLE = '';
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
	];
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
	 * @inheritDoc
	 */
	public function __construct(array $aa_config = []) {
		if (($this->_defaultConfig['implementedEvents'] ?? null) === null) {
			$this->setConfig('implementedEvents', $this->defaultEvents);
		}

		parent::__construct($aa_config);
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

		$lb_isAttributesTable = str_starts_with($this->getTable(), 'attributes_');

		if ($lb_isAttributesTable) {
			$this->addBehavior('Attributes', ['isAttributesTable' => true] + $this->getConfig('attributes', []));
			if (!$this->getConfig('authorize.scope')) {
				$this->setConfig('authorize.scope', substr($this->getTable(), 11));
			}
		}
		else {
			$this->addBehavior(
				'Attributes',
				['isAttributesTable' => false] + $this->getConfig('attributes', []) + [
					'sourceTable' => $this->getTable(),
					'foreignKey' => Inflector::singularize($this->getTable()) . '_id',
				]
			);

			$this->addBehavior('Audit', $this->getConfig('audit', []) + ['priority' => 99999]);
			$this->addBehavior('SoftDelete', $this->getConfig('softDelete', []));
			$this->addBehavior('SystemOrder', $this->getConfig('systemOrder', []));
		}

		$this->addBehavior('Authorize', $this->getConfig('authorize', []) + ['priority' => 1]);
		$this->addBehavior('AutoPrefix', $this->getConfig('autoPrefix', []) + ['priority' => 99999]);
		$this->addBehavior('DefaultValues', $this->getConfig('defaultValues', []));

		//if ($this->getTable() !== 'dates') {
		if ($this->getTable() === 'menus') {
			$this->addBehavior('Date', $this->getConfig('date', []));
		}

		$this->addBehavior('EventTrigger', $this->getConfig('eventTrigger', []));

		/*if ($lb_isAttributesTable) {
			dd($aa_config['translateLanguage'], $this->getConfig('translate', []));
		}*/

		if (0 && !empty($aa_config['translateLanguage']) && $this->getConfig('translate', [])) {
			$this->addBehavior(
				'Translate',
				$this->getConfig('translate') + [
					'allowEmptyTranslations' => false,
					'defaultLocale' => '',
					'locale' => $aa_config['translateLanguage']->shortcode ?? null,
					'strategyClass' => EavStrategy::class,
				]
			);
		}

		$this->initializeSchema($this->getSchema());
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
		$la_options = array_merge(['authorize' => ['skip' => true]], $aa_options);

		$lo_results = $this->find()->applyOptions($la_options)->select(['existing' => 1])->where($aa_conditions)->limit(1)->disableHydration()->toArray();


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
	 * Sets specific column types for attributes
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		if (str_starts_with($this->getTable(), 'attributes_')) {
			foreach ($this->getAttributes() as $lo_attribute) {
				$la_column = $ao_schema->getColumn($lo_attribute->identifier);
				if ($lo_attribute->type === 'json') {
					$ao_schema->setColumnType($lo_attribute->identifier, 'json');
				}

				if ($la_column && $la_column['default'] !== $lo_attribute->defaultValue) {
					$la_column['default'] = $lo_attribute->defaultValue;
					$ao_schema->addColumn($lo_attribute->identifier, $la_column);
				}
			}
		}
	}
}
