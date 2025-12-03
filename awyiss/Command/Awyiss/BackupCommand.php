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
		$startTime = microtime(true);

		// Get the backup directory
		$backupDir = ROOT . DS . 'backup';

		// Check if the backup directory exists
		if (!is_dir($backupDir)) {
			mkdir($backupDir, 0755, true);
		}

		// Get the backup file name
		$backupFile = $backupDir . DS . 'backup-' . date('Y-m-d-H-i-s') . '.zip';

		// Zip the data
		if ($this->zipData(ROOT, $backupFile)) {
			$endTime = microtime(true);

			$this->io->success('Backup created successfully.');
			$this->io->info('Backup file `' . $backupFile . '` created in ' . round($endTime - $startTime, 2) . ' seconds.');
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

		$zip = new ZipArchive();

		if (!$zip->open($destination, ZipArchive::CREATE)) {
			$this->io->error('Could not create backup file `' . $destination . '`.');
			return false;
		}

		$this->addDatabaseBackup($zip);

		$sourcePath = realpath($source);
		if (is_dir($sourcePath)) {
			/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
			$directory = new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS);
			$filter = new RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
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

			$iterator = new RecursiveIteratorIterator($filter);
			foreach ($iterator as $file) {
				$filePath = $file->getPathname();

				if (is_dir($filePath)) {
					$this->io->verbose('Adding `' . $filePath . '` to the backup.');
					$zip->addEmptyDir(str_replace($sourcePath . '/', '', $filePath . '/'));
				}
				elseif (is_file($filePath)) {
					// If the file is inside the 'tmp' directory, skip it
					if (str_starts_with($filePath, ROOT . DS . 'tmp' . DS)) {
						continue;
					}

					$this->io->verbose('Adding `' . $filePath . '` to the backup.');
					$zip->addFromString(str_replace($sourcePath . '/', '', $filePath), file_get_contents($filePath));
				}
			}
		}
		elseif (is_file($sourcePath)) {
			$this->io->verbose('Adding `' . $sourcePath . '` to the backup.');
			$zip->addFromString(basename($sourcePath), file_get_contents($sourcePath));
		}

		return $zip->close();
	}


	/**
	 * @param \ZipArchive $zip
	 */
	protected function addDatabaseBackup(ZipArchive $zip): void {
		$config = Configure::read('Datasources.default');
		$database = $config['database'] ?? false;
		$host = $config['host'] ?? false;
		$username = $config['username'] ?? false;
		$password = $config['password'] ?? false;

		$this->io->out('Backing up database... ', 0);

		if (!$database || !$host || !$username || !$password) {
			$this->io->warning('Database configuration not found. Skipping database backup.');
			return;
		}

		$command = [
			'mysqldump',
			'-h' . $host,
			'-u' . $username,
			'-p' . $password,
			$database,
		];

		$process = new Process($command);
		try {
			$process->mustRun();
			$backup = $process->getOutput();
		}
		catch (ProcessFailedException $ex) {
			$this->io->error('Database backup failed.');
			$this->io->error($ex->getMessage());
			return;
		}

		$zip->addFromString('database.sql', $backup);

		$this->io->out('Database backup created successfully.');
	}
}
