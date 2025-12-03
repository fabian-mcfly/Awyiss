<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Scss;


use Awyiss\Awyiss;
use Awyiss\Command\Scss\CompileCommand;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleOptionParser;
use Cake\TestSuite\ConsoleIntegrationTestTrait;


/**
 * Test case for CompileCommand
 *
 * @see \Awyiss\Command\Scss\CompileCommand
 */
class CompileCommandTest extends TestCase {
	use ConsoleIntegrationTestTrait;


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
	protected function tearDown(): void {
		parent::tearDown();

		// Clean up compiled CSS files
		$testCssPath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		if (file_exists($testCssPath)) {
			unlink($testCssPath);
		}
		if (file_exists($testCssPath . '.map')) {
			unlink($testCssPath . '.map');
		}

		$testCssPath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'subfolder' . DS . 'test.css';
		if (file_exists($testCssPath)) {
			unlink($testCssPath);
		}
		if (file_exists($testCssPath . '.map')) {
			unlink($testCssPath . '.map');
		}
	}


	/**
	 * @return void
	 */
	public function testGetDescription(): void {
		$description = CompileCommand::getDescription();
		$this->assertIsString($description);
		$this->assertEquals('Compiles SCSS files to CSS', $description);
	}


	/**
	 * @return void
	 */
	public function testBuildOptionParserIncludesRealmOption(): void {
		$parser = new ConsoleOptionParser('test');
		$command = new CompileCommand();

		$command->buildOptionParser($parser);
		$options = $parser->options();

		$this->assertArrayHasKey('realm', $options);
		$this->assertEquals(['Backend', 'Frontend'], $options['realm']->choices());
		$this->assertEquals('Frontend', $options['realm']->defaultValue());
		$this->assertEquals('r', $options['realm']->short());
	}


	/**
	 * @return void
	 */
	public function testExecuteWithFrontendRealmSuccess(): void {
		$testCssPath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		if (file_exists($testCssPath)) {
			unlink($testCssPath);
		}

		$this->assertFileDoesNotExist($testCssPath);

		$this->exec('scss compile --realm Frontend');

		$this->assertOutputContains('Fetching folders for realm `Frontend`');
		$this->assertOutputContains('folders found.');

		$this->assertExitCode(CommandInterface::CODE_SUCCESS);

		$this->assertFileExists($testCssPath);
	}


	/**
	 * @return void
	 */
	public function testExecuteWithBackendRealmSuccess(): void {
		$mainBackendCssPath = ROOT . DS . 'awyiss' . DS . 'assets' . DS . 'css' . DS . 'main.css';
		$this->assertFileExists($mainBackendCssPath);

		$fileMTime = filemtime($mainBackendCssPath);

		$this->exec('scss compile --realm Backend');

		$this->assertOutputContains('Fetching folders for realm `Backend`');
		$this->assertOutputContains('folders found.');

		$this->assertExitCode(CommandInterface::CODE_SUCCESS);

		$this->assertFileExists($mainBackendCssPath);

		$this->assertGreaterThan($fileMTime, filemtime($mainBackendCssPath));
	}
}
