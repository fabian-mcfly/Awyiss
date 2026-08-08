<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Widget;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Widget\WidgetInterface;
use Awyiss\Widget\WidgetsProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;


/**
 * Test case for WidgetsProvider
 *
 * @see \Awyiss\Widget\WidgetsProvider
 */
class WidgetsProviderTest extends TestCase {
	/**
	 * @var \ReflectionProperty
	 */
	protected ReflectionProperty $foundAllProperty;
	/**
	 * @var \ReflectionProperty
	 */
	protected ReflectionProperty $widgetsProperty;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		// Get reflection properties to reset static state
		$reflection = new ReflectionClass(WidgetsProvider::class);
		$this->foundAllProperty = $reflection->getProperty('foundAll');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$this->foundAllProperty->setAccessible(true);
		$this->widgetsProperty = $reflection->getProperty('widgets');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$this->widgetsProperty->setAccessible(true);

		// Reset static state before each test
		$this->foundAllProperty->setValue(null, false);
		$this->widgetsProperty->setValue(null, []);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		// Reset static state after each test
		$this->foundAllProperty->setValue(null, false);
		$this->widgetsProperty->setValue(null, []);

		parent::tearDown();
	}


	/**
	 * Test that WidgetsProvider cannot be instantiated
	 *
	 * @return void
	 * @see \Awyiss\Widget\WidgetsProvider::__construct()
	 * @throws \ReflectionException
	 */
	public function testCannotBeInstantiated(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The class `Awyiss\Widget\WidgetsProvider` cannot be instantiated');

		$reflection = new ReflectionClass(WidgetsProvider::class);
		$constructor = $reflection->getConstructor();
		/** @noinspection PhpExpressionResultUnusedInspection */
		$constructor->setAccessible(true);
		$constructor->invoke($reflection->newInstanceWithoutConstructor());
	}


	/**
	 * Test getWidgetFiles method finds all widgets
	 *
	 * @return void
	 * @see \Awyiss\Widget\WidgetsProvider::getWidgetFiles()
	 */
	public function testgetWidgetFilesFindsAllWidgets(): void {
		$result = WidgetsProvider::getWidgetFiles();

		$this->assertIsArray($result);
		$this->assertArrayHasKey('breadcrumbs', $result);
		$this->assertArrayHasKey('newsListing', $result);
		$this->assertArrayHasKey('instagramFeed', $result);
		$this->assertArrayHasKey('routePlanner', $result);
		$this->assertArrayHasKey('test', $result);

		// Verify that each value is a class string that implements WidgetInterface
		foreach ($result as $widgetClass) {
			$this->assertIsString($widgetClass);
			$this->assertTrue(class_exists($widgetClass));
			$this->assertTrue(in_array(WidgetInterface::class, class_implements($widgetClass)));
		}

		// The news listing widget must be a custom widget
		$this->assertSame('\Customer\Widget\NewsListingWidget', $result['newsListing']);

		// Test that foundAll flag is set after first call
		$this->assertTrue($this->foundAllProperty->getValue());

		// Test that subsequent calls don't trigger finding again (cached result)
		$secondResult = WidgetsProvider::getWidgetFiles();
		$this->assertSame($result, $secondResult);
	}


	/**
	 * Test getWidgetFile method with existing widget
	 *
	 * @return void
	 * @see \Awyiss\Widget\WidgetsProvider::getWidgetFile()
	 */
	public function testGetWidgetFileWithExistingWidget(): void {
		$result = WidgetsProvider::getWidgetFile('breadcrumbs');

		$this->assertNotNull($result);
		$this->assertIsString($result);
		$this->assertTrue(class_exists($result));
		$this->assertTrue(in_array(WidgetInterface::class, class_implements($result)));
		$this->assertSame('\Awyiss\Widget\BreadcrumbsWidget', $result);
	}


	/**
	 * Test getWidgetFile method with non-existing widget
	 *
	 * @return void
	 * @see \Awyiss\Widget\WidgetsProvider::getWidgetFile()
	 */
	public function testGetWidgetFileWithNonExistingWidget(): void {
		$result = WidgetsProvider::getWidgetFile('nonExistentWidget');

		$this->assertNull($result);
	}


	/**
	 * Test getWidgetFile method with identifier that needs sanitization
	 *
	 * @return void
	 * @see \Awyiss\Widget\WidgetsProvider::getWidgetFile()
	 */
	public function testGetWidgetFileWithIdentifierNeedingSanitization(): void {
		$result = WidgetsProvider::getWidgetFile('Instagram-Feed!@#');

		$this->assertNotNull($result);
		$this->assertSame('\Awyiss\Widget\InstagramFeedWidget', $result);
	}


	/**
	 * Test getWidgetFile method prefers custom widget over Awyiss widget
	 *
	 * @return void
	 * @see \Awyiss\Widget\WidgetsProvider::getWidgetFile()
	 */
	public function testGetWidgetFilePrefersCustomWidget(): void {
		$result = WidgetsProvider::getWidgetFile('newsListing');

		$this->assertNotNull($result);
		$this->assertSame('\Customer\Widget\NewsListingWidget', $result);
	}


	/**
	 * Test sanitizeIdentifier method with various inputs
	 *
	 * @param string $input
	 * @param string $expected
	 * @return void
	 * @see \Awyiss\Widget\WidgetsProvider::sanitizeIdentifier()
	 */
	#[DataProvider('sanitizeIdentifierDataProvider')]
	public function testSanitizeIdentifier(string $input, string $expected): void {
		$result = WidgetsProvider::sanitizeIdentifier($input);

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
			'with spaces' => ['news listing widget', 'newsListingWidget'],
			'with numbers' => ['widget123test', 'widget123test'],
			'with unicode chars' => ['widgétÜñîçødé', 'widgetUenicode'],
			'empty string' => ['', ''],
			'only special chars' => ['!@#$%', ''],
		];
	}
}
