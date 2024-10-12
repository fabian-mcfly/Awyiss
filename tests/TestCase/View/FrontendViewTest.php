<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View;


use Awyiss\Awyiss;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\Exception\MissingContentException;
use Awyiss\View\Exception\MissingWidgetException;
use Awyiss\View\FrontendView;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use ReflectionClass;


/**
 * FrontendViewTest class
 */
class FrontendViewTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var array
	 */
	protected array $designVariables = [
		'fontNameMain' => [
			'font' => [
				'category' => 'sans-serif',
				'id' => 'red-hat-text',
				'name' => 'Red Hat Text',
				'popularity' => 271,
				'variants' => ['300', 'regular', '500', '600', '700', '300italic', 'italic', '500italic', '600italic', '700italic'],
				'version' => 'v14',
			],
			'variants' => ['300', '300i', '400', '400i', '700', '700i'],
		],
		'fontStackFallbackMain' => 'Gill Sans, Arial, sans-serif',
		'fontWeightMain' => '300',
		'fontStyleMain' => 'normal',
		'fontSizeMain' => '18',
		'fontSizeMainUnit' => 'px',
		'lineHeightMain' => '1.5',
		'lineHeightMainUnit' => 'rem',
		'fontNameAlternative' => [
			'font' => [
				'category' => 'handwriting',
				'id' => 'merienda',
				'name' => 'Merienda',
				'popularity' => 170,
				'variants' => ['300', 'regular', '500', '600', '700', '800', '900'],
				'version' => 'v19',
			],
			'variants' => ['400', '700'],
		],
		'fontStackFallbackAlternative' => 'Lucida Handwriting, cursive',
		'fontWeightAlternative' => '400',
		'fontStyleAlternative' => 'normal',
		'fontSizeAlternative' => '',
		'fontSizeAlternativeUnit' => 'em',
		'lineHeightAlternative' => '',
		'lineHeightAlternativeUnit' => '',
		'colorText' => '#043a4f',
		'colorDark' => '#101820',
		'colorMedium' => '#686e77',
		'colorLight' => '#f2f5f6',
		'colorBright' => '#FFFFFF',
		'colorMain' => '#17bbe1',
		'colorContrast' => '#d22e45',
		'pageWidth' => '1440',
		'pageWidthUnit' => 'px',
		'pagePadding' => '50',
		'pagePaddingUnit' => 'px',
		'columnMargin' => '5',
		'columnMarginUnit' => '%',
		'menuBreakpoint' => '1024',
		'menuBreakpointUnit' => 'px',
		'singleColumnBreakpoint' => '1024',
		'singleColumnBreakpointUnit' => 'px',
	];
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $view;


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
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		$this->loadRoutes();

		Awyiss::setRealm('Frontend');

		$request = new ServerRequest([
			'url' => '/',
			'params' => [
				'lang' => 'xy',
				'controller' => 'TheController',
				'action' => 'theAction',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
			],
		]);

		$this->view = new FrontendView($request);
	}


	/**
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitialize(): void {
		$this->view->initialize();

		$helpers = $this->view->helpers()->loaded();

		$this->assertContains('Asset', $helpers);
		$this->assertContains('Form', $helpers);
		$this->assertContains('Html', $helpers);
		$this->assertContains('Locale', $helpers);
		$this->assertContains('Media', $helpers);
		$this->assertContains('Paginator', $helpers);
		$this->assertContains('Url', $helpers);
	}


	/**
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTwigHasGlobals(): void {
		$this->view->initialize();

		$twig = $this->view->getTwig();

		$globals = $twig->getGlobals();

		$expectedGlobals = [
			'currentUrl' => 'http://localhost/',
			'languageShortcode' => 'de',
			'previewMode' => [],
			'webfontTimestamp' => filemtime(ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'css' . DS . 'webfonts.css'),
		];

		foreach ($expectedGlobals as $key => $value) {
			$this->assertArrayHasKey($key, $globals);
			$this->assertSame($value, $globals[$key]);
		}
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetRowClass(): void {
		FrontendView::setRowClass('test-class');
		$this->assertSame('test-class', FrontendView::getRowClass());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @throws \ReflectionException
	 */
	public function testSetOgImage(): void {
		$this->view->set('ogImage');

		$this->callProtectedMethod($this->view, 'setOgImage');

		$ogImage = $this->view->get('ogImage');

		$this->assertSame('http://localhost/assets/img/login-logo.png', $ogImage);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testContent(): void {
		$result = $this->view->content('test_content', ['key' => 'value']);

		$this->assertIsString($result);
		$this->assertSame('value', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testContentThrowsExceptionForMissingContent(): void {
		$this->expectException(MissingContentException::class);
		$this->view->content('not_existing_test_content', ['key' => 'value'], ['ignoreMissing' => false]);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testContentThrowsNoExceptionForMissingContentWhenIgnoring(): void {
		$result = $this->view->content('not_existing_test_content', ['key' => 'value'], ['ignoreMissing' => true]);

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testContentCache(): void {
		$name = 'test_content';
		$data = ['key' => 'value'];
		$options = ['cache' => ['key' => 'test_key', 'config' => 'default']];

		$result = $this->view->content($name, $data, $options);

		$this->assertIsString($result);
		$this->assertSame('value', $result);

		$data = ['key' => 'new_value'];
		$result = $this->view->content($name, $data);

		$this->assertIsString($result);
		$this->assertSame('new_value', $result);

		$result = $this->view->content($name, $data, $options);

		$this->assertIsString($result);
		$this->assertSame('value', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWidget(): void {
		$result = $this->view->widget('test_widget', ['key' => 'value']);

		$this->assertIsString($result);
		$this->assertSame('value', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testWidgetThrowsExceptionForMissingWidget(): void {
		$this->expectException(MissingWidgetException::class);
		$this->view->widget('not_existing_test_widget', ['key' => 'value'], ['ignoreMissing' => false]);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testWidgetThrowsNoExceptionForMissingWidgetWhenIgnoring(): void {
		$result = $this->view->widget('not_existing_test_widget', ['key' => 'value'], ['ignoreMissing' => true]);

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWidgetCache(): void {
		$name = 'test_widget';
		$data = ['key' => 'value'];
		$options = ['cache' => ['key' => 'test_key', 'config' => 'default']];

		$result = $this->view->widget($name, $data, $options);

		$this->assertIsString($result);
		$this->assertSame('value', $result);

		$data = ['key' => 'new_value'];
		$result = $this->view->widget($name, $data);

		$this->assertIsString($result);
		$this->assertSame('new_value', $result);

		$result = $this->view->widget($name, $data, $options);

		$this->assertIsString($result);
		$this->assertSame('value', $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetWebfontData(): void {
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->method('getDesignVariables')->willReturn($this->designVariables);

		$requestMock = $this->createMock(ServerRequest::class);
		$requestMock->method('getAttribute')->with('design')->willReturn($designMiddlewareMock);

		$this->view->setRequest($requestMock);

		$result = $this->callProtectedMethod($this->view, 'getWebfontData');

		$expectedResult = [
			'fontNameMain' => [
				'name' => 'Red Hat Text',
				'variants' => ['300', '300i', '400', '400i', '700', '700i'],
			],
			'fontNameAlternative' => [
				'name' => 'Merienda',
				'variants' => ['400', '700'],
			],
		];

		$this->assertSame($expectedResult, $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetContentFileName(): void {
		$name = 'test_content';
		$result = $this->view->content($name);
		$this->assertIsString($result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetWidgetFileName(): void {
		$name = 'test_widget';
		$result = $this->view->widget($name);
		$this->assertIsString($result);
	}
}
