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
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class SystemOrderComponent extends Component {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		// Can be a boolean value or an array containing all action names for which the records should get autoloaded
		'autoload' => ['add', 'edit'],
		// Singularized variable name of the entity that's used to autoload records
		'entityName' => null,
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
		$controller = $this->getController();
		$view = $controller->viewBuilder();

		if (
			!$this->getConfig('tableName')
			|| Inflector::variable($this->getConfig('field', 'systemOrder')) !== 'systemOrder'
		) {
			return;
		}

		/** @var \Awyiss\Model\Table $table */
		$table = $controller->{$this->getConfig('tableName')} ?? null;

		//Do nothing when no table's set or when the behavior is disabled
		if (
			!$table
			|| !$table->hasBehavior('SystemOrder')
			|| !$table->getBehavior('SystemOrder')->getConfig('enabled')
		) {
			return;
		}

		$records = $this->getConfig('records');

		if (!$records) {
			$records = $this->autoloadRecords($controller, $view);
		}

		//Set view vars if they don't already exist
		if (!$view->getVar('systemOrderRecords')) {
			$view->setVar('systemOrderRecords', $records);
		}

		//Set view vars if they don't already exist
		if (!$view->getVar('systemOrderRelatedColumns')) {
			$relatedColumns = $this->getConfig('relatedColumns');
			if (!$relatedColumns) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$relatedColumns = $table->getSystemOrderRelatedColumns();
			}

			$view->setVar('systemOrderRelatedColumns', $relatedColumns);
		}

		//Set view vars if they don't already exist
		if (!$view->getVar('systemOrderField')) {
			$view->setVar('systemOrderField', $this->getConfig('field'));
		}
	}


	/**
	 * @param \Awyiss\Controller\AppController $controller
	 * @param \Cake\View\ViewBuilder $view
	 * @return \Cake\Datasource\ResultSetInterface|null
	 */
	protected function autoloadRecords(AppController $controller, ViewBuilder $view): ?ResultSetInterface {
		$action = $controller->getRequest()->getParam('action');
		$autoload = $this->getConfig('autoload');

		//Shall we autoload the records?
		if (
			$autoload !== true
			&& (
				!is_array($autoload)
				|| !in_array($action, $autoload)
			)
			&& (
				!is_string($autoload)
				|| $action !== $autoload
			)
		) {
			return null;
		}

		$varName = $this->getConfig('entityName');
		$entity = $view->getVar($varName);

		if (!$entity) {
			return null;
		}

		//Get the records from the database
		$records = $this->getRecords($entity);

		//Make sure the system_order property of the found entity is a legit one
		$this->ensurePossibleSystemOrder($entity);

		$request = $controller->getRequest();
		//When system_order is part of the request data, overwrite it since it might be outdated
		if ($request->getData('systemOrder')) {
			$request = $request->withData('systemOrder', $entity->systemOrder);
			$controller->setRequest($request);
		}


		return $records;
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
		$controller = $this->getController();
		/** @var \Awyiss\Model\Table $table */
		$table = $controller->{$this->getConfig('tableName')};

		if (!$table) {
			return null;
		}

		if (!$table->hasBehavior('SystemOrder') || !$table->getBehavior('SystemOrder')->getConfig('enabled')) {
			return null;
		}

		$query = $table->addSystemOrderQueryConditions($table->find(), $entity);
		if ($table->hasBehavior('MediaAssignment')) {
			$query->find('mediaAssignments', useMediaEntity: true);
		}

		$systemOrderRecords = $query->all();

		$this->setConfig('records', $systemOrderRecords);


		return $systemOrderRecords;
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

		/** @var \Awyiss\Model\Table $table */
		$table = $this->getController()->{$this->getConfig('tableName')};
		$records ??= $this->getConfig('records') ?? $this->getRecords($entity);

		$highestSystemOrder = $records->max('systemOrder')?->systemOrder ?? 0;

		$requestData = $this
			->getController()
			->getRequest()
			->getData()
		;
		$systemOrder = $entity->get('systemOrder');

		if ($requestData['reloadForm'] ?? false) {
			if ($requestData['reloadForm'] === '1') {
				$requestData['reloadForm'] = null;
			}

			if ($table->hasDirtySystemOrderRelatedColumns($entity, $requestData['reloadForm'])) {
				unset($requestData['systemOrder']);
				$entity->set('systemOrder');
			}
			else {
				$requestData['systemOrder'] = $entity->hasOriginal('systemOrder')
					? $entity->getOriginal('systemOrder')
					: $entity->get('systemOrder');
			}
		}

		if (isset($requestData['systemOrder'])) {
			$systemOrder = $requestData['systemOrder'];
		}
		elseif (!$entity->systemOrder || $entity->isNew()) {
			$systemOrder = $highestSystemOrder + 1;
		}

		if (!$entity->systemOrder || $entity->isNew()) {
			$entity->set('systemOrder', min($systemOrder, $highestSystemOrder + 1));
		}
		elseif ($entity->systemOrder > $highestSystemOrder) {
			$entity->set('systemOrder', min($systemOrder, $highestSystemOrder));
		}
	}
}
