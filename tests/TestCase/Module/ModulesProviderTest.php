<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Module;


use Awyiss\Module\ModuleInterface;
use Awyiss\Module\ModulesProvider;
use Awyiss\Test\TestSuite\TestCase;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;


/**
 * Test case for ModulesProvider
 *
 * @see \Awyiss\Module\ModulesProvider
 */
class ModulesProviderTest extends TestCase {
	/**
	 * @var \ReflectionProperty
	 */
	protected ReflectionProperty $foundAllProperty;
	/**
	 * @var \ReflectionProperty
	 */
	protected ReflectionProperty $modulesProperty;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		// Get reflection properties to reset static state
		$reflection = new ReflectionClass(ModulesProvider::class);
		$this->foundAllProperty = $reflection->getProperty('foundAll');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$this->foundAllProperty->setAccessible(true);
		$this->modulesProperty = $reflection->getProperty('modules');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$this->modulesProperty->setAccessible(true);

		// Reset static state before each test
		$this->foundAllProperty->setValue(null, false);
		$this->modulesProperty->setValue(null, []);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		// Reset static state after each test
		$this->foundAllProperty->setValue(null, false);
		$this->modulesProperty->setValue(null, []);

		parent::tearDown();
	}


	/**
	 * Test that ModulesProvider cannot be instantiated
	 *
	 * @return void
	 * @see \Awyiss\Module\ModulesProvider::__construct()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCannotBeInstantiated(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The class `Awyiss\Module\ModulesProvider` cannot be instantiated');

		$reflection = new ReflectionClass(ModulesProvider::class);
		$constructor = $reflection->getConstructor();
		/** @noinspection PhpExpressionResultUnusedInspection */
		$constructor->setAccessible(true);
		$constructor->invoke($reflection->newInstanceWithoutConstructor());
	}


	/**
	 * Test getModuleFiles method finds all modules
	 *
	 * @return void
	 * @see \Awyiss\Module\ModulesProvider::getModuleFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetModuleFilesFindsAllModules(): void {
		$result = ModulesProvider::getModuleFiles();

		$this->assertIsArray($result);
		$this->assertArrayHasKey('breadcrumbs', $result);
		$this->assertArrayHasKey('newsListing', $result);
		$this->assertArrayHasKey('instagramFeed', $result);
		$this->assertArrayHasKey('routePlanner', $result);
		$this->assertArrayHasKey('test', $result);

		// Verify that each value is a class string that implements ModuleInterface
		foreach ($result as $moduleClass) {
			$this->assertIsString($moduleClass);
			$this->assertTrue(class_exists($moduleClass));
			$this->assertTrue(in_array(ModuleInterface::class, class_implements($moduleClass)));
		}

		// The news listing module must be a custom module
		$this->assertSame('\Customer\Module\NewsListingModule', $result['newsListing']);

		// Test that foundAll flag is set after first call
		$this->assertTrue($this->foundAllProperty->getValue());

		// Test that subsequent calls don't trigger finding again (cached result)
		$secondResult = ModulesProvider::getModuleFiles();
		$this->assertSame($result, $secondResult);
	}


	/**
	 * Test getModuleFile method with existing module
	 *
	 * @return void
	 * @see \Awyiss\Module\ModulesProvider::getModuleFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetModuleFileWithExistingModule(): void {
		$result = ModulesProvider::getModuleFile('breadcrumbs');

		$this->assertNotNull($result);
		$this->assertIsString($result);
		$this->assertTrue(class_exists($result));
		$this->assertTrue(in_array(ModuleInterface::class, class_implements($result)));
		$this->assertSame('\Awyiss\Module\BreadcrumbsModule', $result);
	}


	/**
	 * Test getModuleFile method with non-existing module
	 *
	 * @return void
	 * @see \Awyiss\Module\ModulesProvider::getModuleFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetModuleFileWithNonExistingModule(): void {
		$result = ModulesProvider::getModuleFile('nonExistentModule');

		$this->assertNull($result);
	}


	/**
	 * Test getModuleFile method with identifier that needs sanitization
	 *
	 * @return void
	 * @see \Awyiss\Module\ModulesProvider::getModuleFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetModuleFileWithIdentifierNeedingSanitization(): void {
		$result = ModulesProvider::getModuleFile('Instagram-Feed!@#');

		$this->assertNotNull($result);
		$this->assertSame('\Awyiss\Module\InstagramFeedModule', $result);
	}


	/**
	 * Test getModuleFile method prefers custom module over Awyiss module
	 *
	 * @return void
	 * @see \Awyiss\Module\ModulesProvider::getModuleFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetModuleFilePrefersCustomModule(): void {
		$result = ModulesProvider::getModuleFile('newsListing');

		$this->assertNotNull($result);
		$this->assertSame('\Customer\Module\NewsListingModule', $result);
	}


	/**
	 * Test sanitizeIdentifier method with various inputs
	 *
	 * @dataProvider sanitizeIdentifierDataProvider
	 * @param string $input
	 * @param string $expected
	 * @return void
	 * @see \Awyiss\Module\ModulesProvider::sanitizeIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSanitizeIdentifier(string $input, string $expected): void {
		$result = ModulesProvider::sanitizeIdentifier($input);

		$this->assertSame($expected, $result);
	}


	/**
	 * Data provider for testSanitizeIdentifier
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function sanitizeIdentifierDataProvider(): array {
		return [
			'simple identifier' => ['breadcrumbs', 'breadcrumbs'],
			'camelCase identifier' => ['newsListing', 'newsListing'],
			'kebab-case identifier' => ['news-listing', 'newsListing'],
			'snake_case identifier' => ['news_listing', 'newsListing'],
			'mixed case with special chars' => ['News-Listing!@#', 'newsListing'],
			'with spaces' => ['news listing module', 'newsListingModule'],
			'with numbers' => ['module123test', 'module123test'],
			'with unicode chars' => ['módulo-especial', 'moduloEspecial'],
			'empty string' => ['', ''],
			'only special chars' => ['!@#$%', ''],
		];
	}
}
