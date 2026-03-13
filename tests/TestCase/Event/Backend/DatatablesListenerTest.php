<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Event\Backend\DatatablesListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\EventManager;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Queue\Model\Table\QueuedJobsTable;


/**
 * DatatablesListener Test Case
 *
 * @see \Awyiss\Event\Backend\DatatablesListener
 */
class DatatablesListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\DatatablesListener
	 */
	protected DatatablesListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new DatatablesListener();
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
	 * @see \Awyiss\Event\Backend\DatatablesListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Datatables.beforeSave' => 'beforeSave',
			'Model.Datatables.afterSave' => 'afterSave',
			'Model.Datatables.afterSaveCommit' => 'afterSaveCommit',
			'Model.Datatables.afterSoftDelete' => 'afterSoftDelete',
			'Model.Datatables.afterSoftDeleteCommit' => 'afterSoftDeleteCommit',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::beforeSave()
	 */
	public function testBeforeSaveWithNoAttributesTable(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'employers',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->never())->method('isQueued');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Datatables.beforeSave', $datatableTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::beforeSave()
	 */
	public function testBeforeSaveWithAttributesTableButNoQueuedJob(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'cars',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('Attributes::tableChanges')->willReturn(false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Datatables.beforeSave', $datatableTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::beforeSave()
	 */
	public function testBeforeSaveWithAttributesTableAndQueuedJob(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'cars',
		]);

		$event = new Event('Model.Datatables.beforeSave', $datatableTable);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('Attributes::tableChanges')->willReturn(true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertTrue($event->isStopped());
		$this->assertTrue($entity->hasErrors());

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertSame(['Attributes::table_changes_in_progress'], $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSave()
	 */
	public function testAfterSaveCreatesBackendMenuEntries(): void {
		Configure::write('Awyiss.Datatables.Backend.autoCreateMenuEntries', true);

		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
		$initialCount = $backendMenuEntriesTable->find()->count();
		$highestId = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->first()->id;

		$event = new Event('Model.Datatables.afterSave', $datatableTable);

		$this->listener->afterSave($event, $entity);

		$finalCount = $backendMenuEntriesTable->find()->count();
		$this->assertSame($initialCount + 4, $finalCount);

		$entries = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->limit(4)->all();
		$entries = $entries->combine('title', 'link')->toArray();

		$this->assertSame([
			'GenericDatatables::menu_configure' => 'Configuration::overview::scope:TestDatatable',
			'GenericDatatables::menu_add' => 'TestDatatable::add',
			'GenericDatatables::menu_overview' => 'TestDatatable::overview',
			'' => 'TestDatatable::overview',
		], $entries);

		$backendMenuEntriesTable->deleteAll(['id >' => $highestId]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSave()
	 */
	public function testAfterSaveNotCreatesBackendMenuEntriesWhenConfigFalse(): void {
		Configure::write('Awyiss.Datatables.Backend.autoCreateMenuEntries', false);

		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
		$initialCount = $backendMenuEntriesTable->find()->count();

		$event = new Event('Model.Datatables.afterSave', $datatableTable);

		$this->listener->afterSave($event, $entity);

		$finalCount = $backendMenuEntriesTable->find()->count();
		$this->assertSame($initialCount, $finalCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSave()
	 */
	public function testAfterSaveNotCreatesBackendMenuEntriesWhenExistingEntity(): void {
		Configure::write('Awyiss.Datatables.Backend.autoCreateMenuEntries', true);

		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);
		$entity->setNew(false);

		$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
		$initialCount = $backendMenuEntriesTable->find()->count();

		$event = new Event('Model.Datatables.afterSave', $datatableTable);

		$this->listener->afterSave($event, $entity);

		$finalCount = $backendMenuEntriesTable->find()->count();
		$this->assertSame($initialCount, $finalCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitBakesMigrationAndModelForNewEntity(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();


		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				$expectedColumns = [
					'parentId:integer?[11]:index',
					'languageShortcode:char?[2]:index',
					'title:string?[255]',
					'systemOrder:integer[11](0)',
					'active:tinyinteger[1](1):index',
					'deleted:tinyinteger[1](0):index',
					'createdBy:integer?[11]',
					'createdOn:datetime?',
					'changedBy:integer?[11]',
					'changedOn:datetime?',
					'deletedBy:integer?[11]',
					'deletedOn:datetime?',
				];

				$expectedCommand = '(' . implode(' && ', array_map('escapeshellcmd', [
						'bin' . DS . 'cake bake migration create_testDatatable ' . implode(' ', $expectedColumns) . ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations',
						'bin' . DS . 'cake migrations migrate --source ../../' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations --no-lock',
						'bin' . DS . 'cake schema_cache clear',
						'bin' . DS . 'cake bake model testDatatable --namespace ' . CUSTOM_NAMESPACE . ' --no-fixture --no-test --update --force --is-datatable',
						'bin' . DS . 'cake bake seed --data Datatables --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate',
					])) . ')';

				return $data['command'] === $expectedCommand;
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'Datatables::tableChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Datatables.afterSaveCommit', $datatableTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitNotBakesMigrationAndModelForExistingEntity(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);
		$entity->setNew(false);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Datatables.afterSaveCommit', $datatableTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitDispatchesDeleteCustomConfigEventWhenNew(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$eventSent = false;
		$eventManager = EventManager::instance();
		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventSent) {
			$eventSent = true;
		});

		$event = new Event('Model.Datatables.afterSaveCommit', $datatableTable);

		$this->listener->afterSaveCommit($event, $entity);

		$this->assertTrue($eventSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitNotDispatchesDeleteCustomConfigEventWhenNotNew(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);
		$entity->setNew(false);

		$eventSent = false;
		$eventManager = EventManager::instance();
		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventSent) {
			$eventSent = true;
		});

		$event = new Event('Model.Datatables.afterSaveCommit', $datatableTable);

		$this->listener->afterSaveCommit($event, $entity);

		$this->assertFalse($eventSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSoftDelete()
	 */
	public function testAfterSoftDeleteCleansUpBackendMenuEntries(): void {
		Configure::write('Awyiss.Datatables.Backend.autoCreateMenuEntries', true);

		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
		$highestId = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->first()->id;

		$afterSaveEvent = new Event('Model.Datatables.afterSave', $datatableTable);
		$this->listener->afterSave($afterSaveEvent, $entity);

		$initialCount = $backendMenuEntriesTable->find()->count();
		$newHighestId = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->first()->id;

		$this->assertGreaterThan($highestId, $newHighestId);

		$afterSoftDeleteEvent = new Event('Model.Datatables.afterSoftDelete', $datatableTable);
		$this->listener->afterSoftDelete($afterSoftDeleteEvent, $entity);

		$finalCount = $backendMenuEntriesTable->find()->count();
		$this->assertLessThan($initialCount, $finalCount);

		$newNewHighestId = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->first()->id;

		$this->assertSame($highestId, $newNewHighestId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSoftDelete()
	 * @throws \Exception
	 */
	public function testAfterSoftDeleteCleansUpConfiguration(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$configurationTable = $this->fetchTable('Configuration');

		$configs = [
			$configurationTable->newDefaultEntity([
				'scope' => 'TestDatatable',
				'identifier' => 'testConfig1',
				'value' => 'test_value_1',
			]),
			$configurationTable->newDefaultEntity([
				'scope' => 'TestDatatable',
				'identifier' => 'testConfig2',
				'value' => 'test_value_2',
			]),
			$configurationTable->newDefaultEntity([
				'scope' => 'OtherScope',
				'identifier' => 'otherConfig',
				'value' => 'other_value',
			]),
		];

		$result = $configurationTable->saveMany($configs, ['checkRules' => false]);
		$this->assertNotFalse($result);

		$event = new Event('Model.Datatables.afterSoftDelete', $datatableTable);

		$this->listener->afterSoftDelete($event, $entity);

		$this->assertSame(0, $configurationTable->find()->where(['scope' => 'TestDatatable'])->count());
		$this->assertSame(1, $configurationTable->find()->where(['scope' => 'OtherScope'])->count());

		$configurationTable->deleteAll(['scope' => 'OtherScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSoftDelete()
	 * @throws \Exception
	 */
	public function testAfterSoftDeleteCleansUpI18n(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$i18nTable = $this->fetchTable('I18n');

		$i18n = [
			$i18nTable->newEntity([
				'locale' => 'de',
				'model' => 'TestDatatable',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Test German',
			]),
			$i18nTable->newEntity([
				'locale' => 'en',
				'model' => 'TestDatatable',
				'foreignKey' => 2,
				'field' => 'description',
				'content' => 'Test English',
			]),
			$i18nTable->newEntity([
				'locale' => 'de',
				'model' => 'OtherModel',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Other Content',
			]),
		];

		$result = $i18nTable->saveMany($i18n, ['checkRules' => false]);
		$this->assertNotFalse($result);

		$event = new Event('Model.Datatables.afterSoftDelete', $datatableTable);

		$this->listener->afterSoftDelete($event, $entity);

		$this->assertSame(0, $i18nTable->find()->where(['model' => 'TestDatatable'])->count());
		$this->assertSame(1, $i18nTable->find()->where(['model' => 'OtherModel'])->count());

		$i18nTable->deleteAll(['model' => 'OtherModel']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSoftDelete()
	 * @throws \Exception
	 */
	public function testAfterSoftDeleteCleansUpUsergroupPermissions(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$usergroupPermissionsTable = $this->fetchTable('UsergroupPermissions');

		$permissions = [
			$usergroupPermissionsTable->newEntity([
				'scope' => 'TestDatatable',
				'identifier' => 'index',
				'usergroupId' => 1,
				'access' => 1,
			]),
			$usergroupPermissionsTable->newEntity([
				'scope' => 'TestDatatable',
				'identifier' => 'edit',
				'usergroupId' => 2,
				'access' => 1,
			]),
			$usergroupPermissionsTable->newEntity([
				'scope' => 'OtherScope',
				'identifier' => 'view',
				'usergroupId' => 1,
				'access' => 1,
			]),
		];

		$result = $usergroupPermissionsTable->saveMany($permissions, ['checkRules' => false]);
		$this->assertNotFalse($result);

		$event = new Event('Model.Datatables.afterSoftDelete', $datatableTable);

		$this->listener->afterSoftDelete($event, $entity);

		$this->assertSame(0, $usergroupPermissionsTable->find()->where(['scope' => 'TestDatatable'])->count());
		$this->assertSame(1, $usergroupPermissionsTable->find()->where(['scope' => 'OtherScope'])->count());

		$usergroupPermissionsTable->deleteAll(['scope' => 'OtherScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSoftDeleteCommit()
	 */
	public function testAfterSoftDeleteCommitQueuesDropCommands(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				$expectedCommand = '(' . implode(' && ', array_map('escapeshellcmd', [
						'bin' . DS . 'cake bake migration drop_testDatatable --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations',
						'bin' . DS . 'cake migrations migrate --source ../../' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations --no-lock',
						'bin' . DS . 'cake schema_cache clear',
						'bin' . DS . 'cake bake seed --data Datatables --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate',
					])) . ')';

				return $data['command'] === $expectedCommand;
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'Datatables::dropTable',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Datatables.afterSoftDeleteCommit', $datatableTable);

		$this->listener->afterSoftDeleteCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSoftDeleteCommit()
	 */
	public function testAfterSoftDeleteCommitQueueUnlinksModelFilesIfExists(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'testDatatable',
		]);

		$entityFile = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Entity', 'TestDatatable.php']);
		touch($entityFile);
		$this->assertFileExists($entityFile);

		$tableFile = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Table', 'TestDatatableTable.php']);
		touch($tableFile);
		$this->assertFileExists($tableFile);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				$expectedCommand = '(' . implode(' && ', array_map('escapeshellcmd', [
						'unlink ' . ROOT . DS . CUSTOM_DIR . '/Model/Entity/TestDatatable.php',
						'unlink ' . ROOT . DS . CUSTOM_DIR . '/Model/Table/TestDatatableTable.php',
						'bin' . DS . 'cake bake migration drop_testDatatable --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations',
						'bin' . DS . 'cake migrations migrate --source ../../' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations --no-lock',
						'bin' . DS . 'cake schema_cache clear',
						'bin' . DS . 'cake bake seed --data Datatables --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate',
					])) . ')';

				return $data['command'] === $expectedCommand;
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'Datatables::dropTable',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Datatables.afterSoftDeleteCommit', $datatableTable);

		$this->listener->afterSoftDeleteCommit($event, $entity);

		unlink($entityFile);
		unlink($tableFile);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DatatablesListener::afterSoftDeleteCommit()
	 */
	public function testAfterSoftDeleteCommitWithAttributesTableQueuesAttributesDelete(): void {
		$datatableTable = $this->fetchTable('Datatables');
		$entity = $datatableTable->newDefaultEntity([
			'identifier' => 'cars',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$callCounter = 0;
		$queueTable->expects($this->exactly(2))->method('createJob')->with(
			$this->callback(function ($jobType) use (&$callCounter) {
				$callCounter++;

				return ($callCounter === 1 && $jobType === 'Attributes/Delete') || ($callCounter === 2 && $jobType === 'Queue.Execute');
			}),
			$this->callback(function ($data) use (&$callCounter) {
				if ($callCounter === 1) {
					return $data['identifier'] === 'Cars';
				}

				return true;
			}),
			$this->callback(function ($options) use (&$callCounter) {
				if ($callCounter === 1) {
					return $options === [
							'group' => 'general',
							'priority' => 1,
							'reference' => 'Attributes::tableChanges',
						];
				}

				return $options === [
						'group' => 'general',
						'priority' => 1,
						'reference' => 'Datatables::dropTable',
					];
			})
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Datatables.afterSoftDeleteCommit', $datatableTable);

		$this->listener->afterSoftDeleteCommit($event, $entity);
	}
}
