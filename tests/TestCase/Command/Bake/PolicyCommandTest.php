<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Bake;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\Bake\BakeTestTrait;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;


/**
 * Class PolicyCommandTest
 */
class PolicyCommandTest extends TestCase {
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
		$this->exec('bake model --help');

		$this->assertExitSuccess();

		$this->assertOutputContains('--namespace');
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPolicyCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'Authorization' . DS . 'Policy' . DS . 'DummyPolicy.php';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Authorization' . DS . 'Policy' . DS . 'DummyPolicy.php';

		$this->exec('bake policy dummy --namespace Customer');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPolicyPrefixCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'Authorization' . DS . 'Policy' . DS . 'Backend' . DS . 'DummyPolicy.php';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Authorization' . DS . 'Policy' . DS . 'Backend' . DS . 'DummyPolicy.php';

		$this->exec('bake policy dummy --namespace Customer --prefix Backend');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);
	}
}
