<?php declare(strict_types=1);


namespace Awyiss\Model;


use Awyiss\ORM\RulesChecker;
use Cake\ORM\Query;


abstract class Table extends \Cake\ORM\Table {
	use \Cake\Core\InstanceConfigTrait;


	/**
	 * The rules class name that is used.
	 *
	 * @var string
	 */
	public const RULES_CLASS = RulesChecker::class;
	protected array $_defaultConfig = [];
	protected ?bool $hasAttributes = NULL;
	protected string $attributesTable;


	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 *
	 * @throws \ReflectionException
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->_validatorClass = \Awyiss\Validation\Validator::class;

		/** @var \Awyiss\Validation\Validator $lo_validator */
		$lo_validator = $this->getValidator();
		$lo_validator->setI18nDomain($this->getAlias());

		if (str_starts_with($this->getTable(), '_attributes') || $this->getTable() == 'attri') { //$this->getTable() != 'attri' <- ?? Whatever CakePHP does, this is needed
			return;
		}

		$this->attributesTable = '_attributes_' . $this->getTable();
		if (is_null($this->hasAttributes)) {
			$this->hasAttributes = count($this->getConnection()->execute('SHOW TABLES LIKE \'' . $this->attributesTable . '\'')->fetchAll('assoc')) == 1;

			if ($this->hasAttributes) {
				$lo_assoc = $this->hasOne('Attributes')
					->setClassName(\Awyiss\Model\Table\Attributes::class)
					->setForeignKey('parent_id')
					->setProperty('attributes')
					->setDependent(TRUE);
				$lo_assoc->setTable($this->attributesTable);
			}
		}

		\Awyiss\Event\EventListenersProvider::loadListener($this->getAlias(), defined('IS_BACKEND') ? 'backend' : 'frontend');

		$this->addBehavior('Audit', $this->getConfig('audit', []));
		$this->addBehavior('DefaultValues', $this->getConfig('defaultValues', []));
		$this->addBehavior('EventTrigger', $this->getConfig('eventTrigger', []));
		$this->addBehavior('SoftDelete', $this->getConfig('softDelete', []));
		$this->addBehavior('SystemOrder', $this->getConfig('systemOrder', []));
		$this->addBehavior('TimeTracker', $this->getConfig('timeTracker', []));
	}


	/**
	 * @param null|string $as_type
	 * @param array $aa_options
	 *
	 * @return \Cake\ORM\Query
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
				$ls_type = 'withAttributes';
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
	 * @param mixed $conditions
	 *
	 * @return int
	 */
	/*public function deleteAll ($conditions): int {
		return $this->_table->updateAll([
			'deleted_on' => new \Cake\I18n\Time(),
			'deleted' => 1,
		], $conditions);

		return parent::deleteAll($conditions);
	}*/
}