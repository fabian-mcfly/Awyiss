<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Event\Backend\PageRolesListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Queue\Model\Table\QueuedJobsTable;


/**
 * PageRolesListener Test Case
 *
 * @see \Awyiss\Event\Backend\PageRolesListener
 */
class PageRolesListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\PageRolesListener
	 */
	protected PageRolesListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new PageRolesListener();
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
	 * @see \Awyiss\Event\Backend\PageRolesListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.PageRoles.beforeSave' => 'beforeSave',
			'Model.PageRoles.afterSave' => 'afterSave',
			'Model.PageRoles.afterSaveCommit' => 'afterSaveCommit',
			'Model.PageRoles.afterSoftDelete' => 'afterSoftDelete',
			'Model.PageRoles.afterSoftDeleteCommit' => 'afterSoftDeleteCommit',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::beforeSave()
	 */
	public function testBeforeSaveWithNoAttributesTable(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->never())->method('isQueued');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.beforeSave', $pageRolesTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::beforeSave()
	 */
	public function testBeforeSaveWithAttributesTableButNoQueuedJob(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'pages',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('Attributes::tableChanges')->willReturn(false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.beforeSave', $pageRolesTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::beforeSave()
	 */
	public function testBeforeSaveWithAttributesTableAndQueuedJob(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'pages',
		]);

		$event = new Event('Model.PageRoles.beforeSave', $pageRolesTable);

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
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSave()
	 */
	public function testAfterSaveCreatesBackendMenuEntries(): void {
		Configure::write('Awyiss.PageRoles.Backend.autoCreateMenuEntries', true);

		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
		$initialCount = $backendMenuEntriesTable->find()->count();
		$highestId = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->first()->id;

		$event = new Event('Model.PageRoles.afterSave', $pageRolesTable);

		$this->listener->afterSave($event, $entity);

		$finalCount = $backendMenuEntriesTable->find()->count();
		$this->assertSame($initialCount + 4, $finalCount);

		$entries = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->limit(4)->all();
		$entries = $entries->combine('title', 'link')->toArray();

		$this->assertSame([
			'GenericDatatables::menu_configure' => 'Configuration::overview::scope:TestRoles',
			'GenericDatatables::menu_add' => 'TestRoles::add',
			'GenericDatatables::menu_overview' => 'TestRoles::overview',
			'' => 'TestRoles::overview',
		], $entries);

		$backendMenuEntriesTable->deleteAll(['id >' => $highestId]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSave()
	 */
	public function testAfterSaveNotCreatesBackendMenuEntriesWhenConfigFalse(): void {
		Configure::write('Awyiss.PageRoles.Backend.autoCreateMenuEntries', false);

		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
		$initialCount = $backendMenuEntriesTable->find()->count();

		$event = new Event('Model.PageRoles.afterSave', $pageRolesTable);

		$this->listener->afterSave($event, $entity);

		$finalCount = $backendMenuEntriesTable->find()->count();
		$this->assertSame($initialCount, $finalCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSave()
	 */
	public function testAfterSaveNotCreatesBackendMenuEntriesWhenExistingEntity(): void {
		Configure::write('Awyiss.PageRoles.Backend.autoCreateMenuEntries', true);

		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);
		$entity->setNew(false);

		$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
		$initialCount = $backendMenuEntriesTable->find()->count();

		$event = new Event('Model.PageRoles.afterSave', $pageRolesTable);

		$this->listener->afterSave($event, $entity);

		$finalCount = $backendMenuEntriesTable->find()->count();
		$this->assertSame($initialCount, $finalCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSave()
	 */
	public function testAfterSaveNotCreatesBackendMenuEntriesWhenPageRolePage(): void {
		Configure::write('Awyiss.PageRoles.Backend.autoCreateMenuEntries', true);

		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'page',
		]);

		$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
		$initialCount = $backendMenuEntriesTable->find()->count();

		$event = new Event('Model.PageRoles.afterSave', $pageRolesTable);

		$this->listener->afterSave($event, $entity);

		$finalCount = $backendMenuEntriesTable->find()->count();
		$this->assertSame($initialCount, $finalCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitBakesPageRoleEnumForNewEntity(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued', 'createJob'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->willReturn(false);

		$callCounter = 0;
		$queueTable->expects($this->exactly(2))->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) use (&$callCounter) {
				$callCounter++;

				if ($callCounter > 1) {
					return true;
				}

				return isset($data['command']) &&
					   $data['command'] === 'bin/cake bake enum PageRole page:1,newscategory:2,news:3,product:4 -i --namespace Customer --is-pagerole --force';
			}),
			$this->callback(function ($data) use (&$callCounter) {
				if ($callCounter > 1) {
					return true;
				}

				return $data === [
						'group' => 'general',
						'priority' => 1,
						'reference' => 'PageRoles::createEnum',
					];
			})
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.afterSaveCommit', $pageRolesTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitBakesPageRoleEnumForChangedIdentifier(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->identifier = 'new_role';

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued', 'createJob'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->willReturn(false);

		$callCounter = 0;
		$queueTable->expects($this->exactly(2))->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) use (&$callCounter) {
				$callCounter++;

				if ($callCounter > 1) {
					return true;
				}

				return isset($data['command']) &&
					   $data['command'] === 'bin/cake bake enum PageRole page:1,newscategory:2,news:3,product:4 -i --namespace Customer --is-pagerole --force';
			}),
			$this->callback(function ($data) use (&$callCounter) {
				if ($callCounter > 1) {
					return true;
				}

				return $data === [
						'group' => 'general',
						'priority' => 1,
						'reference' => 'PageRoles::createEnum',
					];
			})
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.afterSaveCommit', $pageRolesTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitNotBakesPageRoleEnumForExistingEntity(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);
		$entity->clean();
		$entity->setNew(false);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued', 'createJob'])->getMock();

		$queueTable->expects($this->never())->method('isQueued')->willReturn(false);

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.afterSaveCommit', $pageRolesTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitNotBakesPageRoleEnumForUnchangedIdentifier(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);
		$entity->clean();
		$entity->setNew(false);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->identifier = 'new_role';
		$entity->identifier = 'test_role';


		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued', 'createJob'])->getMock();

		$queueTable->expects($this->never())->method('isQueued')->willReturn(false);

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.afterSaveCommit', $pageRolesTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitBakesPageRoleModelForNewEntity(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued', 'createJob'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->willReturn(false);

		$callCounter = 0;
		$queueTable->expects($this->exactly(2))->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) use (&$callCounter) {
				$callCounter++;

				if ($callCounter === 1) {
					return true;
				}

				return isset($data['command']) && $data['command'] === '(bin/cake bake model TestRoles --namespace Customer --force --is-pagerole --no-associations --no-fixture --no-hidden --no-rules --no-test --no-validation --skip-relation-check --table pages --update &&'
				   . ' bin/cake bake seed --data PageRoles --folder tests/customer/config/Seeds --force --truncate)';
			}),
			$this->callback(function ($data) use (&$callCounter) {
				if ($callCounter === 1) {
					return true;
				}

				return $data === [
					'group' => 'general',
					'priority' => 1,
					'reference' => 'System::createPageRoleModel::test_role',
				];
			})
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.afterSaveCommit', $pageRolesTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitBakesPageRoleModelForChangedIdentifier(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->identifier = 'new_role';

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued', 'createJob'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->willReturn(false);

		$callCounter = 0;
		$queueTable->expects($this->exactly(2))->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) use (&$callCounter) {
				$callCounter++;

				if ($callCounter === 1) {
					return true;
				}

				return isset($data['command']) &&
					   $data['command'] ===
					   '(bin/cake bake model NewRoles --namespace Customer --force --is-pagerole --no-associations --no-fixture --no-hidden --no-rules --no-test --no-validation --skip-relation-check --table pages --update && bin/cake bake seed --data PageRoles --folder tests/customer/config/Seeds --force --truncate)';
			}),
			$this->callback(function ($data) use (&$callCounter) {
				if ($callCounter === 1) {
					return true;
				}

				return $data === [
						'group' => 'general',
						'priority' => 1,
						'reference' => 'System::createPageRoleModel::new_role',
					];
			})
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.afterSaveCommit', $pageRolesTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitNotBakesPageRoleModelForExistingEntity(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);
		$entity->clean();
		$entity->setNew(false);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued', 'createJob'])->getMock();

		$queueTable->expects($this->never())->method('isQueued')->willReturn(false);

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.afterSaveCommit', $pageRolesTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitNotBakesPageRoleModelForUnchangedIdentifier(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);
		$entity->clean();
		$entity->setNew(false);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->identifier = 'new_role';
		$entity->identifier = 'test_role';


		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued', 'createJob'])->getMock();

		$queueTable->expects($this->never())->method('isQueued')->willReturn(false);

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.afterSaveCommit', $pageRolesTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitNotBakesPageRoleModelWhenAlreadyQueued(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued', 'createJob'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->willReturn(true);

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) &&
					   $data['command'] !==
					   '(bin/cake bake model NewRoles --namespace Customer --force --is-pagerole --no-associations --no-fixture --no-hidden --no-rules --no-test --no-validation --skip-relation-check --table pages --update && bin/cake bake seed --data PageRoles --folder tests/customer/config/Seeds --force --truncate)';
			}),
			$this->callback(function ($data) {
				return $data !== [
						'group' => 'general',
						'priority' => 1,
						'reference' => 'System::createPageRoleModel::test_role',
					];
			})
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.PageRoles.afterSaveCommit', $pageRolesTable);

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSoftDelete()
	 */
	public function testAfterSoftDeleteCleansUpBackendMenuEntries(): void {
		Configure::write('Awyiss.PageRoles.Backend.autoCreateMenuEntries', true);

		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
		$highestId = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->first()->id;

		$afterSaveEvent = new Event('Model.PageRoles.afterSave', $pageRolesTable);
		$this->listener->afterSave($afterSaveEvent, $entity);

		$initialCount = $backendMenuEntriesTable->find()->count();
		$newHighestId = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->first()->id;

		$this->assertGreaterThan($highestId, $newHighestId);

		$afterSoftDeleteEvent = new Event('Model.PageRoles.afterSoftDelete', $pageRolesTable);
		$this->listener->afterSoftDelete($afterSoftDeleteEvent, $entity);

		$finalCount = $backendMenuEntriesTable->find()->count();
		$this->assertLessThan($initialCount, $finalCount);

		$newNewHighestId = $backendMenuEntriesTable->find()->orderByDesc('BackendMenuEntries.id')->first()->id;

		$this->assertSame($highestId, $newNewHighestId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSoftDelete()
	 * @throws \Exception
	 */
	public function testAfterSoftDeleteCleansUpConfiguration(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$configurationTable = $this->fetchTable('Configuration');

		$configs = [
			$configurationTable->newDefaultEntity([
				'scope' => 'TestRoles',
				'identifier' => 'testConfig1',
				'value' => 'test_value_1',
			]),
			$configurationTable->newDefaultEntity([
				'scope' => 'TestRoles',
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

		$event = new Event('Model.PageRoles.afterSoftDelete', $pageRolesTable);

		$this->listener->afterSoftDelete($event, $entity);

		$this->assertSame(0, $configurationTable->find()->where(['scope' => 'TestRoles'])->count());
		$this->assertSame(1, $configurationTable->find()->where(['scope' => 'OtherScope'])->count());

		$configurationTable->deleteAll(['scope' => 'OtherScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSoftDelete()
	 * @throws \Exception
	 */
	public function testAfterSoftDeleteCleansUpI18n(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$i18nTable = $this->fetchTable('I18n');

		$i18n = [
			$i18nTable->newEntity([
				'locale' => 'de',
				'model' => 'TestRoles',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Test German',
			]),
			$i18nTable->newEntity([
				'locale' => 'en',
				'model' => 'TestRoles',
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

		$event = new Event('Model.PageRoles.afterSoftDelete', $pageRolesTable);

		$this->listener->afterSoftDelete($event, $entity);

		$this->assertSame(0, $i18nTable->find()->where(['model' => 'TestRoles'])->count());
		$this->assertSame(1, $i18nTable->find()->where(['model' => 'OtherModel'])->count());

		$i18nTable->deleteAll(['model' => 'OtherModel']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PageRolesListener::afterSoftDelete()
	 * @throws \Exception
	 */
	public function testAfterSoftDeleteCleansUpUsergroupPermissions(): void {
		$pageRolesTable = $this->fetchTable('PageRoles');
		$entity = $pageRolesTable->newDefaultEntity([
			'identifier' => 'test_role',
		]);

		$usergroupPermissionsTable = $this->fetchTable('UsergroupPermissions');

		$permissions = [
			$usergroupPermissionsTable->newEntity([
				'scope' => 'TestRoles',
				'identifier' => 'index',
				'usergroupId' => 1,
				'access' => 1,
			]),
			$usergroupPermissionsTable->newEntity([
				'scope' => 'TestRoles',
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

		$event = new Event('Model.PageRoles.afterSoftDelete', $pageRolesTable);

		$this->listener->afterSoftDelete($event, $entity);

		$this->assertSame(0, $usergroupPermissionsTable->find()->where(['scope' => 'TestRoles'])->count());
		$this->assertSame(1, $usergroupPermissionsTable->find()->where(['scope' => 'OtherScope'])->count());

		$usergroupPermissionsTable->deleteAll(['scope' => 'OtherScope']);
	}
}
