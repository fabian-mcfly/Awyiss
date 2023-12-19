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
		'Model.beforeMarshal' => 'beforeMarshal',
		'Model.afterMarshal' => 'afterMarshal',
		'Model.beforeFind' => 'beforeFind',
		'Model.beforeSave' => 'beforeSave',
		'Model.afterSave' => 'afterSave',
		'Model.afterSaveCommit' => 'afterSaveCommit',
		'Model.beforeDelete' => 'beforeDelete',
		'Model.afterDelete' => 'afterDelete',
		'Model.afterDeleteCommit' => 'afterDeleteCommit',
		'Model.buildValidator' => 'buildValidator',
		'Model.buildRules' => 'buildRules',
		'Model.beforeRules' => 'beforeRules',
		'Model.afterRules' => 'afterRules',
	];


	/**
	 * @inheritDoc
	 *
	 * @param \Cake\ORM\Table $ao_table
	 * @param array $aa_config
	 */
	public function __construct(Table $ao_table, array $aa_config = []) {
		parent::__construct($ao_table, $aa_config);

		$la_implementedEvents = $this->getConfig('implementedEvents');
		if ($la_implementedEvents === NULL) {
			$this->setConfig('implementedEvents', $this->defaultEvents);
		}
	}


	/**
	 * @inheritDoc
	 *
	 * This variation will use the class' "defaultEvents"-property instead of a hardcoded array of events names
	 */
	public function implementedEvents (): array {
		$li_priority = $this->getConfig('priority');
		$la_eventMap = $this->getConfig('implementedEvents', []);

		$la_events = [];
		foreach ($la_eventMap as $ls_event => $lx_callable) {
			if (is_array($lx_callable)) {
				if (isset($lx_callable['priority'])) {
					$li_priority = $lx_callable['priority'];
				}

				$lx_callable = $lx_callable['callable'] ?? NULL;
			}

			if ((is_string($lx_callable) && ! method_exists($this, $lx_callable))
				|| (!is_string($lx_callable) && ! is_callable($lx_callable))) {
				continue;
			}

			if ($li_priority === NULL) {
				$la_events[ $ls_event ] = $lx_callable;
			}
			else {
				$la_events[ $ls_event ] = [
					'callable' => $lx_callable,
					'priority' => $li_priority,
				];
			}
		}

		return $la_events;
	}
}