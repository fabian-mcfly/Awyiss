<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command;


use Awyiss\Awyiss;
use Awyiss\Command\IntegrityCheckCommand;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\Arguments;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;


/**
 * Class IntegrityCheckCommandTest
 */
class IntegrityCheckCommandTest extends TestCase {
	use ConsoleIntegrationTestTrait;


	/**
	 * @var string|false
	 */
	protected string|false $fileHashesConfig;


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		$this->fileHashesConfig = file_get_contents(CONFIG . 'file_hashes.php');
	}


	/**
	 * @inheritDoc
	 */
	public function tearDown(): void {
		parent::tearDown();

		if (!empty($this->fileHashesConfig)) {
			file_put_contents(CONFIG . 'file_hashes.php', $this->fileHashesConfig);
		}
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testExecuteAddCommandWithPath(): void {
		$args = $this->createMock(Arguments::class);
		$args->method('getArgument')->willReturnMap([
			['command', 'add'],
			['path', 'some/path'],
		]);

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->never())->method('error');

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->onlyMethods(['addFile'])->getMock();
		$command->expects($this->once())->method('addFile')->with($io, 'some/path');

		$result = $command->execute($args, $io);
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testExecuteRemoveCommandWithPath(): void {
		$args = $this->createMock(Arguments::class);
		$args->method('getArgument')->willReturnMap([
			['command', 'remove'],
			['path', 'some/path'],
		]);

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->never())->method('error');

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->onlyMethods(['removeFile'])->getMock();
		$command->expects($this->once())->method('removeFile')->with($io, 'some/path');

		$result = $command->execute($args, $io);
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testExecuteCheckCommandWithPath(): void {
		$args = $this->createMock(Arguments::class);
		$args->method('getArgument')->willReturnMap([
			['command', 'check'],
			['path', 'some/path'],
		]);
		$args->method('getOption')->willReturnMap([
			['reportOnlyModified', false],
			['interactive', false],
		]);

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->never())->method('error');

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->onlyMethods(['checkFiles'])->getMock();
		$command->expects($this->once())->method('checkFiles')->with($io, 'some/path', false, false);

		$result = $command->execute($args, $io);
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testExecuteInvalidCommand(): void {
		$args = $this->createMock(Arguments::class);
		$args->method('getArgument')->willReturnMap([
			['command', 'invalid'],
			['path', null],
		]);

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('error')->with('Invalid command. Use "add", "remove", or "check".');

		$command = new IntegrityCheckCommand();
		$result = $command->execute($args, $io);
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testExecuteAddCommandWithoutPath(): void {
		$args = $this->createMock(Arguments::class);
		$args->method('getArgument')->willReturnMap([
			['command', 'add'],
			['path', null],
		]);

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('error')->with('The "path" argument is required for "add" and "remove" commands.');

		$command = new IntegrityCheckCommand();
		$result = $command->execute($args, $io);
		$this->assertEquals(CommandInterface::CODE_ERROR, $result);
	}


	/**
	 * @return void
	 */
	public function testIntegrityCheckCommandHelp(): void {
		$this->exec('integrity_check --help');

		$this->assertExitSuccess();

		$this->assertOutputContains('--reportOnlyModified');

		$this->assertOutputContains('--interactive');

		$this->assertOutputContains('The command to execute: add, remove, check');

		$this->assertOutputContains('The file path for the add/check command or the identifier for');
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testAddFileWithValidFilePathAddsFileSuccessfully() {
		$path = 'tests/customer/Model/Enum/PageRole.php';
		$fullPath = ROOT . DS . $path;
		$hash = md5_file($fullPath);

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('success')->with(sprintf('Added: %s with hash `%s`', $path, $hash));

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->getMock();

		Configure::write('FileHashes', []);

		$this->callProtectedMethod($command, 'addFile', $io, $path);

		$config = Configure::read('FileHashes');

		$this->assertArrayHasKey($path, $config);
		$this->assertEquals($hash, $config[ $path ]);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testAddFileWithNonExistentFilePathShowsError() {
		$path = 'invalid/path/to/file.php';

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('error')->with(sprintf('File does not exist: %s', $path));

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->getMock();

		$this->callProtectedMethod($command, 'addFile', $io, $path);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testAddFileWithValidClassMethodAddsMethodSuccessfully() {
		$path = '\Awyiss\Test\TestCase\Command\Bake\EnumCommandTest::testEnumCommandHelp';
		$hash = 'b68617ec6a04f9cea319a8b1c1c0d110';

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('success')->with(sprintf('Added: %s with hash `%s`', $path, $hash));

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->getMock();

		Configure::write('FileHashes', []);

		$this->callProtectedMethod($command, 'addFile', $io, $path);

		$config = Configure::read('FileHashes');

		$this->assertArrayHasKey($path, $config);
		$this->assertEquals($hash, $config[ $path ]);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testAddFileWithInvalidClassMethodShowsError() {
		$path = '\Awyiss\Test\TestCase\Command\Bake\EnumCommandTest::testEnumCommandHelpNotExisting';
		$exceptionMessage = 'Method `testEnumCommandHelpNotExisting` not found in `\Awyiss\Test\TestCase\Command\Bake\EnumCommandTest`';

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('error')->with(sprintf('Error processing method `%s`', $exceptionMessage));

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->getMock();

		$this->callProtectedMethod($command, 'addFile', $io, $path);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testRemoveFileWithValidFilePathRemovesFileSuccessfully() {
		$path = 'tests/customer/Model/Enum/PageRole.php';

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('success')->with(sprintf('Removed `%s`', $path));

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->getMock();

		Configure::write('FileHashes', [$path => 'somehash', 'dummy' => 'somehash']);

		$this->callProtectedMethod($command, 'removeFile', $io, $path);

		$config = Configure::read('FileHashes');
		$this->assertArrayNotHasKey($path, $config);
		$this->assertArrayHasKey('dummy', $config);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testRemoveFileWithNonExistentFilePathShowsError() {
		$path = 'tests/customer/Model/Enum/PageRoleDummy.php';

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('error')->with(sprintf('File does not exist `%s`', $path));

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->getMock();

		$this->callProtectedMethod($command, 'removeFile', $io, $path);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testRemoveFileWithUnknownFileShowsError() {
		$path = 'tests/customer/Model/Enum/PageRole.php';

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('error')->with(sprintf('Identifier not found `%s`', $path));

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->getMock();

		$this->callProtectedMethod($command, 'removeFile', $io, $path);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testRemoveFileWithValidClassMethodRemovesMethodSuccessfully() {
		$path = '\Awyiss\Test\TestCase\Command\Bake\EnumCommandTest::testEnumCommandHelpNotExisting';

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('success')->with(sprintf('Removed `%s`', $path));

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->getMock();

		Configure::write('FileHashes', [$path => 'somehash', 'dummy' => 'somehash']);

		$this->callProtectedMethod($command, 'removeFile', $io, $path);

		$config = Configure::read('FileHashes');
		$this->assertArrayNotHasKey($path, $config);
		$this->assertArrayHasKey('dummy', $config);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 */
	public function testRemoveFileWithInvalidClassMethodShowsError() {
		$path = '\Awyiss\Test\TestCase\Command\Bake\EnumCommandTest::testEnumCommandHelpNotExisting';

		$io = $this->createMock(ConsoleIo::class);
		$io->expects($this->once())->method('error')->with(sprintf('Identifier not found `%s`', $path));

		$command = $this->getMockBuilder(IntegrityCheckCommand::class)->getMock();

		Configure::write('FileHashes', ['dummy' => 'somehash']);

		$this->callProtectedMethod($command, 'removeFile', $io, $path);
	}
}
