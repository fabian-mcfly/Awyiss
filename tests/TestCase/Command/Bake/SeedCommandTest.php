<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Bake;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\Bake\BakeTestTrait;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;


/**
 * Class SeedCommandTest
 */
class SeedCommandTest extends TestCase {
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
	public function testPolicyCommandHelp(): void {
		$this->exec('bake seed --help');

		$this->assertExitSuccess();

		$this->assertOutputContains('--folder');
		$this->assertOutputContains('--truncate');
	}


	/**
	 * Make sure the folder-option works as expected.
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSeedCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'config' . DS . 'Seeds' . DS . 'DummyUsersSeed.php';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'config' . DS . 'Seeds' . DS . 'DummyUsersSeed.txt';

		$this->exec('bake seed dummy_users --data --table users --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds', ['a']);

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);
	}


	/**
	 * Make sure the truncate-statement is added to the seed.
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPolicyTruncateCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'config' . DS . 'Seeds' . DS . 'DummyUsersTruncatingSeed.php';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'config' . DS . 'Seeds' . DS . 'DummyUsersTruncatingSeed.txt';

		$this->exec('bake seed dummy_users_truncating --data --table users --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --truncate', ['a']);

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);
	}
}
