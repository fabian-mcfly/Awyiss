<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Bake;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\Bake\BakeTestTrait;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;


/**
 * Class EnumCommandTest
 */
class EnumCommandTest extends TestCase {
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
	public function testEnumCommandHelp(): void {
		$this->exec('bake enum --help');

		$this->assertExitSuccess();

		$this->assertOutputContains('--namespace');
	}


	/**
	 * @return void
	 */
	public function testEnumCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Enum' . DS . 'FoobarEnum.php';
		$comparisonFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Enum' . DS . 'FoobarEnum.txt';

		$this->exec('bake enum foobar_enum case1:1,case2:2,case3:3 -i --namespace Customer');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);

		$this->assertSameAsFile($comparisonFile, $result);
	}


	/**
	 * @return void
	 */
	public function testPageRoleEnumCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Enum' . DS . 'PageRoleTest.php';
		$comparisonFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Enum' . DS . 'PageRoleTest.txt';

		$this->exec('bake enum PageRoleTest page:1,newscategory:2 -i --namespace Customer --is-pagerole');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);

		$this->assertSameAsFile($comparisonFile, $result);
	}
}
