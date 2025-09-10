<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View;


use Awyiss\Awyiss;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Model\Entity\Language;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\Exception\MissingContentException;
use Awyiss\View\Exception\MissingWidgetException;
use Awyiss\View\FrontendView;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;


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
				'plugin' => null,
			],
		]);

		$this->view = new FrontendView($request);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::initialize()
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
	 * @see \Awyiss\View\FrontendView::initialize()
	 * @throws \Twig\Error\LoaderError
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTwigHasGlobals(): void {
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->method('getDesignVariables')->willReturn($this->designVariables);

		$request = $this->view->getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);

		$this->view->setRequest($request);

		$this->view->initialize();

		$twig = $this->view->getTwig();

		$globals = $twig->getGlobals();

		$this->assertArrayHasKey('baseUrl', $globals);
		$this->assertSame('http://localhost/', $globals['baseUrl']);

		$this->assertArrayHasKey('config', $globals);
		$this->assertIsArray($globals['config']);

		$this->assertArrayHasKey('currentLanguage', $globals);
		$this->assertInstanceOf(Language::class, $globals['currentLanguage']);

		$this->assertArrayHasKey('currentPath', $globals);
		$this->assertSame('/', $globals['currentPath']);

		$this->assertArrayHasKey('currentUrl', $globals);
		$this->assertSame('http://localhost/', $globals['currentUrl']);

		$this->assertArrayHasKey('designSettings', $globals);
		$this->assertSame($this->designVariables, $globals['designSettings']);

		$this->assertArrayHasKey('environment', $globals);
		$this->assertSame(null, $globals['environment']);

		$this->assertArrayHasKey('folder', $globals);
		$this->assertSame('/', $globals['folder']);

		$this->assertArrayHasKey('languages', $globals);
		$this->assertIsArray($globals['languages']);

		$this->assertArrayHasKey('languageShortcode', $globals);
		$this->assertSame('de', $globals['languageShortcode']);

		$this->assertArrayHasKey('previewMode', $globals);
		$this->assertSame([], $globals['previewMode']);

		$this->assertArrayHasKey('webfont', $globals);
		$this->assertSame([
			'fontNameMain' => [
				'name' => 'Red Hat Text',
				'variants' => ['300', '300i', '400', '400i', '700', '700i'],
			],
			'fontNameAlternative' => [
				'name' => 'Merienda',
				'variants' => ['400', '700'],
			],
		], $globals['webfont']);

		$this->assertArrayHasKey('webfontTimestamp', $globals);
		$this->assertSame(filemtime(ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'css' . DS . 'webfonts.css'), $globals['webfontTimestamp']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::getRowClass()
	 * @see \Awyiss\View\FrontendView::setRowClass()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAndGetRowClass(): void {
		FrontendView::setRowClass('test-class');
		$this->assertSame('test-class', FrontendView::getRowClass());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\BackendView::getLoginLogoPath()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLoginLogoPath(): void {
		$path = $this->callProtectedMethod($this->view, 'getLoginLogoPath');

		$this->assertSame('/assets/img/login-logo.png', $path);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::setOgImage()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetOgImage(): void {
		$this->view->set('ogImage');

		$this->callProtectedMethod($this->view, 'setOgImage');

		$ogImage = $this->view->get('ogImage');

		$this->assertSame('http://localhost/assets/img/login-logo.png', $ogImage);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::setOgImage()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetOgImageSkipsIfExistsBySet(): void {
		$this->view->set('ogImage', 'existing-image.png');

		$this->callProtectedMethod($this->view, 'setOgImage');

		$ogImage = $this->view->get('ogImage');

		$this->assertSame('existing-image.png', $ogImage);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::setOgImage()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetOgImageSkipsIfExistsByAssign(): void {
		$this->view->assign('ogImage', 'existing-image.png');

		$this->callProtectedMethod($this->view, 'setOgImage');

		$ogImage = $this->view->fetch('ogImage');

		$this->assertSame('existing-image.png', $ogImage);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::content()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testContent(): void {
		$result = $this->view->content('test_content', ['key' => 'value']);

		$this->assertIsString($result);
		$this->assertSame('value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::content()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testContentThrowsExceptionForMissingContent(): void {
		$this->expectException(MissingContentException::class);
		$this->view->content('not_existing_test_content', ['key' => 'value'], ['ignoreMissing' => false]);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::content()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testContentThrowsNoExceptionForMissingContentWhenIgnoring(): void {
		$result = $this->view->content('not_existing_test_content', ['key' => 'value'], ['ignoreMissing' => true]);

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::content()
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
	 * @see \Awyiss\View\FrontendView::widget()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWidget(): void {
		$result = $this->view->widget('test_widget', ['key' => 'value']);

		$this->assertIsString($result);
		$this->assertSame('value', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::widget()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWidgetThrowsExceptionForMissingWidget(): void {
		$this->expectException(MissingWidgetException::class);
		$this->view->widget('not_existing_test_widget', ['key' => 'value'], ['ignoreMissing' => false]);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::widget()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWidgetThrowsNoExceptionForMissingWidgetWhenIgnoring(): void {
		$result = $this->view->widget('not_existing_test_widget', ['key' => 'value'], ['ignoreMissing' => true]);

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::widget()
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
	 * @see \Awyiss\View\FrontendView::getWebfontData()
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
	 * @see \Awyiss\View\FrontendView::getContentFileName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetContentFileName(): void {
		$name = 'test_content';
		$result = $this->view->content($name);
		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\FrontendView::getContentFileName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetContentFileNameWithData(): void {
		$name = 'test_content';
		$result = $this->view->content($name, ['key' => 'value']);
		$this->assertSame('value', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetWidgetFileName(): void {
		$name = 'test_widget';
		$result = $this->view->widget($name);
		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetWidgetFileNameWithData(): void {
		$name = 'test_widget';
		$result = $this->view->widget($name, ['key' => 'value']);
		$this->assertSame('value', $result);
	}
}
