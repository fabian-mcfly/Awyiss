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
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 * @throws \ReflectionException
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$ls_command = $args->getArgument('command');
		$ls_path = $args->getArgument('path');

		if (in_array($ls_command, ['add', 'remove']) && empty($ls_path)) {
			$io->error('The "path" argument is required for "add" and "remove" commands.');


			return static::CODE_ERROR;
		}

		Configure::load('file_hashes');

		switch ($ls_command) {
			case 'add':
				$this->addFile($io, $ls_path);
				break;
			case 'remove':
				$this->removeFile($io, $ls_path);
				break;
			case 'check':
				$this->checkFiles($io, $ls_path, $args->getOption('reportOnlyModified'), $args->getOption('interactive'));
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


		return $parser;
	}


	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $path
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function addFile(ConsoleIo $io, string $path): void {
		$ls_fullPath = $this->sanitizePath($path);

		if (!$ls_fullPath) {
			$io->error(sprintf('File does not exist: %s', $path));


			return;
		}


		if (str_contains($path, '::')) {
			[$ls_className, $ls_method] = explode('::', $path, 2);

			try {
				$ls_hash = $this->getMethodHash($ls_className, $ls_method);
			}
			catch (ReflectionException $ex) {
				$io->error(sprintf('Error processing method `%s`', $ex->getMessage()));


				return;
			}

			$ls_key = $ls_className . '::' . $ls_method;
		}
		else {
			$ls_hash = md5_file($ls_fullPath);
			$ls_key = substr($ls_fullPath, strlen(ROOT . DS));
		}

		$la_config = Configure::read('FileHashes', []);

		$la_config[ $ls_key ] = $ls_hash;

		ksort($la_config);

		Configure::write('FileHashes', $la_config);

		Configure::dump('Awyiss.file_hashes', 'default', ['FileHashes']);

		$io->success(sprintf('Added: %s with hash `%s`', $ls_key, $ls_hash));
	}


	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $path
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function removeFile(ConsoleIo $io, string $path): void {
		$ls_fullPath = $this->sanitizePath($path);

		if (!$ls_fullPath) {
			$io->error(sprintf('File does not exist `%s`', $path));


			return;
		}

		$la_config = Configure::read('FileHashes', []);

		if (str_contains($path, '::')) {
			[$ls_className, $ls_method] = explode('::', $path, 2);
			$ls_key = $ls_className . '::' . $ls_method;
		}
		else {
			$ls_key = substr($ls_fullPath, strlen(ROOT . DS));
		}

		if (!isset($la_config[ $ls_key ])) {
			$io->error(sprintf('Identifier not found `%s`', $path));


			return;
		}

		unset($la_config[ $ls_key ]);

		ksort($la_config);

		Configure::write('FileHashes', $la_config);
		Configure::dump('Awyiss.file_hashes', 'default', ['FileHashes']);

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
		$la_files = $la_config = Configure::read('FileHashes', []);

		if ($path) {
			$ls_fullPath = $this->sanitizePath($path);

			if (!$ls_fullPath) {
				$io->error(sprintf('File does not exist `%s`', $path));


				return;
			}

			if (str_contains($path, '::')) {
				[$ls_className, $ls_method] = explode('::', $path, 2);
				$ls_key = $ls_className . '::' . $ls_method;
			}
			else {
				$ls_key = substr($ls_fullPath, strlen(ROOT . DS));
			}

			if (!isset($la_config[ $ls_key ])) {
				$io->error(sprintf('Identifier not found `%s`', $path));


				return;
			}

			$la_files = [$ls_key => $la_config[ $ls_key ]];
		}

		$la_results = [
			'unchanged' => 0,
			'changed' => 0,
		];

		$lb_forceUpdate = false;
		$la_updatedData = [];
		foreach ($la_files as $ls_file => $ls_storedHash) {
			if (str_contains($ls_file, '::')) {
				[$ls_className, $ls_method] = explode('::', $ls_file, 2);

				try {
					$ls_currentHash = $this->getMethodHash($ls_className, $ls_method);
				}
				catch (ReflectionException $ex) {
					$io->error(sprintf('Error processing method `%s`', $ex->getMessage()));
					continue;
				}
			}
			else {
				$ls_fullPath = ROOT . DS . $ls_file;

				if (file_exists($ls_fullPath)) {
					$ls_currentHash = md5_file($ls_fullPath);
				}
				else {
					$io->error(sprintf('File not found `%s`', $ls_fullPath));
					continue;
				}
			}

			if (!$reportOnlyModified) {
				$io->out(sprintf('Checking %s `%s`... ', str_contains($ls_file, '::') ? 'method' : 'file', $ls_file), 0);

				if ($ls_currentHash === $ls_storedHash) {
					$la_results['unchanged']++;
					$io->success('unchanged');
				}
				else {
					$la_results['changed']++;
					$io->error('changed');
				}
			}
			else {
				if ($ls_currentHash === $ls_storedHash) {
					$la_results['unchanged']++;
				}
				else {
					$la_results['changed']++;
					$io->error(sprintf('%s `%s` was modified', str_contains($ls_file, '::') ? 'Method' : 'File', $ls_file));
				}
			}

			if ($ls_currentHash !== $ls_storedHash) {
				$this->askForUpdate(
					$io,
					$ls_file,
					$ls_currentHash,
					$la_updatedData,
					$interactive,
					$lb_forceUpdate
				);
			}
		}

		if ($la_updatedData) {
			$la_config = $la_updatedData + $la_config;

			ksort($la_config);

			Configure::write('FileHashes', $la_config);

			Configure::dump('Awyiss.file_hashes', 'default', ['FileHashes']);
		}

		if (!$reportOnlyModified || $la_results['changed']) {
			$io->hr();
		}

		$io->out(sprintf('Finished checking %d files. ', count($la_files)), 0);

		$io->success(sprintf('%d files unchanged.', $la_results['unchanged']), $la_results['changed'] ? 0 : 1);

		if ($la_results['changed']) {
			$io->out(' | ', 0);
			$io->error(sprintf('%d files changed. ', $la_results['changed']));
		}
	}


	/**
	 * @param string $className
	 * @param string $method
	 * @return string
	 * @throws \ReflectionException
	 */
	protected function getMethodHash(string $className, string $method): string {
		$lo_reflection = new ReflectionClass($className);
		if (!$lo_reflection->hasMethod($method)) {
			throw new ReflectionException(sprintf('Method `%s` not found in `%s`', $method, $className));
		}

		$ls_fileCode = file($lo_reflection->getFileName());

		$lx_method = $lo_reflection->getMethod($method);

		$ls_methodContent = implode('', array_slice($ls_fileCode, $lx_method->getStartLine() - 1, $lx_method->getEndLine() - $lx_method->getStartLine() + 1));


		return md5($ls_methodContent);
	}


	/**
	 * @param string $path
	 * @return string|false
	 * @throws \ReflectionException
	 */
	protected function sanitizePath(string $path): string|false {
		if (str_starts_with($path, '\\')) {
			$ls_className = $path;
			if (str_contains($ls_className, '::')) {
				[$ls_className] = explode('::', $ls_className, 2);
			}

			$lo_reflector = new ReflectionClass($ls_className);
			$ls_fullPath = $lo_reflector->getFileName();
		}
		else {
			$ls_fullPath = realpath(ROOT . DS . $path);

			if (!$ls_fullPath || !str_starts_with($ls_fullPath, ROOT . DS) || !is_file($ls_fullPath)) {
				return false;
			}
		}


		return $ls_fullPath;
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
		$lb_update = $forceUpdate;
		if ($interactive && !$forceUpdate) {
			$ls_key = $io->askChoice('Update hash?', ['y', 'n', 'a', 'q'], 'n');
			$ls_key = strtolower($ls_key);

			if ($ls_key === 'q') {
				$io->error('Quitting.', 2);
				throw new StopException('Not creating file. Quitting.');
			}

			if ($ls_key === 'a') {
				/** @noinspection PhpVariableNamingConventionInspection */
				$forceUpdate = true;
				$ls_key = 'y';
			}

			$lb_update = $ls_key === 'y';
		}

		if ($lb_update) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$updatedData[ $file ] = $currentHash;
		}
	}
}
