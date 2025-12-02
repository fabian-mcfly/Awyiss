<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Media;


use Awyiss\Command\Media\ClearCacheCommand;
use Awyiss\Model\Table\MediaFoldersTable;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\Arguments;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Query\SelectQuery;
use Symfony\Component\Process\Process;


/**
 * Class ClearCacheCommandTest
 */
class ClearCacheCommandTest extends TestCase {
	/**
	 * @var object
	 */
	protected object $args;
	/**
	 * @var object
	 * @noinspection PhpPropertyNamingConventionInspection
	 */
	protected object $io;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->args = $this->createMock(Arguments::class);

		$this->io = $this->createMock(ConsoleIo::class);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		if (is_dir(TMP . 'media' . DS . 'testfolder1')) {
			new Process(['rm', '-r', TMP . 'media' . DS . 'testfolder1'])->run();
		}
		if (is_dir(TMP . 'media' . DS . 'testfolder2')) {
			new Process(['rm', '-r', TMP . 'media' . DS . 'testfolder2'])->run();
		}
		if (is_dir(TMP . 'media' . DS . 'testfolder3')) {
			new Process(['rm', '-r', TMP . 'media' . DS . 'testfolder3'])->run();
		}
	}


	/**
	 * @return void
	 */
	public function testBuildOptionParserIncludesSetOptions(): void {
		$parser = new ConsoleOptionParser('test');

		$command = new ClearCacheCommand();

		$command->buildOptionParser($parser);
		$options = $parser->options();

		$this->assertArrayHasKey('only-database', $options);
		$this->assertTrue($options['only-database']->isBoolean());

		$this->assertArrayHasKey('type', $options);
		$this->assertEquals([
			'all',
			'deleted',
			'effects',
			'previews',
			'resized',
			'avif',
			'webp',
		], $options['type']->choices());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testExecuteSuccess(): void {
		$command = $this->getMockBuilder(ClearCacheCommand::class)->onlyMethods([
			'fetchFolders',
			'deleteFolders',
			'removeDeletedFoldersFromDatabase',
			'resetDatabaseRecords',
			'deleteResizedDatabaseRecords',
		])->getMock();

		$this->args->method('getOption')->willReturnMap([
			['only-database', false],
			['type', 'all'],
		]);

		$this->io->expects($this->exactly(2))->method('out')->willReturnMap([
			['Fetching folders... ', 0],
			['3 folders found.', 0],
		]);

		// Mock methods in the command to avoid full processing
		$command->expects($this->once())->method('fetchFolders')->withAnyParameters()->willReturn([1, 2, 3]);

		$command->expects($this->once())->method('deleteFolders');

		$command->expects($this->once())->method('removeDeletedFoldersFromDatabase');

		$command->expects($this->once())->method('resetDatabaseRecords');

		$command->expects($this->once())->method('deleteResizedDatabaseRecords');

		$result = $command->execute($this->args, $this->io);

		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testDeleteFolders(): void {
		$command = $this->getMockBuilder(ClearCacheCommand::class)->onlyMethods([
			'deleteFolder',
		])->getMock();

		$command->expects($this->exactly(3))->method('deleteFolder')->willReturnMap([
			['path1', true],
			['path2', false],
			['path3', true],
		]);

		$this->io->expects($this->exactly(3))->method('out');
		$this->io->expects($this->exactly(2))->method('success');
		$this->io->expects($this->exactly(1))->method('error');

		$result = $this->callProtectedMethod($command, 'deleteFolders', ['path1', 'path2', 'path3'], $this->io);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testDeleteFoldersWithNoRecords(): void {
		$command = $this->getMockBuilder(ClearCacheCommand::class)->onlyMethods([
			'deleteFolder',
		])->getMock();

		$command->expects($this->never())->method('deleteFolder');

		$result = $this->callProtectedMethod($command, 'deleteFolders', [], $this->io);

		$this->assertNull($result);
	}

	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testRemoveDeletedFoldersFromDatabase(): void {
		$table = $this->createMock(MediaFoldersTable::class);

		$table->expects($this->once())->method('deleteAll')->with(['deleted' => true])->willReturn(5);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->onlyMethods([
			'fetchTable',
		])->getMock();

		$command->method('fetchTable')->willReturn($table);

		$this->io->expects($this->once())->method('success')->with('Deleted 5 rows');

		$result = $this->callProtectedMethod($command, 'removeDeletedFoldersFromDatabase', 'all', $this->io);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testRemoveDeletedFoldersFromDatabaseWithUnknownType(): void {
		$table = $this->createMock(MediaFoldersTable::class);

		$table->expects($this->never())->method('deleteAll');

		$command = $this->getMockBuilder(ClearCacheCommand::class)->getMock();

		$result = $this->callProtectedMethod($command, 'removeDeletedFoldersFromDatabase', 'unknown', $this->io);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testResetDatabaseRecords(): void {
		$table = $this->createMock(MediaFoldersTable::class);

		$table->expects($this->once())->method('updateAll')->willReturn(123);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->onlyMethods([
			'fetchTable',
		])->getMock();

		$command->method('fetchTable')->willReturn($table);

		$this->io->expects($this->once())->method('success')->with('Updated 123 media rows');

		$result = $this->callProtectedMethod($command, 'resetDatabaseRecords', 'all', $this->io);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testDeleteResizedDatabaseRecords(): void {
		$table = $this->createMock(MediaFoldersTable::class);

		$table->expects($this->once())->method('deleteAll')->with([])->willReturn(23);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->onlyMethods([
			'fetchTable',
		])->getMock();

		$command->method('fetchTable')->willReturn($table);

		$this->io->expects($this->once())->method('success')->with('Deleted 23 resized media rows');

		$result = $this->callProtectedMethod($command, 'deleteResizedDatabaseRecords', 'all', $this->io);

		$this->assertNull($result);
	}

	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testDeleteResizedDatabaseRecordsWithUnknownType(): void {
		$table = $this->createMock(MediaFoldersTable::class);

		$table->expects($this->never())->method('deleteAll');

		$command = $this->getMockBuilder(ClearCacheCommand::class)->getMock();

		$result = $this->callProtectedMethod($command, 'deleteResizedDatabaseRecords', 'unknown', $this->io);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testFetchFolders(): void {
		$this->_createTestFolders();

		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = $this->fetchTable('MediaFolders');
		/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
		$records = $table->find('withDeleted')->all();

		$table = $this->createMock(MediaFoldersTable::class);
		$query = $this->createMock(SelectQuery::class);

		$table->expects($this->once())->method('find')->willReturn($query);
		$query->expects($this->once())->method('find')->with('withDeleted')->willReturn($query);
		$query->expects($this->once())->method('all')->willReturn($records);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->getMock();

		$command->expects($this->once())->method('fetchTable')->willReturn($table);

		$result = $this->callProtectedMethod($command, 'fetchFolders', 'all');

		$this->assertSame([
			WWW_ROOT . '../tmp/media/testfolder1/_effects',
			WWW_ROOT . '../tmp/media/testfolder1/_pdf_preview',
			WWW_ROOT . '../tmp/media/testfolder1/_resized',
			WWW_ROOT . '../tmp/media/testfolder1/_avif',
			WWW_ROOT . '../tmp/media/testfolder1/_webp',
			WWW_ROOT . '../tmp/media/testfolder2',
			WWW_ROOT . '../tmp/media/testfolder3/_effects',
			WWW_ROOT . '../tmp/media/testfolder3/_pdf_preview',
			WWW_ROOT . '../tmp/media/testfolder3/_resized',
			WWW_ROOT . '../tmp/media/testfolder3/_avif',
			WWW_ROOT . '../tmp/media/testfolder3/_webp',
		], $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testFetchDeletedFolders(): void {
		$this->_createTestFolders();

		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = $this->fetchTable('MediaFolders');
		/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findDeleted() */
		$records = $table->find('deleted')->all();

		$table = $this->createMock(MediaFoldersTable::class);
		$query = $this->createMock(SelectQuery::class);

		$table->expects($this->once())->method('find')->willReturn($query);
		$query->expects($this->once())->method('find')->with('deleted')->willReturn($query);
		$query->expects($this->once())->method('all')->willReturn($records);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->getMock();

		$command->expects($this->once())->method('fetchTable')->willReturn($table);

		$result = $this->callProtectedMethod($command, 'fetchFolders', 'deleted');

		$this->assertSame([
			WWW_ROOT . '../tmp/media/testfolder2',
		], $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testFetchEffectsFolders(): void {
		$this->_createTestFolders();

		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = $this->fetchTable('MediaFolders');
		$records = $table->find()->all();

		$table = $this->createMock(MediaFoldersTable::class);
		$query = $this->createMock(SelectQuery::class);

		$table->expects($this->once())->method('find')->willReturn($query);
		$query->expects($this->once())->method('all')->willReturn($records);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->getMock();

		$command->expects($this->once())->method('fetchTable')->willReturn($table);

		$result = $this->callProtectedMethod($command, 'fetchFolders', 'effects');

		$this->assertSame([
			WWW_ROOT . '../tmp/media/testfolder1/_effects',
			WWW_ROOT . '../tmp/media/testfolder3/_effects',
		], $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testFetchPreviewsFolders(): void {
		$this->_createTestFolders();

		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = $this->fetchTable('MediaFolders');
		$records = $table->find()->all();

		$table = $this->createMock(MediaFoldersTable::class);
		$query = $this->createMock(SelectQuery::class);

		$table->expects($this->once())->method('find')->willReturn($query);
		$query->expects($this->once())->method('all')->willReturn($records);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->getMock();

		$command->expects($this->once())->method('fetchTable')->willReturn($table);

		$result = $this->callProtectedMethod($command, 'fetchFolders', 'previews');

		$this->assertSame([
			WWW_ROOT . '../tmp/media/testfolder1/_pdf_preview',
			WWW_ROOT . '../tmp/media/testfolder3/_pdf_preview',
		], $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testFetchResizedFolders(): void {
		$this->_createTestFolders();

		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = $this->fetchTable('MediaFolders');
		$records = $table->find()->all();

		$table = $this->createMock(MediaFoldersTable::class);
		$query = $this->createMock(SelectQuery::class);

		$table->expects($this->once())->method('find')->willReturn($query);
		$query->expects($this->once())->method('all')->willReturn($records);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->getMock();

		$command->expects($this->once())->method('fetchTable')->willReturn($table);

		$result = $this->callProtectedMethod($command, 'fetchFolders', 'resized');

		$this->assertSame([
			WWW_ROOT . '../tmp/media/testfolder1/_resized',
			WWW_ROOT . '../tmp/media/testfolder3/_resized',
		], $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testFetchAvifFolders(): void {
		$this->_createTestFolders();

		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = $this->fetchTable('MediaFolders');
		$records = $table->find()->all();

		$table = $this->createMock(MediaFoldersTable::class);
		$query = $this->createMock(SelectQuery::class);

		$table->expects($this->once())->method('find')->willReturn($query);
		$query->expects($this->once())->method('all')->willReturn($records);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->getMock();

		$command->expects($this->once())->method('fetchTable')->willReturn($table);

		$result = $this->callProtectedMethod($command, 'fetchFolders', 'avif');

		$this->assertSame([
			WWW_ROOT . '../tmp/media/testfolder1/_avif',
			WWW_ROOT . '../tmp/media/testfolder3/_avif',
		], $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException|\PHPUnit\Framework\MockObject\Exception
	 */
	public function testFetchWebpFolders(): void {
		$this->_createTestFolders();

		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = $this->fetchTable('MediaFolders');
		$records = $table->find()->all();

		$table = $this->createMock(MediaFoldersTable::class);
		$query = $this->createMock(SelectQuery::class);

		$table->expects($this->once())->method('find')->willReturn($query);
		$query->expects($this->once())->method('all')->willReturn($records);

		$command = $this->getMockBuilder(ClearCacheCommand::class)->getMock();

		$command->expects($this->once())->method('fetchTable')->willReturn($table);

		$result = $this->callProtectedMethod($command, 'fetchFolders', 'webp');

		$this->assertSame([
			WWW_ROOT . '../tmp/media/testfolder1/_webp',
			WWW_ROOT . '../tmp/media/testfolder3/_webp',
		], $result);
	}


	/**
	 * @return void
	 */
	protected function _createTestFolders(): void {
		mkdir(TMP . 'media' . DS . 'testfolder1' . DS . '_effects', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder1' . DS . '_pdf_preview', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder1' . DS . '_resized', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder1' . DS . '_avif', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder1' . DS . '_webp', 0777, true);

		mkdir(TMP . 'media' . DS . 'testfolder2' . DS . '_effects', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder2' . DS . '_pdf_preview', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder2' . DS . '_resized', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder2' . DS . '_avif', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder2' . DS . '_webp', 0777, true);

		mkdir(TMP . 'media' . DS . 'testfolder3' . DS . '_effects', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder3' . DS . '_pdf_preview', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder3' . DS . '_resized', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder3' . DS . '_avif', 0777, true);
		mkdir(TMP . 'media' . DS . 'testfolder3' . DS . '_webp', 0777, true);
	}
}
