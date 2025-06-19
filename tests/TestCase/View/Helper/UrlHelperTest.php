<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\FrontendView;
use Awyiss\View\Helper\UrlHelper;
use Cake\Core\Configure;
use Cake\Routing\Exception\MissingRouteException;
use Cake\TestSuite\IntegrationTestTrait;
use ReflectionClass;


/**
 * UrlHelperTest class
 */
class UrlHelperTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function tearDownAfterClass(): void {
		$reflection = new ReflectionClass(FrontendView::class);
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
	 * @var \Awyiss\View\Helper\UrlHelper
	 */
	protected UrlHelper $urlHelper;


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		Awyiss::setRealm('Frontend');

		$view = new FrontendView();
		$this->urlHelper = new UrlHelper($view);
	}


	/**
	 * @return array<string, bool>
	 */
	public static function configProvider(): array {
		return [
			'enabled' => [true],
			'disabled' => [false],
		];
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildUrlWithoutNameUsesSetRealm(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'lang' => 'de',
			'slug' => 'testslug',
		];
		$options = ['fullBase' => true, 'escape' => false];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/de/testslug/', $result);
		}
		else {
			$this->assertSame('https://cms.de/testslug/', $result);
		}
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildUrlWithFrontendName(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
			'slug' => 'test-route',
		];
		$options = ['fullBase' => true, 'escape' => false];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/en/test-route/', $result);
		}
		else {
			$this->assertSame('https://cms.de/test-route/', $result);
		}
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testBuildUrlWithFrontendNameWithoutSlugThrowsException(): void {
		$this->loadRoutes();

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
		];
		$options = ['fullBase' => true, 'escape' => false];

		$this->expectException(MissingRouteException::class);
		$this->urlHelper->build($url, $options);
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testBuildUrlWithFrontendNameAndAdditionalParameter(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
			'slug' => 'test-route',
			'additionalParameter' => 'unusedValue',
		];
		$options = ['fullBase' => true, 'escape' => false];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/en/test-route/additional-parameter:unused-value/', $result);
		}
		else {
			$this->assertSame('https://cms.de/test-route/additional-parameter:unused-value/', $result);
		}
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildUrlWithInvalidRoute(): void {
		$this->loadRoutes();

		$url = ['_name' => 'invalid_route'];
		$options = ['fullBase' => true, 'escape' => false];

		$this->expectException(MissingRouteException::class);
		$this->urlHelper->build($url, $options);
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildUrlWithOptionWithParams(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$view = $this->urlHelper->getView();
		$request = $view->getRequest()->withParam('parts', [
			'lang' => 'en',
			'slug' => 'test-route',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		]);
		$view->setRequest($request);

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
			'slug' => 'test-route',
		];
		$options = [
			'withParams' => ['param1', 'param2'],
			'fullBase' => true,
			'escape' => false,
		];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/en/test-route/param1:value1/param2:value2/', $result);
		}
		else {
			$this->assertSame('https://cms.de/test-route/param1:value1/param2:value2/', $result);
		}
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildUrlWithOptionWithParamsAll(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$view = $this->urlHelper->getView();
		$request = $view->getRequest()->withParam('parts', [
			'lang' => 'en',
			'slug' => 'test-route',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		]);
		$view->setRequest($request);

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
			'slug' => 'test-route',
		];
		$options = [
			'withParams' => true,
			'fullBase' => true,
			'escape' => false,
		];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/en/test-route/param1:value1/param2:value2/param3:value3/param4:value4/', $result);
		}
		else {
			$this->assertSame('https://cms.de/test-route/param1:value1/param2:value2/param3:value3/param4:value4/', $result);
		}
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildUrlWithOptionWithParamsString(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$view = $this->urlHelper->getView();
		$request = $view->getRequest()->withParam('parts', [
			'lang' => 'en',
			'slug' => 'test-route',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		]);
		$view->setRequest($request);

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
			'slug' => 'test-route',
		];
		$options = [
			'withParams' => 'param3',
			'fullBase' => true,
			'escape' => false,
		];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/en/test-route/param3:value3/', $result);
		}
		else {
			$this->assertSame('https://cms.de/test-route/param3:value3/', $result);
		}
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildUrlWithoutOptionWithParams(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$view = $this->urlHelper->getView();
		$request = $view->getRequest()->withParam('parts', [
			'lang' => 'en',
			'slug' => 'test-route',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		]);
		$view->setRequest($request);

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
			'slug' => 'test-route',
			'param4' => 'value4',
		];
		$options = [
			'fullBase' => true,
			'escape' => false,
		];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/en/test-route/param4:value4/', $result);
		}
		else {
			$this->assertSame('https://cms.de/test-route/param4:value4/', $result);
		}
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildUrlWithOptionWithoutParams(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$view = $this->urlHelper->getView();
		$request = $view->getRequest()->withParam('parts', [
			'lang' => 'en',
			'slug' => 'test-route',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		]);
		$view->setRequest($request);

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
			'slug' => 'test-route',
		];
		$options = [
			'withoutParams' => ['param3'],
			'fullBase' => true,
			'escape' => false,
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/en/test-route/param1:value1/param2:value2/param4:value4/', $result);
		}
		else {
			$this->assertSame('https://cms.de/test-route/param1:value1/param2:value2/param4:value4/', $result);
		}
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testBuildUrlWithOptionWithoutParamsString(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$view = $this->urlHelper->getView();
		$request = $view->getRequest()->withParam('parts', [
			'lang' => 'en',
			'slug' => 'test-route',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		]);
		$view->setRequest($request);

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
			'slug' => 'test-route',
		];
		$options = [
			'withoutParams' => 'param3',
			'fullBase' => true,
			'escape' => false,
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/en/test-route/param1:value1/param2:value2/param4:value4/', $result);
		}
		else {
			$this->assertSame('https://cms.de/test-route/param1:value1/param2:value2/param4:value4/', $result);
		}
	}


	/**
	 * @dataProvider configProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testBuildUrlWithOptionWithoutParamsAll(bool $includeLanguageShortcode): void {
		Configure::write('Route.includeLanguageShortcode', $includeLanguageShortcode);
		$this->loadRoutes();

		$view = $this->urlHelper->getView();
		$request = $view->getRequest()->withParam('parts', [
			'lang' => 'en',
			'slug' => 'test-route',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		]);
		$view->setRequest($request);

		$url = [
			'_host' => 'cms.de',
			'_https' => true,
			'_name' => 'Frontend',
			'lang' => 'en',
			'slug' => 'test-route',
		];
		$options = [
			'withoutParams' => true,
			'fullBase' => true,
			'escape' => false,
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'param4' => 'value4',
		];

		$result = $this->urlHelper->build($url, $options);

		$this->assertIsString($result);

		if ($includeLanguageShortcode) {
			$this->assertSame('https://cms.de/en/test-route/', $result);
		}
		else {
			$this->assertSame('https://cms.de/test-route/', $result);
		}
	}
}
