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
			'foobar',
			'db_username',
			'db_password',
			'db_database',
			'db_host',
			'awyiss',
			'',
			'',
		]);

		$this->assertExitSuccess();

		//<success>.env file created.</success>
		$this->assertOutputContains('.env file created.');

		//<success>Base config file set.</success>
		$this->assertOutputContains('Base config file set.');

		//<success>Environment config file set.</success>
		$this->assertOutputContains('Environment config file set.');

		//<success>Connected to the database successfully.</success>
		$this->assertOutputContains('Connected to the database successfully.');

		//<success>Migrations completed.</success>
		$this->assertOutputContains('Migrations completed.');

		//<success>Queue migrations completed.</success>
		$this->assertOutputContains('Queue migrations completed.');

		//<success>Seeding completed.</success>
		$this->assertOutputContains('Seeding completed.');

		//<success>Admin usergroup created successfully.</success>
		$this->assertOutputContains('Admin usergroup created successfully.');

		//<success>Admin user created successfully.</success>
		$this->assertOutputContains('Admin user created successfully.');

		//<info> The password for the admin user is: ...</info>
		$this->assertOutputContains('The password for the admin user is:');

		//<success>\Customer\Attribute\AttributeOptionsCollection file updated.</success>
		$this->assertOutputContains('\Customer\Attribute\AttributeOptionsCollection file updated.');

		//<success>\Customer\View\Cell\Frontend\MenuCell file updated.</success>
		$this->assertOutputContains('\Customer\View\Cell\Frontend\MenuCell file updated.');

		//<success>ide-twig.json file updated.</success>
		$this->assertOutputContains('ide-twig.json file updated.');

		//<success>\Twig\Extension\CustomerExtension file updated and renamed.</success>
		$this->assertOutputContains('\Twig\Extension\CustomerExtension file updated and renamed.');

		//<success>.gitkeep files removed successfully.</success>
		$this->assertOutputContains('.gitkeep files removed successfully.');

		//<success>Skeleton folder removed successfully.</success>
		$this->assertOutputContains('Skeleton folder removed successfully.');

		//<success>Installation completed.</success
		$this->assertOutputContains('Installation completed.');
	}


	/**
	 * Test the installation
	 *
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testInstallCommandWithBlocklistedCustomerName(): void {
		$this->exec('awyiss install --dry-run', [
			'awyiss',
			'db_username',
			'db_password',
			'db_database',
			'db_host',
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
	 * @noinspection PhpMethodNamingConventionInspection
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
