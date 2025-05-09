<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\AssetHelper;
use Cake\Core\Configure;
use InvalidArgumentException;
use ReflectionClass;


/**
 * AssetHelperTest class
 */
class AssetHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\AssetHelper
	 */
	protected AssetHelper $helper;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function tearDownAfterClass(): void {
		$reflection = new ReflectionClass(BackendView::class);

		$property = $reflection->getProperty('twig');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null);

		$property = $reflection->getProperty('twigInitialized');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(false);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 * @throws \Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);

		Configure::write('debug', false);

		$view = new BackendView();

		$this->helper = new AssetHelper($view);
		$this->helper->clearAssets();
		$this->helper->setAutoMinify(false);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testGetAssets(): void {
		$this->assertEquals([
			'all' => [],
			'css' => [
				'critical' => [],
				'nonCritical' => [],
			],
			'cssLayer' => [],
			'font' => [
				'critical' => [],
				'nonCritical' => [],
			],
			'js' => [
				'critical' => [],
				'nonCritical' => [],
			],
		], $this->helper->getAssets());
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testGetNoScriptAssets(): void {
		$this->assertEquals([], $this->helper->getNoScriptAssets());
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testGetAutoMinify(): void {
		$this->assertFalse($this->helper->getAutoMinify());
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testSetAutoMinify(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->setAutoMinify(true);
		$this->assertTrue($this->helper->getAutoMinify());
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testGetRealm(): void {
		$this->assertEquals(Awyiss::REALM_BACKEND, $this->helper->getRealm());
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testSetRealm(): void {
		$this->helper->setRealm('newRealm');

		$this->assertEquals('newRealm', $this->helper->getRealm());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddCss(): void {
		$this->helper->add('dummy.css');

		$assets = $this->helper->getAssets();

		$testArray = [
			'dummy.css' => [
				'minified' => false,
				'critical' => false,
				'attributes' => [],
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEquals($testArray, $assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEmpty($assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddJs(): void {
		$this->helper->add('dummy.js');

		$assets = $this->helper->getAssets();

		$testArray = [
			'dummy.js' => [
				'minified' => false,
				'critical' => false,
				'attributes' => [],
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEmpty($assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEquals($testArray, $assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddFont(): void {
		$this->helper->add('2f_media-webfont.woff2');

		$assets = $this->helper->getAssets();

		$testArray = [
			'2f_media-webfont.woff2' => [
				'minified' => false,
				'critical' => false,
				'attributes' => [],
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEmpty($assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEquals($testArray, $assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEmpty($assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddGoogleFonts(): void {
		$url = 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lato:wght@300;400;700&display=swap';
		$this->helper->add($url);

		$assets = $this->helper->getAssets();

		$testArray = [
			$url => [
				'minified' => false,
				'critical' => false,
				'attributes' => [],
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEquals($testArray, $assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEmpty($assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddWithArray(): void {
		$this->helper->add(['dummy.css', 'dummy.js']);

		$assets = $this->helper->getAssets();

		$testArrayCss = [
			'dummy.css' => [
				'minified' => false,
				'critical' => false,
				'attributes' => [],
				'priority' => 10,
			],
		];

		$testArrayJs = [
			'dummy.js' => [
				'minified' => false,
				'critical' => false,
				'attributes' => [],
				'priority' => 10,
			],
		];

		$this->assertEquals($testArrayCss + $testArrayJs, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEquals($testArrayCss, $assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEquals($testArrayJs, $assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddWithArrayOptions(): void {
		$this->helper->add([
			'dummy.css' => [
				'async' => true,
				'minified' => true,
			],
			'dummy.js' => [
				'defer' => true,
				'minified' => false,
			],
		]);

		$assets = $this->helper->getAssets();

		$testArrayCss = [
			'dummy.css' => [
				'minified' => true,
				'critical' => false,
				'attributes' => ['async' => true],
				'priority' => 10,
			],
		];

		$testArrayJs = [
			'dummy.js' => [
				'minified' => false,
				'critical' => false,
				'attributes' => ['defer' => true],
				'priority' => 10,
			],
		];

		$this->assertEquals($testArrayCss + $testArrayJs, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEquals($testArrayCss, $assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEquals($testArrayJs, $assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddWithUnknownFileExtensionIgnoresWithDebugDisabled(): void {
		$this->helper->add('dummy.unknown');

		$assets = $this->helper->getAssets();

		$this->assertEquals([], $assets['all']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddWithUnknownFileExtensionThrowsExceptionWithDebugEnabled(): void {
		Configure::write('debug', true);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown asset type: `unknown`');

		$this->helper->add('dummy.unknown');
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddWithAutoMinify(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->setAutoMinify(true);

		$this->helper->add('dummy.js', [], false, null, 5);

		$assets = $this->helper->getAssets();

		$testArray = [
			'dummy.js' => [
				'minified' => true,
				'critical' => false,
				'attributes' => [],
				'priority' => 5,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEmpty($assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEquals($testArray, $assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddWithAttributes(): void {
		$this->helper->add('dummy.js', ['async' => true]);

		$assets = $this->helper->getAssets();

		$testArray = [
			'dummy.js' => [
				'minified' => false,
				'critical' => false,
				'attributes' => ['async' => true],
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEmpty($assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEquals($testArray, $assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddWithMinified(): void {
		$this->helper->add('dummy.js', [], false, true);

		$assets = $this->helper->getAssets();

		$testArray = [
			'dummy.js' => [
				'minified' => true,
				'critical' => false,
				'attributes' => [],
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEmpty($assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEquals($testArray, $assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddWithCritical(): void {
		$this->helper->add('dummy.js', [], true);

		$assets = $this->helper->getAssets();

		$testArray = [
			'dummy.js' => [
				'minified' => false,
				'critical' => true,
				'attributes' => [],
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEmpty($assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEquals($testArray, $assets['js']['critical']);
		$this->assertEmpty($assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddWithPriority(): void {
		$this->helper->add('dummy.js', [], false, false, 5);

		$assets = $this->helper->getAssets();

		$testArray = [
			'dummy.js' => [
				'minified' => false,
				'critical' => false,
				'attributes' => [],
				'priority' => 5,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEmpty($assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEquals($testArray, $assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddConsecutiveIgnoresDuplicate(): void {
		$this->helper->add('dummy.js');
		$this->helper->add('dummy.js', ['foo' => 'bar']);
		$this->helper->add('dummy.js', ['foo' => 'bar'], false, true, 5);

		$assets = $this->helper->getAssets();

		$testArray = [
			'dummy.js' => [
				'attributes' => [],
				'critical' => false,
				'minified' => false,
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets['all']);
		$this->assertEmpty($assets['css']['critical']);
		$this->assertEmpty($assets['css']['nonCritical']);
		$this->assertEmpty($assets['font']['critical']);
		$this->assertEmpty($assets['font']['nonCritical']);
		$this->assertEmpty($assets['js']['critical']);
		$this->assertEquals($testArray, $assets['js']['nonCritical']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddNoScriptCss(): void {
		$this->helper->addNoScriptAsset('dummy.css');

		$assets = $this->helper->getNoScriptAssets();

		$testArray = [
			'dummy.css' => [
				'attributes' => [],
				'critical' => false,
				'minified' => false,
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddNoScriptJsWillBeIgnored(): void {
		$this->helper->addNoScriptAsset('dummy.js');

		$assets = $this->helper->getNoScriptAssets();

		$this->assertEmpty($assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddNoScriptFontWillBeIgnored(): void {
		$this->helper->addNoScriptAsset('2f_media-webfont.woff2');

		$assets = $this->helper->getNoScriptAssets();

		$this->assertEmpty($assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddNoScriptGoogleFonts(): void {
		$url = 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lato:wght@300;400;700&display=swap';
		$this->helper->addNoScriptAsset($url);

		$assets = $this->helper->getNoScriptAssets();

		$testArray = [
			$url => [
				'attributes' => [],
				'critical' => false,
				'minified' => false,
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddNoScriptWithArray(): void {
		$this->helper->addNoScriptAsset(['dummy.css', 'dummy2.css']);

		$assets = $this->helper->getNoScriptAssets();

		$testArray = [
			'dummy.css' => [
				'attributes' => [],
				'critical' => false,
				'minified' => false,
				'priority' => 10,
			],
			'dummy2.css' => [
				'attributes' => [],
				'critical' => false,
				'minified' => false,
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddNoScriptWithArrayOptions(): void {
		$this->helper->addNoScriptAsset([
			'dummy.css' => [
				'async' => true,
				'minified' => true,
			],
			'dummy2.css' => [
				'defer' => true,
				'minified' => false,
			],
		]);

		$assets = $this->helper->getNoScriptAssets();

		$testArray = [
			'dummy.css' => [
				'attributes' => ['async' => true],
				'critical' => false,
				'minified' => true,
				'priority' => 10,
			],
			'dummy2.css' => [
				'attributes' => ['defer' => true],
				'critical' => false,
				'minified' => false,
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddNoScriptWithUnknownFileExtensionIgnoresWithDebugDisabled(): void {
		$this->helper->addNoScriptAsset('dummy.unknown');

		$assets = $this->helper->getNoScriptAssets();

		$this->assertEmpty($assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddNoScriptWithUnknownFileExtensionThrowsExceptionWithDebugEnabled(): void {
		Configure::write('debug', true);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown asset type: `unknown`');

		$this->helper->addNoScriptAsset('dummy.unknown');
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddNoScriptWithAutoMinify(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->setAutoMinify(true);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->addNoScriptAsset('dummy.css', ['async' => true], null);

		$assets = $this->helper->getNoScriptAssets();

		$testArray = [
			'dummy.css' => [
				'attributes' => ['async' => true],
				'critical' => false,
				'minified' => true,
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddNoScriptWithAttributes(): void {
		$this->helper->addNoScriptAsset('dummy.css', ['async' => true]);

		$assets = $this->helper->getNoScriptAssets();

		$testArray = [
			'dummy.css' => [
				'attributes' => ['async' => true],
				'critical' => false,
				'minified' => false,
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddNoScriptWithMinified(): void {
		$this->helper->addNoScriptAsset('dummy.css', [], true);

		$assets = $this->helper->getNoScriptAssets();

		$testArray = [
			'dummy.css' => [
				'attributes' => [],
				'critical' => false,
				'minified' => true,
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets);
	}



	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddNoScriptWithPriority(): void {
		$this->helper->addNoScriptAsset('dummy.css', [], false, 5);

		$assets = $this->helper->getNoScriptAssets();

		$testArray = [
			'dummy.css' => [
				'attributes' => [],
				'critical' => false,
				'minified' => false,
				'priority' => 5,
			],
		];

		$this->assertEquals($testArray, $assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddNoScriptConsecutiveIgnoresDuplicate(): void {
		$this->helper->addNoScriptAsset('dummy.css');
		$this->helper->addNoScriptAsset('dummy.css', ['foo' => 'bar']);
		$this->helper->addNoScriptAsset('dummy.css', ['foo' => 'bar'], true, 5);

		$assets = $this->helper->getNoScriptAssets();

		$testArray = [
			'dummy.css' => [
				'attributes' => [],
				'critical' => false,
				'minified' => false,
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $assets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemove(): void {
		$this->helper->add('dummy.css');
		$this->helper->addNoScriptAsset('dummy.css');

		$assets = $this->helper->getAssets();
		$noScriptAssets = $this->helper->getNoScriptAssets();

		$this->assertArrayHasKey('dummy.css', $assets['all']);
		$this->assertArrayHasKey('dummy.css', $noScriptAssets);

		$this->helper->remove('dummy.css');

		$assets = $this->helper->getAssets();
		$noScriptAssets = $this->helper->getNoScriptAssets();

		$this->assertEmpty($assets['all']);
		$this->assertEmpty($noScriptAssets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveJs(): void {
		$this->helper->add('dummy.js');

		$assets = $this->helper->getAssets();

		$this->assertArrayHasKey('dummy.js', $assets['all']);

		$this->helper->remove('dummy.js');

		$assets = $this->helper->getAssets();

		$this->assertEmpty($assets['all']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveFont(): void {
		$this->helper->add('2f_media-webfont.woff2');

		$assets = $this->helper->getAssets();

		$this->assertArrayHasKey('2f_media-webfont.woff2', $assets['all']);

		$this->helper->remove('2f_media-webfont.woff2');

		$assets = $this->helper->getAssets();

		$this->assertEmpty($assets['all']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveWithArray(): void {
		$this->helper->add(['dummy.css', 'dummy.js']);

		$assets = $this->helper->getAssets();

		$this->assertArrayHasKey('dummy.css', $assets['all']);
		$this->assertArrayHasKey('dummy.js', $assets['all']);

		$this->helper->remove(['dummy.css']);

		$assets = $this->helper->getAssets();

		$this->assertArrayNotHasKey('dummy.css', $assets['all']);
		$this->assertArrayHasKey('dummy.js', $assets['all']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveNonExistentAsset(): void {
		$this->helper->add('dummy.css');
		$this->helper->remove('nonexistent.css');

		$assets = $this->helper->getAssets();

		$this->assertArrayHasKey('dummy.css', $assets['all']);
		$this->assertArrayNotHasKey('nonexistent.css', $assets['all']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAssetTagForFont(): void {
		$assetPath = 'assets/fonts/dummy.woff';

		$result = $this->helper->createAssetTag($assetPath, []);

		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<link rel="preload" href="assets/fonts/dummy.woff" as="font" type="font/woff" crossorigin>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAssetTagForJs(): void {
		$assetPath = 'assets/js/dummy.js';

		$result = $this->helper->createAssetTag($assetPath, []);

		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<script async src="assets/js/dummy.js">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAssetTagForCriticalJs(): void {
		$assetPath = 'assets/js/dummy.js';

		$result = $this->helper->createAssetTag($assetPath, ['critical' => true]);

		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<script defer src="assets/js/dummy.js">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownTarget
	 */
	public function testCreateAssetTagForCss(): void {
		$assetPath = 'assets/css/dummy.css';

		$result = $this->helper->createAssetTag($assetPath, []);

		$this->assertStringContainsString('<link rel="preload" href="assets/css/dummy.css" as="style" data-lazyload="true">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAssetTagForCriticalCss(): void {
		$assetPath = 'assets/css/dummy.css';

		$result = $this->helper->createAssetTag($assetPath, ['critical' => true]);

		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="assets/css/dummy.css">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAssetTagWithNonce(): void {
		$assetPath = 'assets/js/dummy.js';

		$view = $this->helper->getView();
		$request = $view->getRequest()->withAttribute('cspScriptNonce', 'test-nonce');
		$view->setRequest($request);

		$result = $this->helper->createAssetTag($assetPath, []);

		$this->assertStringContainsString('nonce="test-nonce"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAssetTagForFontWithAttributes(): void {
		$assetPath = 'assets/fonts/dummy.woff';

		$result = $this->helper->createAssetTag($assetPath, ['attributes' => ['data-custom' => 'value']]);

		$this->assertStringContainsString('data-custom="value"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAssetTagForJsWithAttributes(): void {
		$assetPath = 'assets/js/dummy.js';

		$result = $this->helper->createAssetTag($assetPath, ['attributes' => ['data-custom' => 'value']]);

		$this->assertStringContainsString('data-custom="value"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testCreateAssetTagForCriticalJsWithAttributes(): void {
		$assetPath = 'assets/js/dummy.js';

		$result = $this->helper->createAssetTag($assetPath, ['critical' => true, 'attributes' => ['data-custom' => 'value']]);

		$this->assertStringContainsString('<script', $result);
		$this->assertStringNotContainsString('async', $result);
		$this->assertStringContainsString('data-custom="value"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAssetTagForCssWithAttributes(): void {
		$assetPath = 'assets/css/dummy.css';

		$result = $this->helper->createAssetTag($assetPath, ['attributes' => ['data-custom' => 'value']]);

		$this->assertStringContainsString('rel="preload"', $result);
		$this->assertStringContainsString('as="style"', $result);
		$this->assertStringContainsString('data-custom="value"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testCreateAssetTagForCriticalCssWithAttributes(): void {
		$assetPath = 'assets/css/dummy.css';

		$result = $this->helper->createAssetTag($assetPath, ['critical' => true, 'attributes' => ['data-custom' => 'value']]);

		$this->assertStringContainsString('rel="stylesheet"', $result);
		$this->assertStringNotContainsString('data-lazyload', $result);
		$this->assertStringContainsString('data-custom="value"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAssetTagWithNonceAndAttributes(): void {
		$assetPath = 'assets/js/dummy.js';

		$view = $this->helper->getView();
		$request = $view->getRequest()->withAttribute('cspScriptNonce', 'test-nonce');
		$view->setRequest($request);

		$result = $this->helper->createAssetTag($assetPath, ['attributes' => ['data-custom' => 'value']]);

		$this->assertStringContainsString('nonce="test-nonce"', $result);
		$this->assertStringContainsString('data-custom="value"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTagsAllAssets(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->add('dummy.css', [], false);
		$this->helper->add('dummy.js', [], true);

		$result = $this->helper->getTags();

		$fileMTimeCss = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css');
		$fileMTimeJs = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/js/dummy.js');

		$this->assertStringContainsString('<link rel="preload"', $result);
		$this->assertStringContainsString('href="http://localhost/assets/awyiss/css/dummy.' . $fileMTimeCss . '.css"', $result);
		$this->assertStringContainsString('as="style"', $result);
		$this->assertStringContainsString('data-lazyload="true"', $result);
		$this->assertStringContainsString('<noscript><link rel="stylesheet"', $result);

		$this->assertStringContainsString('<script', $result);
		$this->assertStringNotContainsString('async', $result);
		$this->assertStringContainsString('src="http://localhost/assets/awyiss/js/dummy.' . $fileMTimeJs . '.js"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTagsAllCriticalAssets(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->add('dummy.css', [], false);
		$this->helper->add('dummy.js', [], true);

		$result = $this->helper->getTags('all', true);

		$fileMTimeJs = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/js/dummy.js');

		$this->assertStringNotContainsString('<link rel="stylesheet"', $result);
		$this->assertStringNotContainsString('<link rel="preload"', $result);
		$this->assertStringNotContainsString('<noscript><link rel="stylesheet"', $result);

		$this->assertStringContainsString('<script', $result);
		$this->assertStringNotContainsString('async', $result);
		$this->assertStringContainsString('src="http://localhost/assets/awyiss/js/dummy.' . $fileMTimeJs . '.js"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTagsAllNonCriticalAssets(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->add('dummy.css', [], false);
		$this->helper->add('dummy.js', [], true);

		$result = $this->helper->getTags('all', false);

		$fileMTimeCss = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css');
		$fileMTimeJs = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/js/dummy.js');

		$this->assertStringContainsString('<link rel="preload"', $result);
		$this->assertStringContainsString('href="http://localhost/assets/awyiss/css/dummy.' . $fileMTimeCss . '.css"', $result);
		$this->assertStringContainsString('as="style"', $result);
		$this->assertStringContainsString('data-lazyload="true"', $result);
		$this->assertStringContainsString('<noscript><link rel="stylesheet"', $result);

		$this->assertStringNotContainsString('async', $result);
		$this->assertStringNotContainsString('src="http://localhost/assets/awyiss/js/dummy.' . $fileMTimeJs . '.js"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetTagsAllNonCriticalAssetsWithoutNoScript(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->add('dummy.css', [], false);
		$this->helper->add('dummy.js', [], true);

		$result = $this->helper->getTags('all', false, false);

		$fileMTimeCss = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css');
		$fileMTimeJs = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/js/dummy.js');

		$this->assertStringContainsString('<link rel="preload"', $result);
		$this->assertStringContainsString('href="http://localhost/assets/awyiss/css/dummy.' . $fileMTimeCss . '.css"', $result);
		$this->assertStringContainsString('as="style"', $result);
		$this->assertStringContainsString('data-lazyload="true"', $result);
		$this->assertStringNotContainsString('<noscript><link rel="stylesheet"', $result);

		$this->assertStringNotContainsString('async', $result);
		$this->assertStringNotContainsString('src="http://localhost/assets/awyiss/js/dummy.' . $fileMTimeJs . '.js"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTagsByType(): void {
		$this->helper->add('dummy.css', [], true);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->add('dummy.js', [], false);

		$result = $this->helper->getTags('css');

		$fileMTimeCss = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css');
		$fileMTimeJs = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/js/dummy.js');

		$this->assertStringContainsString('<link rel="stylesheet"', $result);
		$this->assertStringNotContainsString('<link rel="preload"', $result);
		$this->assertStringContainsString('href="http://localhost/assets/awyiss/css/dummy.' . $fileMTimeCss . '.css"', $result);
		$this->assertStringNotContainsString('<noscript><link rel="stylesheet"', $result);

		$this->assertStringNotContainsString('<script', $result);
		$this->assertStringNotContainsString('src="http://localhost/assets/awyiss/js/dummy.' . $fileMTimeJs . '.js"', $result);

		$result = $this->helper->getTags('js');
		$this->assertStringNotContainsString('href="http://localhost/assets/awyiss/css/dummy.' . $fileMTimeCss . '.css"', $result);
		$this->assertStringContainsString('src="http://localhost/assets/awyiss/js/dummy.' . $fileMTimeJs . '.js"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTagsWithNonce(): void {
		$view = $this->helper->getView();
		$request = $view->getRequest()->withAttribute('cspScriptNonce', 'test-js-nonce');
		$request = $request->withAttribute('cspStyleNonce', 'test-css-nonce');
		$view->setRequest($request);

		$this->helper->add(['dummy.js', 'dummy.css']);

		$result = $this->helper->getTags();

		$this->assertStringContainsString('nonce="test-js-nonce"', $result);
		$this->assertStringContainsString('nonce="test-css-nonce"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTagsContainsMinified(): void {
		$this->helper->add('dummy.css', [], false, true);

		$result = $this->helper->getTags();

		$fileMTimeCss = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');

		$this->assertStringContainsString('dummy.min.' . $fileMTimeCss . '.css', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTagsSortedByPriority(): void {
		$this->helper->add('dummy2.css', [], false, null, 5);
		$this->helper->add('dummy.css', [], false, null, 20);

		$result = $this->helper->getTags();

		$this->assertTrue(strpos($result, 'dummy.') < strpos($result, 'dummy2.'));
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTagsWithNonExistentFile(): void {
		$this->helper->add('nonexistent.css');

		$result = $this->helper->getTags();

		$this->assertStringNotContainsString('nonexistent.', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTagsWithoutTimestamp(): void {
		$this->helper->add('dummy.css', ['includeTimestamp' => false]);

		$result = $this->helper->getTags();

		$this->assertStringContainsString('dummy.css', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNoScriptTags(): void {
		$this->helper->addNoScriptAsset('dummy.css');

		$tags = $this->helper->getNoScriptTags();

		$this->assertStringContainsString('<noscript>', $tags);
		$this->assertStringContainsString('<link rel="stylesheet"', $tags);
		$this->assertStringNotContainsString('<link rel="preload"', $tags);
		$this->assertStringNotContainsString('.min.', $tags);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNoScriptTagsContainsMinified(): void {
		$this->helper->addNoScriptAsset('dummy.css', [], true);

		$tags = $this->helper->getNoScriptTags();

		$this->assertStringContainsString('<noscript>', $tags);
		$this->assertStringContainsString('<link rel="stylesheet"', $tags);
		$this->assertStringNotContainsString('<link rel="preload"', $tags);
		$this->assertStringContainsString('.min.', $tags);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNoScriptTagsWithNonExistentFile(): void {
		$this->helper->addNoScriptAsset('nonexistent.css');

		$tags = $this->helper->getNoScriptTags();

		$this->assertSame('', $tags);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNoScriptTagsWithNonce(): void {
		$view = $this->helper->getView();
		$request = $view->getRequest()->withAttribute('cspStyleNonce', 'test-nonce');
		$view->setRequest($request);

		$this->helper->addNoScriptAsset('dummy.css');

		$tags = $this->helper->getNoScriptTags();

		$this->assertStringContainsString('nonce="test-nonce"', $tags);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNoScriptTagsWithAttributes(): void {
		$this->helper->addNoScriptAsset('dummy.css', ['async' => true]);

		$tags = $this->helper->getNoScriptTags();

		$this->assertStringContainsString('async', $tags);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNoScriptTagsWithoutTimestamp(): void {
		$this->helper->addNoScriptAsset('dummy.css', ['includeTimestamp' => false]);

		$tags = $this->helper->getNoScriptTags();

		$this->assertStringContainsString('dummy.css', $tags);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNoScriptTagsWithNonCss(): void {
		$this->helper->addNoScriptAsset('dummy.js');

		$tags = $this->helper->getNoScriptTags();

		$this->assertSame('', $tags);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInlineStyles(): void {
		$styles = $this->helper->inlineStyles('dummy.css');

		$this->assertStringContainsString('<style', $styles);
		$this->assertStringContainsString('* Dummy CSS file', $styles);
		$this->assertStringContainsString('</style', $styles);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInlineStylesWithOptions(): void {
		$styles = $this->helper->inlineStyles('dummy.css', ['strReplace' => ['Dummy' => 'Test']]);

		$this->assertStringContainsString('<style', $styles);
		$this->assertStringContainsString('* Test CSS file', $styles);
		$this->assertStringContainsString('</style', $styles);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInlineStylesWithNonExistentFile(): void {
		$styles = $this->helper->inlineStyles('nonexistent.css');

		$this->assertSame('', $styles);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInlineStylesWithNonce(): void {
		$view = $this->helper->getView();
		$request = $view->getRequest()->withAttribute('cspStyleNonce', 'test-nonce');
		$view->setRequest($request);

		$styles = $this->helper->inlineStyles('dummy.css');

		$this->assertStringContainsString('nonce="test-nonce"', $styles);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddJsModule(): void {
		$this->helper->addJsModule('dummy.js');

		$this->assertArrayHasKey('dummy.js', $this->helper->getJsModules());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddJsModuleWithConsecutive(): void {
		$this->helper->addJsModule('dummy.js');
		$this->helper->addJsModule('dummy.js');

		$this->assertCount(1, $this->helper->getJsModules());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddJsModuleAsArray(): void {
		$this->helper->addJsModule(['dummy.js']);

		$this->assertArrayHasKey('dummy.js', $this->helper->getJsModules());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddJsModuleAsArrayOfAttributes(): void {
		$this->helper->addJsModule(['dummy.js' => ['minified' => true]]);

		$modules = $this->helper->getJsModules();

		$this->assertArrayHasKey('dummy.js', $modules);
		$this->assertTrue($modules['dummy.js']['minified']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddJsModuleWithMinified(): void {
		$this->helper->addJsModule('dummy.js', true);

		$modules = $this->helper->getJsModules();
		$this->assertArrayHasKey('dummy.js', $modules);

		$this->assertTrue($modules['dummy.js']['minified']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveJsModule(): void {
		$this->helper->addJsModule('dummy.js');

		$this->assertArrayHasKey('dummy.js', $this->helper->getJsModules());

		$this->helper->removeJsModule('dummy.js');

		$this->assertArrayNotHasKey('dummy.js', $this->helper->getJsModules());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateImportMap(): void {
		$this->helper->addJsModule('dummy.js');

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script type="importmap">', $importMap);
		$this->assertStringContainsString('{"imports":{"dummy":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.1', $importMap);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateImportMapForNonExistentFile(): void {
		$this->helper->addJsModule('module.js');

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('{"imports":{"module":null', $importMap);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateImportMapWithoutScriptTag(): void {
		$this->helper->addJsModule('dummy.js');

		$importMap = $this->helper->createImportMap(false);

		$this->assertStringNotContainsString('<script', $importMap);
		$this->assertStringContainsString('{"imports":{"dummy":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.1', $importMap);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateImportMapWithMinified(): void {
		$this->helper->addJsModule('dummy.js', true);

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script', $importMap);
		$this->assertStringContainsString('{"imports":{"dummy":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.min', $importMap);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFinalAssets(): void {
		$this->helper->add('dummy.css');

		$finalAssets = $this->helper->getFinalAssets();

		$fileMTime = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css');

		$testArray = [
			'dummy.css' => [
				'attributes' => [],
				'critical' => false,
				'minified' => false,
				'path' => 'http://localhost/assets/awyiss/css/dummy.' . $fileMTime . '.css',
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $finalAssets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFinalAssetsWithMinified(): void {
		$this->helper->add('dummy.css', [], false, true);

		$finalAssets = $this->helper->getFinalAssets();

		$fileMTime = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');

		$testArray = [
			'dummy.css' => [
				'attributes' => [],
				'critical' => false,
				'minified' => true,
				'path' => 'http://localhost/assets/awyiss/css/dummy.min.' . $fileMTime . '.css',
				'priority' => 10,
			],
		];

		$this->assertEquals($testArray, $finalAssets);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetFinalAssetsNotContainsNonExistentFile(): void {
		$this->helper->add(['dummy.js', 'nonexistent.css']);

		$finalAssets = $this->helper->getFinalAssets();

		$this->assertEquals(1, count($finalAssets));
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFinalAssetsSortedByPriority(): void {
		$this->helper->add('dummy2.css', [], false, false, 5);
		$this->helper->add('dummy.css', [], false, false, 20);

		$finalAssets = $this->helper->getFinalAssets();

		$this->assertSame(['dummy.css', 'dummy2.css'], array_keys($finalAssets));
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetScriptNonce(): void {
		$nonce = 'test-nonce';

		$view = $this->helper->getView();
		$request = $view->getRequest()->withAttribute('cspScriptNonce', $nonce);
		$view->setRequest($request);

		$this->assertEquals($nonce, $this->helper->getScriptNonce());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetScriptNonceWhenNotSet(): void {
		$this->assertNull($this->helper->getScriptNonce());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetStyleNonce(): void {
		$nonce = 'test-nonce';

		$view = $this->helper->getView();
		$request = $view->getRequest()->withAttribute('cspStyleNonce', $nonce);
		$view->setRequest($request);

		$this->assertEquals($nonce, $this->helper->getStyleNonce());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetStyleNonceWhenNotSet(): void {
		$this->assertNull($this->helper->getStyleNonce());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAssetPath(): void {
		$fileMTime = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css');

		$path = $this->helper->getAssetPath('dummy.css');

		$this->assertSame('http://localhost/assets/awyiss/css/dummy.' . $fileMTime . '.css', $path);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAssetPathMinifiesFileIfNotExist(): void {
		$filePath = ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy2.min.css';
		if (file_exists($filePath)) {
			unlink($filePath);
		}

		$path = $this->helper->getAssetPath('dummy2.css', ['minified' => true]);

		$this->assertStringContainsString('dummy2.min.' . time() . '.css', $path);
		$this->assertFileExists($filePath);
		$this->assertSame('.foo{color:red}', file_get_contents($filePath));

		unlink($filePath);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAssetPathAcceptsRealm(): void {
		$path = $this->helper->getAssetPath('dummy_webfont.css');
		$this->assertNull($path);

		$fileMTime = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/css/dummy_webfont.css');

		$path = $this->helper->getAssetPath('dummy_webfont.css', ['realm' => Awyiss::REALM_FRONTEND]);
		$this->assertEquals('http://localhost/assets/css/dummy_webfont.' . $fileMTime . '.css', $path);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAssetPathWithoutTimestamp(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['includeTimestamp' => false]);

		$this->assertSame('http://localhost/assets/awyiss/css/dummy.css', $path);
	}

	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAssetPathWithLocalPath(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['localPath' => true]);

		$this->assertSame(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css', $path);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAssetPathWithMinified(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['minified' => true]);

		$this->assertStringContainsString('dummy.min.', $path);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetAssetPathWithMinifiedWithoutTimestamp(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['minified' => true, 'includeTimestamp' => false]);

		$this->assertStringContainsString('dummy.min.css', $path);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAssetPathWithMinifiedAndLocalPath(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['minified' => true, 'localPath' => true]);

		$this->assertSame(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css', $path);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterLayout(): void {
		$this->helper->add('dummy.css');
		$this->helper->afterLayout();

		$response = $this->helper->getView()->getResponse();

		$this->assertTrue($response->hasHeader('Link'));

		$fileMTime = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css');

		$this->assertContains('Link: <http://localhost/assets/awyiss/css/dummy.' . $fileMTime . '.css>; rel=preload; as=style; nopush', $response->getHeader('Link'));
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterLayoutWithMinified(): void {
		$this->helper->add('dummy.css', [], false, true);
		$this->helper->afterLayout();

		$response = $this->helper->getView()->getResponse();

		$this->assertTrue($response->hasHeader('Link'));

		$fileMTime = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');

		$this->assertContains('Link: <http://localhost/assets/awyiss/css/dummy.min.' . $fileMTime . '.css>; rel=preload; as=style; nopush', $response->getHeader('Link'));
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterLayoutWithMultipleAssets(): void {
		$this->helper->add('dummy.css');
		$this->helper->add('dummy.js');

		$this->helper->afterLayout();

		$fileMTimeCss = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css');
		$fileMTimeJs = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/js/dummy.js');

		$response = $this->helper->getView()->getResponse();

		$this->assertTrue($response->hasHeader('Link'));

		$this->assertStringContainsString('Link: <http://localhost/assets/awyiss/css/dummy.' . $fileMTimeCss . '.css>; rel=preload; as=style; nopush,', $response->getHeader('Link')[0]);
		$this->assertStringContainsString('Link: <http://localhost/assets/awyiss/js/dummy.' . $fileMTimeJs . '.js>; rel=preload; as=script; nopush', $response->getHeader('Link')[0]);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterLayoutWithNoAssets(): void {
		$this->helper->afterLayout();

		$response = $this->helper->getView()->getResponse();

		$this->assertFalse($response->hasHeader('Link'));
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterLayoutWithNonExistentFile(): void {
		$this->helper->add('nonexistent.css');

		$this->helper->afterLayout();

		$response = $this->helper->getView()->getResponse();

		$this->assertFalse($response->hasHeader('Link'));
	}
}
