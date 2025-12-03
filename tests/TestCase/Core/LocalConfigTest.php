<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Core;


use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;


/**
 * LocalConfig Test Case
 *
 * @see \Awyiss\Core\LocalConfig
 */
class LocalConfigTest extends TestCase {
	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$request = Router::getRequest();
		$request = $request->withParam('controller', 'TestController');
		Router::setRequest($request);

		Configure::write('Awyiss', []);
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
	}


	/**
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::read()
	 */
	public function testReadWithStringPath(): void {
		Configure::write('Awyiss.TestController.Backend.test.key', 'test-value');

		$result = LocalConfig::read('test.key');
		$this->assertSame('test-value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::read()
	 */
	public function testReadWithArrayPath(): void {
		Configure::write('Awyiss.TestController.Backend.test.key', 'test-value');

		$result = LocalConfig::read(['test', 'key']);
		$this->assertSame('test-value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::read()
	 */
	public function testReadWithNullPath(): void {
		Configure::write('Awyiss.TestController.Backend', [
			'test' => [
				'key' => 'test-value',
			],
		]);

		$result = LocalConfig::read();
		$this->assertIsArray($result);
		$this->assertArrayHasKey('test', $result);
		$this->assertIsArray($result['test']);
		$this->assertArrayHasKey('key', $result['test']);
		$this->assertSame('test-value', $result['test']['key']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::read()
	 */
	public function testReadWithCustomScope(): void {
		Configure::write('Awyiss.CustomScope.Backend.test.key', 'custom-value');

		$result = LocalConfig::read('test.key', null, 'CustomScope');
		$this->assertSame('custom-value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::read()
	 */
	public function testReadWithDefaultValue(): void {
		$result = LocalConfig::read('non.existent.key', 'default-value');
		$this->assertSame('default-value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::write()
	 */
	public function testWriteWithStringConfig(): void {
		LocalConfig::write('test.key', 'new-value');

		$result = Configure::read('Awyiss.TestController.Backend.test.key');
		$this->assertSame('new-value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::write()
	 */
	public function testWriteWithArrayConfig(): void {
		LocalConfig::write([
			'test.key1' => 'value1',
			'test.key2' => 'value2',
		]);

		$result1 = Configure::read('Awyiss.TestController.Backend.test.key1');
		$result2 = Configure::read('Awyiss.TestController.Backend.test.key2');
		$this->assertSame('value1', $result1);
		$this->assertSame('value2', $result2);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::write()
	 */
	public function testWriteWithCustomScope(): void {
		LocalConfig::write('test.key', 'custom-scope-value', 'CustomScope');

		$result = Configure::read('Awyiss.CustomScope.Backend.test.key');
		$this->assertSame('custom-scope-value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::stringify()
	 */
	public function testStringify(): void {
		$result = LocalConfig::stringify(['one', 'two', 'three']);
		$this->assertSame('one.two.three', $result);

		$result = LocalConfig::stringify([]);
		$this->assertSame('', $result);

		$result = LocalConfig::stringify(['part1.a', 'part2.b']);
		$this->assertSame('part1.a.part2.b', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::read() and \Awyiss\Core\LocalConfig::write()
	 */
	public function testReadWriteIntegration(): void {
		LocalConfig::write('integration.test', 'integration-value');

		$result = LocalConfig::read('integration.test');
		$this->assertSame('integration-value', $result);

		LocalConfig::write('integration.test', 'modified-value');

		$result = LocalConfig::read('integration.test');
		$this->assertSame('modified-value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::read() and \Awyiss\Core\LocalConfig::write()
	 */
	public function testDifferentRealmAndController(): void {
		Awyiss::setRealm('Frontend');
		$request = new ServerRequest();
		$request = $request->withParam('controller', 'OtherController');
		Router::setRequest($request);

		// Write some data
		LocalConfig::write('realm.test', 'frontend-value');

		// Read it back
		$result = LocalConfig::read('realm.test');
		$this->assertSame('frontend-value', $result);

		// Verify it's in the expected path in Configure
		$result = Configure::read('Awyiss.OtherController.Frontend.realm.test');
		$this->assertSame('frontend-value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\LocalConfig::write()
	 */
	public function testWriteWithNestedArrays(): void {
		LocalConfig::write('nested', [
			'level1' => [
				'level2' => 'nested-value',
			],
		]);

		$result = Configure::read('Awyiss.TestController.Backend.nested.level1.level2');
		$this->assertSame('nested-value', $result);

		$result = LocalConfig::read('nested.level1.level2');
		$this->assertSame('nested-value', $result);
	}
}
