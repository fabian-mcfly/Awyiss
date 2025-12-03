<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Event\Backend\ConfigurationListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Queue\Model\Table\QueuedJobsTable;


/**
 * ConfigurationListener Test Case
 *
 * @see \Awyiss\Event\Backend\ConfigurationListener
 */
class ConfigurationListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\ConfigurationListener
	 */
	protected ConfigurationListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new ConfigurationListener();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Configuration.beforeSave' => 'beforeSave',
			'Model.Configuration.afterSave' => 'afterSave',
			'Model.Configuration.afterSaveCommit' => 'afterSaveCommit',
			'Model.Configuration.beforeDelete' => 'beforeDelete',
			'Model.Configuration.afterDelete' => 'afterDelete',
			'Model.Configuration.afterDeleteCommit' => 'afterDeleteCommit',
			'Awyiss.Configuration.createCustomConfiguration' => 'createCustomConfiguration',
			'Awyiss.Configuration.deleteCustomConfiguration' => 'deleteCustomConfiguration',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::beforeSave()
	 */
	public function testBeforeSaveTypecastsValue(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity();
		$entity->patch([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => '85',
			'languageShortcode' => 'en',
		]);

		$event = new Event('Model.Configuration.beforeSave');
		$options = new ArrayObject([]);

		$this->listener->beforeSave($event, $entity, $options);

		$this->assertSame(85, $entity->value);

		$entity->patch([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'overview.displayed_fields',
			'value' => ['id', 'title', 'created_on'],
			'languageShortcode' => 'en',
		]);

		$this->listener->beforeSave($event, $entity, $options);

		$this->assertIsString($entity->value);
		$decodedValue = json_decode($entity->value, true);
		$this->assertIsArray($decodedValue);

		$entity->patch([
			'scope' => 'test_scope',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'test_identifier',
			'value' => (object)['key1' => 'value1', 'key2' => 'value2'],
			'languageShortcode' => 'en',
		]);

		$this->listener->beforeSave($event, $entity, $options);

		$this->assertSame('{"key1":"value1","key2":"value2"}', $entity->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitRecompilesFrontendScssWhenColumnClassNameChanged(): void {
		$this->listener = $this->getMockBuilder(ConfigurationListener::class)->onlyMethods([
			'createCustomConfiguration',
		])->getMock();

		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$callCount = 0;
		$designMiddlewareMock->expects($this->exactly(2))->method('compileScss')->with(
			true,
			$this->callback(function ($value) use (&$callCount) {
				$callCount++;

				if ($callCount === 1) {
					return $value === Awyiss::REALM_FRONTEND;
				}
				if ($callCount === 2) {
					return $value === Awyiss::REALM_BACKEND;
				}

				return false;
			})
		);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$configTable = $this->fetchTable('Configuration');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'contents',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'column_system.class_name',
			'value' => '\Awyiss\Utility\Content\BootstrapColumnSystem',
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$this->assertSame('\Awyiss\Utility\Content\BootstrapColumnSystem', Configure::read('Awyiss.Contents.Backend.columnSystem.className'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitRecompilesFrontendScssWhenColumnMaxColumnsChanged(): void {
		$this->listener = $this->getMockBuilder(ConfigurationListener::class)->onlyMethods([
			'createCustomConfiguration',
		])->getMock();

		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$callCount = 0;
		$designMiddlewareMock->expects($this->exactly(2))->method('compileScss')->with(
			true,
			$this->callback(function ($value) use (&$callCount) {
				$callCount++;

				if ($callCount === 1) {
					return $value === Awyiss::REALM_FRONTEND;
				}
				if ($callCount === 2) {
					return $value === Awyiss::REALM_BACKEND;
				}

				return false;
			})
		);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$configTable = $this->fetchTable('Configuration');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'contents',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'column_system.max_columns',
			'value' => 10,
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$this->assertSame(10, Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitNotRecompilesFrontendScssWhenNotContentsScope(): void {
		$this->listener = $this->getMockBuilder(ConfigurationListener::class)->onlyMethods([
			'createCustomConfiguration',
		])->getMock();

		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->expects($this->never())->method('compileScss');

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$configTable = $this->fetchTable('Configuration');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'widgets',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'column_system.max_columns',
			'value' => 10,
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$this->assertNull(Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitUnnestsNestedEntriesOfScope(): void {
		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'nest.enabled',
			'value' => false,
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			$this->assertNull($employee->parentId);
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			1 => 'Employee 1',
			2 => 'Employee 1.1',
			5 => 'Employee 2.1',
			7 => 'Employee 2.2.1',
			10 => 'Employee 3.1',
			3 => 'Employee 1.2',
			4 => 'Employee 2',
			6 => 'Employee 2.2',
			8 => 'Employee 2.2.2',
			11 => 'Employee 3.2',
			9 => 'Employee 3',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			1 => 1,
			2 => 2,
			5 => 3,
			7 => 4,
			10 => 5,
			3 => 6,
			4 => 7,
			6 => 8,
			8 => 9,
			11 => 10,
			9 => 11,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitUnnestingRebuildsSystemOrder(): void {
		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		Configure::write('Awyiss.Employees.Backend.systemOrder.field', 'title');
		Configure::write('Awyiss.Employees.Backend.systemOrder.direction', SORT_DESC);

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'nest.enabled',
			'value' => false,
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			$this->assertNull($employee->parentId);
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			11 => 'Employee 3.2',
			10 => 'Employee 3.1',
			9 => 'Employee 3',
			8 => 'Employee 2.2.2',
			7 => 'Employee 2.2.1',
			6 => 'Employee 2.2',
			5 => 'Employee 2.1',
			4 => 'Employee 2',
			3 => 'Employee 1.2',
			2 => 'Employee 1.1',
			1 => 'Employee 1',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			11 => 1,
			10 => 2,
			9 => 3,
			8 => 4,
			7 => 5,
			6 => 6,
			5 => 7,
			4 => 8,
			3 => 9,
			2 => 10,
			1 => 11,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitNotUnnestsNestedEntriesOfScopeWhenEnabled(): void {
		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'nest.enabled',
			'value' => true,
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			switch ($employee->id) {
				case 1:
				case 4:
				case 9:
					$this->assertNull($employee->parentId);
					break;
				case 2:
				case 3:
					$this->assertSame(1, $employee->parentId);
					break;
				case 5:
				case 6:
					$this->assertSame(4, $employee->parentId);
					break;
				case 7:
				case 8:
					$this->assertSame(6, $employee->parentId);
					break;
				case 10:
				case 11:
					$this->assertSame(9, $employee->parentId);
					break;
			}
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			1 => 'Employee 1',
			2 => 'Employee 1.1',
			5 => 'Employee 2.1',
			7 => 'Employee 2.2.1',
			10 => 'Employee 3.1',
			3 => 'Employee 1.2',
			4 => 'Employee 2',
			6 => 'Employee 2.2',
			8 => 'Employee 2.2.2',
			11 => 'Employee 3.2',
			9 => 'Employee 3',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			1 => 1,
			2 => 1,
			5 => 1,
			7 => 1,
			10 => 1,
			3 => 2,
			4 => 2,
			6 => 2,
			8 => 2,
			11 => 2,
			9 => 3,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitRebuildsSystemOrderWhenNewAndIdentifierSystemOrderField(): void {
		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'system_order.field',
			'value' => 'title',
		]);

		$employeesTable->updateAll([
			'parent_id' => null,
			'system_order' => 999,
		], []);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			$this->assertNull($employee->parentId);
			$this->assertNotSame(999, $employee->systemOrder);
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			1 => 'Employee 1',
			2 => 'Employee 1.1',
			3 => 'Employee 1.2',
			4 => 'Employee 2',
			5 => 'Employee 2.1',
			6 => 'Employee 2.2',
			7 => 'Employee 2.2.1',
			8 => 'Employee 2.2.2',
			9 => 'Employee 3',
			10 => 'Employee 3.1',
			11 => 'Employee 3.2',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			1 => 1,
			2 => 2,
			3 => 3,
			4 => 4,
			5 => 5,
			6 => 6,
			7 => 7,
			8 => 8,
			9 => 9,
			10 => 10,
			11 => 11,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitRebuildsSystemOrderWhenNotNewAndValueChangedAndIdentifierSystemOrderField(): void {
		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'system_order.field',
			'value' => 'languageShortcode',
		]);
		$entity->clean();
		$entity->setNew(false);

		$entity->value = 'title';

		$employeesTable->updateAll([
			'parent_id' => null,
			'system_order' => 999,
		], []);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			$this->assertNull($employee->parentId);
			$this->assertNotSame(999, $employee->systemOrder);
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			1 => 'Employee 1',
			2 => 'Employee 1.1',
			3 => 'Employee 1.2',
			4 => 'Employee 2',
			5 => 'Employee 2.1',
			6 => 'Employee 2.2',
			7 => 'Employee 2.2.1',
			8 => 'Employee 2.2.2',
			9 => 'Employee 3',
			10 => 'Employee 3.1',
			11 => 'Employee 3.2',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			1 => 1,
			2 => 2,
			3 => 3,
			4 => 4,
			5 => 5,
			6 => 6,
			7 => 7,
			8 => 8,
			9 => 9,
			10 => 10,
			11 => 11,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitNotRebuildsSystemOrderWhenNotNewAndValueUnchangedAndIdentifierSystemOrderField(): void {
		Configure::write('Awyiss.Employees.Backend.systemOrder.field', 'title');

		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'system_order.field',
			'value' => 'title',
		]);
		$entity->clean();
		$entity->setNew(false);

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->value = 'languageShortcode';
		$entity->value = 'title';

		$employeesTable->updateAll([
			'parent_id' => null,
			'system_order' => 999,
		], []);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			$this->assertNull($employee->parentId);
			$this->assertSame(999, $employee->systemOrder);
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			1 => 'Employee 1',
			2 => 'Employee 1.1',
			3 => 'Employee 1.2',
			4 => 'Employee 2',
			5 => 'Employee 2.1',
			6 => 'Employee 2.2',
			7 => 'Employee 2.2.1',
			8 => 'Employee 2.2.2',
			9 => 'Employee 3',
			10 => 'Employee 3.1',
			11 => 'Employee 3.2',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			1 => 999,
			2 => 999,
			3 => 999,
			4 => 999,
			5 => 999,
			6 => 999,
			7 => 999,
			8 => 999,
			9 => 999,
			10 => 999,
			11 => 999,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitRebuildsSystemOrderWhenNewAndIdentifierSystemOrderDirection(): void {
		Configure::write('Awyiss.Employees.Backend.systemOrder.field', 'title');

		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'system_order.field',
			'value' => 4,
		]);

		$employeesTable->updateAll([
			'parent_id' => null,
			'system_order' => 999,
		], []);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			$this->assertNull($employee->parentId);
			$this->assertNotSame(999, $employee->systemOrder);
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			1 => 'Employee 1',
			2 => 'Employee 1.1',
			3 => 'Employee 1.2',
			4 => 'Employee 2',
			5 => 'Employee 2.1',
			6 => 'Employee 2.2',
			7 => 'Employee 2.2.1',
			8 => 'Employee 2.2.2',
			9 => 'Employee 3',
			10 => 'Employee 3.1',
			11 => 'Employee 3.2',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			1 => 1,
			2 => 2,
			3 => 3,
			4 => 4,
			5 => 5,
			6 => 6,
			7 => 7,
			8 => 8,
			9 => 9,
			10 => 10,
			11 => 11,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitRebuildsSystemOrderWhenNotNewAndValueChangedAndIdentifierSystemOrderDirection(): void {
		Configure::write('Awyiss.Employees.Backend.systemOrder.field', 'title');

		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'system_order.direction',
			'value' => 4,
		]);
		$entity->clean();
		$entity->setNew(false);

		$entity->value = 3;

		$employeesTable->updateAll([
			'parent_id' => null,
			'system_order' => 999,
		], []);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			$this->assertNull($employee->parentId);
			$this->assertNotSame(999, $employee->systemOrder);
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			11 => 'Employee 3.2',
			10 => 'Employee 3.1',
			9 => 'Employee 3',
			8 => 'Employee 2.2.2',
			7 => 'Employee 2.2.1',
			6 => 'Employee 2.2',
			5 => 'Employee 2.1',
			4 => 'Employee 2',
			3 => 'Employee 1.2',
			2 => 'Employee 1.1',
			1 => 'Employee 1',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			11 => 1,
			10 => 2,
			9 => 3,
			8 => 4,
			7 => 5,
			6 => 6,
			5 => 7,
			4 => 8,
			3 => 9,
			2 => 10,
			1 => 11,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitNotRebuildsSystemOrderWhenNotNewAndValueUnchangedAndIdentifierSystemOrderDirection(): void {
		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'system_order.direction',
			'value' => 3,
		]);
		$entity->clean();
		$entity->setNew(false);

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->value = 4;
		$entity->value = 3;

		$employeesTable->updateAll([
			'parent_id' => null,
			'system_order' => 999,
		], []);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			$this->assertNull($employee->parentId);
			$this->assertSame(999, $employee->systemOrder);
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			1 => 'Employee 1',
			2 => 'Employee 1.1',
			3 => 'Employee 1.2',
			4 => 'Employee 2',
			5 => 'Employee 2.1',
			6 => 'Employee 2.2',
			7 => 'Employee 2.2.1',
			8 => 'Employee 2.2.2',
			9 => 'Employee 3',
			10 => 'Employee 3.1',
			11 => 'Employee 3.2',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			1 => 999,
			2 => 999,
			3 => 999,
			4 => 999,
			5 => 999,
			6 => 999,
			7 => 999,
			8 => 999,
			9 => 999,
			10 => 999,
			11 => 999,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitCreatesCustomConfiguration(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => 20,
		]);

		$pattern = ENV_CUSTOM_CONFIG . 'customer\[??\]\[??\].php';

		$files = glob($pattern);
		$this->assertCount(0, $files);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);

		$files = glob($pattern);
		$this->assertCount(6, $files);
		$this->assertSame([
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[de][de].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[de][en].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[es][de].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[es][en].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[zu][de].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[zu][en].php',
		], $files);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitClearsMediaCacheForMediaResizingFileType(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.file_type',
			'value' => 'webp',
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'bin' . DS . 'cake media clear_cache';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'media::clear_cache',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitClearsMediaCacheForMediaResizingQuality(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => 20,
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'bin' . DS . 'cake media clear_cache';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'media::clear_cache',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitNotClearsMediaCacheForOtherConfig(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'other_identifier',
			'value' => 'test_value',
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitNotClearsMediaCacheWhenNotNewAndValueNotChanged(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => 50,
		]);
		$entity->clean();
		$entity->setNew(false);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 */
	public function testAfterSaveCommitClearsMediaCacheWhenNotNewAndValueChanged(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => 50,
		]);
		$entity->clean();
		$entity->setNew(false);

		$entity->value = 80;

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'bin' . DS . 'cake media clear_cache';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'media::clear_cache',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterSaveCommit');
		$options = new ArrayObject([]);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitRecompilesFrontendScssWhenColumnClassNameChanged(): void {
		$this->listener = $this->getMockBuilder(ConfigurationListener::class)->onlyMethods([
			'createCustomConfiguration',
		])->getMock();

		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$callCount = 0;
		$designMiddlewareMock->expects($this->exactly(2))->method('compileScss')->with(
			true,
			$this->callback(function ($value) use (&$callCount) {
				$callCount++;

				if ($callCount === 1) {
					return $value === Awyiss::REALM_FRONTEND;
				}
				if ($callCount === 2) {
					return $value === Awyiss::REALM_BACKEND;
				}

				return false;
			})
		);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$configTable = $this->fetchTable('Configuration');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'contents',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'column_system.class_name',
			'value' => '\Awyiss\Utility\Content\BootstrapColumnSystem',
		]);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);

		$this->assertNull(Configure::read('Awyiss.Contents.Backend.columnSystem.className'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitRecompilesFrontendScssWhenColumnMaxColumnsChanged(): void {
		$this->listener = $this->getMockBuilder(ConfigurationListener::class)->onlyMethods([
			'createCustomConfiguration',
		])->getMock();

		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$callCount = 0;
		$designMiddlewareMock->expects($this->exactly(2))->method('compileScss')->with(
			true,
			$this->callback(function ($value) use (&$callCount) {
				$callCount++;

				if ($callCount === 1) {
					return $value === Awyiss::REALM_FRONTEND;
				}
				if ($callCount === 2) {
					return $value === Awyiss::REALM_BACKEND;
				}

				return false;
			})
		);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$configTable = $this->fetchTable('Configuration');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'contents',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'column_system.max_columns',
			'value' => 10,
		]);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);

		$this->assertNull(Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitNotRecompilesFrontendScssWhenNotContentsScope(): void {
		$this->listener = $this->getMockBuilder(ConfigurationListener::class)->onlyMethods([
			'createCustomConfiguration',
		])->getMock();

		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->expects($this->never())->method('compileScss');

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$configTable = $this->fetchTable('Configuration');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'widgets',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'column_system.max_columns',
			'value' => 10,
		]);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);

		$this->assertNull(Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitUnnestsNestedEntriesOfScope(): void {
		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'nest.enabled',
			'value' => false,
		]);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			$this->assertNull($employee->parentId);
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			1 => 'Employee 1',
			2 => 'Employee 1.1',
			5 => 'Employee 2.1',
			7 => 'Employee 2.2.1',
			10 => 'Employee 3.1',
			3 => 'Employee 1.2',
			4 => 'Employee 2',
			6 => 'Employee 2.2',
			8 => 'Employee 2.2.2',
			11 => 'Employee 3.2',
			9 => 'Employee 3',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			1 => 1,
			2 => 2,
			5 => 3,
			7 => 4,
			10 => 5,
			3 => 6,
			4 => 7,
			6 => 8,
			8 => 9,
			11 => 10,
			9 => 11,
		], $employeesOrder);

		$this->deleteTestEmployees();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitNotUnnestsNestedEntriesOfScopeWhenDefaultTrue(): void {
		$configuration = ConfigOptionsProvider::loadConfigOptions('employees');
		$configOption = $configuration?->getConfigOption(Awyiss::REALM_BACKEND, 'nest.enabled');
		$configOption->setDefaultValue(true);

		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'nest.enabled',
			'value' => false,
		]);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);

		$employees = $employeesTable->find()->all();

		/** @var \Customer\Model\Entity\Employee $employee */
		foreach ($employees as $employee) {
			switch ($employee->id) {
				case 1:
				case 4:
				case 9:
					$this->assertNull($employee->parentId);
					break;
				case 2:
				case 3:
					$this->assertSame(1, $employee->parentId);
					break;
				case 5:
				case 6:
					$this->assertSame(4, $employee->parentId);
					break;
				case 7:
				case 8:
					$this->assertSame(6, $employee->parentId);
					break;
				case 10:
				case 11:
					$this->assertSame(9, $employee->parentId);
					break;
			}
		}

		$employeesTitle = $employees->combine('id', 'title')->toArray();
		$this->assertSame([
			1 => 'Employee 1',
			2 => 'Employee 1.1',
			5 => 'Employee 2.1',
			7 => 'Employee 2.2.1',
			10 => 'Employee 3.1',
			3 => 'Employee 1.2',
			4 => 'Employee 2',
			6 => 'Employee 2.2',
			8 => 'Employee 2.2.2',
			11 => 'Employee 3.2',
			9 => 'Employee 3',
		], $employeesTitle);

		$employeesOrder = $employees->combine('id', 'systemOrder')->toArray();
		$this->assertSame([
			1 => 1,
			2 => 1,
			5 => 1,
			7 => 1,
			10 => 1,
			3 => 2,
			4 => 2,
			6 => 2,
			8 => 2,
			11 => 2,
			9 => 3,
		], $employeesOrder);

		$this->deleteTestEmployees();
		$configOption->setDefaultValue(false);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitCreatesCustomConfiguration(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => 20,
		]);

		$pattern = ENV_CUSTOM_CONFIG . 'customer\[??\]\[??\].php';

		$files = glob($pattern);
		$this->assertCount(0, $files);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);

		$files = glob($pattern);
		$this->assertCount(6, $files);
		$this->assertSame([
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[de][de].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[de][en].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[es][de].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[es][en].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[zu][de].php',
			ROOT . DS . CUSTOM_DIR . '/config/development/customer[zu][en].php',
		], $files);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitClearsMediaCacheForMediaResizingFileType(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.file_type',
			'value' => 'webp',
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'bin' . DS . 'cake media clear_cache';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'media::clear_cache',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitClearsMediaCacheForMediaResizingQuality(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => 20,
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'bin' . DS . 'cake media clear_cache';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'media::clear_cache',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitNotClearsMediaCacheForOtherConfig(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'other_identifier',
			'value' => 'test_value',
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitClearsMediaCacheWhenNotNewAndValueUnchanged(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => 50,
		]);
		$entity->clean();
		$entity->setNew(false);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'bin' . DS . 'cake media clear_cache';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'media::clear_cache',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDeleteCommit()
	 * @throws \Exception
	 */
	public function testAfterDeleteCommitClearsMediaCacheWhenNotNewAndValueChanged(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => 50,
		]);
		$entity->clean();
		$entity->setNew(false);

		$entity->value = 80;

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'bin' . DS . 'cake media clear_cache';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'media::clear_cache',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Configuration.afterDeleteCommit');
		$options = new ArrayObject([]);

		$this->listener->afterDeleteCommit($event, $entity, $options);
	}



	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::deleteCustomConfiguration()
	 */
	public function testDeleteCustomConfigurationRemovesFiles(): void {
		$testFile1 = ENV_CUSTOM_CONFIG . 'customer[en][en].php';
		$testFile2 = ENV_CUSTOM_CONFIG . 'customer[de][de].php';

		if (!is_dir(ENV_CUSTOM_CONFIG)) {
			mkdir(ENV_CUSTOM_CONFIG, 0755, true);
		}

		file_put_contents($testFile1, '<?php return [];');
		file_put_contents($testFile2, '<?php return [];');

		$this->assertFileExists($testFile1);
		$this->assertFileExists($testFile2);

		$this->listener->deleteCustomConfiguration();

		$this->assertFileDoesNotExist($testFile1);
		$this->assertFileDoesNotExist($testFile2);
	}


	/**
	 * @return void
	 */
	protected function createTestEmployees(): void {
		$employeesTable = $this->fetchTable('Employees');

		$employeesTable->deleteAll([]);

		$insertQuery = $employeesTable->insertQuery();

		$insertQuery->insert([
			'id',
			'title',
			'language_shortcode',
			'parent_id',
			'system_order',
		]);

		$insertQuery->values([
			'id' => 1,
			'title' => 'Employee 1',
			'language_shortcode' => 'en',
			'parent_id' => null,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 2,
			'title' => 'Employee 1.1',
			'language_shortcode' => 'en',
			'parent_id' => 1,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 3,
			'title' => 'Employee 1.2',
			'language_shortcode' => 'en',
			'parent_id' => 1,
			'system_order' => 2,
		]);

		$insertQuery->values([
			'id' => 4,
			'title' => 'Employee 2',
			'language_shortcode' => 'en',
			'parent_id' => null,
			'system_order' => 2,
		]);

		$insertQuery->values([
			'id' => 5,
			'title' => 'Employee 2.1',
			'language_shortcode' => 'en',
			'parent_id' => 4,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 6,
			'title' => 'Employee 2.2',
			'language_shortcode' => 'en',
			'parent_id' => 4,
			'system_order' => 2,
		]);

		$insertQuery->values([
			'id' => 7,
			'title' => 'Employee 2.2.1',
			'language_shortcode' => 'en',
			'parent_id' => 6,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 8,
			'title' => 'Employee 2.2.2',
			'language_shortcode' => 'en',
			'parent_id' => 6,
			'system_order' => 2,
		]);

		$insertQuery->values([
			'id' => 9,
			'title' => 'Employee 3',
			'language_shortcode' => 'en',
			'parent_id' => null,
			'system_order' => 3,
		]);

		$insertQuery->values([
			'id' => 10,
			'title' => 'Employee 3.1',
			'language_shortcode' => 'en',
			'parent_id' => 9,
			'system_order' => 1,
		]);

		$insertQuery->values([
			'id' => 11,
			'title' => 'Employee 3.2',
			'language_shortcode' => 'en',
			'parent_id' => 9,
			'system_order' => 2,
		]);

		$this->assertNotFalse($insertQuery->execute());
	}


	/**
	 * @return void
	 */
	protected function deleteTestEmployees(): void {
		$employeesTable = $this->fetchTable('Employees');

		$employeesTable->deleteAll([]);
	}
}
