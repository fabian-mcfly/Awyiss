<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Controller\AppController;
use Awyiss\Utility\Inflector;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\ResultSet;
use Cake\View\ViewBuilder;


/**
 * This component provides and handles system order-specific logic.
 *
 * It sets view vars if they don't already exist,
 * offers a convenient `getRecords` method to retrieve all categories for a given entity,
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
		'entityName' => null, //singularized variable name of the entity that's used to autoload records
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

		if (Inflector::variable($this->getConfig('field', 'systemOrder')) !== 'systemOrder') {
			$this->setConfig('autoload', false);
		}

		if ($this->getConfig('tableName') === null) {
			$this->setConfig('tableName', $this->getController()->getName());
		}
	}


	/**
	 * Sets view vars before rendering a view
	 *  - systemOrderRecords
	 *  - systemOrderRelatedColumns
	 *
	 * @return void
	 */
	public function beforeRender(): void {
		$lo_controller = $this->getController();
		$lo_view = $lo_controller->viewBuilder();

		if (!$this->getConfig('tableName') || Inflector::variable($this->getConfig('field', 'systemOrder')) !== 'systemOrder') {
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
			$lo_records = $this->autoloadRecords($lo_controller, $lo_view);
		}

		//Set view vars if they don't already exist
		if (!$lo_view->getVar('systemOrderRecords')) {
			$lo_view->setVar('systemOrderRecords', $lo_records);
		}

		//Set view vars if they don't already exist
		if (!$lo_view->getVar('systemOrderRelatedColumns')) {
			$la_relatedColumns = $this->getConfig('relatedColumns');
			if (!$la_relatedColumns) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$la_relatedColumns = $lo_table->getSystemOrderRelatedColumns();
			}

			$lo_view->setVar('systemOrderRelatedColumns', $la_relatedColumns);
		}

		//Set view vars if they don't already exist
		if (!$lo_view->getVar('systemOrderField')) {
			$lo_view->setVar('systemOrderField', $this->getConfig('field'));
		}
	}


	/**
	 * @param \Awyiss\Controller\AppController $controller
	 * @param \Cake\View\ViewBuilder $view
	 * @return \Cake\Datasource\ResultSetInterface|null
	 */
	protected function autoloadRecords(AppController $controller, ViewBuilder $view): ?ResultSetInterface {
		$ls_action = $controller->getRequest()->getParam('action');
		$lx_autoload = $this->getConfig('autoload');

		//Shall we autoload the records?
		if ($lx_autoload !== true && (!is_array($lx_autoload) || !in_array($ls_action, $lx_autoload)) && (!is_string($lx_autoload) || $ls_action !== $lx_autoload)) {
			return null;
		}

		$ls_varName = $this->getConfig('entityName');
		$lo_entity = $view->getVar($ls_varName);

		if (!$lo_entity) {
			return null;
		}

		//Get the records from the database
		$lo_records = $this->getRecords($lo_entity);

		//Make sure the system_order property of the found entity is a legit one
		$this->ensurePossibleSystemOrder($lo_entity);

		$lo_request = $controller->getRequest();
		//When system_order is part of the request data, overwrite it since it might be outdated
		if ($lo_request->getData('system_order')) {
			$lo_request = $lo_request->withData('system_order', $lo_entity->systemOrder);
			$controller->setRequest($lo_request);
		}


		return $lo_records;
	}


	/**
	 * Load records from the same table as the entity's, limited to specified conditions provided by the
	 * `SystemOrderBehavior::addQueryConditions()` method.
	 *
	 * @param EntityInterface $entity
	 * @return ResultSetInterface|null
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions() method
	 */
	public function getRecords(EntityInterface $entity): ?ResultSetInterface {
		$lo_controller = $this->getController();
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $lo_controller->{$this->getConfig('tableName')};

		if (!$lo_table) {
			return null;
		}

		if (!$lo_table->hasBehavior('SystemOrder') || !$lo_table->getBehavior('SystemOrder')->getConfig('enabled')) {
			return null;
		}

		$lo_query = $lo_table->addSystemOrderQueryConditions($lo_table->find(), $entity);
		if ($lo_table->hasBehavior('MediaAssignment')) {
			$lo_query->find('mediaAssignments', useMediaEntity: true);
		}

		$lo_systemOrderRecords = $lo_query->all();

		$this->setConfig('records', $lo_systemOrderRecords);


		return $lo_systemOrderRecords;
	}


	/**
	 * Make sure the `system_order` of `$entity` is legit.
	 * Possible values are everything between 1 and the highest system order of all records provided/set in the config
	 *
	 * For Example: for 4 existing records, possible values are [1-4]
	 *
	 * If the entity has no `system_order` set, the maximum possible value is increased by one.
	 *
	 * For example: for 4 existing records, a new entity can have system_order of [1-5]
	 *
	 * @param EntityInterface $entity
	 * @param ResultSet|null $records
	 * @return void
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function ensurePossibleSystemOrder(EntityInterface $entity, ?ResultSet $records = null): void {
		if (!$entity->has('systemOrder') || Inflector::variable($this->getConfig('field', 'systemOrder')) !== 'systemOrder') {
			return;
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->getController()->{$this->getConfig('tableName')};
		$lo_records = $records ?? $this->getConfig('records') ?? $this->getRecords($entity);

		$li_highestSystemOrder = $lo_records->max('systemOrder')?->systemOrder ?? 0;

		$la_requestData = $this->getController()->getRequest()->getData();
		$li_systemOrder = $entity->get('systemOrder');

		if ($la_requestData['reload_form'] ?? false) {
			if ($lo_table->hasDirtySystemOrderRelatedColumns($entity)) {
				unset($la_requestData['system_order']);
				$entity->set('systemOrder');
			}
			else {
				$la_requestData['system_order'] = $entity->hasOriginal('systemOrder') ? $entity->getOriginal('systemOrder') : $entity->get('systemOrder');
			}
		}

		if (isset($la_requestData['system_order'])) {
			$li_systemOrder = $la_requestData['system_order'];
		}
		elseif (!$entity->systemOrder || $entity->isNew()) {
			$li_systemOrder = $li_highestSystemOrder + 1;
		}

		if (!$entity->systemOrder || $entity->isNew()) {
			$entity->set('systemOrder', min($li_systemOrder, $li_highestSystemOrder + 1));
		}
		elseif ($entity->systemOrder > $li_highestSystemOrder) {
			$entity->set('systemOrder', min($li_systemOrder, $li_highestSystemOrder));
		}
	}
}
