<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Configuration\ConfigOptions\MediaConfigOptions;
use Awyiss\Event\Backend\MediaFoldersListener;
use Awyiss\Event\Backend\MediaListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Table\UrlHistoryTable;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\ORM\Query\InsertQuery;
use Symfony\Component\Process\Process;


/**
 * MediaFoldersListener Test Case
 *
 * @see \Awyiss\Event\Backend\MediaFoldersListener
 */
class MediaFoldersListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\MediaFoldersListener
	 */
	protected MediaFoldersListener $listener;
	/**
	 * @var array<string>
	 */
	protected array $tmpDirs = [];


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new MediaFoldersListener();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		foreach ($this->tmpDirs as $dir) {
			if (is_dir($dir)) {
				new Process(['rm', '-r', $dir])->run();
			}
		}

		if (is_dir(WWW_ROOT . 'media' . DS . 'test-folder')) {
			new Process(['rm', '-r', WWW_ROOT . 'media' . DS . 'test-folder'])->run();
		}

		if (is_dir(WWW_ROOT . 'media' . DS . 'parent')) {
			new Process(['rm', '-r', WWW_ROOT . 'media' . DS . 'parent'])->run();
		}

		if (is_dir(WWW_ROOT . 'media' . DS . 'new-parent')) {
			new Process(['rm', '-r', WWW_ROOT . 'media' . DS . 'new-parent'])->run();
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.MediaFolders.afterCopyCommit' => 'afterCopyCommit',
			'Model.MediaFolders.beforeSave' => 'beforeSave',
			'Model.MediaFolders.afterSave' => 'afterSave',
			'Model.MediaFolders.afterSaveCommit' => 'afterSaveCommit',
			'Model.MediaFolders.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.MediaFolders.afterDeleteCommit' => 'afterDeleteCommit',
			'Model.MediaFolders.afterSoftDeleteCommit' => 'afterSoftDeleteCommit',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 */
	public function testBeforeSaveSetsPathFromTitleWhenEmpty(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$entity = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Test Folder',
			'path' => '',
		]);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('media/test-folder', $entity->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 */
	public function testBeforeSaveMarksPathAsCleanWhenUnchanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$entity = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Test Folder',
			'path' => 'media/original-path',
		]);
		$entity->setNew(false);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->path = 'media/different-path';
		$entity->path = 'media/original-path';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($entity->isDirty('path'));

		$entity->title = 'Original Path';
		$entity->path = null;

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($entity->isDirty('path'));

		$entity->path = 'media/different-path';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$this->assertTrue($entity->isDirty('path'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 * @throws \Exception
	 */
	public function testBeforeSavePrependsParentPath(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		$entity = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Child Folder',
			'path' => 'some-ignored-path/new-child',
			'parentId' => 890,
		]);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('media/parent/new-child', $entity->path);

		$entity->parentId = 892;

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('media/parent/child1/grandchild1/new-child', $entity->path);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 * @throws \Exception
	 */
	public function testBeforeSaveSetsParentsActiveDependingOnParent(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		$entity = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Child Folder',
			'path' => 'some-ignored-path/new-child',
			'parentId' => 891,
		]);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$mediaFoldersTable->updateAll(['active' => false], ['id' => 891]);

		$this->listener->beforeSave($event, $entity);

		$mediaFoldersTable->updateAll(['active' => true], ['id' => 891]);

		$this->assertFalse($entity->parentsActive);

		$this->listener->beforeSave($event, $entity);

		$this->assertTrue($entity->parentsActive);

		$mediaFoldersTable->updateAll(['parentsActive' => false], ['id' => 891]);

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($entity->parentsActive);

		$mediaFoldersTable->updateAll(['parentsActive' => true], ['id' => 891]);

		$this->listener->beforeSave($event, $entity);

		$this->assertTrue($entity->parentsActive);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 */
	public function testBeforeSaveSetsParentsActiveTrueWhenNoParent(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$entity = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Root Folder',
			'path' => 'root',
			'parentId' => null,
			'parentsActive' => false,
		]);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertTrue($entity->parentsActive);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 * @throws \Exception
	 */
	public function testBeforeSaveEnsuresUniquePathForNewEntity(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		$entity = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Child 1',
			'path' => 'media/parent/child1',
			'parentId' => 890,
		]);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('media/parent/child1-2', $entity->path);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 * @throws \Exception
	 */
	public function testBeforeSaveEnsuresUniquePathForExistingEntityWhenPathChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(891);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->path = 'media/parent/child2';

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('media/parent/child2-2', $entity->path);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 * @throws \Exception
	 */
	public function testBeforeSaveNotEnsuresUniquePathForExistingEntityWhenPathUnchanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(891);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('media/parent/child1', $entity->path);

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->path = 'media/parent/child2';
		$entity->path = 'media/parent/child1';

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('media/parent/child1', $entity->path);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 * @throws \Exception
	 */
	public function testBeforeSaveEnsuresUniquePathForExistingEntityWhenPathUnchangedAndLanguageChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(5);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$entity->path = 'parent';
		$entity->parentId = null;
		$entity->clean();
		$entity->languageShortcode = 'es';

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('media/parent-2', $entity->path);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSave()
	 * @throws \Exception
	 */
	public function testBeforeSaveEnsuresUniquePathNeverExceedsMaxLength(): void {
		$longPath = 'media/parent/' . str_repeat('dummyfolder', 92);
		$longPath = substr($longPath, 0, 1024);

		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		$entity = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Test Folder',
			'path' => $longPath,
			'languageShortcode' => 'de',
			'parentId' => 890,
		]);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$mediaFoldersTable->updateAll(['path' => $longPath], ['id' => 891]);

		$this->listener->beforeSave($event, $entity);

		$this->assertEquals(1024, strlen($entity->path));
		$this->assertStringStartsWith('media/parent/', $entity->path);
		$this->assertStringEndsWith('dummyfol-2', $entity->path);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterCopyCommit()
	 * @throws \Exception
	 */
	public function testAfterCopyCommitCopiesMediaEntities(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$mediaTable = $this->fetchTable('Media');

		$this->createDummyFolders();

		$insertQuery = $mediaTable->insertQuery();

		$insertQuery->insert([
			'id',
			'name',
			'path',
			'mimeType',
			'mediaFolderId',
		]);

		$insertQuery->values([
			'id' => 890,
			'name' => 'file1.jpg',
			'path' => 'media/parent/file1.jpg',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => 890,
		]);

		$insertQuery->values([
			'id' => 891,
			'name' => 'file2.png',
			'path' => 'media/parent/file2.png',
			'mimeType' => 'image/png',
			'mediaFolderId' => 890,
		]);

		$this->assertNotFalse($insertQuery->execute());

		$this->assertCount(2, $mediaTable->find()->where(['mediaFolderId' => 890])->all());
		$this->assertCount(0, $mediaTable->find()->where(['mediaFolderId' => 896])->all());

		$originalEntity = $mediaFoldersTable->get(890);

		$entity = $mediaFoldersTable->newDefaultEntity([
			'id' => 896,
			'path' => 'media/target-folder',
		]);
		$entity->originalEntity = $originalEntity;

		$event = new Event('Model.MediaFolders.afterCopyCommit', $mediaFoldersTable);

		$this->listener->afterCopyCommit($event, $entity, new ArrayObject(['_primary' => false]));

		$this->assertCount(2, $mediaTable->find()->where(['mediaFolderId' => 890])->all());
		$this->assertCount(2, $mediaTable->find()->where(['mediaFolderId' => 896])->all());

		$newFiles = $mediaTable->find()->where(['mediaFolderId' => 896])->all();
		foreach ($newFiles as $file) {
			$this->assertStringStartsWith('media/parent/child3/file', $file->path);
		}

		$this->deleteDummyFolders();

		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterCopyCommit()
	 * @throws \Exception
	 */
	public function testAfterCopyCommitCopiesDirectory(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$sourceDir = WWW_ROOT . 'media' . DS . 'source-folder';
		$targetDir = WWW_ROOT . 'media' . DS . 'target-folder';

		if (!is_dir($sourceDir)) {
			mkdir($sourceDir, 0755, true);
		}
		file_put_contents($sourceDir . DS . 'test.txt', 'test content');

		$this->tmpDirs[] = $sourceDir;
		$this->tmpDirs[] = $targetDir;

		$originalEntity = $mediaFoldersTable->newDefaultEntity([
			'id' => 1,
			'path' => 'media/source-folder',
		]);

		$entity = $mediaFoldersTable->newDefaultEntity([
			'id' => 890,
			'path' => 'media/target-folder',
		]);
		$entity->originalEntity = $originalEntity;

		$event = new Event('Model.MediaFolders.afterCopyCommit', $mediaFoldersTable);

		$this->listener->afterCopyCommit($event, $entity, new ArrayObject(['_primary' => true]));

		$this->assertFileExists($targetDir . DS . 'test.txt');
		$this->assertSame('test content', file_get_contents($targetDir . DS . 'test.txt'));

		$mediaTable = $this->fetchTable('Media');
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterCopyCommit()
	 * @throws \Exception
	 */
	public function testAfterCopyCommitNotCopiesDirectoryWhenNotPrimary(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$sourceDir = WWW_ROOT . 'media' . DS . 'source-folder-2';
		$targetDir = WWW_ROOT . 'media' . DS . 'target-folder-2';

		if (!is_dir($sourceDir)) {
			mkdir($sourceDir, 0755, true);
		}
		file_put_contents($sourceDir . DS . 'test.txt', 'test content');

		$this->tmpDirs[] = $sourceDir;

		$originalEntity = $mediaFoldersTable->newDefaultEntity([
			'id' => 1,
			'path' => 'source-folder-2',
		]);

		$entity = $mediaFoldersTable->newDefaultEntity([
			'id' => 890,
			'path' => 'media/target-folder-2',
		]);
		$entity->originalEntity = $originalEntity;

		$event = new Event('Model.MediaFolders.afterCopyCommit', $mediaFoldersTable);

		$this->listener->afterCopyCommit($event, $entity, new ArrayObject(['_primary' => false]));

		$this->assertDirectoryDoesNotExist($targetDir);

		$mediaTable = $this->fetchTable('Media');
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotCreatesHistoricalPathsWhenPathChangedAndConfigSettingDisabled(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_DISABLED);

		$this->createDummyFolders();

		$mediaTable = $this->fetchTable('Media');
		$media = [
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file1.jpg',
				'path' => 'media/parent/file1.jpg',
				'mimeType' => 'image/jpeg',
			]),
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file2.png',
				'path' => 'media/parent/file2.png',
				'mimeType' => 'image/png',
			]),
		];
		$result = $mediaTable->saveMany($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		// Mock the UrlHistory table
		$urlHistoryTableMock = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods([
			'insertQuery',
		])->getMock();
		$urlHistoryTableMock->expects($this->never())->method('insertQuery');

		/** @var \Awyiss\ORM\Locator\TableLocator $tableLocator */
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('UrlHistory');
		$tableLocator->set('UrlHistory', $urlHistoryTableMock);

		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder  $entity */
		$entity = $mediaFoldersTable->get(890);
		$entity->path = 'media/new-folder';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$this->deleteDummyFolders();
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotCreatesHistoricalPathsWhenPathChangedAndConfigSettingFalse(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', false);

		$this->createDummyFolders();

		$mediaTable = $this->fetchTable('Media');
		$media = [
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file1.jpg',
				'path' => 'media/parent/file1.jpg',
				'mimeType' => 'image/jpeg',
			]),
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file2.png',
				'path' => 'media/parent/file2.png',
				'mimeType' => 'image/png',
			]),
		];
		$result = $mediaTable->saveMany($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		// Mock the UrlHistory table
		$urlHistoryTableMock = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods([
			'insertQuery',
		])->getMock();
		$urlHistoryTableMock->expects($this->never())->method('insertQuery');

		/** @var \Awyiss\ORM\Locator\TableLocator $tableLocator */
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('UrlHistory');
		$tableLocator->set('UrlHistory', $urlHistoryTableMock);

		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder  $entity */
		$entity = $mediaFoldersTable->get(890);
		$entity->path = 'media/new-folder';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$this->deleteDummyFolders();
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveCreatesHistoricalPathsWhenPathChangedAndConfigSettingAlways(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS);

		$this->createDummyFolders();

		$mediaTable = $this->fetchTable('Media');
		$media = [
			$media1 = $mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file1.jpg',
				'path' => 'media/parent/file1.jpg',
				'mimeType' => 'image/jpeg',
			]),
			$media2 = $mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file2.png',
				'path' => 'media/parent/file2.png',
				'mimeType' => 'image/png',
			]),
		];
		$result = $mediaTable->saveMany($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$mockQuery = $this->getMockBuilder(InsertQuery::class)->disableOriginalConstructor()->onlyMethods([
			'insert',
			'values',
			'execute',
		])->getMock();

		// Mock the UrlHistory table
		$urlHistoryTableMock = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods([
			'insertQuery',
		])->getMock();
		$urlHistoryTableMock->expects($this->once())->method('insertQuery')->willReturn($mockQuery);

		$mockQuery->expects($this->once())->method('insert')->willReturnSelf();
		$mockQuery->expects($this->exactly(2))->method('values')->with($this->callback(function (array $data) use ($media1, $media2): bool {
			return $data['scope'] === 'Media' &&
				   $data['status'] === 308 &&
				   ($data['foreignKey'] === $media1->id || $data['foreignKey'] === $media2->id) &&
				   ($data['url'] === $media1->path || $data['url'] === $media2->path);
		}))->willReturnSelf();
		$mockQuery->expects($this->once())->method('execute');

		/** @var \Awyiss\ORM\Locator\TableLocator $tableLocator */
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('UrlHistory');
		$tableLocator->set('UrlHistory', $urlHistoryTableMock);

		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);
		$entity->path = 'media/new-folder';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$this->deleteDummyFolders();
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotCreatesHistoricalPathsWhenPathChangedAndConfigSettingFileNameChange(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_FILE_NAME_CHANGE);

		$this->createDummyFolders();

		$mediaTable = $this->fetchTable('Media');
		$media = [
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file1.jpg',
				'path' => 'media/parent/file1.jpg',
				'mimeType' => 'image/jpeg',
			]),
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file2.png',
				'path' => 'media/parent/file2.png',
				'mimeType' => 'image/png',
			]),
		];
		$result = $mediaTable->saveMany($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		// Mock the UrlHistory table
		$urlHistoryTableMock = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods([
			'insertQuery',
		])->getMock();
		$urlHistoryTableMock->expects($this->never())->method('insertQuery');

		/** @var \Awyiss\ORM\Locator\TableLocator $tableLocator */
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('UrlHistory');
		$tableLocator->set('UrlHistory', $urlHistoryTableMock);

		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);
		$entity->path = 'media/new-folder';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$this->deleteDummyFolders();
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveCreatesHistoricalPathsWhenPathChangedAndConfigSettingFolderNameChange(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_FOLDER_NAME_CHANGE);

		$this->createDummyFolders();

		$mediaTable = $this->fetchTable('Media');
		$media = [
			$media1 = $mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file1.jpg',
				'path' => 'media/parent/file1.jpg',
				'mimeType' => 'image/jpeg',
			]),
			$media2 = $mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file2.png',
				'path' => 'media/parent/file2.png',
				'mimeType' => 'image/png',
			]),
		];
		$result = $mediaTable->saveMany($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$mockQuery = $this->getMockBuilder(InsertQuery::class)->disableOriginalConstructor()->onlyMethods([
			'insert',
			'values',
			'execute',
		])->getMock();

		// Mock the UrlHistory table
		$urlHistoryTableMock = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods([
			'insertQuery',
		])->getMock();
		$urlHistoryTableMock->expects($this->once())->method('insertQuery')->willReturn($mockQuery);
		$mockQuery->expects($this->once())->method('insert')->willReturnSelf();
		$mockQuery->expects($this->exactly(2))->method('values')->with($this->callback(function (array $data) use ($media1, $media2): bool {
			return $data['scope'] === 'Media' &&
				   $data['status'] === 308 &&
				   ($data['foreignKey'] === $media1->id || $data['foreignKey'] === $media2->id) &&
				   ($data['url'] === $media1->path || $data['url'] === $media2->path);
		}))->willReturnSelf();
		$mockQuery->expects($this->once())->method('execute');

		/** @var \Awyiss\ORM\Locator\TableLocator $tableLocator */
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('UrlHistory');
		$tableLocator->set('UrlHistory', $urlHistoryTableMock);

		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);
		$entity->path = 'media/new-folder';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$this->deleteDummyFolders();
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotCreatesHistoricalPathsWhenPathUnchangedAndConfigSettingFileNameChange(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_FOLDER_NAME_CHANGE);

		$this->createDummyFolders();

		$mediaTable = $this->fetchTable('Media');
		$media = [
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file1.jpg',
				'path' => 'media/parent/file1.jpg',
				'mimeType' => 'image/jpeg',
			]),
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file2.png',
				'path' => 'media/parent/file2.png',
				'mimeType' => 'image/png',
			]),
		];
		$result = $mediaTable->saveMany($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		// Mock the UrlHistory table
		$urlHistoryTableMock = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods([
			'insertQuery',
		])->getMock();
		$urlHistoryTableMock->expects($this->never())->method('insertQuery');

		/** @var \Awyiss\ORM\Locator\TableLocator $tableLocator */
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('UrlHistory');
		$tableLocator->set('UrlHistory', $urlHistoryTableMock);

		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->path = 'media/new-folder';
		$entity->path = 'media/parent';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$this->deleteDummyFolders();
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveRenamesDirectoryWhenPathChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);

		$oldDir = WWW_ROOT . 'media' . DS . 'parent';
		$newDir = WWW_ROOT . 'media' . DS . 'new-parent';

		$this->assertDirectoryDoesNotExist($oldDir);
		$this->assertDirectoryDoesNotExist($newDir);

		if (!is_dir($oldDir)) {
			mkdir($oldDir, 0755, true);
		}
		$this->tmpDirs[] = $oldDir;
		$this->tmpDirs[] = $newDir;

		$this->assertDirectoryExists($oldDir);

		$entity->path = 'media/new-parent';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$this->assertDirectoryDoesNotExist($oldDir);
		$this->assertDirectoryExists($newDir);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveRebuildsPathInMediaFoldersTableWhenPathChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);
		$entity->path = 'media/new-parent';

		$folders = $mediaFoldersTable->find()->where(['path LIKE' => 'media/parent/%'])->all();
		$this->assertCount(6, $folders);

		$folders = $mediaFoldersTable->find()->where(['path LIKE' => 'media/new-parent/%'])->all();
		$this->assertCount(0, $folders);

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$folders = $mediaFoldersTable->find()->where(['path LIKE' => 'media/parent/%'])->all();
		$this->assertCount(0, $folders);

		$folders = $mediaFoldersTable->find()->where(['path LIKE' => 'media/new-parent/%'])->all();
		$this->assertCount(6, $folders);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveRebuildsPathInMediaTableWhenPathChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$mediaTable = $this->fetchTable('Media');

		$this->createDummyFolders();

		$media = [
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file1.jpg',
				'path' => 'media/parent/file1.jpg',
				'mimeType' => 'image/jpeg',
			]),
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file2.png',
				'path' => 'media/parent/file2.png',
				'mimeType' => 'image/png',
			]),
		];
		$result = $mediaTable->saveMany($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);
		$entity->path = 'media/new-parent';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$updatedMedia = $mediaTable->find()->where(['mediaFolderId' => 890])->all();
		$this->assertCount(2, $updatedMedia);
		foreach ($updatedMedia as $file) {
			$this->assertStringStartsWith('media/new-parent/', $file->path);
		}

		$this->deleteDummyFolders();
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotRebuildsPathInMediaTableWhenPathUnchanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$mediaTable = $this->fetchTable('Media');

		$this->createDummyFolders();

		$media = [
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file1.jpg',
				'path' => 'media/parent/file1.jpg',
				'mimeType' => 'image/jpeg',
			]),
			$mediaTable->newDefaultEntity([
				'mediaFolderId' => 890,
				'name' => 'file2.png',
				'path' => 'media/parent/file2.png',
				'mimeType' => 'image/png',
			]),
		];
		$result = $mediaTable->saveMany($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->path = 'media/new-parent';
		$entity->path = 'media/parent';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$updatedMedia = $mediaTable->find()->where(['mediaFolderId' => 890])->all();
		$this->assertCount(2, $updatedMedia);
		foreach ($updatedMedia as $file) {
			$this->assertStringStartsWith('media/parent/', $file->path);
		}

		$this->deleteDummyFolders();
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveRebuildsPathInMediaResizedImagesTableWhenPathChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$mediaTable = $this->fetchTable('Media');
		$mediaResizedImagesTable = $this->fetchTable('MediaResizedImages');

		$this->createDummyFolders();

		$media = $mediaTable->newDefaultEntity([
			'mediaFolderId' => 890,
			'name' => 'file1.jpg',
			'path' => 'media/parent/file1.jpg',
			'mimeType' => 'image/jpeg',
		]);
		$result = $mediaTable->save($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$resizedImages = [
			$mediaResizedImagesTable->newDefaultEntity([
				'mediaId' => $media->id,
				'name' => 'file1_200x200.jpg',
				'path' => 'media/parent/file1_200x200.jpg',
				'mimeType' => 'image/jpeg',
				'width' => 200,
				'height' => 200,
			]),
			$mediaResizedImagesTable->newDefaultEntity([
				'mediaId' => $media->id,
				'name' => 'file1_400x400.jpg',
				'path' => 'media/parent/file1_400x400.jpg',
				'mimeType' => 'image/jpeg',
				'width' => 400,
				'height' => 400,
			]),
		];
		$result = $mediaResizedImagesTable->saveMany($resizedImages, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);
		$entity->path = 'media/new-parent';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$updatedResizedImages = $mediaResizedImagesTable->find()->where(['mediaId' => $media->id])->all();
		$this->assertCount(2, $updatedResizedImages);
		foreach ($updatedResizedImages as $file) {
			$this->assertStringStartsWith('media/new-parent/', $file->path);
		}

		$this->deleteDummyFolders();
		$mediaResizedImagesTable->deleteAll(['mediaId' => $media->id]);
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotRebuildsPathInMediaResizedImagesTableWhenPathUnchanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$mediaTable = $this->fetchTable('Media');
		$mediaResizedImagesTable = $this->fetchTable('MediaResizedImages');

		$this->createDummyFolders();

		$media = $mediaTable->newDefaultEntity([
			'mediaFolderId' => 890,
			'name' => 'file1.jpg',
			'path' => 'media/parent/file1.jpg',
			'mimeType' => 'image/jpeg',
		]);
		$result = $mediaTable->save($media, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$resizedImages = [
			$mediaResizedImagesTable->newDefaultEntity([
				'mediaId' => $media->id,
				'name' => 'file1_200x200.jpg',
				'path' => 'media/parent/file1_200x200.jpg',
				'mimeType' => 'image/jpeg',
				'width' => 200,
				'height' => 200,
			]),
			$mediaResizedImagesTable->newDefaultEntity([
				'mediaId' => $media->id,
				'name' => 'file1_400x400.jpg',
				'path' => 'media/parent/file1_400x400.jpg',
				'mimeType' => 'image/jpeg',
				'width' => 400,
				'height' => 400,
			]),
		];
		$result = $mediaResizedImagesTable->saveMany($resizedImages, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->path = 'media/new-parent';
		$entity->path = 'media/parent';

		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);
		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $entity, new ArrayObject());

		$updatedResizedImages = $mediaResizedImagesTable->find()->where(['mediaId' => $media->id])->all();
		$this->assertCount(2, $updatedResizedImages);
		foreach ($updatedResizedImages as $file) {
			$this->assertStringStartsWith('media/parent/', $file->path);
		}

		$this->deleteDummyFolders();
		$mediaResizedImagesTable->deleteAll(['mediaId' => $media->id]);
		$mediaTable->deleteAll(['mediaFolderId' => 890]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveUpdatesDescendantParentsActiveWhenActiveChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);

		$entity->active = false;

		$options = new ArrayObject();
		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);

		$this->listener->afterSave($event, $entity, $options);

		$entitys = $mediaFoldersTable->find('all')->where(['languageShortcode' => 'xy'])->orderByAsc('MediaFolders.id')->all();
		$actives = $entitys->extract(function (MediaFolder $entity): array {
			return [$entity->active, $entity->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
		], $actives);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotUpdatesParentsParentsActiveWhenActiveChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(891);

		$entity->active = false;

		$options = new ArrayObject();
		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);

		$this->listener->afterSave($event, $entity, $options);

		$entitys = $mediaFoldersTable->find('all')->where(['languageShortcode' => 'xy'])->orderByAsc('MediaFolders.id')->all();
		$actives = $entitys->extract(function (MediaFolder $entity): array {
			return [$entity->active, $entity->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, false],
			[true, false],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotUpdatesParentsParentsActiveWhenActiveUnchanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(891);

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->active = false;
		$entity->active = true;

		$options = new ArrayObject();
		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);

		$this->listener->afterSave($event, $entity, $options);

		$entitys = $mediaFoldersTable->find('all')->where(['languageShortcode' => 'xy'])->orderByAsc('MediaFolders.id')->all();
		$actives = $entitys->extract(function (MediaFolder $entity): array {
			return [$entity->active, $entity->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotUpdatesDescendantParentsActiveForDescendantsWithInactiveParentsWheActiveChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $root */
		$root = $mediaFoldersTable->get(890);
		$root->active = false;
		$mediaFoldersTable->save($root, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);

		/** @var \Awyiss\Model\Entity\MediaFolder $child1 */
		$child1 = $mediaFoldersTable->get(891);

		$this->assertFalse($child1->parentsActive);

		$child1->active = false;
		$mediaFoldersTable->save($child1, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);

		/** @var \Awyiss\Model\Entity\MediaFolder $grandchild */
		$grandchild = $mediaFoldersTable->get(892);
		$this->assertFalse($grandchild->parentsActive);

		$root->active = true;

		$options = new ArrayObject();
		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $root);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $root, $options);

		/** @var \Awyiss\Model\Entity\MediaFolder $child1 */
		$child1 = $mediaFoldersTable->get(891);
		$this->assertTrue($child1->parentsActive);
		$this->assertFalse($child1->active);

		/** @var \Awyiss\Model\Entity\MediaFolder $grandchild */
		$grandchild = $mediaFoldersTable->get(892);
		$this->assertFalse($grandchild->parentsActive);
		$this->assertTrue($grandchild->active);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveUpdatesDescendantParentsActiveWhenParentsActiveChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(890);

		$entity->parentsActive = false;

		$options = new ArrayObject();
		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);

		$this->listener->afterSave($event, $entity, $options);

		$entitys = $mediaFoldersTable->find('all')->where(['languageShortcode' => 'xy'])->orderByAsc('MediaFolders.id')->all();
		$actives = $entitys->extract(function (MediaFolder $entity): array {
			return [$entity->active, $entity->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
			[true, false],
		], $actives);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotUpdatesParentsParentsActiveWhenParentsActiveChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(891);

		$entity->parentsActive = false;

		$options = new ArrayObject();
		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);

		$this->listener->afterSave($event, $entity, $options);

		$entitys = $mediaFoldersTable->find('all')->where(['languageShortcode' => 'xy'])->orderByAsc('MediaFolders.id')->all();
		$actives = $entitys->extract(function (MediaFolder $entity): array {
			return [$entity->active, $entity->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, false],
			[true, false],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotUpdatesParentsParentsActiveWhenParentsActiveUnchanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $mediaFoldersTable->get(891);

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->parentsActive = false;
		$entity->parentsActive = true;

		$options = new ArrayObject();
		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $entity);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);

		$this->listener->afterSave($event, $entity, $options);

		$entitys = $mediaFoldersTable->find('all')->where(['languageShortcode' => 'xy'])->orderByAsc('MediaFolders.id')->all();
		$actives = $entitys->extract(function (MediaFolder $entity): array {
			return [$entity->active, $entity->parentsActive];
		})->toList();

		$this->assertSame([
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
			[true, true],
		], $actives);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotUpdatesDescendantParentsActiveForDescendantsWithInactiveParentsWhenParentsActiveChanged(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$this->createDummyFolders();

		/** @var \Awyiss\Model\Entity\MediaFolder $root */
		$root = $mediaFoldersTable->get(890);
		$root->parentsActive = false;
		$mediaFoldersTable->save($root, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);

		/** @var \Awyiss\Model\Entity\MediaFolder $child1 */
		$child1 = $mediaFoldersTable->get(891);

		$this->assertFalse($child1->parentsActive);

		$child1->active = false;
		$mediaFoldersTable->save($child1, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);

		/** @var \Awyiss\Model\Entity\MediaFolder $grandchild */
		$grandchild = $mediaFoldersTable->get(892);
		$this->assertFalse($grandchild->parentsActive);

		$root->parentsActive = true;

		$options = new ArrayObject();
		$event = new Event('Model.MediaFolders.beforeSave', $mediaFoldersTable);

		$this->listener->beforeSave($event, $root);

		$event = new Event('Model.MediaFolders.afterSave', $mediaFoldersTable);
		$this->listener->afterSave($event, $root, $options);

		/** @var \Awyiss\Model\Entity\MediaFolder $child1 */
		$child1 = $mediaFoldersTable->get(891);
		$this->assertTrue($child1->parentsActive);
		$this->assertFalse($child1->active);

		/** @var \Awyiss\Model\Entity\MediaFolder $grandchild */
		$grandchild = $mediaFoldersTable->get(892);
		$this->assertFalse($grandchild->parentsActive);
		$this->assertTrue($grandchild->active);

		$this->deleteDummyFolders();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitCreatesDirectory(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$entity = $mediaFoldersTable->newDefaultEntity([
			'path' => 'media/test-folder',
		]);

		$dir = WWW_ROOT . 'media' . DS . 'test-folder';
		if (is_dir($dir)) {
			rmdir($dir);
		}

		$event = new Event('Model.MediaFolders.afterSaveCommit', $mediaFoldersTable);

		$this->listener->afterSaveCommit($event, $entity, new ArrayObject());

		$this->assertDirectoryExists($dir);

		rmdir($dir);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitNotCreatesDirectoryWhenIsCopy(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$entity = $mediaFoldersTable->newDefaultEntity([
			'path' => 'media/test-folder-copy',
		]);

		$dir = WWW_ROOT . 'media' . DS . 'test-folder-copy';
		if (is_dir($dir)) {
			rmdir($dir);
		}

		$event = new Event('Model.MediaFolders.afterSaveCommit', $mediaFoldersTable);

		$this->listener->afterSaveCommit($event, $entity, new ArrayObject(['isCopy' => true]));

		$this->assertDirectoryDoesNotExist($dir);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSoftDelete()
	 */
	public function testBeforeSoftDeleteRenamesPathWhenPrimary(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$entity = $mediaFoldersTable->newDefaultEntity([
			'path' => 'media/folder-to-delete',
		]);
		$entity->deleted = true;

		$event = new Event('Model.MediaFolders.beforeSoftDelete', $mediaFoldersTable);

		$this->listener->beforeSoftDelete($event, $entity, new ArrayObject(['_primary' => true]));

		$this->assertSame('media/_deleted_folder-to-delete_' . time(), $entity->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::beforeSoftDelete()
	 */
	public function testBeforeSoftDeleteNotRenamesPathWhenNotPrimary(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$entity = $mediaFoldersTable->newDefaultEntity([
			'path' => 'media/folder-to-delete',
		]);

		$originalPath = $entity->path;

		$event = new Event('Model.MediaFolders.beforeSoftDelete', $mediaFoldersTable);

		$this->listener->beforeSoftDelete($event, $entity, new ArrayObject(['_primary' => false]));

		$this->assertSame($originalPath, $entity->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterDeleteCommit()
	 */
	public function _testAfterDeleteCommitClearsCache(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$entity = $mediaFoldersTable->newDefaultEntity();

		$event = new Event('Model.MediaFolders.afterDeleteCommit', $mediaFoldersTable);

		$this->expectNotToPerformAssertions();

		$this->listener->afterDeleteCommit($event, $entity, new ArrayObject());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaFoldersListener::afterSoftDeleteCommit()
	 */
	public function _testAfterSoftDeleteCommitClearsCache(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$entity = $mediaFoldersTable->newDefaultEntity();

		$event = new Event('Model.MediaFolders.afterSoftDeleteCommit', $mediaFoldersTable);

		$this->expectNotToPerformAssertions();

		$this->listener->afterSoftDeleteCommit($event, $entity, new ArrayObject());
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function createDummyFolders(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$mediaFoldersTable->deleteAll(['languageShortcode' => 'xy']);
		$mediaFoldersTable->deleteAll(['languageShortcode' => 'yx']);

		$insertQuery = $mediaFoldersTable->insertQuery();

		$insertQuery->insert([
			'id',
			'title',
			'path',
			'active',
			'languageShortcode',
			'parentId',
			'systemOrder',
		]);

		$insertQuery->values([
			'id' => 890,
			'title' => 'Root',
			'path' => 'media/parent',
			'active' => true,
			'languageShortcode' => 'xy',
			'parentId' => null,
			'systemOrder' => 1,
		]);

		$insertQuery->values([
			'id' => 891,
			'title' => 'Child 1',
			'path' => 'media/parent/child1',
			'active' => true,
			'languageShortcode' => 'xy',
			'parentId' => 890,
			'systemOrder' => 1,
		]);

		$insertQuery->values([
			'id' => 892,
			'title' => 'Grandchild 1',
			'path' => 'media/parent/child1/grandchild1',
			'active' => true,
			'languageShortcode' => 'xy',
			'parentId' => 891,
			'systemOrder' => 1,
		]);

		$insertQuery->values([
			'id' => 893,
			'title' => 'Grandchild 2',
			'path' => 'media/parent/child1/grandchild2',
			'active' => true,
			'languageShortcode' => 'xy',
			'parentId' => 891,
			'systemOrder' => 2,
		]);

		$insertQuery->values([
			'id' => 894,
			'title' => 'Child 2',
			'path' => 'media/parent/child2',
			'active' => true,
			'languageShortcode' => 'xy',
			'parentId' => 890,
			'systemOrder' => 2,
		]);

		$insertQuery->values([
			'id' => 895,
			'title' => 'Grandchild 3',
			'path' => 'media/parent/child2/grandchild3',
			'active' => true,
			'languageShortcode' => 'xy',
			'parentId' => 894,
			'systemOrder' => 1,
		]);

		$insertQuery->values([
			'id' => 896,
			'title' => 'Child 3',
			'path' => 'media/parent/child3',
			'active' => true,
			'languageShortcode' => 'xy',
			'parentId' => 890,
			'systemOrder' => 3,
		]);

		$insertQuery->values([
			'id' => 897,
			'title' => 'Root in Different Language',
			'path' => 'media/parent-lang2',
			'active' => true,
			'languageShortcode' => 'yx',
			'parentId' => null,
			'systemOrder' => 1,
		]);

		$insertQuery->values([
			'id' => 898,
			'title' => 'Child in Different Language',
			'path' => 'media/parent-lang2/child',
			'active' => true,
			'languageShortcode' => 'yx',
			'parentId' => 897,
			'systemOrder' => 1,
		]);

		$insertQuery->values([
			'id' => 899,
			'title' => 'Grandchild in Different Language',
			'path' => 'media/parent-lang2/child/grandchild',
			'active' => true,
			'languageShortcode' => 'yx',
			'parentId' => 898,
			'systemOrder' => 1,
		]);

		$this->assertNotFalse($insertQuery->execute());

		$this->assertCount(7, $mediaFoldersTable->find()->where(['languageShortcode' => 'xy'])->all());
		$this->assertCount(3, $mediaFoldersTable->find()->where(['languageShortcode' => 'yx'])->all());

		MediaListener::clearMediaFoldersCache();
	}


	/**
	 * @return void
	 */
	protected function deleteDummyFolders(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');

		$mediaFoldersTable->deleteAll(['languageShortcode' => 'xy']);
		$mediaFoldersTable->deleteAll(['languageShortcode' => 'yx']);

		$this->assertCount(0, $mediaFoldersTable->find('all')->where(['languageShortcode' => 'xy'])->all());
		$this->assertCount(0, $mediaFoldersTable->find('all')->where(['languageShortcode' => 'yx'])->all());

		MediaListener::clearMediaFoldersCache();
	}
}
