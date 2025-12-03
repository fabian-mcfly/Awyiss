<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Design;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Design\WebfontProvider;
use Cake\Cache\Cache;
use ReflectionClass;


/**
 * Test case for WebfontProvider
 *
 * @see \Awyiss\Utility\Design\WebfontProvider
 */
class WebfontProviderTest extends TestCase {
	/**
	 * @var array<array{id: int, category: string, family: string, popularity: int, variants: array<int|string>, version: string}>
	 */
	protected array $dummyFonts = [
		[
			'id' => 1,
			'category' => 'sans-serif',
			'family' => 'Open Sans',
			'popularity' => 10,
			'subsets' => ['latin', 'latin-ext'],
			'variants' => [300, 'regular', 700],
			'version' => 'v48',
		],
		[
			'id' => 2,
			'category' => 'serif',
			'family' => 'Roboto',
			'popularity' => 20,
			'subsets' => ['latin', 'latin-ext'],
			'variants' => [400, 'italic', 700],
			'version' => 'v34',
		],
		[
			'id' => 3,
			'category' => 'display',
			'family' => 'Lobster',
			'popularity' => 5,
			'subsets' => ['latin', 'latin-ext'],
			'variants' => ['regular'],
			'version' => 'v12',
		],
		[
			'id' => 4,
			'category' => 'handwriting',
			'family' => 'Dancing Script',
			'popularity' => 1500,
			'subsets' => ['latin', 'latin-ext'],
			'variants' => ['regular', '700'],
			'version' => 'v18',
		],
		[
			'id' => 5,
			'category' => 'handwriting',
			'family' => 'Courier New',
			'popularity' => 800,
			'subsets' => ['vietnamese'],
			'variants' => ['regular', 'italic'],
			'version' => 'v20',
		],
	];
	/**
	 * @var \Awyiss\Utility\Design\WebfontProvider
	 */
	protected WebfontProvider $webfontProvider;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->webfontProvider = new WebfontProvider();

		// Use data stream wrapper to mock the API response
		$dataUrl = 'data://text/plain;base64,' . base64_encode(json_encode($this->dummyFonts));

		// Overwrite the fontApiUrl to a tmp URL for testing
		$reflection = new ReflectionClass($this->webfontProvider);
		$property = $reflection->getProperty('fontApiUrl');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($this->webfontProvider, $dataUrl);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$this->webfontProvider->clearCache();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\WebfontProvider::clearCache();
	 */
	public function testClearCache(): void {
		Cache::write('webfonts', 'Foobar', 'persistent');
		$this->assertSame('Foobar', Cache::read('webfonts', 'persistent'));

		$this->webfontProvider->clearCache();

		$this->assertEmpty(Cache::read('webfonts', 'persistent'));
	}


	/**
	 * @return void
	 */
	public function testGetWebfonts(): void {
		$this->webfontProvider->clearCache();

		$this->assertEmpty(Cache::read('webfonts', 'persistent'));

		// Fetch webfonts
		$webfonts = $this->webfontProvider->getWebfonts();

		// Check if the result is an array
		$this->assertIsArray($webfonts);

		// The webfonts should contain only fonts with popularity < 1000
		$this->assertCount(3, $webfonts);
		// The cached webfonts should contain only the fonts with 'latin' subset
		$this->assertCount(4, Cache::read('webfonts', 'persistent'));

		// Check if the webfonts are sorted by name
		$expectedOrder = ['Lobster', 'Open Sans', 'Roboto'];
		$actualOrder = array_column($webfonts, 'name');
		$this->assertSame($expectedOrder, $actualOrder);
	}
}
