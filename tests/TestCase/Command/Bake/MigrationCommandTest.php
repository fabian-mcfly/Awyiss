<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Bake;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\Bake\BakeTestTrait;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Migrations\Util\Util;


/**
 * Class MigrationCommandTest
 */
class MigrationCommandTest extends TestCase {
	use ConsoleIntegrationTestTrait;
	use BakeTestTrait;


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();
	}


	/**
	 * @return void
	 */
	public function testMigrationCommandHelp(): void {
		$this->exec('bake migration --help');

		$this->assertExitSuccess();

		$this->assertOutputContains('--folder');
	}


	/**
	 * @return void
	 */
	public function testMigrationCreateCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'config' . DS . 'Migrations' . DS . Util::getCurrentTimestamp() . '_CreateDummyMigration.php';
		$comparisonFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'config' . DS . 'Migrations' . DS . 'CreateDummyMigration.txt';

		$this->exec('bake migration create_dummy_migration content_id:integer[11] testattribute:string?[255] --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);

		$this->assertSameAsFile($comparisonFile, $result);
	}


	/**
	 * @return void
	 */
	public function testMigrationAddCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'config' . DS . 'Migrations' . DS . Util::getCurrentTimestamp() . '_AddBackgroundColorToDummyMigration.php';
		$comparisonFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'config' . DS . 'Migrations' . DS . 'AddBackgroundColorToDummyMigration.txt';

		$this->exec('bake migration add_backgroundColor_to_dummy_migration backgroundColor:string?[50] --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);

		$this->assertSameAsFile($comparisonFile, $result);
	}


	/**
	 * @return void
	 */
	public function testMigrationAlterCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'config' . DS . 'Migrations' . DS . Util::getCurrentTimestamp() . '_AlterBackgroundColorOnDummyMigration.php';
		$comparisonFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'config' . DS . 'Migrations' . DS . 'AlterBackgroundColorOnDummyMigration.txt';

		$this->exec('bake migration alter_backgroundColor_on_dummy_migration backgroundColor:string[100] --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);

		$this->assertSameAsFile($comparisonFile, $result);
	}


	/**
	 * @return void
	 */
	public function testMigrationAlterTwiceCommand(): void {
		$this->generatedFiles[0] = ROOT . DS . CUSTOM_DIR . DS . 'config' . DS . 'Migrations' . DS . Util::getCurrentTimestamp() . '_AlterBackgroundColorOnDummyMigration.php';
		$comparisonFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'config' . DS . 'Migrations' . DS . 'AlterBackgroundColorOnDummyMigration.txt';

		$this->exec('bake migration alter_backgroundColor_on_dummy_migration backgroundColor:string[100] --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFiles[0]);

		$this->assertSameAsFile($comparisonFile, $result);

		$comparisonFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'config' . DS . 'Migrations' . DS . 'AlterBackgroundColorOnDummyMigrationV2.txt';

		$this->exec('bake migration alter_backgroundColor_on_dummy_migration backgroundColor:string[10]:index --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations');

		$this->assertExitSuccess();

		for ($i = 0; $i < 2; $i++) {
			$this->generatedFiles[1] = ROOT . DS . CUSTOM_DIR . DS . 'config' . DS . 'Migrations' . DS . Util::getCurrentTimestamp($i) . '_AlterBackgroundColorOnDummyMigrationV2.php';
			if (file_exists($this->generatedFiles[1])) {
				$result = file_get_contents($this->generatedFiles[1]);
			}
		}

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertSameAsFile($comparisonFile, $result ?? '');
	}


	/**
	 * @return void
	 */
	public function testMigrationAlterRenameCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'config' . DS . 'Migrations' . DS . Util::getCurrentTimestamp() . '_AlterBackgroundColorOnDummyMigration.php';
		$comparisonFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'config' . DS . 'Migrations' . DS . 'RenameBackgroundColorOnDummyMigration.txt';

		$this->exec('bake migration alter_backgroundColor_on_dummy_migration backgroundColorRenamed:string?[50] --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);

		$this->assertSameAsFile($comparisonFile, $result);
	}
}
