<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss;


use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use ZipArchive;


/**
 * Class BackupCommand
 * This class handles the backup process.
 */
class BackupCommand extends Command {
	/**
	 * @var \Cake\Console\ConsoleIo The console I/O
	 * @noinspection PhpPropertyNamingConventionInspection
	 */
	protected ConsoleIo $io;


	/**
	 * Main execution method
	 *
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$this->io = $io;

		// Start time
		$lf_start = microtime(true);

		// Get the backup directory
		$ls_backupDir = ROOT . DS . 'backup';

		// Check if the backup directory exists
		if (!is_dir($ls_backupDir)) {
			mkdir($ls_backupDir, 0750, true);
		}

		// Get the backup file name
		$ls_backupFile = $ls_backupDir . DS . 'backup-' . date('Y-m-d-H-i-s') . '.zip';

		// Zip the data
		if ($this->zipData(ROOT, $ls_backupFile)) {
			$lf_end = microtime(true);

			$this->io->success('Backup created successfully.');
			$this->io->info('Backup file `' . $ls_backupFile . '` created in ' . round($lf_end - $lf_start, 2) . ' seconds.');
		}
		else {
			$this->io->error('Backup creation failed.');
		}

		return static::CODE_SUCCESS;
	}


	/**
	 * @param string $source
	 * @param string $destination
	 * @return bool
	 */
	public function zipData(string $source, string $destination): bool {
		if (!extension_loaded('zip')) {
			return false;
		}

		$lo_zip = new ZipArchive();

		if (!$lo_zip->open($destination, ZipArchive::CREATE)) {
			$this->io->error('Could not create backup file `' . $destination . '`.');
			return false;
		}

		$this->addDatabaseBackup($lo_zip);

		$ls_source = realpath($source);
		if (is_dir($ls_source)) {
			/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
			$lo_directory = new RecursiveDirectoryIterator($ls_source, RecursiveDirectoryIterator::SKIP_DOTS);
			$lo_filter = new RecursiveCallbackFilterIterator($lo_directory, function ($current, $key, $iterator) {
				// Skip directories starting with a dot
				if ($current->isDir() && $current->getFilename()[0] === '.') {
					return false;
				}

				if ($iterator->hasChildren()) {
					// Exclude directories with names starting with '_deleted_' or matching '_*_preview'
					if (
						fnmatch('_deleted_*', $current->getFilename()) ||
						fnmatch('_*_preview', $current->getFilename())
					) {
						return false;
					}

					// Exclude directories with certain names
					return !in_array($current->getFilename(), ['_resized', '_avif', '_webp', 'backup', 'vendor']);
				}

				return true;
			});

			$lo_iterator = new RecursiveIteratorIterator($lo_filter);
			foreach ($lo_iterator as $lo_file) {
				$ls_filePath = $lo_file->getPathname();

				if (is_dir($ls_filePath)) {
					$this->io->verbose('Adding `' . $ls_filePath . '` to the backup.');
					$lo_zip->addEmptyDir(str_replace($ls_source . '/', '', $ls_filePath . '/'));
				}
				elseif (is_file($ls_filePath)) {
					// If the file is inside the 'tmp' directory, skip it
					if (str_starts_with($ls_filePath, ROOT . DS . 'tmp' . DS)) {
						continue;
					}

					$this->io->verbose('Adding `' . $ls_filePath . '` to the backup.');
					$lo_zip->addFromString(str_replace($ls_source . '/', '', $ls_filePath), file_get_contents($ls_filePath));
				}
			}
		}
		elseif (is_file($ls_source)) {
			$this->io->verbose('Adding `' . $ls_source . '` to the backup.');
			$lo_zip->addFromString(basename($ls_source), file_get_contents($ls_source));
		}

		return $lo_zip->close();
	}


	/**
	 * @param \ZipArchive $zip
	 */
	protected function addDatabaseBackup(ZipArchive $zip): void {
		$la_config = Configure::read('Datasources.default');
		$ls_database = $la_config['database'] ?? false;
		$ls_host = $la_config['host'] ?? false;
		$ls_username = $la_config['username'] ?? false;
		$ls_password = $la_config['password'] ?? false;

		$this->io->out('Backing up database... ', 0);

		if (!$ls_database || !$ls_host || !$ls_username || !$ls_password) {
			$this->io->warning('Database configuration not found. Skipping database backup.');
			return;
		}

		$la_command = [
			'mysqldump',
			'-h' . $ls_host,
			'-u' . $ls_username,
			'-p' . $ls_password,
			$ls_database,
		];

		$lo_process = new Process($la_command);
		try {
			$lo_process->mustRun();
			$ls_backup = $lo_process->getOutput();
		}
		catch (ProcessFailedException $ex) {
			$this->io->error('Database backup failed.');
			$this->io->error($ex->getMessage());
			return;
		}

		$zip->addFromString('database.sql', $ls_backup);

		$this->io->out('Database backup created successfully.');
	}
}
