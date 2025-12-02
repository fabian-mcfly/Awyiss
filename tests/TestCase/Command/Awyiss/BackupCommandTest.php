<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Awyiss;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;


/**
 * Class BackupCommandTest
 */
class BackupCommandTest extends TestCase {
	use ConsoleIntegrationTestTrait;


	/**
	 * @var array|false The existing backups
	 */
	protected array|false $existingBackups = [];


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		// Find all zip files in the backup directory
		$this->existingBackups = glob(ROOT . DS . 'backup' . DS . '*.zip');
	}


	/**
	 * @inheritDoc
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Remove the created backup
		foreach (glob(ROOT . DS . 'backup' . DS . '*.zip') as $filePath) {
			if (!in_array($filePath, $this->existingBackups)) {
				unlink($filePath);
			}
		}
	}


	/**
	 * Test the backup command
	 **/
	public function testBackupCommand(): void {
		// Get the backup file name
		$backupFile = ROOT . DS . 'backup' . DS . 'backup-' . date('Y-m-d-H-i-s') . '.zip';

		$this->exec('awyiss backup');

		$this->assertOutputContains('Backup created successfully.');
		$this->assertFileExists($backupFile);
	}
}
