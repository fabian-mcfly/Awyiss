<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Core\Configure\Engine;


use Awyiss\Core\Configure\Engine\PhpConfig;
use Awyiss\Test\TestSuite\TestCase;


/**
 * PhpConfig Test Case
 *
 * @see \Awyiss\Core\Configure\Engine\PhpConfig
 */
class PhpConfigTest extends TestCase {
	/**
	 * @return void
	 */
	public function testReadTraversesAllPaths(): void {
		$config = new PhpConfig();

		$result = $config->read('awyiss');
		$this->assertArrayHasKey('SomeDebugKey', $result);
		$this->assertSame('someDebugValue', $result['SomeDebugKey']);

		$this->assertArrayHasKey('Design', $result);
		$this->assertArrayHasKey('fontStacks', $result['Design']);
		$this->assertArrayHasKey('sans-serif', $result['Design']['fontStacks']);
		$this->assertSame([
			'Arial, Helvetica, sans-serif',
			'Verdana, Geneva, sans-serif',
			'Trebuchet MS, Helvetica, sans-serif',
			'Gill Sans, Arial, sans-serif',
			'Calibri, Arial, sans-serif',
			'Tahoma, Geneva, sans-serif',
		], $result['Design']['fontStacks']['sans-serif']);

		$this->assertArrayHasKey('Design', $result);
		$this->assertArrayHasKey('allowGoogleFonts', $result['Design']);
		$this->assertTrue($result['Design']['allowGoogleFonts']);
	}


	/**
	 * @return void
	 * @throws \Brick\VarExporter\ExportException
	 */
	public function testDump(): void {
		$config = new PhpConfig();

		$data = [
			'SomeKey' => 'SomeValue',
			'SomeArray' => [
				'SubKey' => 'SubValue',
				'SubArray' => [
					1,
					2,
					3,
				],
			],
			'SomeBoolean' => true,
			'SomeNull' => null,
			'SomeInteger' => 123,
			'SomeFloat' => 123.45,
		];

		$result = $config->dump('test_config', $data);
		$this->assertTrue($result);

		$expectedFilePath = ENV_CUSTOM_CONFIG . 'test_config.php';
		$this->assertFileExists($expectedFilePath);

		$this->assertFileEquals(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'config' . DS . 'test_config.txt', $expectedFilePath);

		unlink($expectedFilePath);
	}
}
