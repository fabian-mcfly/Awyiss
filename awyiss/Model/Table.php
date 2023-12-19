<?php declare(strict_types=1);


namespace Awyiss\Model;


use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\Policy\GenericPagePolicy;
use Awyiss\Core\App;
use Awyiss\Model\Behavior\AuthorizeBehavior;
use Awyiss\Model\Behavior\DefaultValuesBehavior;
use Awyiss\Model\Behavior\Translate\EavStrategy;
use Awyiss\Model\Entity\Attribute;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Cake\Collection\Collection;
use Cake\Core\InstanceConfigTrait;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * Base Table
 *
 * @method Query findById(int $ai_id)
 * @method Query addSystemOrderQueryConditions(?Query $ao_query, EntityInterface $ao_entity)
 * @method AuthorizationServiceInterface getAuthorizationService()
 * @method bool|int getHighestSystemOrder(EntityInterface $ao_entity)
 * @method string|GenericPagePolicy|NULL getPolicyClass()
 * @method array getSystemOrderRelatedColumns(?EntityInterface $ao_entity = NULL)
 * @method array getAttributes()
 * @method string getAttributesTable(bool $ab_camelized = FALSE)
 * @method bool hasAttributes()
 * @method AuthorizeBehavior setAuthorizationService(AuthorizationServiceInterface $ao_authorizationService)
 * @method AuthorizeBehavior setPolicyClass(string|GenericPagePolicy|NULL $ax_policyClass)
 * @method AuthorizeBehavior skipAuthorizationCheck(bool $ab_skip = TRUE)
 * @method AuthorizeBehavior skipAuthorizationCheckOnce(bool $ab_skip = TRUE)
 * @method DefaultValuesBehavior newDefaultEntity(array $aa_additionalData = [])
 */
class Table extends \Cake\ORM\Table {
	use InstanceConfigTrait;


	/**
	 * Allows \Awyiss\Model\Table\AttributesTable to set attributes for this table.
	 *
	 * @var bool
	 */
	public const ATTRIBUTABLE = TRUE;
	/**
	 * @inheritDoc
	 */
	public const RULES_CLASS = RulesChecker::class;
	/**
	 * Name of the database table. Used in static::initialize() ($this->setTable(static::TABLE)) and in
	 * \Awyiss\Model\Table\AttributesTable::getScopes()
	 *
	 * @see \Awyiss\Model\Table\AttributesTable::getAvailableScopes();
	 *
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
		'beforeSoftDelete',
		'afterSoftDelete',
		'afterSoftDeleteCommit',
	];
	/**
	 * Validator class.
	 *
	 * @var string
	 */
	protected string $_validatorClass = \Awyiss\Validation\Validator::class;
	/**
	 * @var string
	 */
	protected string $i18nDomain;


	/**
	 * @inheritDoc
	 */
	public function __construct (array $aa_config = []) {
		if (($this->_defaultConfig['implementedEvents'] ?? NULL) === NULL) {
			$this->setConfig('implementedEvents', $this->defaultEvents);
		}

		parent::__construct($aa_config);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize (array $aa_config): void {
		if (static::TABLE) {
			$this->setTable(static::TABLE);
		}

		if (!$this->_table) {
			return;
		}

		$this->setPrimaryKey('id');

		$lb_isAttributesTable = str_starts_with($this->getTable(), 'attributes_');


		if ($lb_isAttributesTable) {
			$this->addBehavior('Attributes', ['isAttributesTable' => TRUE] + $this->getConfig('attributes', []));
			if (!$this->getConfig('authorize.scope')) {
				$this->setConfig('authorize.scope', substr($this->getTable(), 11));
			}
		}
		else {
			$this->addBehavior('Attributes',
				['isAttributesTable' => FALSE] +
				$this->getConfig('attributes', []) +
				[
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
		$this->addBehavior('EventTrigger', $this->getConfig('eventTrigger', []));

		/*if ($lb_isAttributesTable) {
			dd($aa_config['translateLanguage'], $this->getConfig('translate', []));
		}*/

		if (!empty($aa_config['translateLanguage']) && $this->getConfig('translate', [])) {
			$this->addBehavior('Translate',
				$this->getConfig('translate') +
				[
					'allowEmptyTranslations' => FALSE,
					'defaultLocale' => '',
					'locale' => $aa_config['translateLanguage']->shortcode ?? NULL,
					'strategyClass' => EavStrategy::class,
				]
			);
		}

		$this->initializeSchema($this->getSchema());
	}


	/*
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 *
	public function find (?string $as_type = NULL, array $aa_options = []): Query {
		$lo_query = $this->query();
		$lo_query->select();

		$ls_type = $as_type ?: 'all';
		if ($ls_type == 'all' && ! defined('IS_BACKEND') || ! IS_BACKEND) {
			$ls_type = 'active';
		}

		return $this->callFinder($ls_type, $lo_query, $aa_options);
	}*/


	/*
	 * @param Query $ao_query
	 * @param array $aa_options
	 *
	 * @return Query
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 *
	public function findAll (Query $ao_query, array $aa_options): Query {
		if ($this->hasAttributes()) {
			$ao_query->contain($this->getAttributesTable(TRUE));
		}

		if ($this->getConfig('translate') && $this->hasBehavior('Translate')) {
			$ao_query->find('translations');
		}

		return $ao_query;
	}*/


	/**
	 * @param SelectQuery $ao_query
	 * @param array $aa_options
	 *
	 * @return SelectQuery
	 *
	 * @noinspection PhpUnused
	 */
	public function findActive (SelectQuery $ao_query): SelectQuery {
		if ( ! $this->getSchema()->getColumn('active')) {
			throw new RuntimeException(sprintf('Cannot use `findActive` on table `%s` ', $this->getAlias()));
		}

		$ao_query->where(['active' => TRUE]);

		return $ao_query;
	}


	/*public function deleteAll ($conditions): int {
		return $this->_table->updateAll([
			'deleted_on' => new \Cake\I18n\Time(),
			'deleted' => 1,
		], $conditions);

		return parent::deleteAll($conditions);
	}*/


	/**
	 * @inheritDoc
	 */
	public function getEntityClass (): string {
		if ( ! $this->_entityClass) {
			$ls_default = Entity::class;
			$ls_self = static::class;
			$la_parts = explode('\\', $ls_self);

			if ($ls_self === self::class || count($la_parts) < 3) {
				return $this->_entityClass = $ls_default;
			}

			$ls_alias = Inflector::classify(Inflector::underscore(substr(array_pop($la_parts), 0, -5)));
			$ls_name = implode('\\', array_slice($la_parts, 0, -1)) . '\\Entity\\' . $ls_alias;
			/*if ( ! class_exists($ls_name)) {
			var_dump($ls_name, $ls_default);
				return $this->_entityClass = $ls_default;
			}*/

			/** @var class-string<EntityInterface>|NULL $ls_class */
			$ls_class = App::className($ls_name, 'Model/Entity');
			if ( ! $ls_class) {
				$ls_class = App::className($ls_alias, 'Model/Entity');
			}

			if ( ! $ls_class) {
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
	 *
	 * @return BelongsTo
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function belongsTo (string $as_associated, array $aa_options = []): BelongsTo {
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
	 *
	 * @return HasOne
	 *
	 * * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function hasOne (string $as_associated, array $aa_options = []): HasOne {
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
	 *
	 * @return HasOne
	 *
	 * * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function hasMany (string $as_associated, array $aa_options = []): HasMany {
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
	 *
	 * @return HasOne
	 *
	 * * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function belongsToMany (string $as_associated, array $aa_options = []): BelongsToMany {
		$aa_options += ['sourceTable' => $this];

		/** @var BelongsToMany $lo_association */
		$lo_association = $this->_associations->load(BelongsToMany::class, $as_associated, $aa_options);

		return $lo_association;
	}



	/**
	 * Returns true if there is any record in this repository matching the specified conditions.
	 *
	 * Does the same as \Cake\ORM\Table::exists but accepts an array of options as the second parameter
	 *
	 * @param $aa_conditions
	 * @param array $aa_options
	 *
	 * @return bool
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function exists ($aa_conditions, array $aa_options = []): bool {
		return (bool)count(
			$this->find()
				->applyOptions($aa_options)
				->select(['existing' => 1])
				->where($aa_conditions)
				->limit(1)
				->disableHydration()
				->toArray()
		);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$ao_validator->setI18nDomain($this->getI18nDomain());
		$ao_validator->setStopOnFailure();

		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 */
	public function implementedEvents (): array {
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
	 *
	 * @return array
	 */
	public function buildEventMap (Table|Behavior $ao_instance, array $aa_eventMap, int $ai_priority = NULL): array {
		$la_eventMap = [];
		$li_priority = $ai_priority;
		foreach ($aa_eventMap as $ls_event => $lx_callable) {
			if (is_array($lx_callable)) {
				if (isset($lx_callable['priority'])) {
					$li_priority = $lx_callable['priority'];
				}

				$lx_callable = $lx_callable['callable'] ?? NULL;
			}

			if ((is_string($lx_callable) && ! method_exists($ao_instance, $lx_callable)) || ( ! is_string($lx_callable) && ! is_callable($lx_callable))) {
				continue;
			}

			if (is_numeric($ls_event)) {
				if ( ! is_string($lx_callable)) {
					throw new RuntimeException(sprintf('When provided a callable, the key must be a string. `%s` given', gettype($ls_event)));
				}
				$ls_event = 'Model.' . $lx_callable;
			}

			if ($li_priority === NULL) {
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
	 * Sets specific column types for attributes
	 */
	protected function initializeSchema (TableSchemaInterface $ao_schema): void {
		/** @var Collection $lo_attributes */
		static $lo_attributes;

		if (str_starts_with($this->getTable(), 'attributes_')) {
			if ( ! $lo_attributes) {
				$lo_attributesTable = FactoryLocator::get('Table')->get('Attributes');
				$lo_attributes = $lo_attributesTable->find('all',
					authorize: [
						'skip' => TRUE
					],
				)->all()->groupBy('scope');
			}

			/** @noinspection PhpUndefinedMethodInspection */
			if ($lo_attributes->offsetExists($ls_offset = substr($this->getTable(), 11))) {
				/**
				 * @var Attribute $lo_attribute
				 * @noinspection PhpUndefinedMethodInspection
				*/
				foreach ($lo_attributes->offsetGet($ls_offset) AS $lo_attribute) {
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


	/**
	 * @return string
	 */
	public function getI18nDomain (): string {
		if (isset($this->i18nDomain)) {
			return $this->i18nDomain;
		}

		$ls_alias = $this->getAlias();
		if (str_starts_with($ls_alias, 'Attributes') && strlen($ls_alias) > 10) {
			$ls_alias = substr($ls_alias, 10);
		}

		return $this->i18nDomain = Inflector::underscore($ls_alias);
	}
}
