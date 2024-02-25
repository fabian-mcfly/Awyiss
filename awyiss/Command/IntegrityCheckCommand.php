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
	 * @param \Cake\Console\Arguments $ao_args
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @throws \ReflectionException
	 */
	public function execute(Arguments $ao_args, ConsoleIo $ao_io): int {
		$ls_command = $ao_args->getArgument('command');
		$ls_path = $ao_args->getArgument('path');

		if (in_array($ls_command, ['add', 'remove']) && empty($ls_path)) {
			$ao_io->error('The "path" argument is required for "add" and "remove" commands.');


			return static::CODE_ERROR;
		}

		Configure::load('file_hashes');

		switch ($ls_command) {
			case 'add':
				$this->addFile($ao_io, $ls_path);
				break;
			case 'remove':
				$this->removeFile($ao_io, $ls_path);
				break;
			case 'check':
				$this->checkFiles($ao_io, $ls_path, $ao_args->getOption('reportOnlyModified'), $ao_args->getOption('interactive'));
				break;
			default:
				$ao_io->error('Invalid command. Use "add", "remove", or "check".');
				break;
		}

		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Cake\Console\ConsoleOptionParser $ao_parser
	 * @return \Cake\Console\ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$ao_parser->addArgument('command', [
			'help' => 'The command to execute: add, remove, check',
			'choices' => ['add', 'remove', 'check'],
			'required' => true,
		]);

		$ao_parser->addArgument('path', [
			'help' => 'The file path for the add/check command or the identifier for the remove command',
		]);

		$ao_parser->addOption('reportOnlyModified', [
			'boolean' => true,
			'help' => 'Whether to report only modified files and methods',
			'short' => 'm',
		]);

		$ao_parser->addOption('interactive', [
			'boolean' => true,
			'help' => 'Whether to ask if modified files/methods should have their hashes updated',
			'short' => 'i',
		]);


		return $ao_parser;
	}


	/**
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @param string $as_path
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function addFile(ConsoleIo $ao_io, string $as_path): void {
		$ls_fullPath = $this->sanitizePath($as_path);

		if (!$ls_fullPath) {
			$ao_io->error(sprintf('File does not exist: %s', $as_path));


			return;
		}


		if (str_contains($as_path, '::')) {
			[$ls_className, $ls_method] = explode('::', $as_path, 2);

			try {
				$ls_hash = $this->getMethodHash($ls_className, $ls_method);
			}
			catch (ReflectionException $ex) {
				$ao_io->error(sprintf('Error processing method `%s`', $ex->getMessage()));


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

		$ao_io->success(sprintf('Added: %s with hash `%s`', $ls_key, $ls_hash));
	}


	/**
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @param string $as_path
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function removeFile(ConsoleIo $ao_io, string $as_path): void {
		$ls_fullPath = $this->sanitizePath($as_path);

		if (!$ls_fullPath) {
			$ao_io->error(sprintf('File does not exist `%s`', $as_path));


			return;
		}

		$la_config = Configure::read('FileHashes', []);

		if (str_contains($as_path, '::')) {
			[$ls_className, $ls_method] = explode('::', $as_path, 2);
			$ls_key = $ls_className . '::' . $ls_method;
		}
		else {
			$ls_key = substr($ls_fullPath, strlen(ROOT . DS));
		}

		if (!isset($la_config[ $ls_key ])) {
			$ao_io->error(sprintf('Identifier not found `%s`', $as_path));


			return;
		}

		unset($la_config[ $ls_key ]);

		ksort($la_config);

		Configure::write('FileHashes', $la_config);
		Configure::dump('Awyiss.file_hashes', 'default', ['FileHashes']);

		$ao_io->success(sprintf('Removed `%s`', $as_path));
	}


	/**
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @param string|null $as_path
	 * @param bool $ab_reportOnlyModified
	 * @param bool $ab_interactive
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function checkFiles(ConsoleIo $ao_io, ?string $as_path = null, bool $ab_reportOnlyModified = false, bool $ab_interactive = false): void {
		$la_files = $la_config = Configure::read('FileHashes', []);

		if ($as_path) {
			$ls_fullPath = $this->sanitizePath($as_path);

			if (!$ls_fullPath) {
				$ao_io->error(sprintf('File does not exist `%s`', $as_path));


				return;
			}

			if (str_contains($as_path, '::')) {
				[$ls_className, $ls_method] = explode('::', $as_path, 2);
				$ls_key = $ls_className . '::' . $ls_method;
			}
			else {
				$ls_key = substr($ls_fullPath, strlen(ROOT . DS));
			}

			if (!isset($la_config[ $ls_key ])) {
				$ao_io->error(sprintf('Identifier not found `%s`', $as_path));


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
					$ao_io->error(sprintf('Error processing method `%s`', $ex->getMessage()));
					continue;
				}
			}
			else {
				$ls_fullPath = ROOT . DS . $ls_file;

				if (file_exists($ls_fullPath)) {
					$ls_currentHash = md5_file($ls_fullPath);
				}
				else {
					$ao_io->error(sprintf('File not found `%s`', $ls_fullPath));
					continue;
				}
			}

			if (!$ab_reportOnlyModified) {
				$ao_io->out(sprintf('Checking %s `%s`... ', str_contains($ls_file, '::') ? 'method' : 'file', $ls_file), 0);

				if ($ls_currentHash === $ls_storedHash) {
					$la_results['unchanged']++;
					$ao_io->success('unchanged');
				}
				else {
					$la_results['changed']++;
					$ao_io->error('changed');
				}
			}
			else {
				if ($ls_currentHash === $ls_storedHash) {
					$la_results['unchanged']++;
				}
				else {
					$la_results['changed']++;
					$ao_io->error(sprintf('%s `%s` was modified', str_contains($ls_file, '::') ? 'Method' : 'File', $ls_file));
				}
			}

			if ($ls_currentHash !== $ls_storedHash) {
				$this->askForUpdate(
					$ao_io,
					$ls_file,
					$ls_currentHash,
					$la_updatedData,
					$ab_interactive,
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

		if (!$ab_reportOnlyModified || $la_results['changed']) {
			$ao_io->hr();
		}

		$ao_io->out(sprintf('Finished checking %d files. ', count($la_files)), 0);
		$ao_io->success(sprintf('%d files unchanged.', $la_results['unchanged']), 1 - $la_results['changed']);

		if ($la_results['changed']) {
			$ao_io->out(' | ', 0);
			$ao_io->error(sprintf('%d files changed. ', $la_results['changed']));
		}
	}


	/**
	 * @param string $as_className
	 * @param string $as_method
	 * @return string
	 * @throws \ReflectionException
	 */
	protected function getMethodHash(string $as_className, string $as_method): string {
		if (!class_exists($as_className)) {
			if (file_exists($as_className)) {
				require_once $as_className;
			}

			throw new ReflectionException(sprintf('Class `%s` not found', $as_className));
		}

		$lo_reflection = new ReflectionClass($as_className);
		if (!$lo_reflection->hasMethod($as_method)) {
			throw new ReflectionException(sprintf('Method `%s` not found in `%s`', $as_method, $as_className));
		}

		$ls_fileCode = file($lo_reflection->getFileName());

		$lx_method = $lo_reflection->getMethod($as_method);

		$ls_methodContent = implode('', array_slice($ls_fileCode, $lx_method->getStartLine() - 1, $lx_method->getEndLine() - $lx_method->getStartLine() + 1));


		return md5($ls_methodContent);
	}


	/**
	 * @param string $as_path
	 * @return string|false
	 * @throws \ReflectionException
	 */
	protected function sanitizePath(string $as_path): string|false {
		if (str_starts_with($as_path, '\\')) {
			$ls_className = $as_path;
			if (str_contains($ls_className, '::')) {
				[$ls_className] = explode('::', $ls_className, 2);
			}

			$lo_reflector = new ReflectionClass($ls_className);
			$ls_fullPath = $lo_reflector->getFileName();
		}
		else {
			$ls_fullPath = realpath(ROOT . DS . $as_path);

			if (!$ls_fullPath || !str_starts_with($ls_fullPath, ROOT . DS) || !is_file($ls_fullPath)) {
				return false;
			}
		}


		return $ls_fullPath;
	}


	/**
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @param string $as_file
	 * @param mixed $as_currentHash
	 * @param array $aa_updatedData
	 * @param bool $ab_interactive
	 * @param bool $ab_forceUpdate
	 * @return void
	 */
	protected function askForUpdate(ConsoleIo $ao_io, string $as_file, mixed $as_currentHash, array &$aa_updatedData, bool $ab_interactive, bool &$ab_forceUpdate): void {
		$lb_update = $ab_forceUpdate;
		if ($ab_interactive && !$ab_forceUpdate) {
			$ls_key = $ao_io->askChoice('Update hash?', ['y', 'n', 'a', 'q'], 'n');
			$ls_key = strtolower($ls_key);

			if ($ls_key === 'q') {
				$ao_io->error('Quitting.', 2);
				throw new StopException('Not creating file. Quitting.');
			}

			if ($ls_key === 'a') {
				$ab_forceUpdate = true;
				$ls_key = 'y';
			}

			$lb_update = $ls_key === 'y';
		}

		if ($lb_update) {
			$aa_updatedData[ $as_file ] = $as_currentHash;
		}
	}
}
