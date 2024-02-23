<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\ResultSet;
use Cake\Utility\Inflector;


/**
 * This component provides and handles system order-specific logic.
 *
 * It sets view vars if they don't already exist,
 * offers a convenient `getRecords` method to retreive all categories for a given entity,
 * and `ensurePossibleSystemOrder()` to make sure the set `system_order` is valid.
 *
 * @method \Awyiss\Controller\AppController getController()
 */
class SystemOrderComponent extends Component {
	/**
	 * @inheritDoc
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'autoload' => ['add', 'edit'], //can be a boolean value or an array containing all action names for which the records should get autoloaded
		'entityName' => null, //singlularized variable name of the entity that's used to autoload records
		'field' => 'systemOrder',
		'records' => null,
		'tableName' => null,
	];


	/**
	 * Called after `Controller::beforeFilter()` method, and before the controller action is called.
	 *
	 * @return void
	 */
	public function startup(): void {
		if ($this->getConfig('entityName') === null) {
			$this->setConfig('entityName', Inflector::variable(Inflector::singularize($this->getController()->getName())));
		}

		if ($this->getConfig('field') !== 'systemOrder') {
			$this->setConfig('autoload', false);
		}

		if ($this->getConfig('tableName') === null) {
			$this->setConfig('tableName', $this->getController()->getName());
		}
	}


	/**
	 * Sets view vars before rendering a view
	 *  - ao_systemOrderRecords
	 *  - aa_systemOrderRelatedColumns
	 *
	 * @return void
	 */
	public function beforeRender(): void {
		$lo_controller = $this->getController();
		$lo_view = $lo_controller->viewBuilder();

		if (!$this->getConfig('tableName') || $this->getConfig('field') !== 'systemOrder') {
			return;
		}

		/** @var \Cake\ORM\Table $lo_table */
		$lo_table = $lo_controller->{$this->getConfig('tableName')} ?? null;

		//Do nothing when no table's set or when the behavior is disabled
		if (!$lo_table || !$lo_table->hasBehavior('SystemOrder') || !$lo_table->getBehavior('SystemOrder')->getConfig('enabled')) {
			return;
		}

		$lo_records = $this->getConfig('records');

		if (!$lo_records) {
			$ls_action = $lo_controller->getRequest()->getParam('action');
			$lx_autoload = $this->getConfig('autoload');

			//Shall we autoload the records?
			if ($lx_autoload === true || (is_array($lx_autoload) && in_array($ls_action, $lx_autoload)) || (is_string($lx_autoload) && $ls_action === $lx_autoload)) {
				$ls_varName = 'ao_' . $this->getConfig('entityName');
				$lo_entity = $lo_view->getVar($ls_varName);
				if ($lo_entity) {
					//Get the records from the database
					$lo_records = $this->getRecords($lo_entity);

					//Make sure the system_order property of the found entity is a legit one
					$this->ensurePossibleSystemOrder($lo_entity);

					$lo_request = $lo_controller->getRequest();
					//When system_order is part of the request data, overwrite it since it might be outdated
					if ($lo_request->getData('system_order')) {
						$lo_request = $lo_request->withData('system_order', $lo_entity->systemOrder);
						$lo_controller->setRequest($lo_request);
					}
				}
			}
		}

		//Set view vars if they don't already exist
		if (!$lo_view->getVar('ao_systemOrderRecords')) {
			$lo_view->setVar('ao_systemOrderRecords', $lo_records);
		}

		//Set view vars if they don't already exist
		if (!$lo_view->getVar('aa_systemOrderRelatedColumns')) {
			$la_relatedColumns = $this->getConfig('relatedColumns');
			if (!$la_relatedColumns) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$la_relatedColumns = $lo_table->getSystemOrderRelatedColumns();
			}

			$lo_view->setVar('aa_systemOrderRelatedColumns', $la_relatedColumns);
		}

		//Set view vars if they don't already exist
		if (!$lo_view->getVar('as_systemOrderField')) {
			$lo_view->setVar('as_systemOrderField', $this->getConfig('field'));
		}
	}


	/**
	 * Load records from the same table as the entity's, limited to specified conditions provided by the
	 * `SystemOrderBehavior::addQueryConditions()` method.
	 *
	 * @param EntityInterface $ao_entity
	 * @return ResultSetInterface|null
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions() method
	 */
	public function getRecords(EntityInterface $ao_entity): ?ResultSetInterface {
		$lo_controller = $this->getController();
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $lo_controller->{$this->getConfig('tableName')};

		if (!$lo_table) {
			return null;
		}

		if (!$lo_table->hasBehavior('SystemOrder') || !$lo_table->getBehavior('SystemOrder')->getConfig('enabled')) {
			return null;
		}

		$lo_systemOrderQuery = $lo_table->addSystemOrderQueryConditions(null, $ao_entity);
		$lo_systemOrderRecords = $lo_systemOrderQuery->all();

		$this->setConfig('records', $lo_systemOrderRecords);


		return $lo_systemOrderRecords;
	}


	/**
	 * Make sure the `system_order` of `$ao_entity` is legit.
	 * Possible values are everything between 1 and the highest system order of all records provided/set in the config
	 *
	 * For Example: for 4 existing records, possible values are [1-4]
	 *
	 * If the entity has no `system_order` set, the maximum possible value is increased by one.
	 *
	 * For example: for 4 existing records, a new entity can have system_order of [1-5]
	 *
	 * @param EntityInterface $ao_entity
	 * @param ResultSet|null $ao_records
	 * @return void
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function ensurePossibleSystemOrder(EntityInterface $ao_entity, ?ResultSet $ao_records = null): void {
		if (!$ao_entity->has('systemOrder') || $this->getConfig('field') !== 'systemOrder') {
			return;
		}

		$lo_records = $ao_records ?? $this->getConfig('records') ?? $this->getRecords($ao_entity);

		$li_highestSystemOrder = $lo_records->max('systemOrder')?->systemOrder ?? 0;

		if (empty($ao_entity->systemOrder)) {
			$ao_entity->set('systemOrder', $li_highestSystemOrder + 1);
		}
		elseif ($ao_entity->systemOrder > $li_highestSystemOrder) {
			$ao_entity->set('systemOrder', $li_highestSystemOrder);
		}
	}
}
