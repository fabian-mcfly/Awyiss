<?php

/**
 * @noinspection PhpComplexClassInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Middleware;


use Awyiss\Awyiss;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Design\ScssCompiler;
use Cake\Core\Configure;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Exception;


/**
 * DesignMiddleware Test Case
 *
 * @see \Awyiss\Middleware\DesignMiddleware
 */
class DesignMiddlewareTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var string
	 */
	protected static string $oldLocale;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);
	}


	/**
	 * @inheritDoc
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		static::$oldLocale = I18n::getLocale();
	}


	/**
	 * @inheritDoc
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		ini_set('intl.default_locale', static::$oldLocale);
		I18n::setLocale(static::$oldLocale);
		setlocale(LC_ALL, static::$oldLocale . '.utf8');
		TypeFactory::build('datetime')->setUserTimezone(null);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

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

		$filePath = ROOT . DS . CUSTOM_DIR . '/assets/js/main.min.js';
		if (file_exists($filePath)) {
			unlink($filePath);
		}

		$invalidSCssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'invalid.scss';
		if (file_exists($invalidSCssFile)) {
			unlink($invalidSCssFile);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessSetsDesignAttribute(): void {
		$this->get('/zu/users/overview/foo:bar/baz:qux');
		$request = $this->_controller->getRequest();

		$this->assertInstanceOf(DesignMiddleware::class, $request->getAttribute('design'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessCallsDesignAllowCompileCallable(): void {
		$callableCalled = false;
		$callable = function () use (&$callableCalled) {
			$callableCalled = true;
			return true;
		};

		Configure::write('Design.allowCompile', $callable);

		$this->get('/zu/users/overview/foo:bar/baz:qux');

		$this->assertTrue($callableCalled);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessNotCompilesUpToDateFiles(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		if (file_exists($assetsPath . 'css' . DS . 'test.css')) {
			unlink($assetsPath . 'css' . DS . 'test.css');
		}
		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		$this->get('/zu/users/overview/foo:bar/baz:qux');

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertEquals($lastModified, $files->getLastModified());

		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessNotCompilesOutdatedFilesWhenConfigAutoCompileNotTrue(): void {
		Configure::write('Design.autoCompile', false);

		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		if (file_exists($assetsPath . 'css' . DS . 'test.css')) {
			unlink($assetsPath . 'css' . DS . 'test.css');
		}
		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		$this->get('/zu/users/overview/foo:bar/baz:qux');

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertEquals($lastModified, $files->getLastModified());

		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessCompilesOutdatedFiles(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		if (file_exists($assetsPath . 'css' . DS . 'test.css')) {
			unlink($assetsPath . 'css' . DS . 'test.css');
		}
		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		foreach ($files->getFiles() as $file) {
			$this->assertLessThan($now, $file->getMTime());
		}

		$this->get('/zu/users/overview/foo:bar/baz:qux');

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertNotEquals($lastModified, $files->getLastModified());

		$this->assertFileExists($assetsPath . 'css' . DS . 'test.css');
		$this->assertFileEquals(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'test.css', $assetsPath . 'css' . DS . 'test.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessNotCompilesOutdatedFilesFromDifferentRealm(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		if (file_exists($assetsPath . 'css' . DS . 'test.css')) {
			unlink($assetsPath . 'css' . DS . 'test.css');
		}
		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		$user = $this->login();
		$this->session(['Backend.Auth' => $user]);

		$this->get('/backend/zu/users/login');

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertEquals($lastModified, $files->getLastModified());

		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessCompilesOutdatedFilesWhenConfigAutoCompileNotTrueAndQueryContainsCompileScssTrue(): void {
		Configure::write('Design.autoCompile', false);

		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		if (file_exists($assetsPath . 'css' . DS . 'test.css')) {
			unlink($assetsPath . 'css' . DS . 'test.css');
		}
		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		$this->get('/zu/users/overview/foo:bar/baz:qux/compile-scss:true/');

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertNotEquals($lastModified, $files->getLastModified());

		$this->assertFileExists($assetsPath . 'css' . DS . 'test.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessCompilesOutdatedFilesWhenConfigAutoCompileNotTrueAndQueryContainsCompileScssTrueAndAllowCompileReturnsTrue(): void {
		Configure::write('Design.autoCompile', false);
		Configure::write('Design.allowCompile', fn() => true);

		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		if (file_exists($assetsPath . 'css' . DS . 'test.css')) {
			unlink($assetsPath . 'css' . DS . 'test.css');
		}
		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		$this->get('/zu/users/overview/foo:bar/baz:qux/compile-scss:true/');

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertNotEquals($lastModified, $files->getLastModified());

		$this->assertFileExists($assetsPath . 'css' . DS . 'test.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessNotCompilesOutdatedFilesWhenConfigAutoCompileNotTrueAndQueryContainsCompileScssTrueAndAllowCompileNotTrue(): void {
		Configure::write('Design.autoCompile', false);
		Configure::write('Design.allowCompile', false);

		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		if (file_exists($assetsPath . 'css' . DS . 'test.css')) {
			unlink($assetsPath . 'css' . DS . 'test.css');
		}
		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		$this->get('/zu/users/overview/foo:bar/baz:qux/compile-scss:true/');

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertEquals($lastModified, $files->getLastModified());

		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessNotCompilesOutdatedFilesWhenConfigAutoCompileNotTrueAndQueryContainsCompileScssTrueAndAllowCompileNotReturnsTrue(): void {
		Configure::write('Design.autoCompile', false);
		Configure::write('Design.allowCompile', fn() => false);

		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		if (file_exists($assetsPath . 'css' . DS . 'test.css')) {
			unlink($assetsPath . 'css' . DS . 'test.css');
		}
		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		$this->get('/zu/users/overview/foo:bar/baz:qux/compile-scss:true/');

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertEquals($lastModified, $files->getLastModified());

		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessCompilesAllFilesWhenQueryContainsCompileScssTrue(): void {
		Configure::write('Design.autoCompile', false);

		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		if (file_exists($assetsPath . 'css' . DS . 'test.css')) {
			unlink($assetsPath . 'css' . DS . 'test.css');
		}
		$this->assertFileDoesNotExist($assetsPath . 'css' . DS . 'test.css');

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$this->get('/zu/users/overview/foo:bar/baz:qux/compile-scss:true/');

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			$this->assertGreaterThanOrEqual($now, $file->getMTime());
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessShowsErrorWhenConfigAutoCompileNotTrue(): void {
		Configure::write('Design.autoCompile', true);
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);

		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$invalidScssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'invalid.scss';
		if (!file_exists($invalidScssFile)) {
			file_put_contents($invalidScssFile, 'This is not valid SCSS code.');
			$this->assertFileExists($invalidScssFile);
		}

		ob_start();

		$this->get('/compile-scss:true/');

		if (file_exists($invalidScssFile)) {
			unlink($invalidScssFile);
		}

		$output = ob_get_clean();

		$this->assertStringContainsString('Cannot compile SCSS file', $output);

		$invalidCssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'css' . DS . 'invalid.css';
		$this->assertFileDoesNotExist($invalidCssFile);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::process()
	 */
	public function testProcessNotShowsErrorWhenConfigAutoCompileNotTrue(): void {
		Configure::write('Design.autoCompile', false);
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);

		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() + 3600);
			$this->assertGreaterThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$invalidScssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'invalid.scss';
		if (!file_exists($invalidScssFile)) {
			file_put_contents($invalidScssFile, 'This is not valid SCSS code.');
			$this->assertFileExists($invalidScssFile);
		}

		ob_start();

		$this->get('/compile-scss:true/');

		if (file_exists($invalidScssFile)) {
			unlink($invalidScssFile);
		}

		$output = ob_get_clean();

		$this->assertStringNotContainsString('Cannot compile SCSS file', $output);

		$invalidCssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'css' . DS . 'invalid.css';
		$this->assertFileDoesNotExist($invalidCssFile);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssCompilesOutdatedFiles(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 1800);
			$this->assertLessThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		$designMiddleware = new DesignMiddleware();
		$designMiddleware->compileScss();

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertNotEquals($lastModified, $files->getLastModified());

		foreach ($files->getFiles() as $file) {
			$this->assertGreaterThanOrEqual($now, $file->getMTime());
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssNotCompilesUpToDateFiles(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 1800);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		$designMiddleware = new DesignMiddleware();
		$designMiddleware->compileScss();

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertEquals($lastModified, $files->getLastModified());
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssCompilesUpToDateFilesWhenMustCompile(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		// Get all css files and mark them as recently modified.
		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 1800);
			$this->assertLessThan($now, $file->getMTime());
		}

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		// Get thew last modified time of the css files.
		$lastModified = $files->getLastModified();

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		$designMiddleware = new DesignMiddleware();
		$designMiddleware->compileScss(true);

		$files = ScssCompiler::discoverFiles($assetsPath . 'css');
		$newNewLastModified = $files->getLastModified();

		$this->assertNotNull($newNewLastModified);
		$this->assertNotEquals($lastModified, $files->getLastModified());

		foreach ($files->getFiles() as $file) {
			$this->assertGreaterThanOrEqual($now, $file->getMTime());
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssNotShowsErrorWhenFileInvalidAndShowExceptionsFalseAndDebugFalse(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$invalidScssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'invalid.scss';
		if (!file_exists($invalidScssFile)) {
			file_put_contents($invalidScssFile, 'This is not valid SCSS code.');
			$this->assertFileExists($invalidScssFile);
		}

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		$designMiddleware = new DesignMiddleware();

		ob_start();

		$designMiddleware->compileScss(true);

		if (file_exists($invalidScssFile)) {
			unlink($invalidScssFile);
		}

		$output = ob_get_clean();

		$this->assertStringNotContainsString('Cannot compile SCSS file', $output);

		$invalidCssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'css' . DS . 'invalid.css';
		$this->assertFileDoesNotExist($invalidCssFile);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssShowsErrorWhenFileInvalidAndShowExceptionsTrueAndDebugFalse(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$invalidScssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'invalid.scss';
		if (!file_exists($invalidScssFile)) {
			file_put_contents($invalidScssFile, 'This is not valid SCSS code.');
			$this->assertFileExists($invalidScssFile);
		}

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		$designMiddleware = new DesignMiddleware();

		ob_start();

		$designMiddleware->compileScss(true, null, true);

		if (file_exists($invalidScssFile)) {
			unlink($invalidScssFile);
		}

		$output = ob_get_clean();

		$this->assertStringContainsString('Cannot compile SCSS file', $output);

		$invalidCssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'css' . DS . 'invalid.css';
		$this->assertFileDoesNotExist($invalidCssFile);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssNotShowsExceptionWhenFileInvalidAndShowExceptionsFalseAndDebugTrue(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;
		Configure::write('debug', true);

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$invalidScssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'invalid.scss';
		if (!file_exists($invalidScssFile)) {
			file_put_contents($invalidScssFile, 'This is not valid SCSS code.');
			$this->assertFileExists($invalidScssFile);
		}

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		$designMiddleware = new DesignMiddleware();

		ob_start();

		$designMiddleware->compileScss(true);

		if (file_exists($invalidScssFile)) {
			unlink($invalidScssFile);
		}

		$output = ob_get_clean();

		$this->assertStringNotContainsString('Cannot compile SCSS file', $output);

		$invalidCssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'css' . DS . 'invalid.css';
		$this->assertFileDoesNotExist($invalidCssFile);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssShowsExceptionWhenFileInvalidAndShowExceptionsTrueAndDebugTrue(): void {
		$assetsPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;
		Configure::write('debug', true);

		$now = time();

		$files = ScssCompiler::discoverFiles($assetsPath . 'scss');
		foreach ($files->getFiles() as $file) {
			touch($file->getRealPath(), time() - 3600);
			$this->assertLessThan($now, $file->getMTime());
		}

		$invalidScssFile = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'invalid.scss';
		if (!file_exists($invalidScssFile)) {
			file_put_contents($invalidScssFile, 'This is not valid SCSS code.');
			$this->assertFileExists($invalidScssFile);
		}

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		$designMiddleware = new DesignMiddleware();

		$this->expectException(Exception::class);

		$designMiddleware->compileScss(true, null, true);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::getDesignVariables()
	 */
	public function testGetDesignVariablesForBackend(): void {
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		$designMiddleware = new DesignMiddleware();
		$this->assertSame([], $designMiddleware->getDesignVariables());
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\DesignMiddleware::getDesignVariables()
	 */
	public function testGetDesignVariablesForFrontend(): void {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		$designMiddleware = new DesignMiddleware();
		$variables = $designMiddleware->getDesignVariables();
		$this->assertSame([], $variables);

		$table = $this->fetchTable('Designs');
		$table->updateAll(['in_use' => true], ['id' => 2]);

		$designMiddleware->resetDesignVariables();
		$variables = $designMiddleware->getDesignVariables();

		$table = $this->fetchTable('Designs');
		$table->updateAll(['in_use' => false], ['id' => 2]);

		$this->assertSame([
			'colorDark' => '#101820',
			'colorMedium' => '#686e77',
			'colorLight' => '#f2f5f6',
			'colorBright' => '#FFFFFF',
			'colorMain' => '#17bbe1',
		], $variables);

		$designMiddleware->resetDesignVariables();
		$variables = $designMiddleware->getDesignVariables();

		$this->assertSame([], $variables);
	}
}
