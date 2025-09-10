<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Event\Backend\ConfigurationListener;
use Awyiss\Event\EventListenersProvider;
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Configuration.beforeSave' => 'beforeSave',
			'Model.Configuration.afterSaveCommit' => 'afterSaveCommit',
			'Model.Configuration.afterDelete' => 'afterDelete',
			'Awyiss.Configuration.createCustomConfiguration' => 'createCustomConfiguration',
			'Awyiss.Configuration.deleteCustomConfiguration' => 'deleteCustomConfiguration',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
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

		$this->listener->beforeSave($event, $entity);

		$this->assertSame(85, $entity->value);

		$entity->patch([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'overview.displayed_fields',
			'value' => ['id', 'title', 'created_on'],
			'languageShortcode' => 'en',
		]);

		$this->listener->beforeSave($event, $entity);

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

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('{"key1":"value1","key2":"value2"}', $entity->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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

		$this->listener->afterSaveCommit($event, $entity);

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
	 * @noinspection PhpVariableNamingConventionInspection
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

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterSaveCommit()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteUnnestsNestedEntriesOfScope(): void {
		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'nest.enabled',
			'value' => false,
		]);

		$event = new Event('Model.Configuration.afterDelete');
		$this->listener->afterDelete($event, $entity);

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
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteNotUnnestsNestedEntriesOfScopeWhenDefaultTrue(): void {
		$lo_configuration = ConfigOptionsProvider::loadConfigOptions('employees');
		$lo_configOption = $lo_configuration?->getConfigOption(Awyiss::REALM_BACKEND, 'nest.enabled');
		$lo_configOption->setDefaultValue(true);

		$this->createTestEmployees();

		$configTable = $this->fetchTable('Configuration');
		$employeesTable = $this->fetchTable('Employees');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'employees',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'nest.enabled',
			'value' => false,
		]);

		$event = new Event('Model.Configuration.afterDelete');
		$this->listener->afterDelete($event, $entity);

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
		$lo_configOption->setDefaultValue(false);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteCreatesCustomConfiguration(): void {
		$entity = $this->fetchTable('Configuration')->newDefaultEntity([
			'scope' => 'media',
			'realm' => Awyiss::REALM_FRONTEND,
			'identifier' => 'resizing.quality',
			'value' => 20,
		]);

		$pattern = ENV_CUSTOM_CONFIG . 'customer\[??\]\[??\].php';

		$files = glob($pattern);
		$this->assertCount(0, $files);

		$event = new Event('Model.Configuration.afterDelete');

		$this->listener->afterDelete($event, $entity);

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
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteClearsMediaCacheForMediaResizingFileType(): void {
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

		$event = new Event('Model.Configuration.afterDelete');

		$this->listener->afterDelete($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteClearsMediaCacheForMediaResizingQuality(): void {
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

		$event = new Event('Model.Configuration.afterDelete');

		$this->listener->afterDelete($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteNotClearsMediaCacheForOtherConfig(): void {
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

		$event = new Event('Model.Configuration.afterDelete');

		$this->listener->afterDelete($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteClearsMediaCacheWhenNotNewAndValueUnchanged(): void {
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

		$event = new Event('Model.Configuration.afterDelete');

		$this->listener->afterDelete($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteClearsMediaCacheWhenNotNewAndValueChanged(): void {
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

		$event = new Event('Model.Configuration.afterDelete');

		$this->listener->afterDelete($event, $entity);
	}



	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ConfigurationListener::deleteCustomConfiguration()
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function deleteTestEmployees(): void {
		$employeesTable = $this->fetchTable('Employees');

		$employeesTable->deleteAll([]);
	}
}
