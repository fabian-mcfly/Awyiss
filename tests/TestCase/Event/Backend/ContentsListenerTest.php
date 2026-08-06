<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Event\Backend\ContentsListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Model\Table\LocksTable;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Exception;
use Queue\Model\Table\QueuedJobsTable;


/**
 * ContentsListener Test Case
 *
 * @see \Awyiss\Event\Backend\ContentsListener
 */
class ContentsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\ContentsListener
	 */
	protected ContentsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new ContentsListener();
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
	 * @see \Awyiss\Event\Backend\ContentsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Contents.beforeSave' => 'beforeSave',
			'Model.Contents.afterSave' => 'afterSave',
			'Model.Contents.afterSaveCommit' => 'afterSaveCommit',
			'Model.Pages.afterSaveCommit' => 'afterPageSaveCommit',
			'Configuration.Contents.Backend.columnSystem.className.afterSaveCommit' => 'recompileAfterClassNameSave',
			'Configuration.Contents.Backend.columnSystem.maxColumns.afterSaveCommit' => 'recompileAfterMaxColumnsSave',
			'Configuration.Contents.Backend.columnSystem.className.afterDeleteCommit' => 'recompileAfterClassNameDelete',
			'Configuration.Contents.Backend.columnSystem.maxColumns.afterDeleteCommit' => 'recompileAfterMaxColumnsDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::beforeSave()
	 */
	public function testBeforeSaveEmptiesTitleTagWhenEmptyTitle(): void {
		$entity = $this->fetchTable('Contents')->newDefaultEntity([
			'title' => '',
			'titleTag' => 'h2',
		]);

		$event = new Event('Model.Contents.beforeSave', $this->fetchTable('Contents'));

		$this->listener->beforeSave($event, $entity);

		$this->assertNull($entity->titleTag);

		$entity = $this->fetchTable('Contents')->newDefaultEntity([
			'title' => 'Foobar',
			'titleTag' => 'h2',
		]);

		$event = new Event('Model.Contents.beforeSave', $this->fetchTable('Contents'));

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('h2', $entity->titleTag);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::beforeSave()
	 */
	public function testBeforeSaveEmptiesSubtitleTagWhenEmptySubtitle(): void {
		$entity = $this->fetchTable('Contents')->newDefaultEntity([
			'subtitle' => '',
			'subtitleTag' => 'h3',
		]);

		$event = new Event('Model.Contents.beforeSave', $this->fetchTable('Contents'));

		$this->listener->beforeSave($event, $entity);

		$this->assertNull($entity->subtitleTag);

		$entity = $this->fetchTable('Contents')->newDefaultEntity([
			'subtitle' => 'Foobar',
			'subtitleTag' => 'h3',
		]);

		$event = new Event('Model.Contents.beforeSave', $this->fetchTable('Contents'));

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('h3', $entity->subtitleTag);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::recompileAfterClassNameSave
	 * @throws \Exception
	 */
	public function testRecompilesFrontendScssWhenColumnClassNameChanged(): void {
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
			'scope' => 'Contents',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'columnSystem.className',
			'value' => '\Awyiss\Utility\Content\BootstrapColumnSystem',
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');

		$this->listener->recompileAfterClassNameSave($event, $entity);

		$this->assertSame('\Awyiss\Utility\Content\BootstrapColumnSystem', Configure::read('Awyiss.Contents.Backend.columnSystem.className'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::recompileAfterMaxColumnsSave()
	 * @throws \Exception
	 */
	public function testRecompilesFrontendScssWhenColumnMaxColumnsChanged(): void {
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
			'scope' => 'Contents',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'columnSystem.maxColumns',
			'value' => 10,
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');

		$this->listener->recompileAfterMaxColumnsSave($event, $entity);

		$this->assertSame(10, Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::recompileAfterClassNameDelete()
	 * @throws \Exception
	 */
	public function testRecompilesFrontendScssWhenColumnClassNameDeleted(): void {
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

		$this->listener->recompileAfterClassNameDelete();

		$this->assertNull(Configure::read('Awyiss.Contents.Backend.columnSystem.className'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::recompileAfterMaxColumnsDelete()
	 * @throws \Exception
	 */
	public function testRecompilesFrontendScssWhenColumnMaxColumnsDeleted(): void {
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

		$this->listener->recompileAfterMaxColumnsDelete();

		$this->assertNull(Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::afterSave()
	 * @see \Awyiss\Event\Backend\ContentsListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\ContentsListener::detectLanguageChange()
	 * @see \Awyiss\Event\Backend\ContentsListener::createAutoTranslationJobs()
	 */
	public function testAfterSaveCommitCreatesAutoTranslationJobWhenLanguageChanges(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.mode', 'auto');

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(1);

		// Move content from page 1 (de) to page 50 (es)
		$entity->pageId = 50;

		$options = new ArrayObject(['transactionId' => 'test-transaction-1']);
		$event = new Event('Model.Contents.afterSave', $contentsTable);

		// Call afterSave to detect language change
		$this->listener->afterSave($event, $entity, $options);

		// Mock Queue and Locks tables
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->setConstructorArgs([
			['table' => 'queued_jobs'],
		])->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())
			->method('createJob')
			->with(
				'AutoTranslate',
				$this->callback(function ($data) {
					return $data['sourceLanguage'] === 'de'
						&& $data['targetLanguage'] === 'es'
						&& $data['ids'] === [1]
						&& $data['type'] === 'content';
				}),
				[
					'group' => 'general',
					'priority' => 1,
					'reference' => 'System::autoTranslation',
				]
			);

		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['saveMany'])->getMock();

		$locksTable->expects($this->once())
			->method('saveMany')
			->with(
				$this->callback(function ($locks) {
					return count($locks) === 1
						&& $locks[0]->scope === 'Contents'
						&& $locks[0]->foreignKey === 1
						&& $locks[0]->uniqueId === 'autoTranslate';
				}),
				['checkRules' => false]
			);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		// Call afterSaveCommit to trigger job creation
		$event = new Event('Model.Contents.afterSaveCommit', $contentsTable);
		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\ContentsListener::detectLanguageChange()
	 * @see \Awyiss\Event\Backend\ContentsListener::createAutoTranslationJobs()
	 */
	public function testAfterSaveCommitNotCreatesJobWhenAutoTranslateManual(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.mode', 'manual');

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(1);

		$options = new ArrayObject(['transactionId' => 'test-transaction-1']);

		// Mock Queue and Locks tables
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->setConstructorArgs([
			['table' => 'queued_jobs'],
		])->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['saveMany'])->getMock();

		$locksTable->expects($this->never())->method('saveMany');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		// Call afterSaveCommit
		$event = new Event('Model.Contents.afterSaveCommit', $contentsTable);
		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\ContentsListener::detectLanguageChange()
	 * @see \Awyiss\Event\Backend\ContentsListener::createAutoTranslationJobs()
	 */
	public function testAfterSaveCommitNotCreatesJobWhenAutoTranslateDisabled(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.mode', 'disabled');

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(1);

		$options = new ArrayObject(['transactionId' => 'test-transaction-1']);

		// Mock Queue and Locks tables
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->setConstructorArgs([
			['table' => 'queued_jobs'],
		])->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['saveMany'])->getMock();

		$locksTable->expects($this->never())->method('saveMany');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		// Call afterSaveCommit
		$event = new Event('Model.Contents.afterSaveCommit', $contentsTable);
		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\ContentsListener::detectLanguageChange()
	 * @see \Awyiss\Event\Backend\ContentsListener::createAutoTranslationJobs()
	 */
	public function testAfterSaveCommitDoesNotCreateJobWhenNoLanguageChange(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.mode', 'auto');

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(1);

		// Move content from page 1 (de) to page 2 (de) - same language
		$entity->pageId = 2;

		$options = new ArrayObject(['transactionId' => 'test-transaction-1']);
		$event = new Event('Model.Contents.afterSave', $contentsTable);

		$this->listener->afterSave($event, $entity, $options);

		// Mock Queue and Locks tables
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$locksTable = $this->getMockBuilder(LocksTable::class)->disableOriginalConstructor()->onlyMethods(['saveMany'])->getMock();

		$locksTable->expects($this->never())->method('saveMany');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		// Call afterSaveCommit
		$event = new Event('Model.Contents.afterSaveCommit', $contentsTable);
		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\ContentsListener::detectLanguageChange()
	 * @see \Awyiss\Event\Backend\ContentsListener::createAutoTranslationJobs()
	 */
	public function testAfterSaveCommitBundlesMultipleContentsIntoOneJob(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.mode', 'auto');

		$contentsTable = $this->fetchTable('Contents');

		// Move multiple contents from page 1 (de) to page 50 (es)
		$options = new ArrayObject(['transactionId' => 'test-transaction-1']);
		$event = new Event('Model.Contents.afterSave', $contentsTable);

		foreach ([27, 32, 37] as $contentId) {
			/** @var \Awyiss\Model\Entity\Content $entity */
			$entity = $contentsTable->get($contentId);
			$entity->pageId = 50;
			$options['transactionId'] = 'test-transaction-' . $contentId;
			$this->listener->afterSave($event, $entity, $options);
		}

		// Mock Queue and Locks tables
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->setConstructorArgs([
			['table' => 'queued_jobs'],
		])->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())
			->method('createJob')
			->with(
				'AutoTranslate',
				$this->callback(function ($data) {
					return $data['sourceLanguage'] === 'de'
						&& $data['targetLanguage'] === 'es'
						&& $data['ids'] === [27, 32, 37]
						&& $data['type'] === 'content';
				}),
				[
					'group' => 'general',
					'priority' => 1,
					'reference' => 'System::autoTranslation',
				]
			);

		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['saveMany'])->getMock();

		$locksTable->expects($this->once())
			->method('saveMany')
			->with(
				$this->callback(function ($locks) {
					return count($locks) === 3;
				}),
				['checkRules' => false]
			);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		$event = new Event('Model.Contents.afterSaveCommit', $contentsTable);
		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\ContentsListener::detectLanguageChange()
	 * @see \Awyiss\Event\Backend\ContentsListener::createAutoTranslationJobs()
	 */
	public function testAfterSaveCommitRemovesContentsWithNoLanguageChange(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.mode', 'auto');

		$contentsTable = $this->fetchTable('Contents');

		// Move multiple contents from page 1 (de) to page 50 (es)
		$options = new ArrayObject(['transactionId' => 'test-transaction-1']);
		$event = new Event('Model.Contents.afterSave', $contentsTable);

		foreach ([27, 50, 37] as $contentId) {
			/** @var \Awyiss\Model\Entity\Content $entity */
			$entity = $contentsTable->get($contentId);
			$entity->pageId = 50;
			$options['transactionId'] = 'test-transaction-' . $contentId;
			$this->listener->afterSave($event, $entity, $options);
		}

		// Mock Queue and Locks tables
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->setConstructorArgs([
			['table' => 'queued_jobs'],
		])->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())
			->method('createJob')
			->with(
				'AutoTranslate',
				$this->callback(function ($data) {
					return $data['sourceLanguage'] === 'de'
						&& $data['targetLanguage'] === 'es'
						&& $data['ids'] === [27, 37]
						&& $data['type'] === 'content';
				}),
				[
					'group' => 'general',
					'priority' => 1,
					'reference' => 'System::autoTranslation',
				]
			);

		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['saveMany'])->getMock();

		$locksTable->expects($this->once())
			->method('saveMany')
			->with(
				$this->callback(function ($locks) {
					return count($locks) === 2;
				}),
				['checkRules' => false]
			);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		$event = new Event('Model.Contents.afterSaveCommit', $contentsTable);
		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\ContentsListener::detectLanguageChange()
	 */
	public function testAfterSaveCommitSkipsContentsWhenPageIdNotChanged(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.mode', 'auto');

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(1);

		$entity->clean();
		$entity->pageId = 1;

		// Don't change pageId, just change something else
		$entity->title = 'New Title';

		$options = new ArrayObject(['transactionId' => 'test-transaction-1']);
		$event = new Event('Model.Contents.afterSave', $contentsTable);

		$this->listener->afterSave($event, $entity, $options);

		// Mock Queue and Locks tables
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)
			->disableOriginalConstructor()
			->onlyMethods(['createJob'])
			->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		// Call afterSaveCommit
		$event = new Event('Model.Contents.afterSaveCommit', $contentsTable);
		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\ContentsListener::createAutoTranslationJobs()
	 */
	public function testAfterSaveCommitIgnoresExceptionWhenLockSaveFails(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.mode', 'auto');

		$contentsTable = $this->fetchTable('Contents');
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $contentsTable->get(1);
		$entity->setNew(false);
		$entity->clean();

		// Move content from page 1 (de) to page 50 (es)
		$entity->pageId = 50;

		$options = new ArrayObject(['transactionId' => 'test-transaction-1']);
		$event = new Event('Model.Contents.afterSave', $contentsTable);

		$this->listener->afterSave($event, $entity, $options);

		// Mock Queue and Locks tables
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->setConstructorArgs([
			['table' => 'queued_jobs'],
		])->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob');

		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['saveMany'])->getMock();

		// Simulate saveMany throwing an exception
		$locksTable->expects($this->once())->method('saveMany')->willThrowException(new Exception('Lock save failed'));

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		// Call afterSaveCommit - should not throw exception
		$event = new Event('Model.Contents.afterSaveCommit', $contentsTable);
		$this->listener->afterSaveCommit($event, $entity, $options);
	}
}
