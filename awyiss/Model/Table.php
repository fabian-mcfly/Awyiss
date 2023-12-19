<?php declare(strict_types=1);


namespace Awyiss\Model;


use ArrayObject;
use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Core\App;
use Awyiss\Model\Behavior\AccessBehavior;
use Awyiss\ORM\RulesChecker;
use Awyiss\Validation\Validator;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * @method Query addSystemOrderQueryConditions(?Query $ao_query, EntityInterface $ao_entity)
 * @method AuthorizationServiceInterface getAuthorizationService()
 * @method bool|int getHighestSystemOrder(EntityInterface $ao_entity)
 * @method string|AnonymousPolicy|NULL getPolicyClass()
 * @method array getSystemOrderRelatedColumns(?EntityInterface $ao_entity = NULL)
 * @method AccessBehavior setAuthorizationService(AuthorizationServiceInterface $ao_authorizationService)
 * @method AccessBehavior setPolicyClass(string|AnonymousPolicy|NULL $ax_policyClass)
 * @method AccessBehavior skipAccessCheck(bool $ab_skip = TRUE)
 * @method AccessBehavior skipAccessCheckOnce(bool $ab_skip = TRUE)
 */
class Table extends \Cake\ORM\Table {
	use \Cake\Core\InstanceConfigTrait;


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
	 * Name of the database table. Used in static::initialize() ($this->setTable(static::TABLE));
	 *
	 * @var string
	 */
	public const TABLE = '';
	/**
	 * The attributes table is name "attributes_<name>" with <name> being the current table's name.
	 *
	 * @var string
	 */
	protected string $attributesTable;
	/**
	 * The default values set for this table
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [];
	/**
	 * A boolean value, indicating if the table has a corresponding attributes table.
	 *
	 * @var bool
	 */
	protected bool $hasAttributes = FALSE;
	/**
	 * Validator class.
	 *
	 * @var string
	 */
	protected $_validatorClass = Validator::class;


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @throws \ReflectionException
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		//$this->setDisplayField('label');

		/** @var \Awyiss\Validation\Validator $lo_validator */
		$lo_validator = $this->getValidator();
		$lo_validator->setI18nDomain($this->getAlias());


		if (str_starts_with($this->getTable(), 'attributes_')/* || $this->getTable() == 'attributes'*/) {
			$this->addBehavior('AttributesDataTypes');

			return;
		}

		$this->attributesTable = 'attributes_' . $this->getTable();
		if (!$this->hasAttributes) {
			if (/*$ls_attributesClass = */App::className(Inflector::camelize($this->attributesTable), 'Model\Table', 'Table')) {
				$this->hasAttributes = TRUE;

				$this->hasOne(Inflector::camelize($this->attributesTable))
					//->setClassName($ls_attributesClass)
					//->setForeignKey($this->getTable() . '_id')
					->setProperty('attributes')
					->setDependent(TRUE);
				//$lo_assoc->setTable($this->attributesTable);
			}
		}


		\Awyiss\Event\EventListenersProvider::loadListener($this->getAlias(), defined('IS_BACKEND') && IS_BACKEND ? 'backend' : 'frontend');


		$this->addBehavior('Access', $this->getConfig('access', []) + ['priority' => 1]);
		$this->addBehavior('Audit', $this->getConfig('audit', []) + ['priority' => 99999]);
		$this->addBehavior('AutoPrefix', $this->getConfig('autoPrefix', []) + ['priority' => 99999]);
		$this->addBehavior('DefaultValues', $this->getConfig('defaultValues', []));
		$this->addBehavior('EventTrigger', $this->getConfig('eventTrigger', []));
		$this->addBehavior('SoftDelete', $this->getConfig('softDelete', []));
		$this->addBehavior('SystemOrder', $this->getConfig('systemOrder', []));


		if ($this->getConfig('translate', []) && $this->getTable() != 'languages') {
			$lo_defaultLanguage = \Awyiss\Middleware\LocaleMiddleware::getDefaultLanguage(defined('IS_BACKEND') && IS_BACKEND ? 'backend' : 'frontend');

			$this->addBehavior('Translate', $this->getConfig('translate', []) + [
				'allowEmptyTranslations' => FALSE,
				//'defaultLocale' => $lo_defaultLanguage?->shortcode ?? NULL,
				'defaultLocale' => '',
				'strategyClass' => \Awyiss\Model\Behavior\Translate\EavStrategy::class,
			]);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$this->getBehavior('Translate')->setLocale($lo_defaultLanguage?->shortcode ?? NULL);
		}
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function find (?string $as_type = NULL, array $aa_options = []): Query {
		$lo_query = $this->query();
		$lo_query->select();

		$ls_type = $as_type;
		if (is_null($as_type)) {
			if (defined('IS_BACKEND') && IS_BACKEND) {
				$ls_type = 'all';
			}
			else {
				$ls_type = 'activeAndWithAttributes';
			}
		}

		return $this->callFinder($ls_type, $lo_query, $aa_options);
	}


	/**
	 * @param \Cake\ORM\Query $ao_query
	 * @param array $aa_options
	 *
	 * @return \Cake\ORM\Query
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findWithAttributes (Query $ao_query, array $aa_options): Query {
		if ($this->hasAttributes) {
			$ao_query->contain(Inflector::camelize($this->attributesTable));
			//dd($ao_query->order(['background_color DESC NULLS FIRST']));
		}

		return $ao_query;
	}


	/**
	 * @param \Cake\ORM\Query $ao_query
	 * @param array $aa_options
	 *
	 * @return \Cake\ORM\Query
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findActive (Query $ao_query, array $aa_options): Query {
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

			/** @var class-string<\Cake\Datasource\EntityInterface>|null $ls_class */
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
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$lo_event = $this->dispatchEvent($ao_entity->isNew() ? 'Model.beforeCreate' : 'Model.beforeUpdate', ['entity' => $ao_entity, 'options' => $ao_options]);

		if ($lo_event->isStopped()) {
			$ao_event->stopPropagation();
			$ao_event->setResult($lo_event->getResult());

			//return;
		}

		/*if ($this->hasBehavior('Translate')) {
			$lo_translateBehavior = $this->getBehavior('Translate');
			$ls_lang = \Awyiss\Middleware\LocaleMiddleware::getLanguageFromUrl(TRUE)?->shortcode ?? NULL;

			foreach ($lo_translateBehavior->getConfig('fields') AS $ls_field) {
				if (!$ao_entity->translation($ls_lang)->$ls_field) {
					$ao_entity->translation($ls_lang)->set([$ls_field => $ao_entity->$ls_field], ['guard' => false]);
				}
			}
		}*/
	}


	/** @noinspection PhpUnusedParameterInspection */
	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$this->dispatchEvent($ao_entity->isNew() ? 'Model.afterCreate' : 'Model.afterUpdate', ['entity' => $ao_entity, 'options' => $ao_options]);
	}


	/**
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpUnused
	 */
	public function afterSaveCommit (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$this->dispatchEvent($ao_entity->isNew() ? 'Model.afterCreateCommit' : 'Model.afterUpdateCommit', ['entity' => $ao_entity, 'options' => $ao_options]);
	}


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _initializeSchema (TableSchemaInterface $ao_schema): TableSchemaInterface {
		/** @var \Cake\Collection\Collection $lo_attributes */
		static $lo_attributes;

		if (str_starts_with($this->getTable(), 'attributes_')) {
			if (!$lo_attributes) {
				$lo_attributesTable = \Cake\Datasource\FactoryLocator::get('Table')->get('Attributes');
				$lo_attributes = $lo_attributesTable->find('all', [
					'access' => [
						'skip' => TRUE
					],
				])->all()->groupBy('scope');
			}

			/** @noinspection PhpUndefinedMethodInspection */
			if ($lo_attributes->offsetExists($ls_offset = substr($this->getTable(), 11))) {
				/**
				 * @var \Awyiss\Model\Entity\Attribute $lo_attribute
				 * @noinspection PhpUndefinedMethodInspection
				*/
				foreach ($lo_attributes->offsetGet($ls_offset) AS $lo_attribute) {
					if ($lo_attribute->type === 'json') {
						$ao_schema->setColumnType($lo_attribute->name, 'json');
					}
				}
			}
		}

		return $ao_schema;
	}
}