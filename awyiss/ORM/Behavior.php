<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Cake\ORM\Table;


/**
 * General overwrite of the default Behavior class
 */
class Behavior extends \Cake\ORM\Behavior {
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
	 *
	 * @param Table $ao_table
	 * @param array $aa_config
	 */
	public function __construct(Table $ao_table, array $aa_config = []) {
		parent::__construct($ao_table, $aa_config);

		$la_implementedMethods = $this->getConfig('implementedMethods');
		if (empty($la_implementedMethods)) {
			$this->setConfig('implementedMethods', []);
		}

		$la_implementedEvents = $this->getConfig('implementedEvents');
		if ($la_implementedEvents === NULL) {
			$this->setConfig('implementedEvents', $this->defaultEvents);
		}
	}


	/**
	 * @inheritDoc
	 *
	 * This variation will use the class's "defaultEvents"-property instead of a hardcoded array of events names
	 */
	public function implementedEvents(): array {
		$li_priority = $this->getConfig('priority');
		$la_eventMap = $this->getConfig('implementedEvents', []);

		if (empty($la_eventMap)) {
			return [];
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();


		return $lo_table->buildEventMap($this, $la_eventMap, $li_priority);
	}


	/**
	 * @return array
	 *
	 * @noinspection PhpUnused
	 */
	public function getDefaultEvents(): array {
		return $this->defaultEvents;
	}


	public function enable() {
		if (array_key_exists('enabled', $this->_config)) {
			$this->_config['enabled'] = TRUE;


			return;
		}

		throw new \RuntimeException(sprintf('Cannot enable behavior `%s`', static::class));
	}


	public function disable() {
		if (array_key_exists('enabled', $this->_config)) {
			$this->_config['enabled'] = FALSE;


			return;
		}

		throw new \RuntimeException(sprintf('Cannot disable behavior `%s`', static::class));
	}
}
