<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Awyiss;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;


/**
 * Class InstallCommandTest
 */
class InstallCommandTest extends TestCase {
	use ConsoleIntegrationTestTrait;


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();
	}


	/**
	 * Test the installation
	 */
	public function testInstallCommand(): void {
		$this->exec('awyiss install --dry-run', [
			'foo-bar',
			'db_database',
			'postgresql',
			'db_username',
			'db_password',
			'db_host',
			'',
			'awyiss',
			'',
			'dev',
			'',
		]);

		$this->assertExitSuccess();

		$this->assertOutputContains('<success>Skeleton folder can be copied.</success>');
		$this->assertOutputContains('<success>.env file can be created.</success>');
		$this->assertOutputContains('You need to create an admin user and run the migrations once the database connection is fixed manually.');
		$this->assertOutputContains('<success>Skipping symlink creation in dry run mode.</success>');
		$this->assertOutputContains('<success>Skipping .gitkeep removal in dry run mode.</success>');
		$this->assertOutputContains('<success>Installation completed.</success>');
	}


	/**
	 * Test the installation
	 *
	 * @return void
	 */
	public function testInstallCommandWithBlocklistedCustomerName(): void {
		$this->exec('awyiss install --dry-run', [
			'awyiss',
			'db_username',
			'',
			'db_password',
			'db_database',
			'db_host',
			'',
			'awyiss',
			'',
			'',
		]);

		$this->assertExitError();

		$this->assertErrorContains('Invalid customer name.');
	}


	/**
	 * Test the installation
	 *
	 * @return void
	 */
	public function testInstallCommandWithInvalidCustomerName(): void {
		$this->exec('awyiss install --dry-run', [
			'.Ümlaut/Test',
			'n',
		]);

		$this->assertExitError();

		$this->assertErrorContains('Invalid customer name.');
	}


	/**
	 * Test the installation
	 */
	public function testRebuildSymlinksCommand(): void {
		$this->exec('awyiss install --dry-run --rebuild-symlinks');

		$this->assertExitSuccess();

		$this->assertOutputContains('Symlinks rebuilt.');
	}
}
