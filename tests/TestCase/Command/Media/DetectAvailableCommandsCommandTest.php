<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Media;


use Awyiss\Command\Media\DetectAvailableCommandsCommand;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use ReflectionClass;


/**
 * Class DetectAvailableCommandsCommandTest
 *
 * TODO: make the use of Process in DetectAvailableCommandsCommand
 * use a wrapper method that can be mocked in tests.
 */
class DetectAvailableCommandsCommandTest extends TestCase {
	/**
	 * @var object
	 */
	protected object $args;
	/**
	 * @var \Awyiss\Command\Media\DetectAvailableCommandsCommand
	 */
	protected DetectAvailableCommandsCommand $command;
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

		$this->command = new DetectAvailableCommandsCommand();
		$this->io = $this->createMock(ConsoleIo::class);
		$this->args = $this->createMock(Arguments::class);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildOptionParser() {
		$parser = $this->createMock(ConsoleOptionParser::class);

		$parser->expects($this->once())->method('addOption')->with('retry', [
			'boolean' => true,
			'help' => 'Retry the detection of available commands, even if the config setting exists.',
			'short' => 'r',
		]);

		$this->command->buildOptionParser($parser);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDetectCommands() {
		Configure::write('AvailableCommands');

		$expectedCalls = [
			['Testing ffmpg... ', 0],
			['Testing ImageMagick (`convert`)... ', 0],
			['Testing PDF support... ', 0],
		];

		$this->io->expects($this->atLeast(3))->method('out')
		->willReturnCallback(function ($message, $code) use (&$expectedCalls): void {
			if (!empty($expectedCalls)) {
				return;
			}

			$expectedCall = array_shift($expectedCalls);
			$this->assertEquals($expectedCall, [$message, $code]);
		});

		$result = $this->command->execute($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExecuteWithoutRetryOption() {
		$this->args->method('getOption')->with('retry')->willReturn(false);

		Configure::write('AvailableCommands', ['ffmpeg' => false]);

		$this->io->expects($this->once())->method('out')
		->with('Commands already detected.', 1);

		$result = $this->command->execute($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExecuteWithRetryOption() {
		$this->args->method('getOption')->with('retry')->willReturn(true);

		Configure::write('AvailableCommands', ['ffmpeg' => true]);

		$expectedCalls = [
			['Testing ffmpg... ', 0],
			['Testing ImageMagick (`convert`)... ', 0],
			['Testing PDF support... ', 0],
		];

		$this->io->expects($this->atLeast(3))->method('out')
		->willReturnCallback(function ($message, $code) use (&$expectedCalls): void {
			if (!empty($expectedCalls)) {
				return;
			}

			$expectedCall = array_shift($expectedCalls);
			$this->assertEquals($expectedCall, [$message, $code]);
		});

		$result = $this->command->execute($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTestUnknownProcess() {
		$this->io->expects($this->once())->method('out')
		->with('Testing UnknownAwyissCommand... ', 0);

		$this->io->expects($this->once())->method('error')->with('UnknownAwyissCommand not available', 1);

		$reflection = new ReflectionClass($this->command);
		$method = $reflection->getMethod('testProcess');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$method->setAccessible(true);

		$result = $method->invokeArgs($this->command, [['UnknownAwyissCommand', '-version'], 'UnknownAwyissCommand', $this->io]);

		$this->assertIsBool($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception|\ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTestKnownProcess() {
		$this->io->expects($this->once())->method('out')
		->with('Testing pwd... ', 0);

		$this->io->expects($this->once())->method('success')->with('pwd available', 1);

		$reflection = new ReflectionClass($this->command);
		$method = $reflection->getMethod('testProcess');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$method->setAccessible(true);

		$result = $method->invokeArgs($this->command, [['pwd'], 'pwd', $this->io]);

		$this->assertIsBool($result);
	}
}
