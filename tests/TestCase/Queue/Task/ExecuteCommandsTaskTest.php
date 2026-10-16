<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Queue\Task;


use Awyiss\Queue\Task\ExecuteCommandsTask;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Core\Configure;
use PHPUnit\Framework\Attributes\DataProvider;
use Queue\Console\Io;
use Queue\Model\QueueException;


/**
 * Test case for ExecuteCommandsTask
 *
 * @see \Awyiss\Queue\Task\ExecuteCommandsTask
 */
class ExecuteCommandsTaskTest extends TestCase {
	/**
	 * @var \Awyiss\Queue\Task\ExecuteCommandsTask
	 */
	protected ExecuteCommandsTask $task;
	/**
	 * @var \Cake\Console\ConsoleOutput
	 */
	protected ConsoleOutput $out;
	/**
	 * @var \Cake\Console\ConsoleOutput
	 */
	protected ConsoleOutput $err;
	/**
	 * @var resource
	 */
	protected $outStream;
	/**
	 * @var resource
	 */
	protected $errStream;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->outStream = tmpfile();
		$this->errStream = tmpfile();
		$this->out = new ConsoleOutput($this->outStream);
		$this->err = new ConsoleOutput($this->errStream);
		$io = new Io(new ConsoleIo($this->out, $this->err));

		Configure::write('debug', true);
		Configure::delete('Queue.executeAllowedCommands');
		$this->task = new ExecuteCommandsTask($io);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		Configure::write('debug', true);
		Configure::delete('Queue.executeAllowedCommands');
		fclose($this->outStream);
		fclose($this->errStream);

		parent::tearDown();
	}


	/**
	 * Tests that all command definitions are executed in their supplied order.
	 *
	 * @return void
	 */
	public function testRunExecutesCommandsInOrder(): void {
		$this->task->run([
			'commands' => [
				['command' => PHP_BINARY, 'params' => ['-r', 'echo $argv[1];', 'first']],
				['command' => PHP_BINARY, 'params' => ['-r', 'echo $argv[1];', 'second']],
			],
		], 0);

		rewind($this->outStream);
		$output = stream_get_contents($this->outStream);
		$this->assertStringContainsString('first', $output);
		$this->assertStringContainsString('second', $output);
		$this->assertLessThan(strpos($output, 'second'), strpos($output, 'first'));
	}


	/**
	 * Tests that shell-sensitive parameters remain one argument when escaped.
	 *
	 * @return void
	 */
	public function testRunEscapesParametersAsSingleArguments(): void {
		$parameter = '; echo INJECTION_MARKER';

		$this->task->run([
			'commands' => [
				['command' => PHP_BINARY, 'params' => ['-r', 'echo $argv[1];', $parameter]],
			],
		], 0);

		rewind($this->outStream);
		$output = stream_get_contents($this->outStream);
		$this->assertStringContainsString($parameter, $output);
		$this->assertSame(2, substr_count($output, 'INJECTION_MARKER'));
	}


	/**
	 * Tests that an unaccepted exit code stops execution and raises a queue exception.
	 *
	 * @return void
	 */
	public function testRunThrowsForUnacceptedExitCode(): void {
		$this->expectException(QueueException::class);
		$this->expectExceptionMessageIsOrContains('Failed with error code 7');

		$this->task->run([
			'commands' => [
				['command' => PHP_BINARY, 'params' => ['-r', 'exit(7);']],
			],
		], 0);
	}


	/**
	 * Tests that stderr is not appended when redirecting is disabled.
	 *
	 * @return void
	 */
	public function testRunCanDisableErrorRedirect(): void {
		$exception = null;
		try {
			$this->task->run([
				'commands' => [
					['command' => PHP_BINARY, 'params' => ['-r', 'exit(7);']],
				],
				'redirect' => false,
			], 0);
		}
		catch (QueueException $exceptionThrown) {
			$exception = $exceptionThrown;
		}

		$this->assertInstanceOf(QueueException::class, $exception);
		$this->assertStringNotContainsString('2>&1', $exception->getMessage());
	}


	/**
	 * Tests that explicitly accepted non-zero exit codes allow execution to finish.
	 *
	 * @return void
	 */
	public function testRunAcceptsConfiguredExitCode(): void {
		$this->task->run([
			'commands' => [
				['command' => PHP_BINARY, 'params' => ['-r', 'exit(7);']],
			],
			'accepted' => [7],
		], 0);

		rewind($this->outStream);
		$this->assertStringContainsString('Success (code 7)', stream_get_contents($this->outStream));
	}


	/**
	 * Tests that production mode rejects commands outside the configured allow-list.
	 *
	 * @return void
	 */
	public function testRunRejectsCommandOutsideAllowList(): void {
		Configure::write('debug', false);
		Configure::write('Queue.executeAllowedCommands', [PHP_BINARY]);

		$this->expectException(QueueException::class);
		$this->expectExceptionMessageIsOrContains('Command `not-allowed` is not in Queue.executeAllowedCommands allow-list');

		$this->task->run([
			'commands' => [
				['command' => 'not-allowed', 'params' => []],
			],
		], 0);
	}


	/**
	 * Tests that production mode allows a command listed by its exact raw command name.
	 *
	 * @return void
	 */
	public function testRunAllowsCommandInAllowList(): void {
		Configure::write('debug', false);
		Configure::write('Queue.executeAllowedCommands', [PHP_BINARY]);

		$this->task->run([
			'commands' => [
				['command' => PHP_BINARY, 'params' => ['-r', 'echo "allowed";']],
			],
		], 0);

		rewind($this->outStream);
		$this->assertStringContainsString('allowed', stream_get_contents($this->outStream));
	}


	/**
	 * Tests that malformed command payloads are rejected before execution.
	 *
	 * @param array<string, mixed> $data
	 * @return void
	 */
	#[DataProvider('invalidCommandDataProvider')]
	public function testRunRejectsMalformedCommandPayload(array $data): void {
		$this->expectException(QueueException::class);
		$this->task->run($data, 0);
	}


	/**
	 * Provides malformed command payloads for validation tests.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function invalidCommandDataProvider(): array {
		return [
			'commands is null' => [['commands' => null]],
			'commands is not a list' => [['commands' => 'invalid']],
			'command is missing' => [['commands' => [['params' => []]]]],
			'params are associative' => [['commands' => [['command' => PHP_BINARY, 'params' => ['name' => 'value']]]]],
			'parameter is not scalar' => [['commands' => [['command' => PHP_BINARY, 'params' => [['nested']]]]]],
		];
	}
}

