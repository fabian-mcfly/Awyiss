<?php declare(strict_types=1);


namespace Awyiss\Queue\Task;


use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use Queue\Model\QueueException;
use Queue\Queue\Task;


/**
 * Execute multiple local commands on the server in sequence.
 *
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class ExecuteCommandsTask extends Task {
	use LogTrait;


	/**
	 * Run function.
	 * This function is executed when a worker is executing a task.
	 * The command sequence stops at the first command that returns an unaccepted exit code.
	 *
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 * @throws \Queue\Model\QueueException
	 */
	public function run(array $data, int $jobId): void {
		$data += [
			'commands' => [],
			'redirect' => true,
			'log' => false,
			'accepted' => [CommandInterface::CODE_SUCCESS],
		];

		if (!is_array($data['commands'])) {
			throw new QueueException('The commands value must be a list of command definitions');
		}

		foreach ($data['commands'] as $commandData) {
			if (!is_array($commandData)) {
				throw new QueueException('Each command must be a command definition');
			}

			$rawCommand = (string)($commandData['command'] ?? '');
			$params = $commandData['params'] ?? [];

			if ($rawCommand === '' || !is_array($params) || array_keys($params) !== array_keys(array_values($params))) {
				throw new QueueException('Each command must contain a command and a numeric parameter list');
			}

			foreach ($params as $param) {
				if (!is_scalar($param) && $param !== null) {
					throw new QueueException('Command parameters must be scalar values');
				}
			}

			if (!Configure::read('debug')) {
				$allowed = (array)Configure::read('Queue.executeAllowedCommands', []);
				if (!$allowed || !in_array($rawCommand, $allowed, true)) {
					throw new QueueException(
						'Command `' . $rawCommand . '` is not in Queue.executeAllowedCommands allow-list',
					);
				}
			}

			$command = escapeshellarg($rawCommand);
			foreach ($params as $param) {
				$command .= ' ' . escapeshellarg((string)$param);
			}

			$this->io->out('Executing: `' . $command . '`');

			$executedCommand = $command;

			if ($data['redirect']) {
				$executedCommand .= ' 2>&1';
			}

			$output = [];

			exec($executedCommand, $output, $exitCode);

			$this->io->nl();
			$this->io->out($output);

			if ($data['log']) {
				$queueProcesses = $this->getTableLocator()->get('Queue.QueueProcesses');
				$server = $queueProcesses->buildServerString();
				$this->log(
					$server . ': `'
					. $executedCommand . '` exits with `' . $exitCode
					. '` and returns `' . print_r($output, true) . '`' . PHP_EOL
					. 'Data : ' . print_r($data, true),
					'info'
				);
			}

			$acceptedReturnCodes = $data['accepted'];
			$success = !$acceptedReturnCodes || in_array($exitCode, $acceptedReturnCodes, true);

			if (!$success) {
				$this->io->error('Error (code ' . $exitCode . ')', ConsoleIo::VERBOSE);
				throw new QueueException('Failed with error code ' . $exitCode . ': `' . $executedCommand . '`');
			}

			$this->io->success('Success (code ' . $exitCode . ')', ConsoleIo::VERBOSE);
		}
	}
}
