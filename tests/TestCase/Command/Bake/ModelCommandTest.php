<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Bake;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\Bake\BakeTestTrait;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Datasource\FactoryLocator;


/**
 * Class ModelCommandTest
 */
class ModelCommandTest extends TestCase {
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
	 * @inheritDoc
	 */
	public function tearDown(): void {
		parent::tearDown();

		FactoryLocator::get('Table')->clear();
	}


	/**
	 * @return void
	 */
	public function testModelCommandHelp(): void {
		$this->exec('bake model --help');

		$this->assertExitSuccess();

		$this->assertOutputContains('--namespace');
		$this->assertOutputContains('--is-datatable');
		$this->assertOutputContains('--is-pagerole');
		$this->assertOutputContains('--for-pagerole');
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testModelCommand(): void {
		$this->generatedFiles[0] = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . 'DummyUser.php';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Entity' . DS . 'DummyUser.php';

		$this->generatedFiles[1] = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . 'DummyUsersTable.php';
		$comparisonTableFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Table' . DS . 'DummyUsersTable.php';

		$this->exec('bake model dummy_users --namespace Customer --no-fixture --no-test');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFiles[0]);
		$this->assertSameAsFile($comparisonEntityFile, $result);

		$result = file_get_contents($this->generatedFiles[1]);
		$this->assertSameAsFile($comparisonTableFile, $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testForPageRoleModelCommand(): void {
		$generatedEntityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . 'AttributesNews.php';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Entity' . DS . 'AttributesNews.php';

		$generatedTableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . 'AttributesNewsTable.php';
		$comparisonTableFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Table' . DS . 'AttributesNewsTable.php';

		$this->exec('bake model attributes_news --namespace Customer --no-fixture --no-test --for-pagerole news --force');

		$this->assertExitSuccess();

		$result = file_get_contents($generatedEntityFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);

		$result = file_get_contents($generatedTableFile);
		$this->assertSameAsFile($comparisonTableFile, $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPageRoleModelCommand(): void {
		$generatedEntityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . 'Newscategory.php';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Entity' . DS . 'Newscategory.php';

		$generatedTableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . 'NewscategoriesTable.php';
		$comparisonTableFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Table' . DS . 'NewscategoriesTable.php';

		$this->exec('bake model newscategories --namespace Customer --force --is-pagerole --no-associations --no-fixture --no-hidden --no-rules --no-test --no-validation --skip-relation-check --table pages');

		$this->assertExitSuccess();

		$result = file_get_contents($generatedEntityFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);

		$result = file_get_contents($generatedTableFile);
		$this->assertSameAsFile($comparisonTableFile, $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDatatableModelCommand(): void {
		$generatedEntityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . 'Employer.php';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Entity' . DS . 'Employer.php';

		$generatedTableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . 'EmployersTable.php';
		$comparisonTableFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'Model' . DS . 'Table' . DS . 'EmployersTable.php';

		$this->exec('bake model employers --namespace Customer --no-fixture --no-test --is-datatable --force');

		$this->assertExitSuccess();

		$result = file_get_contents($generatedEntityFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);

		$result = file_get_contents($generatedTableFile);
		$this->assertSameAsFile($comparisonTableFile, $result);
	}
}
