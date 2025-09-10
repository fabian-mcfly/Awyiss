<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Bake;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\Bake\BakeTestTrait;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;


/**
 * Class ControllerCommandTest
 */
class ControllerCommandTest extends TestCase {
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
	public function testControllerCommandHelp(): void {
		$this->exec('bake controller --help');

		$this->assertExitSuccess();

		$this->assertOutputContains('--namespace');
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControllerCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'Controller' . DS . 'Backend' . DS . 'UsersController.php';
		$comparisonFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Controller' . DS . 'Backend' . DS . 'UsersController.txt';

		$this->exec('bake controller users --prefix Backend --namespace Customer --no-test');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);

		$this->assertSameAsFile($comparisonFile, $result);
	}
}
