<?php declare(strict_types=1);


namespace Awyiss\Model;


use ArrayObject;
use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Model\Behavior\AccessBehavior;
use Awyiss\ORM\RulesChecker;
use Awyiss\Validation\Validator;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
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
	 * @inheritDoc
	 */
	public const RULES_CLASS = RulesChecker::class;
	/**
	 * The attributes table is name "_attributes_<name>" with <name> being the current table's name.
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
	 * @var null|bool
	 */
	protected ?bool $hasAttributes = NULL;
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


		if (str_starts_with($this->getTable(), '_attributes') || $this->getTable() == 'attributes') {
			return;
		}

		$this->attributesTable = '_attributes_' . $this->getTable();
		if (is_null($this->hasAttributes)) {
			$this->hasAttributes = count($this->getConnection()->execute('SHOW TABLES LIKE \'' . $this->attributesTable . '\'')->fetchAll('assoc')) == 1;

			if ($this->hasAttributes) {
				$lo_assoc = $this->hasOne('Attributes')
					->setClassName(Table\AttributesTable::class)
					->setForeignKey('parent_id')
					->setProperty('attributes')
					->setDependent(TRUE);
				$lo_assoc->setTable($this->attributesTable);
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

		if ($this->getConfig('translate', []) && !in_array($this->getTable(), ['languages'])) {
			$lo_defaultLanguage = \Awyiss\Middleware\LocaleMiddleware::getDefaultLanguage(defined('IS_BACKEND') && IS_BACKEND ? 'backend' : 'frontend');

			$this->addBehavior('Translate', $this->getConfig('translate', []) + [
				'allowEmptyTranslations' => FALSE,
				//'defaultLocale' => $lo_defaultLanguage?->shortcode ?? NULL,
				'defaultLocale' => '',
				'strategyClass' => \Awyiss\Model\Behavior\Translate\EavStrategy::class,
			]);
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
			$ao_query->contain('Attributes');
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


	/** @noinspection PhpUnusedParameterInspection */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$lo_event = $this->dispatchEvent($ao_entity->isNew() ? 'Model.beforeCreate' : 'Model.beforeUpdate', ['entity' => $ao_entity, 'options' => $ao_options]);

		if ($lo_event->isStopped()) {
			$ao_event->stopPropagation();
			$ao_event->setResult($lo_event->getResult());

			return;
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
}