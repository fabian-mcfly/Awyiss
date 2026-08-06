<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Queue\Task;


use Awyiss\Model\Table\ContentsTable;
use Awyiss\Model\Table\LocksTable;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Queue\Task\AutoTranslateTask;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Translation\TranslationResult;
use Awyiss\Utility\Translation\TranslationServiceInterface;
use Awyiss\Utility\Translation\TranslationUsageInfo;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Customer\Model\Enum\PageRole;
use Queue\Model\Table\QueuedJobsTable;
use ReflectionClass;
use RuntimeException;


/**
 * Test case for AutoTranslateTask
 *
 * @see \Awyiss\Queue\Task\AutoTranslateTask
 */
class AutoTranslateTaskTest extends TestCase {
	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::run()
	 * @throws \Exception
	 */
	public function testRunThrowsExceptionWhenTypeIsMissing(): void {
		$task = new AutoTranslateTask();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('AutoTranslateTask: Missing type in job data.');

		$task->run([], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::run()
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::translateContents()
	 * @throws \Exception
	 */
	public function testRunCallsTranslateContentsWhenTypeIsContent(): void {
		// Create a mock translation service
		$mockService = $this->createMock(TranslationServiceInterface::class);
		$mockService->expects($this->once())->method('getBatchSize')->willReturn(10);

		$mockService->expects($this->atLeastOnce())->method('translateEntity')->willReturnCallback(function (EntityInterface $entity): EntityInterface {
			/** @var \Awyiss\Model\Entity\Content $entity */
			$entity->title = 'Translated Title';
			$entity->text = 'Translated Text';

			return $entity;
		});

		// Create a mock task that returns our mock service
		$task = $this->getMockBuilder(AutoTranslateTask::class)->onlyMethods(['getTranslationService'])->getMock();

		$task->expects($this->once())->method('getTranslationService')->with('de', 'es')->willReturn($mockService);

		// Mock the Locks table to avoid actual database operations
		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['deleteAll'])->getMock();

		$locksTable->expects($this->once())->method('deleteAll')->with([
			'scope' => 'Contents',
			'foreignKey IN' => [1],
			'uniqueId' => 'autoTranslate',
		]);

		// Mock Contents table
		$contentsTable = $this->getMockBuilder(ContentsTable::class)->onlyMethods(['saveMany'])->getMock();
		$contentsTable->expects($this->once())->method('saveMany')->with(
			$this->isType('array'),
			$this->arrayHasKey('atomic')
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Contents');
		$tableLocator->set('Contents', $contentsTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		$task->run([
			'type' => 'content',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [1],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::run()
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::translatePages()
	 * @throws \Exception
	 */
	public function testRunCallsTranslatePagesWhenTypeIsPage(): void {
		// Create a mock translation service
		$mockService = $this->createMock(TranslationServiceInterface::class);
		$mockService->expects($this->once())->method('getBatchSize')->willReturn(10);

		$mockService->expects($this->atLeastOnce())->method('translateEntity')->willReturnCallback(function (EntityInterface $entity): EntityInterface {
			/** @var \Awyiss\Model\Entity\Page $entity */
			$entity->title = 'Translated Page Title';

			return $entity;
		});

		// Create a mock task that returns our mock service
		$task = $this->getMockBuilder(AutoTranslateTask::class)->onlyMethods(['getTranslationService'])->getMock();

		$task->expects($this->once())->method('getTranslationService')->with('de', 'es')->willReturn($mockService);

		// Mock the Locks table
		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['deleteAll'])->getMock();

		$locksTable->expects($this->once())->method('deleteAll')->with([
			'scope' => 'Pages',
			'foreignKey IN' => [1],
			'uniqueId' => 'autoTranslate',
		]);

		// Mock Pages table
		$pagesTable = $this->getMockBuilder(PagesTable::class)->disableOriginalConstructor()->onlyMethods(['saveMany'])->getMock();

		$reflection = new ReflectionClass($pagesTable);
		$reflectionProperty = $reflection->getProperty('pageRole');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue($pagesTable, PageRole::Page);

		$pagesTable->__construct();

		$pagesTable->expects($this->once())->method('saveMany')->with(
			$this->isType('array'),
			$this->arrayHasKey('atomic')
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Pages');
		$tableLocator->set('Pages', $pagesTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		$task->run([
			'type' => 'page',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [1],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::translateContents()
	 * @throws \Exception
	 */
	public function testTranslateContentsCreatesSplitJobWhenBatchSizeExceeded(): void {
		// Create a mock translation service with batch size 2
		$mockService = $this->createMock(TranslationServiceInterface::class);
		$mockService->expects($this->once())->method('getBatchSize')->willReturn(2);

		$mockService->expects($this->exactly(2))->method('translateEntity')->willReturnCallback(function (EntityInterface $entity): EntityInterface {
			return $entity;
		});

		// Create a mock task
		$task = $this->getMockBuilder(AutoTranslateTask::class)->onlyMethods(['getTranslationService'])->getMock();

		$task->expects($this->once())->method('getTranslationService')->willReturn($mockService);

		// Mock Queue table to verify new job is created
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->setConstructorArgs([
			['table' => 'queued_jobs'],
		])->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'AutoTranslate',
			$this->callback(function ($data) {
				return $data['sourceLanguage'] === 'de' && $data['targetLanguage'] === 'es' && $data['ids'] === [3, 4, 5] && $data['type'] === 'content';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'System::autoTranslation',
			]
		);

		// Mock Locks table
		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['deleteAll'])->getMock();

		// Mock Contents table
		$contentsTable = $this->getMockBuilder(ContentsTable::class)->onlyMethods(['saveMany'])->getMock();
		$contentsTable->expects($this->once())->method('saveMany')->with(
			$this->isType('array'),
			$this->arrayHasKey('atomic')
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->remove('Contents');
		$tableLocator->set('Contents', $contentsTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		$task->run([
			'type' => 'content',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [1, 2, 3, 4, 5],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::translatePages()
	 * @throws \Exception
	 */
	public function testTranslatePagesCreatesSplitJobWhenBatchSizeExceeded(): void {
		// Create a mock translation service with batch size 2
		$mockService = $this->createMock(TranslationServiceInterface::class);
		$mockService->expects($this->once())->method('getBatchSize')->willReturn(2);

		$mockService->expects($this->exactly(2))->method('translateEntity')->willReturnCallback(function (EntityInterface $entity): EntityInterface {
			return $entity;
		});

		// Create a mock task
		$task = $this->getMockBuilder(AutoTranslateTask::class)->onlyMethods(['getTranslationService'])->getMock();

		$task->expects($this->once())->method('getTranslationService')->willReturn($mockService);

		// Mock Queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->setConstructorArgs([
			['table' => 'queued_jobs'],
		])->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'AutoTranslate',
			$this->callback(function ($data) {
				return $data['sourceLanguage'] === 'de' && $data['targetLanguage'] === 'es' && $data['ids'] === [3, 4, 5] && $data['type'] === 'page';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'System::autoTranslation',
			]
		);

		// Mock Locks table
		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['deleteAll'])->getMock();

		// Mock Pages table
		$pagesTable = $this->getMockBuilder(PagesTable::class)->disableOriginalConstructor()->onlyMethods(['saveMany'])->getMock();

		$reflection = new ReflectionClass($pagesTable);
		$reflectionProperty = $reflection->getProperty('pageRole');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue($pagesTable, PageRole::Page);

		$pagesTable->__construct();

		$pagesTable->expects($this->once())->method('saveMany')->with(
			$this->isType('array'),
			$this->arrayHasKey('atomic')
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->remove('Pages');
		$tableLocator->set('Pages', $pagesTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		$task->run([
			'type' => 'page',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [1, 2, 3, 4, 5],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::translateEntities()
	 * @throws \Exception
	 */
	public function testTranslateEntitiesSkipsEntitiesThatReturnFalse(): void {
		// Create a mock translation service that returns false for some entities
		$mockService = $this->createMock(TranslationServiceInterface::class);
		$mockService->expects($this->once())->method('getBatchSize')->willReturn(10);

		$callCount = 0;
		$mockService->expects($this->exactly(3))->method('translateEntity')->willReturnCallback(function (EntityInterface $entity) use (&$callCount): EntityInterface|false {
			$callCount++;

			// Return false for the second entity
			if ($callCount === 2) {
				return false;
			}

			return $entity;
		});

		// Create a mock task
		$task = $this->getMockBuilder(AutoTranslateTask::class)->onlyMethods(['getTranslationService'])->getMock();

		$task->expects($this->once())->method('getTranslationService')->willReturn($mockService);

		// Mock Locks table
		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['deleteAll'])->getMock();

		// Mock Contents table
		$contentsTable = $this->getMockBuilder(ContentsTable::class)->onlyMethods(['saveMany'])->getMock();
		$contentsTable->expects($this->once())->method('saveMany')->with(
			$this->isType('array'),
			$this->arrayHasKey('atomic')
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Contents');
		$tableLocator->set('Contents', $contentsTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		$task->run([
			'type' => 'content',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [1, 2, 3],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::translateEntities()
	 * @throws \Exception
	 */
	public function testTranslateEntitiesRemovesLocksAfterCompletion(): void {
		// Create a mock translation service
		$mockService = $this->createMock(TranslationServiceInterface::class);
		$mockService->expects($this->once())->method('getBatchSize')->willReturn(10);

		$mockService->expects($this->exactly(2))->method('translateEntity')->willReturnCallback(function (EntityInterface $entity): EntityInterface {
			return $entity;
		});

		// Create a mock task
		$task = $this->getMockBuilder(AutoTranslateTask::class)->onlyMethods(['getTranslationService'])->getMock();

		$task->expects($this->once())->method('getTranslationService')->willReturn($mockService);

		// Mock Locks table
		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['deleteAll'])->getMock();

		$locksTable->expects($this->once())->method('deleteAll')->with([
			'scope' => 'Contents',
			'foreignKey IN' => [1, 2],
			'uniqueId' => 'autoTranslate',
		]);

		// Mock Contents table
		$contentsTable = $this->getMockBuilder(ContentsTable::class)->onlyMethods(['saveMany'])->getMock();
		$contentsTable->expects($this->once())->method('saveMany')->with(
			$this->isType('array'),
			$this->arrayHasKey('atomic')
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Contents');
		$tableLocator->set('Contents', $contentsTable);
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		$task->run([
			'type' => 'content',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [1, 2],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::getTranslationService()
	 * @throws \Exception
	 */
	public function testGetTranslationServiceThrowsExceptionWhenNotConfigured(): void {
		Configure::write('Awyiss.System.Backend.autoTranslate.translationService');

		$task = new AutoTranslateTask();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No translation service configured for auto translation.');

		$task->run([
			'type' => 'content',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [1],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::getTranslationService()
	 * @throws \ReflectionException|\Exception
	 */
	public function testGetTranslationServiceThrowsExceptionWhenSourceLanguageNotSupported(): void {
		// Create a mock service that doesn't support 'de'
		$mockServiceClass = new class implements TranslationServiceInterface {
			/**
			 * @return int
			 */
			public function getBatchSize(): int {
				return 10;
			}


			/**
			 * @return array<string>
			 */
			public function getSupportedSourceLanguages(): array {
				return ['en', 'fr'];
			}


			/**
			 * @return array<string>
			 */
			public function getSupportedTargetLanguages(): array {
				return ['en', 'es', 'fr'];
			}


			/**
			 * @param string $text
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $options
			 * @return \Awyiss\Utility\Translation\TranslationResult|false
			 */
			public function translateText(
				string $text,
				string $targetLanguage,
				?string $sourceLanguage = null,
				array $options = []
			): TranslationResult|false {
				return false;
			}


			/**
			 * @param array $texts
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $options
			 * @return array|false
			 */
			public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false {
				return false;
			}


			/**
			 * @param \Cake\Datasource\EntityInterface $entity
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $fields
			 * @param array $options
			 * @return \Cake\Datasource\EntityInterface|false
			 */
			public function translateEntity(
				EntityInterface $entity,
				string $targetLanguage,
				?string $sourceLanguage = null,
				array $fields = [],
				array $options = []
			): EntityInterface|false {
				return false;
			}


			/**
			 * @return \Awyiss\Utility\Translation\TranslationUsageInfo|null
			 */
			public function getUsageInfo(): ?TranslationUsageInfo {
				return null;
			}
		};

		Configure::write('Awyiss.System.Backend.autoTranslate.translationService', get_class($mockServiceClass));

		$task = new AutoTranslateTask();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Source language `de` is not supported by the translation service.');

		$task->run([
			'type' => 'content',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [1],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::getTranslationService()
	 * @throws \Exception
	 */
	public function testGetTranslationServiceThrowsExceptionWhenTargetLanguageNotSupported(): void {
		// Create a mock service that doesn't support 'es'
		$mockServiceClass = new class implements TranslationServiceInterface {
			/**
			 * @return int
			 */
			public function getBatchSize(): int {
				return 10;
			}


			/**
			 * @return array<string>
			 */
			public function getSupportedSourceLanguages(): array {
				return ['de', 'en', 'fr'];
			}


			/**
			 * @return array<string>
			 */
			public function getSupportedTargetLanguages(): array {
				return ['en', 'fr'];
			}


			/**
			 * @param string $text
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $options
			 * @return \Awyiss\Utility\Translation\TranslationResult|false
			 */
			public function translateText(
				string $text,
				string $targetLanguage,
				?string $sourceLanguage = null,
				array $options = []
			): TranslationResult|false {
				return false;
			}


			/**
			 * @param array $texts
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $options
			 * @return array|false
			 */
			public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false {
				return false;
			}


			/**
			 * @param \Cake\Datasource\EntityInterface $entity
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $fields
			 * @param array $options
			 * @return \Cake\Datasource\EntityInterface|false
			 */
			public function translateEntity(
				EntityInterface $entity,
				string $targetLanguage,
				?string $sourceLanguage = null,
				array $fields = [],
				array $options = []
			): EntityInterface|false {
				return false;
			}


			/**
			 * @return \Awyiss\Utility\Translation\TranslationUsageInfo|null
			 */
			public function getUsageInfo(): ?TranslationUsageInfo {
				return null;
			}
		};

		Configure::write('Awyiss.System.Backend.autoTranslate.translationService', get_class($mockServiceClass));

		$task = new AutoTranslateTask();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Target language `es` is not supported by the translation service.');

		$task->run([
			'type' => 'content',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [1],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::getTranslationService()
	 * @throws \ReflectionException
	 */
	public function testGetTranslationServiceReturnsServiceWhenLanguagesSupported(): void {
		// Create a mock service that supports both languages
		$mockServiceClass = new class implements TranslationServiceInterface {
			/**
			 * @return int
			 */
			public function getBatchSize(): int {
				return 10;
			}


			/**
			 * @return array<string>
			 */
			public function getSupportedSourceLanguages(): array {
				return ['de', 'en', 'fr'];
			}


			/**
			 * @return array<string>
			 */
			public function getSupportedTargetLanguages(): array {
				return ['en', 'es', 'fr'];
			}


			/**
			 * @param string $text
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $options
			 * @return \Awyiss\Utility\Translation\TranslationResult|false
			 */
			public function translateText(
				string $text,
				string $targetLanguage,
				?string $sourceLanguage = null,
				array $options = []
			): TranslationResult|false {
				return false;
			}


			/**
			 * @param array $texts
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $options
			 * @return array|false
			 */
			public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null, array $options = []): array|false {
				return false;
			}


			/**
			 * @param \Cake\Datasource\EntityInterface $entity
			 * @param string $targetLanguage
			 * @param string|null $sourceLanguage
			 * @param array $fields
			 * @param array $options
			 * @return \Cake\Datasource\EntityInterface|false
			 */
			public function translateEntity(
				EntityInterface $entity,
				string $targetLanguage,
				?string $sourceLanguage = null,
				array $fields = [],
				array $options = []
			): EntityInterface|false {
				return false;
			}


			/**
			 * @return \Awyiss\Utility\Translation\TranslationUsageInfo|null
			 */
			public function getUsageInfo(): ?TranslationUsageInfo {
				return null;
			}
		};

		Configure::write('Awyiss.System.Backend.autoTranslate.translationService', get_class($mockServiceClass));

		$task = new AutoTranslateTask();
		$service = $this->callProtectedMethod($task, 'getTranslationService', 'de', 'es');

		$this->assertInstanceOf(TranslationServiceInterface::class, $service);
		$this->assertSame(10, $service->getBatchSize());
		$this->assertContains('de', $service->getSupportedSourceLanguages());
		$this->assertContains('es', $service->getSupportedTargetLanguages());
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::translateEntities()
	 * @throws \Exception
	 */
	public function testTranslateEntitiesReturnsEarlyWhenNoEntitiesFound(): void {
		// Create a mock translation service
		$mockService = $this->createMock(TranslationServiceInterface::class);
		$mockService->expects($this->once())->method('getBatchSize')->willReturn(10);

		// translateEntity should never be called
		$mockService->expects($this->never())->method('translateEntity');

		// Create a mock task
		$task = $this->getMockBuilder(AutoTranslateTask::class)->onlyMethods(['getTranslationService'])->getMock();

		$task->expects($this->once())->method('getTranslationService')->willReturn($mockService);

		// Mock Locks table - should never be called since no entities exist
		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['deleteAll'])->getMock();

		$locksTable->expects($this->never())->method('deleteAll');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		// Use non-existent IDs
		$task->run([
			'type' => 'content',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [99999, 99998],
		], 1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\AutoTranslateTask::translatePages()
	 * @throws \Exception
	 */
	public function testTranslatePagesUsesCorrectTableForNews(): void {
		// Create a mock translation service
		$mockService = $this->createMock(TranslationServiceInterface::class);
		$mockService->expects($this->once())->method('getBatchSize')->willReturn(10);

		$mockService->expects($this->once())->method('translateEntity')->willReturnCallback(function ($entity) {
			return $entity;
		});

		// Create a mock task
		$task = $this->getMockBuilder(AutoTranslateTask::class)->onlyMethods(['getTranslationService'])->getMock();

		$task->expects($this->once())->method('getTranslationService')->willReturn($mockService);

		// Mock Locks table - verify it uses 'news' as scope
		$locksTable = $this->getMockBuilder(LocksTable::class)->onlyMethods(['deleteAll'])->getMock();

		$locksTable->expects($this->once())->method('deleteAll')->with([
			'scope' => 'News',
			'foreignKey IN' => [40],
			'uniqueId' => 'autoTranslate',
		]);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Locks');
		$tableLocator->set('Locks', $locksTable);

		$newsTable = $this->getMockBuilder(PagesTable::class)->disableOriginalConstructor()->onlyMethods(['saveMany'])->getMock();

		$reflection = new ReflectionClass($newsTable);
		$reflectionProperty = $reflection->getProperty('pageRole');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue($newsTable, PageRole::News);

		$newsTable->__construct();

		$newsTable->expects($this->once())->method('saveMany')->with(
			$this->isType('array'),
			$this->arrayHasKey('atomic')
		);
		$tableLocator->remove('News');
		$tableLocator->set('News', $newsTable);

		$task->run([
			'type' => 'news',
			'sourceLanguage' => 'de',
			'targetLanguage' => 'es',
			'ids' => [40],
		], 1);
	}
}
