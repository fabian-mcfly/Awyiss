<?php declare(strict_types=1);


namespace Awyiss\Command;


use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Exception\StopException;
use Cake\Core\Configure;
use ReflectionClass;
use ReflectionException;


/**
 * Handle integrity checks of vendor files.
 * Used to store the hashed state of CakePHP files that are used in Awyiss.
 */
class IntegrityCheckCommand extends Command {
	/**
	 * @var bool
	 */
	protected bool $forCustomer = false;


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 * @throws \ReflectionException
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$command = $args->getArgument('command');
		$path = $args->getArgument('path');

		if (in_array($command, ['add', 'remove']) && empty($path)) {
			$io->error('The "path" argument is required for "add" and "remove" commands.');


			return static::CODE_ERROR;
		}

		$this->forCustomer = (bool)$args->getOption('customer');
		if ($this->forCustomer) {
			Configure::load('custom_file_hashes');
		}
		else {
			Configure::load('file_hashes');
		}

		switch ($command) {
			case 'add':
				$this->addFile($io, $path);
				break;
			case 'remove':
				$this->removeFile($io, $path);
				break;
			case 'check':
				$this->checkFiles($io, $path, $args->getOption('reportOnlyModified'), $args->getOption('interactive'));
				break;
			default:
				$io->error('Invalid command. Use "add", "remove", or "check".');
				break;
		}

		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser->addArgument('command', [
			'help' => 'The command to execute: add, remove, check',
			'choices' => ['add', 'remove', 'check'],
			'required' => true,
		]);

		$parser->addArgument('path', [
			'help' => 'The file path for the add/check command or the identifier for the remove command',
		]);

		$parser->addOption('reportOnlyModified', [
			'boolean' => true,
			'help' => 'Whether to report only modified files and methods',
			'short' => 'm',
		]);

		$parser->addOption('interactive', [
			'boolean' => true,
			'help' => 'Whether to ask if modified files/methods should have their hashes updated',
			'short' => 'i',
		]);

		$parser->addOption('customer', [
			'boolean' => true,
			'help' => 'Whether to run the command in Awyiss or Customer context',
			'default' => false,
		]);


		return $parser;
	}


	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $path
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function addFile(ConsoleIo $io, string $path): void {
		$fullPath = $this->sanitizePath($path);

		if (!$fullPath) {
			$io->error(sprintf('File does not exist: %s', $path));


			return;
		}

		if (str_contains($path, '::')) {
			[$className, $method] = explode('::', $path, 2);

			try {
				$hash = $this->getMethodHash($className, $method);
			}
			catch (ReflectionException $ex) {
				$io->error(sprintf('Error processing method `%s`', $ex->getMessage()));


				return;
			}

			$key = $className . '::' . $method;
		}
		else {
			$hash = md5_file($fullPath);
			$key = substr($fullPath, strlen(ROOT . DS));
		}

		$config = Configure::read('FileHashes', []);

		$config[ $key ] = $hash;

		ksort($config);

		Configure::write('FileHashes', $config);
		$fileName = $this->forCustomer ? CUSTOM_NAMESPACE . '.custom_file_hashes' : 'Awyiss.file_hashes';
		Configure::dump($fileName, 'default', ['FileHashes']);

		$io->success(sprintf('Added: %s with hash `%s`', $key, $hash));
	}


	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $path
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function removeFile(ConsoleIo $io, string $path): void {
		$fullPath = $this->sanitizePath($path);

		if (!$fullPath) {
			$io->error(sprintf('File does not exist `%s`', $path));


			return;
		}

		$config = Configure::read('FileHashes', []);

		if (str_contains($path, '::')) {
			[$className, $method] = explode('::', $path, 2);
			$key = $className . '::' . $method;
		}
		else {
			$key = substr($fullPath, strlen(ROOT . DS));
		}

		if (!isset($config[ $key ])) {
			$io->error(sprintf('Identifier not found `%s`', $path));


			return;
		}

		unset($config[ $key ]);

		ksort($config);

		Configure::write('FileHashes', $config);
		$fileName = $this->forCustomer ? CUSTOM_NAMESPACE . '.custom_file_hashes' : 'Awyiss.file_hashes';
		Configure::dump($fileName, 'default', ['FileHashes']);

		$io->success(sprintf('Removed `%s`', $path));
	}


	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string|null $path
	 * @param bool $reportOnlyModified
	 * @param bool $interactive
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function checkFiles(ConsoleIo $io, ?string $path = null, bool $reportOnlyModified = false, bool $interactive = false): void {
		$files = $config = Configure::read('FileHashes', []);

		if ($path) {
			$fullPath = $this->sanitizePath($path);

			if (!$fullPath) {
				$io->error(sprintf('File does not exist `%s`', $path));


				return;
			}

			if (str_contains($path, '::')) {
				[$className, $method] = explode('::', $path, 2);
				$key = $className . '::' . $method;
			}
			else {
				$key = substr($fullPath, strlen(ROOT . DS));
			}

			if (!isset($config[ $key ])) {
				$io->error(sprintf('Identifier not found `%s`', $path));


				return;
			}

			$files = [$key => $config[ $key ]];
		}

		$results = [
			'unchanged' => 0,
			'changed' => 0,
		];

		$forceUpdate = false;
		$updatedData = [];
		foreach ($files as $file => $storedHash) {
			if (str_contains($file, '::')) {
				[$className, $method] = explode('::', $file, 2);

				try {
					$currentHash = $this->getMethodHash($className, $method);
				}
				catch (ReflectionException $ex) {
					$io->error(sprintf('Error processing method `%s`', $ex->getMessage()));
					continue;
				}
			}
			else {
				$fullPath = ROOT . DS . $file;

				if (file_exists($fullPath)) {
					$currentHash = md5_file($fullPath);
				}
				else {
					$io->error(sprintf('File not found `%s`', $fullPath));
					continue;
				}
			}

			if (!$reportOnlyModified) {
				$io->out(sprintf('Checking %s `%s`... ', str_contains($file, '::') ? 'method' : 'file', $file), 0);

				if ($currentHash === $storedHash) {
					$results['unchanged']++;
					$io->success('unchanged');
				}
				else {
					$results['changed']++;
					$io->error('changed');
				}
			}
			else {
				if ($currentHash === $storedHash) {
					$results['unchanged']++;
				}
				else {
					$results['changed']++;
					$io->error(sprintf('%s `%s` was modified', str_contains($file, '::') ? 'Method' : 'File', $file));
				}
			}

			if ($currentHash !== $storedHash) {
				$this->askForUpdate(
					$io,
					$file,
					$currentHash,
					$updatedData,
					$interactive,
					$forceUpdate
				);
			}
		}

		if ($updatedData) {
			$config = $updatedData + $config;

			ksort($config);

			Configure::write('FileHashes', $config);
			$fileName = $this->forCustomer ? CUSTOM_NAMESPACE . '.custom_file_hashes' : 'Awyiss.file_hashes';
			Configure::dump($fileName, 'default', ['FileHashes']);
		}

		if (!$reportOnlyModified || $results['changed']) {
			$io->hr();
		}

		$io->out(sprintf('Finished checking %d files. ', count($files)), 0);

		$io->success(sprintf('%d files unchanged.', $results['unchanged']), $results['changed'] ? 0 : 1);

		if ($results['changed']) {
			$io->out(' | ', 0);
			$io->error(sprintf('%d files changed. ', $results['changed']));
		}
	}


	/**
	 * @param string $className
	 * @param string $method
	 * @return string
	 * @throws \ReflectionException
	 */
	protected function getMethodHash(string $className, string $method): string {
		$reflection = new ReflectionClass($className);
		if (!$reflection->hasMethod($method)) {
			throw new ReflectionException(sprintf('Method `%s` not found in `%s`', $method, $className));
		}

		$fileCode = file($reflection->getFileName());

		$method = $reflection->getMethod($method);

		$methodContent = implode('', array_slice($fileCode, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));


		return md5($methodContent);
	}


	/**
	 * @param string $path
	 * @return string|false
	 * @throws \ReflectionException
	 */
	protected function sanitizePath(string $path): string|false {
		if (str_starts_with($path, '\\')) {
			$className = $path;
			if (str_contains($className, '::')) {
				[$className] = explode('::', $className, 2);
			}

			$reflection = new ReflectionClass($className);
			$fullPath = $reflection->getFileName();
		}
		else {
			$fullPath = realpath(ROOT . DS . $path);

			if (!$fullPath || !str_starts_with($fullPath, ROOT . DS) || !is_file($fullPath)) {
				return false;
			}
		}


		return $fullPath;
	}


	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $file
	 * @param mixed $currentHash
	 * @param array $updatedData
	 * @param bool $interactive
	 * @param bool $forceUpdate
	 * @return void
	 */
	protected function askForUpdate(ConsoleIo $io, string $file, mixed $currentHash, array &$updatedData, bool $interactive, bool &$forceUpdate): void {
		$update = $forceUpdate;
		if ($interactive && !$forceUpdate) {
			$key = $io->askChoice('Update hash?', ['y', 'n', 'a', 'q'], 'n');
			$key = strtolower($key);

			if ($key === 'q') {
				$io->error('Quitting.', 2);
				throw new StopException('Not creating file. Quitting.');
			}

			if ($key === 'a') {
				$forceUpdate = true;
				$key = 'y';
			}

			$update = $key === 'y';
		}

		if ($update) {
			$updatedData[ $file ] = $currentHash;
		}
	}
}
