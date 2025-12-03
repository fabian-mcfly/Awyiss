<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Module;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Module\BreadcrumbsModule;
use Awyiss\ORM\Locator\TableLocator;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\TestSuite\IntegrationTestTrait;
use ReflectionClass;


/**
 * Test case for BreadcrumbsModule
 *
 * @see \Awyiss\Module\BreadcrumbsModule
 */
class BreadcrumbsModuleTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $mockBackendView;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $mockFrontendView;
	/**
	 * @var \Awyiss\Model\Entity\Language
	 */
	protected Language $mockLanguage;
	/**
	 * @var \Awyiss\Model\Table\PagesTable
	 */
	protected PagesTable $mockPagesTable;
	/**
	 * @var \Cake\ORM\Query
	 */
	protected Query $mockQuery;
	/**
	 * @var \Awyiss\ORM\Locator\TableLocator
	 */
	protected TableLocator $tableLocator;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockBackendView = $this->createMock(BackendView::class);
		$this->mockFrontendView = $this->createMock(FrontendView::class);
		$this->mockLanguage = $this->createMock(Language::class);
		$this->mockPagesTable = $this->createMock(PagesTable::class);
		$this->mockQuery = $this->createMock(Query::class);

		// Set up the table locator
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->tableLocator = FactoryLocator::get('Table');

		// Mock the FactoryLocator to return our mock table
		$mockTableLocator = $this->createMock(TableLocator::class);
		$mockTableLocator->method('get')->with('Pages')->willReturn($this->mockPagesTable);
		FactoryLocator::add('Table', $mockTableLocator);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		parent::tearDown();

		$reflection = new ReflectionClass(BreadcrumbsModule::class);
		$property = $reflection->getProperty('isPreview');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		// Reset the static property to null
		$property->setValue(null, null);
	}


	/**
	 * Test getTitle method returns 'Breadcrumbs'
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::getTitle()
	 */
	public function testGetTitle(): void {
		$result = BreadcrumbsModule::getTitle();

		$this->assertSame('Breadcrumbs', $result);
	}


	/**
	 * Test getFormFields method with default settings
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::getFormFields()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetFormFieldsWithDefaults(): void {
		// Mock the query chain for getHomepageOptions
		$this->mockQuery->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->method('all')->willReturn($this->createMock(ResultSet::class));
		$this->mockPagesTable->method('find')->willReturn($this->mockQuery);

		// Test the structure without trying to mock static methods
		$result = BreadcrumbsModule::getFormFields($this->mockBackendView);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('settings.includeHomepage', $result);
		$this->assertArrayHasKey('settings.includeCurrentPage', $result);
		$this->assertArrayHasKey('settings.showOnHomepage', $result);
		$this->assertArrayHasKey('settings.homepageId', $result);

		// Test default values
		$this->assertTrue($result['settings.includeHomepage']['checked']);
		$this->assertTrue($result['settings.includeCurrentPage']['checked']);
		$this->assertFalse($result['settings.showOnHomepage']['checked']);
		$this->assertNull($result['settings.homepageId']['value']);

		// Test field types
		$this->assertSame('checkbox', $result['settings.includeHomepage']['type']);
		$this->assertSame('checkbox', $result['settings.includeCurrentPage']['type']);
		$this->assertSame('checkbox', $result['settings.showOnHomepage']['type']);
		$this->assertSame('select', $result['settings.homepageId']['type']);

		// Test that options is an array (from getHomepageOptions call)
		$this->assertIsArray($result['settings.homepageId']['options']);
	}


	/**
	 * Test getFormFields method with custom settings
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::getFormFields()
	 */
	public function testGetFormFieldsWithCustomSettings(): void {
		$settings = [
			'includeHomepage' => 'includeHomepage',
			'includeCurrentPage' => 'includeCurrentPage',
			'showOnHomepage' => 'showOnHomepage',
			'homepageId' => 5,
		];

		$result = BreadcrumbsModule::getFormFields($this->mockBackendView, null, null, $settings);

		$this->assertSame('includeHomepage', $result['settings.includeHomepage']['checked']);
		$this->assertSame('includeCurrentPage', $result['settings.includeCurrentPage']['checked']);
		$this->assertSame('showOnHomepage', $result['settings.showOnHomepage']['checked']);
		$this->assertSame(5, $result['settings.homepageId']['value']);
	}


	/**
	 * Test render method with default settings
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithDefaultSettings(): void {
		$settings = [];
		$entity = $this->createMock(Entity::class);
		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		// Mock homepage entity
		$mockHomepage = $this->createMock(Page::class);
		$mockHomepage->id = 1;

		// Mock request and path
		$mockRequest = $this->createMock(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/about/team');
		Router::setRequest($mockRequest);

		// Mock pages table query chain
		$this->mockQuery->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->method('where')->willReturn($this->mockQuery);
		$this->mockQuery->method('first')->willReturn($mockHomepage);
		$this->mockQuery->method('all')->willReturn($this->createMock(ResultSet::class));

		$this->mockPagesTable->method('find')->willReturn($this->mockQuery);
		$this->mockPagesTable->method('get')->willReturn($mockHomepage);

		$this->mockFrontendView->expects($this->once())->method('element')->willReturn('<nav class="breadcrumbs">Rendered breadcrumbs</nav>');

		$result = BreadcrumbsModule::render(
			$settings,
			$this->mockFrontendView,
			$mediaRenderOptions,
			$entity,
			$this->mockLanguage
		);

		$this->assertSame('<nav class="breadcrumbs">Rendered breadcrumbs</nav>', $result);
	}


	/**
	 * Test render method with custom homepage ID
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithCustomHomepageId(): void {
		$settings = [
			'includeHomepage' => true,
			'includeCurrentPage' => false,
			'showOnHomepage' => true,
			'homepageId' => 5,
		];

		$mockHomepage = new Page();
		$mockHomepage->id = 5;

		$mockRequest = $this->createMock(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/products/widgets');
		Router::setRequest($mockRequest);

		$this->mockPagesTable->method('get')->with(5)->willReturn($mockHomepage);

		// Mock the query chain for path pages
		$this->mockQuery->method('where')->willReturn($this->mockQuery);
		$this->mockQuery->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->method('all')->willReturn($this->createMock(ResultSet::class));

		$this->mockPagesTable->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/breadcrumbs',
			$this->callback(function (array $params): bool {
				$this->assertArrayHasKey('includeHomepage', $params);
				$this->assertArrayHasKey('includeCurrentPage', $params);
				$this->assertArrayHasKey('showOnHomepage', $params);
				$this->assertArrayHasKey('homepageId', $params);
				$this->assertArrayHasKey('homepage', $params);
				$this->assertArrayHasKey('pages', $params);

				$this->assertTrue($params['includeHomepage']);
				$this->assertFalse($params['includeCurrentPage']);
				$this->assertTrue($params['showOnHomepage']);
				$this->assertSame(5, $params['homepageId']);
				$this->assertInstanceOf(Page::class, $params['homepage']);
				$this->assertArrayHasKey(5, $params['pages']);

				return true;
			})
		)->willReturn('<nav class="breadcrumbs custom">Custom breadcrumbs</nav>');

		$result = BreadcrumbsModule::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<nav class="breadcrumbs custom">Custom breadcrumbs</nav>', $result);
	}


	/**
	 * Test render method with preview mode
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderInPreviewMode(): void {
		$settings = [];

		// Mock session to simulate preview mode
		$mockSession = $this->createMock(Session::class);
		$mockSession->method('read')->with('previewMode.enabled', false)->willReturn(true);

		$mockRequest = $this->createMock(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/news');
		$mockRequest->method('getSession')->willReturn($mockSession);
		Router::setRequest($mockRequest);

		$mockHomepage = $this->createMock(Page::class);
		$mockHomepage->id = 1;

		// In preview mode, the query should NOT call find('published')
		$this->mockQuery->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->method('find')->with([])->willReturn($this->mockQuery);
		$this->mockQuery->method('first')->willReturn($mockHomepage);
		$this->mockQuery->method('all')->willReturn($this->createMock(ResultSet::class));

		$this->mockPagesTable->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->method('element')->willReturn('<nav>Preview breadcrumbs</nav>');

		$result = BreadcrumbsModule::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<nav>Preview breadcrumbs</nav>', $result);
	}


	/**
	 * Test getHomepageOptions method
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::getHomepageOptions()
	 * @throws \ReflectionException
	 */
	public function testGetHomepageOptions(): void {
		// Use the real table locator in this test
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		$result = $this->callProtectedMethod(
			BreadcrumbsModule::class,
			'getHomepageOptions',
			[],
			$this->mockPagesTable
		);

		$this->assertSame([
			1 => ' Startseite',
			2 => ' Über uns',
			3 => '-  Unternehmensgeschichte',
			4 => '-  Mission und Vision',
			5 => '-  Teamvorstellung',
			6 => '-  Zertifikate und Auszeichnungen',
			7 => '-  Aktuelles',
			8 => ' Dienstleistungen',
			9 => '-  Seefracht',
			10 => '-  Luftfracht',
			11 => '-  Landtransport',
			12 => '-  Lagerung und Logistik',
			13 => '-  Zollabwicklung',
			14 => ' Flotte',
			15 => '-  Übersicht der Schiffe',
			16 => '-  Technische Daten',
			17 => '-  Sicherheitsstandards',
			18 => '-  Umweltfreundlichkeit',
			19 => ' Kundenbereich',
			20 => '-  Anmeldung/Registrierung',
			22 => '-  Dokumentenverwaltung',
			23 => '-  Rechnungsübersicht',
			24 => ' Karriere',
			25 => '-  Offene Stellen',
			26 => '-  Ausbildungsprogramme',
			27 => '-  Mitarbeiterbenefits',
			28 => '-  Bewerbungsprozess',
			29 => ' Kontakt',
			30 => ' Impressum',
			31 => ' Datenschutzrichtlinien',
			32 => ' Fehler 404',
			33 => ' Fehler 410',
		], $result);


		$request = new ServerRequest([
			'url' => '/es/about/team/john',
			'params' => [
				'lang' => 'es',
			],
		]);
		Router::setRequest($request);

		// Try it again for a different language
		$result = $this->callProtectedMethod(
			BreadcrumbsModule::class,
			'getHomepageOptions',
			[],
			$this->mockPagesTable
		);

		$this->assertSame([50 => ' Startseite (Spanish)'], $result);
	}


	/**
	 * Test render method with empty path
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithEmptyPath(): void {
		$settings = ['includeHomepage' => true];

		$mockHomepage = new Page();
		$mockHomepage->id = 1;

		$mockRequest = $this->createMock(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/');
		Router::setRequest($mockRequest);

		$this->mockQuery->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->method('where')->willReturn($this->mockQuery);
		$this->mockQuery->method('first')->willReturn($mockHomepage);

		$this->mockPagesTable->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/breadcrumbs',
			$this->callback(function (array $params): bool {
				// With empty path, pages array should only contain homepage
				return $params['homepageId'] === 1 && count($params['pages']) === 1;
			})
		)->willReturn('<nav class="breadcrumbs home">Home breadcrumbs</nav>');

		$result = BreadcrumbsModule::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<nav class="breadcrumbs home">Home breadcrumbs</nav>', $result);
	}


	/**
	 * Test render method when excluding homepage and current page
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderExcludingHomepageAndCurrentPage(): void {
		$settings = [
			'includeHomepage' => false,
			'includeCurrentPage' => false,
		];

		$mockHomepage = new Page();
		$mockHomepage->id = 1;

		$mockRequest = $this->createMock(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/about/team/john');
		Router::setRequest($mockRequest);

		$this->mockQuery->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->method('where')->willReturn($this->mockQuery);
		$this->mockQuery->method('first')->willReturn($mockHomepage);
		$this->mockQuery->method('all')->willReturn($this->createMock(ResultSet::class));

		$this->mockPagesTable->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/breadcrumbs',
			$this->callback(function ($params) {
				return $params['includeHomepage'] === false && $params['includeCurrentPage'] === false;
			})
		)->willReturn('<nav class="breadcrumbs minimal">Minimal breadcrumbs</nav>');

		$result = BreadcrumbsModule::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<nav class="breadcrumbs minimal">Minimal breadcrumbs</nav>', $result);
	}


	/**
	 * Test render without mocking the frontend view
	 *
	 * @return void
	 * @see \Awyiss\Module\BreadcrumbsModule::render()
	 */
	public function testRender(): void {
		// Use the real table locator in this test
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		$this->configApplication(Awyiss::class, []);
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		$this->loadRoutes();
		$request = new ServerRequest([
			'url' => '/de/kundenbereich/dokumentenverwaltung/',
			'params' => [
				'lang' => 'de',
				'prefix' => 'Frontend',
				'_name' => 'Frontend',
			],
		]);
		Router::setRequest($request);

		$result = BreadcrumbsModule::render(
			[],
			new FrontendView(),
			null
		);

		// Test basic structure
		$this->assertStringContainsString('<div class="Module-Breadcrumbs">', $result);
		$this->assertStringContainsString('<ol class="Breadcrumbs">', $result);

		// Test that exactly 3 li elements exist
		$liCount = substr_count($result, '<li class="Breadcrumb">');
		$this->assertSame(3, $liCount, 'Expected exactly 3 breadcrumb list items');

		// Test specific breadcrumb links exist
		$this->assertStringContainsString('<a href="/de/startseite/">Startseite</a>', $result);
		$this->assertStringContainsString('<a href="/de/kundenbereich/">Kundenbereich</a>', $result);
		$this->assertStringContainsString('<a href="/de/kundenbereich/dokumentenverwaltung/">Dokumentenverwaltung</a>', $result);

		// Test the order of breadcrumb links is correct
		$startseitePosistion = strpos($result, '<a href="/de/startseite/">Startseite</a>');
		$kundenbereichPosition = strpos($result, '<a href="/de/kundenbereich/">Kundenbereich</a>');
		$dokumentenverwaltungPosition = strpos($result, '<a href="/de/kundenbereich/dokumentenverwaltung/">Dokumentenverwaltung</a>');

		// Verify the order: Startseite < Kundenbereich < Dokumentenverwaltung
		$this->assertLessThan($kundenbereichPosition, $startseitePosistion, 'Startseite should appear before Kundenbereich');
		$this->assertLessThan($dokumentenverwaltungPosition, $kundenbereichPosition, 'Kundenbereich should appear before Dokumentenverwaltung');
	}
}
