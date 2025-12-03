<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\AssetHelper;
use Cake\Core\Configure;
use InvalidArgumentException;


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
	 * @throws \Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		Configure::write('debug', false);

		$view = new BackendView();

		$this->helper = new AssetHelper($view);
		$this->helper->clearAssets();
		$this->helper->setAutoMinify(false);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssets()
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
	 * @see \Awyiss\View\Helper\AssetHelper::getNoScriptAssets()
	 * @throws \Exception
	 */
	public function testGetNoScriptAssets(): void {
		$this->assertEquals([], $this->helper->getNoScriptAssets());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAutoMinify()
	 * @throws \Exception
	 */
	public function testGetAutoMinify(): void {
		$this->assertFalse($this->helper->getAutoMinify());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::setAutoMinify()
	 * @throws \Exception
	 */
	public function testSetAutoMinify(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->setAutoMinify(true);
		$this->assertTrue($this->helper->getAutoMinify());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getRealm()
	 * @throws \Exception
	 */
	public function testGetRealm(): void {
		$this->assertEquals(Awyiss::REALM_BACKEND, $this->helper->getRealm());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::setRealm()
	 * @throws \Exception
	 */
	public function testSetRealm(): void {
		$this->helper->setRealm('newRealm');

		$this->assertEquals('newRealm', $this->helper->getRealm());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
	 */
	public function testAddWithUnknownFileExtensionIgnoresWithDebugDisabled(): void {
		$this->helper->add('dummy.unknown');

		$assets = $this->helper->getAssets();

		$this->assertEquals([], $assets['all']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
	 */
	public function testAddWithUnknownFileExtensionThrowsExceptionWithDebugEnabled(): void {
		Configure::write('debug', true);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown asset type: `unknown`');

		$this->helper->add('dummy.unknown');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::add()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
	 */
	public function testAddNoScriptJsWillBeIgnored(): void {
		$this->helper->addNoScriptAsset('dummy.js');

		$assets = $this->helper->getNoScriptAssets();

		$this->assertEmpty($assets);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
	 */
	public function testAddNoScriptFontWillBeIgnored(): void {
		$this->helper->addNoScriptAsset('2f_media-webfont.woff2');

		$assets = $this->helper->getNoScriptAssets();

		$this->assertEmpty($assets);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
	 */
	public function testAddNoScriptWithUnknownFileExtensionIgnoresWithDebugDisabled(): void {
		$this->helper->addNoScriptAsset('dummy.unknown');

		$assets = $this->helper->getNoScriptAssets();

		$this->assertEmpty($assets);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
	 */
	public function testAddNoScriptWithUnknownFileExtensionThrowsExceptionWithDebugEnabled(): void {
		Configure::write('debug', true);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown asset type: `unknown`');

		$this->helper->addNoScriptAsset('dummy.unknown');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addNoScriptAsset()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::remove()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::remove()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::remove()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::remove()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::remove()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
	 */
	public function testCreateAssetTagForFont(): void {
		$assetPath = 'assets/fonts/dummy.woff';

		$result = $this->helper->createAssetTag($assetPath, []);

		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<link rel="preload" href="assets/fonts/dummy.woff" as="font" type="font/woff" crossorigin>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
	 */
	public function testCreateAssetTagForJs(): void {
		$assetPath = 'assets/js/dummy.js';

		$result = $this->helper->createAssetTag($assetPath, []);

		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<script async src="assets/js/dummy.js">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
	 */
	public function testCreateAssetTagForCriticalJs(): void {
		$assetPath = 'assets/js/dummy.js';

		$result = $this->helper->createAssetTag($assetPath, ['critical' => true]);

		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<script defer src="assets/js/dummy.js">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
	 * @noinspection HtmlUnknownTarget
	 */
	public function testCreateAssetTagForCss(): void {
		$assetPath = 'assets/css/dummy.css';

		$result = $this->helper->createAssetTag($assetPath, []);

		$this->assertStringContainsString('<link rel="preload" href="assets/css/dummy.css" as="style" data-lazyload="true">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
	 */
	public function testCreateAssetTagForCriticalCss(): void {
		$assetPath = 'assets/css/dummy.css';

		$result = $this->helper->createAssetTag($assetPath, ['critical' => true]);

		/** @noinspection HtmlUnknownTarget */
		$this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="assets/css/dummy.css">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
	 */
	public function testCreateAssetTagForFontWithAttributes(): void {
		$assetPath = 'assets/fonts/dummy.woff';

		$result = $this->helper->createAssetTag($assetPath, ['attributes' => ['data-custom' => 'value']]);

		$this->assertStringContainsString('data-custom="value"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
	 */
	public function testCreateAssetTagForJsWithAttributes(): void {
		$assetPath = 'assets/js/dummy.js';

		$result = $this->helper->createAssetTag($assetPath, ['attributes' => ['data-custom' => 'value']]);

		$this->assertStringContainsString('data-custom="value"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::createAssetTag()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
	 */
	public function testGetTagsContainsMinified(): void {
		$this->helper->add('dummy.css', [], false, true);

		$result = $this->helper->getTags();

		$fileMTimeCss = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');

		$this->assertStringContainsString('dummy.min.' . $fileMTimeCss . '.css', $result);

		unlink(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
	 */
	public function testGetTagsSortedByPriority(): void {
		$this->helper->add('dummy2.css', [], false, null, 5);
		$this->helper->add('dummy.css', [], false, null, 20);

		$result = $this->helper->getTags();

		$this->assertTrue(strpos($result, 'dummy.') < strpos($result, 'dummy2.'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
	 */
	public function testGetTagsWithNonExistentFile(): void {
		$this->helper->add('nonexistent.css');

		$result = $this->helper->getTags();

		$this->assertStringNotContainsString('nonexistent.', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getTags()
	 * @throws \Exception
	 */
	public function testGetTagsWithoutTimestamp(): void {
		$this->helper->add('dummy.css', ['includeTimestamp' => false]);

		$result = $this->helper->getTags();

		$this->assertStringContainsString('dummy.css', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getNoScriptTags()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getNoScriptTags()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getNoScriptTags()
	 * @throws \Exception
	 */
	public function testGetNoScriptTagsWithNonExistentFile(): void {
		$this->helper->addNoScriptAsset('nonexistent.css');

		$tags = $this->helper->getNoScriptTags();

		$this->assertSame('', $tags);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getNoScriptTags()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getNoScriptTags()
	 * @throws \Exception
	 */
	public function testGetNoScriptTagsWithAttributes(): void {
		$this->helper->addNoScriptAsset('dummy.css', ['async' => true]);

		$tags = $this->helper->getNoScriptTags();

		$this->assertStringContainsString('async', $tags);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getNoScriptTags()
	 * @throws \Exception
	 */
	public function testGetNoScriptTagsWithoutTimestamp(): void {
		$this->helper->addNoScriptAsset('dummy.css', ['includeTimestamp' => false]);

		$tags = $this->helper->getNoScriptTags();

		$this->assertStringContainsString('dummy.css', $tags);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getNoScriptTags()
	 * @throws \Exception
	 */
	public function testGetNoScriptTagsWithNonCss(): void {
		$this->helper->addNoScriptAsset('dummy.js');

		$tags = $this->helper->getNoScriptTags();

		$this->assertSame('', $tags);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::inlineStyles()
	 * @throws \Exception
	 */
	public function testInlineStyles(): void {
		$styles = $this->helper->inlineStyles('dummy.css');

		$this->assertStringContainsString('<style', $styles);
		$this->assertStringContainsString('* Dummy CSS file', $styles);
		$this->assertStringContainsString('</style', $styles);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::inlineStyles()
	 * @throws \Exception
	 */
	public function testInlineStylesWithOptions(): void {
		$styles = $this->helper->inlineStyles('dummy.css', ['strReplace' => ['Dummy' => 'Test']]);

		$this->assertStringContainsString('<style', $styles);
		$this->assertStringContainsString('* Test CSS file', $styles);
		$this->assertStringContainsString('</style', $styles);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::inlineStyles()
	 * @throws \Exception
	 */
	public function testInlineStylesWithNonExistentFile(): void {
		$styles = $this->helper->inlineStyles('nonexistent.css');

		$this->assertSame('', $styles);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::inlineStyles()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::addJsModule()
	 * @throws \Exception
	 */
	public function testAddJsModule(): void {
		$this->helper->addJsModule('dummy.js');

		$this->assertArrayHasKey('dummy.js', $this->helper->getJsModules());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addJsModule()
	 * @throws \Exception
	 */
	public function testAddJsModuleWithConsecutive(): void {
		$this->helper->addJsModule('dummy.js');
		$this->helper->addJsModule('dummy.js');

		$this->assertCount(1, $this->helper->getJsModules());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addJsModule()
	 * @throws \Exception
	 */
	public function testAddJsModuleAsArray(): void {
		$this->helper->addJsModule(['dummy.js']);

		$this->assertArrayHasKey('dummy.js', $this->helper->getJsModules());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addJsModule()
	 * @throws \Exception
	 */
	public function testAddJsModuleAsArrayOfAttributes(): void {
		$this->helper->addJsModule(['dummy.js' => ['minified' => true]]);

		$modules = $this->helper->getJsModules();

		$this->assertArrayHasKey('dummy.js', $modules);
		$this->assertTrue($modules['dummy.js']['minified']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addJsModule()
	 * @throws \Exception
	 */
	public function testAddJsModuleWithAs(): void {
		$this->helper->addJsModule(['dummy.js' => ['as' => 'DifferentName']]);

		$modules = $this->helper->getJsModules();

		$this->assertArrayHasKey('dummy.js', $modules);
		$this->assertSame('DifferentName', $modules['dummy.js']['as']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addJsModule()
	 * @throws \Exception
	 */
	public function testAddJsModuleWithRealm(): void {
		$this->helper->addJsModule(['dummy.js' => ['realm' => Awyiss::REALM_FRONTEND]]);

		$modules = $this->helper->getJsModules();

		$this->assertArrayHasKey('dummy.js', $modules);
		$this->assertSame(Awyiss::REALM_FRONTEND, $modules['dummy.js']['realm']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addJsModule()
	 * @throws \Exception
	 */
	public function testAddJsModuleUsesAutoMinify(): void {
		$this->helper->setAutoMinify(false);

		$this->helper->addJsModule('dummy.js');

		$modules = $this->helper->getJsModules();
		$this->assertArrayHasKey('dummy.js', $modules);

		$this->assertFalse($modules['dummy.js']['minified']);

		$this->helper->removeJsModule('dummy.js');

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->helper->setAutoMinify(true);

		$this->helper->addJsModule('dummy.js');

		$modules = $this->helper->getJsModules();
		$this->assertArrayHasKey('dummy.js', $modules);

		$this->assertTrue($modules['dummy.js']['minified']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addJsModule()
	 * @throws \Exception
	 */
	public function testAddJsModuleWithMinifiedTrue(): void {
		$this->helper->addJsModule('dummy.js', true);

		$modules = $this->helper->getJsModules();
		$this->assertArrayHasKey('dummy.js', $modules);

		$this->assertTrue($modules['dummy.js']['minified']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addJsModule()
	 * @throws \Exception
	 */
	public function testAddJsModuleWithMinifiedFalse(): void {
		$this->helper->addJsModule('dummy.js', false);

		$modules = $this->helper->getJsModules();
		$this->assertArrayHasKey('dummy.js', $modules);

		$this->assertFalse($modules['dummy.js']['minified']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::removeJsModule()
	 * @throws \Exception
	 */
	public function testRemoveJsModule(): void {
		$this->helper->addJsModule('dummy.js');

		$this->assertArrayHasKey('dummy.js', $this->helper->getJsModules());

		$this->helper->removeJsModule('dummy.js');

		$this->assertArrayNotHasKey('dummy.js', $this->helper->getJsModules());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMap(): void {
		$this->helper->addJsModule('dummy.js');

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script type="importmap">', $importMap);
		$this->assertStringContainsString('{"imports":{"dummy":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.1', $importMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMapForNonExistentFile(): void {
		$this->helper->addJsModule('module.js');

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('{"imports":{"module":null', $importMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMapWithoutScriptTag(): void {
		$this->helper->addJsModule('dummy.js');

		$importMap = $this->helper->createImportMap(false);

		$this->assertStringNotContainsString('<script', $importMap);
		$this->assertStringContainsString('{"imports":{"dummy":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.1', $importMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMapRespectsMinified(): void {
		$this->helper->addJsModule('dummy.js', true);

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script', $importMap);
		$this->assertStringContainsString('{"imports":{"dummy":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.min', $importMap);

		unlink(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/js/dummy.min.js');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMapRespectsAlias(): void {
		$this->helper->addJsModule(['dummy.js' => ['as' => 'DifferentName']]);

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script', $importMap);
		$this->assertStringContainsString('{"imports":{"DifferentName":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.', $importMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMapRespectsRealm(): void {
		$this->helper->addJsModule(['dummy.js' => ['realm' => Awyiss::REALM_BACKEND]]);

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script', $importMap);
		$this->assertStringContainsString('{"imports":{"dummy":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.', $importMap);

		// Remove the module to test the next one
		$this->helper->removeJsModule('dummy.js');

		$this->helper->addJsModule(['dummy.js' => ['realm' => Awyiss::REALM_FRONTEND]]);

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script', $importMap);
		$this->assertStringContainsString('{"imports":{"dummy":null', $importMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMapRespectsFallback(): void {
		$this->helper->addJsModule(['doesnotexist.js' => ['fallback' => 'fallback.js']]);

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script', $importMap);
		$this->assertStringContainsString('{"imports":{"doesnotexist":null', $importMap);

		$this->helper->removeJsModule('doesnotexist.js');

		$this->helper->addJsModule(['doesnotexist2.js' => ['fallback' => 'dummy.js']]);

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script', $importMap);
		$this->assertStringContainsString('{"imports":{"doesnotexist2":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.', $importMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMapRespectsFallbackAndAlias(): void {
		$this->helper->addJsModule(['doesnotexist.js' => ['fallback' => 'dummy.js', 'as' => 'DifferentName']]);

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('<script', $importMap);
		$this->assertStringNotContainsString('doesnotexist', $importMap);
		$this->assertStringContainsString('"DifferentName":"http:\/\/localhost\/assets\/awyiss\/js\/dummy.', $importMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMapRespectsSubfolders(): void {
		$this->helper->addJsModule('Modules/Media/Crop.js');

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('{"imports":{"Media\/Crop":"http:\/\/localhost\/awyiss\/assets\/js\/Modules\/Media\/Crop.', $importMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createImportMap()
	 * @throws \Exception
	 */
	public function testCreateImportMapIgnoresSubfoldersWithAlias(): void {
		$this->helper->addJsModule(['Modules/Media/Crop.js' => ['as' => 'MediaCropAlias']]);

		$importMap = $this->helper->createImportMap();

		$this->assertStringContainsString('{"imports":{"MediaCropAlias":"http:\/\/localhost\/awyiss\/assets\/js\/Modules\/Media\/Crop.', $importMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getFinalAssets()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getFinalAssets()
	 * @throws \Exception
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

		unlink(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getFinalAssets()
	 * @throws \Exception
	 */
	public function testGetFinalAssetsNotContainsNonExistentFile(): void {
		$this->helper->add(['dummy.js', 'nonexistent.css']);

		$finalAssets = $this->helper->getFinalAssets();

		$this->assertEquals(1, count($finalAssets));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getFinalAssets()
	 * @throws \Exception
	 */
	public function testGetFinalAssetsSortedByPriority(): void {
		$this->helper->add('dummy2.css', [], false, false, 5);
		$this->helper->add('dummy.css', [], false, false, 20);

		$finalAssets = $this->helper->getFinalAssets();

		$this->assertSame(['dummy.css', 'dummy2.css'], array_keys($finalAssets));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getScriptNonce()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getScriptNonce()
	 * @throws \Exception
	 */
	public function testGetScriptNonceWhenNotSet(): void {
		$this->assertNull($this->helper->getScriptNonce());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getStyleNonce()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getStyleNonce()
	 * @throws \Exception
	 */
	public function testGetStyleNonceWhenNotSet(): void {
		$this->assertNull($this->helper->getStyleNonce());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
	 */
	public function testGetAssetPath(): void {
		$fileMTime = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css');

		$path = $this->helper->getAssetPath('dummy.css');

		$this->assertSame('http://localhost/assets/awyiss/css/dummy.' . $fileMTime . '.css', $path);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
	 */
	public function testGetAssetPathMinifiesFileIfNotExist(): void {
		$filePath = ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy2.min.css';
		if (file_exists($filePath)) {
			unlink($filePath);
		}

		$path = $this->helper->getAssetPath('dummy2.css', ['minified' => true]);

		$time = filemtime($filePath);

		$this->assertStringContainsString('dummy2.min.' . $time . '.css', $path);
		$this->assertFileExists($filePath);
		$this->assertSame('.foo{color:red}', file_get_contents($filePath));

		unlink($filePath);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
	 */
	public function testGetAssetPathWithoutTimestamp(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['includeTimestamp' => false]);

		$this->assertSame('http://localhost/assets/awyiss/css/dummy.css', $path);
	}

	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
	 */
	public function testGetAssetPathWithLocalPath(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['localPath' => true]);

		$this->assertSame(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.css', $path);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
	 */
	public function testGetAssetPathWithMinified(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['minified' => true]);

		$this->assertStringContainsString('dummy.min.', $path);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
	 */
	public function testGetAssetPathWithMinifiedWithoutTimestamp(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['minified' => true, 'includeTimestamp' => false]);

		$this->assertStringContainsString('dummy.min.css', $path);

		unlink(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
	 */
	public function testGetAssetPathWithMinifiedAndLocalPath(): void {
		$path = $this->helper->getAssetPath('dummy.css', ['minified' => true, 'localPath' => true]);

		$this->assertSame(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css', $path);

		unlink(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::afterLayout()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::afterLayout()
	 * @throws \Exception
	 */
	public function testAfterLayoutWithMinified(): void {
		$this->helper->add('dummy.css', [], false, true);
		$this->helper->afterLayout();

		$response = $this->helper->getView()->getResponse();

		$this->assertTrue($response->hasHeader('Link'));

		$fileMTime = filemtime(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');

		$this->assertContains('Link: <http://localhost/assets/awyiss/css/dummy.min.' . $fileMTime . '.css>; rel=preload; as=style; nopush', $response->getHeader('Link'));

		unlink(ROOT . DS . CUSTOM_DIR . '/assets/awyiss/css/dummy.min.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::afterLayout()
	 * @throws \Exception
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
	 * @see \Awyiss\View\Helper\AssetHelper::afterLayout()
	 * @throws \Exception
	 */
	public function testAfterLayoutWithNoAssets(): void {
		$this->helper->afterLayout();

		$response = $this->helper->getView()->getResponse();

		$this->assertFalse($response->hasHeader('Link'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::afterLayout()
	 * @throws \Exception
	 */
	public function testAfterLayoutWithNonExistentFile(): void {
		$this->helper->add('nonexistent.css');

		$this->helper->afterLayout();

		$response = $this->helper->getView()->getResponse();

		$this->assertFalse($response->hasHeader('Link'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addCssLayer()
	 */
	public function testAddCssLayer(): void {
		$this->helper->addCssLayer('layer1');
		$this->helper->addCssLayer('layer2');
		$layers = $this->helper->getCssLayers();

		$expected = [
			'layer1' => [
				'priority' => 10,
				'layer' => 'layer1',
			],
			'layer2' => [
				'priority' => 10,
				'layer' => 'layer2',
			],
		];
		$this->assertEquals($expected, $layers);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addCssLayer()
	 */
	public function testAddCssLayerWithPriority(): void {
		$this->helper->addCssLayer('layer1', 100);
		$this->helper->addCssLayer('layer2', 50);

		$layers = $this->helper->getCssLayers();

		$expected = [
			'layer1' => [
				'priority' => 100,
				'layer' => 'layer1',
			],
			'layer2' => [
				'priority' => 50,
				'layer' => 'layer2',
			],
		];
		$this->assertEquals($expected, $layers);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addCssLayer()
	 */
	public function testAddCssLayerDuplicate(): void {
		$this->helper->addCssLayer('layer1');
		$this->helper->addCssLayer('layer1', 100);

		$layers = $this->helper->getCssLayers();

		$expected = [
			'layer1' => [
				'priority' => 10,
				'layer' => 'layer1',
			],
		];
		$this->assertEquals($expected, $layers);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addCssLayer()
	 */
	public function testAddCssLayerWithArray(): void {
		$this->helper->addCssLayer(['layer1', 'layer2', 'layer3']);

		$layers = $this->helper->getCssLayers();

		$expected = [
			'layer1, layer2, layer3' => [
				'priority' => 10,
				'layer' => 'layer1, layer2, layer3',
			],
		];
		$this->assertEquals($expected, $layers);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createLayerTag()
	 */
	public function testCreateLayerTag(): void {
		$this->helper->addCssLayer('layer1');
		$this->helper->addCssLayer(['layer2', 'layer3']);
		$this->helper->addCssLayer('layer4');

		$tag = $this->helper->createLayerTag();

		$this->assertStringContainsString('<style>@layer layer1, layer2, layer3, layer4;</style>', $tag);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createLayerTag()
	 */
	public function testCreateLayerTagWithPriority(): void {
		$this->helper->addCssLayer(['layer2', 'layer3']);
		$this->helper->addCssLayer('layer4', 5);
		$this->helper->addCssLayer('layer1', 5);

		$tag = $this->helper->createLayerTag();

		$this->assertStringContainsString('<style>@layer layer4, layer1, layer2, layer3;</style>', $tag);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createLayerTag()
	 */
	public function testCreateLayerTagWithEmptyLayers(): void {
		$tag = $this->helper->createLayerTag();

		$this->assertSame('', $tag);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::createLayerTag()
	 */
	public function testCreateLayerTagWithNonce(): void {
		$view = $this->helper->getView();
		$request = $view->getRequest()->withAttribute('cspStyleNonce', 'test-nonce');
		$view->setRequest($request);

		$this->helper->addCssLayer('layer1');

		$tag = $this->helper->createLayerTag();

		$this->assertStringContainsString('<style nonce="test-nonce">@layer layer1;</style>', $tag);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
	 */
	public function testMinifyCss(): void {
		$filePath = ROOT . DS . CUSTOM_DIR . '/assets/css/main.min.css';
		if (file_exists($filePath)) {
			unlink($filePath);
		}

		$this->helper->getAssetPath('main.css', [
			'minified' => true,
			'realm' => Awyiss::REALM_FRONTEND,
		]);

		$this->assertFileExists($filePath);
		// Make sure "2 of .dummy" keeps the spaces, otherwise it would be invalid CSS
		$this->assertStringEqualsFile($filePath, '.foo{color:red}.bar{color:blue}.foo .bar{color:green}.foo .nested{color:purple}.bar .nested:nth-child(2 of .dummy){color:orange}');

		unlink($filePath);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::getAssetPath()
	 * @throws \Exception
	 */
	public function testMinifyJs(): void {
		$filePath = ROOT . DS . CUSTOM_DIR . '/assets/js/main.min.js';
		if (file_exists($filePath)) {
			unlink($filePath);
		}

		$this->helper->getAssetPath('main.js', [
			'minified' => true,
			'realm' => Awyiss::REALM_FRONTEND,
		]);

		$this->assertFileExists($filePath);
		$this->assertStringEqualsFile($filePath, 'console.log("This is a dummy JavaScript file for testing purposes.");const name="Test User";let count=0;function incrementCount(){count+=1;console.log(`Count is now: ${count}`);return count}' . PHP_EOL . 'const config={enabled:!0,timeout:3000};document.addEventListener(\'click\',function(){incrementCount()});export{incrementCount,config}');

		unlink($filePath);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addContentStyleBlock()
	 */
	public function testAddContentStyleBlock(): void {
		$cssBlock = '.custom-class { color: red; }';
		$this->helper->addContentStyleBlock('#Content123', $cssBlock);

		$result = $this->helper->getContentStyleBlocks();
		$this->assertArrayHasKey('#Content123', $result);
		$this->assertSame([
			'content' => $cssBlock,
			'priority' => 10,
		], $result['#Content123']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::removeContentStyleBlock()
	 */
	public function testRemoveContentStyleBlock(): void {
		$cssBlock = '.custom-class { color: red; }';
		$this->helper->addContentStyleBlock('#Content123', $cssBlock);

		$cssBlock = '.custom-class { color: blue; }';
		$this->helper->addContentStyleBlock('#Content234', $cssBlock);

		$this->helper->removeContentStyleBlock('#Content123');

		$result = $this->helper->getContentStyleBlocks();
		$this->assertArrayNotHasKey('#Content123', $result);
		$this->assertArrayHasKey('#Content234', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::clearContentStyleBlocks()
	 */
	public function testClearContentStyleBlocks(): void {
		$cssBlock = '.custom-class { color: red; }';
		$this->helper->addContentStyleBlock('#Content123', $cssBlock);
		$cssBlock = '.custom-class { color: blue; }';
		$this->helper->addContentStyleBlock('#Content234', $cssBlock);

		$this->helper->clearContentStyleBlocks();

		$result = $this->helper->getContentStyleBlocks();
		$this->assertEmpty($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::clearAssets()
	 */
	public function testClearAssetsRemovedContentStyleBlocks(): void {
		$cssBlock = '.custom-class { color: red; }';
		$this->helper->addContentStyleBlock('#Content123', $cssBlock);
		$cssBlock = '.custom-class { color: blue; }';
		$this->helper->addContentStyleBlock('#Content234', $cssBlock);

		$this->helper->clearAssets();

		$result = $this->helper->getContentStyleBlocks();
		$this->assertEmpty($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addDynamicContentsStylesheet()
	 * @throws \Exception
	 */
	public function testAddDynamicContentsStylesheetCreatesNewScssFile(): void {
		$scssPath = ROOT . DS . CUSTOM_DIR . '/assets/scss/_dynamic/page_123_b3e052fc6c30f602.scss';
		$cssPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_b3e052fc6c30f602.css';
		$cssMapPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_b3e052fc6c30f602.css.map';

		if (file_exists($scssPath)) {
			unlink($scssPath);
		}
		if (file_exists($cssPath)) {
			unlink($cssPath);
		}
		if (file_exists($cssMapPath)) {
			unlink($cssMapPath);
		}

		$this->helper->setRealm(Awyiss::REALM_FRONTEND);

		$scssContent = '$primary-color: #63d1a5; background-color: $primary-color;';
		$this->helper->addContentStyleBlock('#Content123', $scssContent);
		$this->helper->addDynamicContentsStylesheet(123);

		$allTags = $this->helper->getTags();
		/** @noinspection JSUnresolvedLibraryURL */
		$this->assertStringContainsString('<link rel="preload" href="http://localhost/assets/css/_dynamic/page_123_b3e052fc6c30f602.', $allTags);
		/** @noinspection JSUnresolvedLibraryURL */
		$this->assertStringContainsString('<noscript><link rel="stylesheet" type="text/css" href="http://localhost/assets/css/_dynamic/page_123_b3e052fc6c30f602.', $allTags);

		$this->assertFileExists($scssPath);
		unlink($scssPath);

		$this->assertFileExists($cssPath);
		unlink($cssPath);

		$this->assertFileExists($cssMapPath);
		unlink($cssMapPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addDynamicContentsStylesheet()
	 * @throws \Exception
	 */
	public function testAddDynamicContentsStylesheetNotUpdatesExistingScssFile(): void {
		$scssPath = ROOT . DS . CUSTOM_DIR . '/assets/scss/_dynamic/page_123_b3e052fc6c30f602.scss';
		$cssPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_b3e052fc6c30f602.css';
		$cssMapPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_b3e052fc6c30f602.css.map';

		if (file_exists($cssPath)) {
			unlink($cssPath);
		}
		if (file_exists($cssMapPath)) {
			unlink($cssMapPath);
		}

		touch($scssPath, time() - 3600);

		$scssContent = '$primary-color: #63d1a5; background-color: $primary-color;';
		$this->helper->addContentStyleBlock('#Content123', $scssContent);
		$this->helper->addDynamicContentsStylesheet(123);

		$this->assertFileExists($scssPath);
		$this->assertEquals(time() - 3600, filemtime($scssPath));

		$this->assertFileExists($cssPath);
		unlink($cssPath);

		$this->assertFileExists($cssMapPath);
		unlink($cssMapPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addDynamicContentsStylesheet()
	 * @throws \Exception
	 */
	public function testAddDynamicContentsStylesheetNotUpdatesExistingCssFile(): void {
		$scssPath = ROOT . DS . CUSTOM_DIR . '/assets/scss/_dynamic/page_123_b3e052fc6c30f602.scss';
		$cssPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_b3e052fc6c30f602.css';
		$cssMapPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_b3e052fc6c30f602.css.map';

		touch($scssPath, time() - 3600);
		touch($cssPath, time() - 3600);
		touch($cssMapPath, time() - 3600);

		$scssContent = '$primary-color: #63d1a5; background-color: $primary-color;';
		$this->helper->addContentStyleBlock('#Content123', $scssContent);
		$this->helper->addDynamicContentsStylesheet(123);

		$this->assertFileExists($scssPath);
		$this->assertEquals(time() - 3600, filemtime($scssPath));

		$this->assertFileExists($cssPath);
		$this->assertEquals(time() - 3600, filemtime($cssPath));

		$this->assertFileExists($cssMapPath);
		$this->assertEquals(time() - 3600, filemtime($cssMapPath));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addDynamicContentsStylesheet()
	 * @throws \Exception
	 */
	public function testAddDynamicContentsStylesheetRemovesOutdatedFiles(): void {
		$oldScssPath = ROOT . DS . CUSTOM_DIR . '/assets/scss/_dynamic/page_123_b3e052fc6c30f602.scss';
		$newScssPath = ROOT . DS . CUSTOM_DIR . '/assets/scss/_dynamic/page_123_4b0b55b40350baaf.scss';
		$oldCssPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_b3e052fc6c30f602.css';
		$newCssPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_4b0b55b40350baaf.css';
		$oldCssMapPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_b3e052fc6c30f602.css.map';
		$newCssMapPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_4b0b55b40350baaf.css.map';

		touch($oldScssPath, time() - 7200);
		touch($oldCssPath, time() - 7200);
		touch($oldCssMapPath, time() - 7200);

		$scssContent = '$primary-color: #FFC53A; background-color: $primary-color;';
		$this->helper->addContentStyleBlock('#Content123', $scssContent);
		$this->helper->addDynamicContentsStylesheet(123);

		$this->assertFileExists($newScssPath);
		$this->assertFileExists($newCssPath);
		$this->assertFileExists($newCssMapPath);

		$this->assertFileDoesNotExist($oldScssPath);
		$this->assertFileDoesNotExist($oldCssPath);
		$this->assertFileDoesNotExist($oldCssMapPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addDynamicContentsStylesheet()
	 * @throws \Exception
	 */
	public function testAddDynamicContentsStylesheetRespectsPriority(): void {
		$scssPath = ROOT . DS . CUSTOM_DIR . '/assets/scss/_dynamic/page_123_9a26370f2d2e3100.scss';
		$cssPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_9a26370f2d2e3100.css';
		$cssMapPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_9a26370f2d2e3100.css.map';

		$scssContent1 = 'color: red;';
		$scssContent2 = 'color: blue;';

		$this->helper->addContentStyleBlock('#Content123', $scssContent1, 50);
		$this->helper->addContentStyleBlock('#Content234', $scssContent2, 5);
		$this->helper->addDynamicContentsStylesheet(123);

		$this->assertFileExists($scssPath);
		$this->assertFileExists($cssPath);
		$this->assertFileExists($cssMapPath);

		$cssContent = file_get_contents($cssPath);
		/** @noinspection CssUnusedSymbol */
		$this->assertSame(<<<CSS
#Content234 {
  color: blue;
}

#Content123 {
  color: red;
}

/*# sourceMappingURL=page_123_9a26370f2d2e3100.css.map */
CSS, $cssContent);

		unlink($scssPath);
		unlink($cssPath);
		unlink($cssMapPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AssetHelper::addDynamicContentsStylesheet()
	 * @throws \Exception
	 */
	public function testAddDynamicContentsStylesheetUsesDesignSettings(): void {
		$scssPath = ROOT . DS . CUSTOM_DIR . '/assets/scss/_dynamic/page_123_07de357242c7fade.scss';
		$cssPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_07de357242c7fade.css';
		$cssMapPath = ROOT . DS . CUSTOM_DIR . '/assets/css/_dynamic/page_123_07de357242c7fade.css.map';

		if (file_exists($scssPath)) {
			unlink($scssPath);
		}
		if (file_exists($cssPath)) {
			unlink($cssPath);
		}
		if (file_exists($cssMapPath)) {
			unlink($cssMapPath);
		}

		$this->helper->setRealm(Awyiss::REALM_FRONTEND);

		$request = $this->helper->getView()->getRequest();
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->method('getDesignVariables')->willReturn([
			'body-bg-color' => '#ffffff',
		]);

		$request = $request->withAttribute('design', $designMiddlewareMock);
		$this->helper->getView()->setRequest($request);
		Router::setRequest($request);

		$scssContent = 'body { background-color: $body-bg-color; }';
		$this->helper->addContentStyleBlock('#Content123', $scssContent);
		$this->helper->addDynamicContentsStylesheet(123);

		$this->assertFileExists($scssPath);
		unlink($scssPath);

		$this->assertFileExists($cssPath);
		$cssFileContent = file_get_contents($cssPath);
		$this->assertStringContainsString('background-color: #ffffff;', $cssFileContent);
		unlink($cssPath);

		$this->assertFileExists($cssMapPath);
		unlink($cssMapPath);
	}
}
