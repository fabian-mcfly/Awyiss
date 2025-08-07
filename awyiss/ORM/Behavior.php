<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Cake\ORM\Behavior as BaseBehavior;
use Cake\ORM\Table;
use RuntimeException;


/**
 * General overwrite of the default Behavior class
 *
 * @method \Awyiss\Model\Table table()
 */
class Behavior extends BaseBehavior {
	/**
	 * This array contains all implemented events.
	 *
	 * @var array
	 */
	protected array $defaultEvents = [
		'beforeMarshal',
		'afterMarshal',
		'beforeFind',
		'beforeSave',
		'afterSave',
		'afterSaveCommit',
		'beforeDelete',
		'afterDelete',
		'afterDeleteCommit',
		'buildValidator',
		'buildRules',
		'beforeRules',
		'afterRules',
	];


	/**
	 * @inheritDoc
	 */
	public function __construct(Table $table, array $config = []) {
		parent::__construct($table, $config);

		$la_implementedMethods = $this->getConfig('implementedMethods');
		if (empty($la_implementedMethods)) {
			$this->setConfig('implementedMethods', []);
		}

		$la_implementedEvents = $this->getConfig('implementedEvents');
		if ($la_implementedEvents === null) {
			$this->setConfig('implementedEvents', $this->defaultEvents);
		}
	}


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
	}


	/**
	 * This variation will use the `defaultEvents`-property of the extending behavior
	 * instead of a hardcoded array of events names.
	 *
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		$li_priority = $this->getConfig('priority');
		$la_eventMap = $this->getConfig('implementedEvents', []);

		if (empty($la_eventMap)) {
			return [];
		}


		return $this->table()->buildEventMap($this, $la_eventMap, $li_priority);
	}


	/**
	 * @return static
	 */
	public function enable(): void {
		if (array_key_exists('enabled', $this->_config)) {
			$this->_config['enabled'] = true;


			return;
		}

		throw new RuntimeException(sprintf('Cannot enable behavior `%s`', static::class));
	}


	/**
	 * @return static
	 */
	public function disable(): void {
		if (array_key_exists('enabled', $this->_config)) {
			$this->_config['enabled'] = false;


			return;
		}

		throw new RuntimeException(sprintf('Cannot disable behavior `%s`', static::class));
	}


	/**
	 * @inheritDoc
	 * @return \Awyiss\Model\Table
	 */
	public function table(): Table {
		return $this->_table;
	}
}
