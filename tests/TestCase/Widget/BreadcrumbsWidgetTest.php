<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Widget;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Awyiss\Widget\BreadcrumbsWidget;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use ReflectionClass;


/**
 * Test case for BreadcrumbsWidget
 *
 * @see \Awyiss\Widget\BreadcrumbsWidget
 */
class BreadcrumbsWidgetTest extends TestCase {
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
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockBackendView = $this->createStub(BackendView::class);
		$this->mockFrontendView = $this->createMock(FrontendView::class);
		$this->mockLanguage = $this->createStub(Language::class);

		$this->mockPagesTable = $this->createMock(PagesTable::class);
		$this->getTableLocator()->set('Pages', $this->mockPagesTable);

		$this->mockQuery = $this->createMock(Query::class);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		$this->getTableLocator()->clear();

		parent::tearDown();

		$reflection = new ReflectionClass(BreadcrumbsWidget::class);
		$property = $reflection->getProperty('isPreview');
		$property->setAccessible(true);
		// Reset the static property to null
		$property->setValue(null, null);

		BreadcrumbsWidget::clearCrumbs();
	}


	/**
	 * Test getTitle method returns 'Breadcrumbs'
	 *
	 * @return void
	 * @see \Awyiss\Widget\BreadcrumbsWidget::getTitle()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetTitle(): void {
		$result = BreadcrumbsWidget::getTitle();

		$this->assertSame('Breadcrumbs', $result);
	}


	/**
	 * Test getFormFields method with default settings
	 *
	 * @return void
	 * @see \Awyiss\Widget\BreadcrumbsWidget::getFormFields()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetFormFieldsWithDefaults(): void {
		// Stub the query chain for getHomepageOptions
		$this->mockQuery->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);

		$this->mockPagesTable->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);

		// Test the structure without trying to mock static methods
		$result = BreadcrumbsWidget::getFormFields($this->mockBackendView);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('settings.includeHomepage', $result);
		$this->assertArrayHasKey('settings.includeCurrentPage', $result);
		$this->assertArrayHasKey('settings.showOnHomepage', $result);
		$this->assertArrayHasKey('settings.includeInaccessiblePages', $result);
		$this->assertArrayHasKey('settings.homepageId', $result);

		// Test default values
		$this->assertTrue($result['settings.includeHomepage']['checked']);
		$this->assertTrue($result['settings.includeCurrentPage']['checked']);
		$this->assertFalse($result['settings.showOnHomepage']['checked']);
		$this->assertFalse($result['settings.includeInaccessiblePages']['checked']);
		$this->assertNull($result['settings.homepageId']['value']);

		// Test field types
		$this->assertSame('checkbox', $result['settings.includeHomepage']['type']);
		$this->assertSame('checkbox', $result['settings.includeCurrentPage']['type']);
		$this->assertSame('checkbox', $result['settings.showOnHomepage']['type']);
		$this->assertSame('checkbox', $result['settings.includeInaccessiblePages']['type']);
		$this->assertSame('select', $result['settings.homepageId']['type']);

		// Test that options is an array (from getHomepageOptions call)
		$this->assertIsArray($result['settings.homepageId']['options']);
	}


	/**
	 * Test getFormFields method with custom settings
	 *
	 * @return void
	 * @see \Awyiss\Widget\BreadcrumbsWidget::getFormFields()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetFormFieldsWithCustomSettings(): void {
		$settings = [
			'includeHomepage' => true,
			'includeCurrentPage' => true,
			'showOnHomepage' => true,
			'includeInaccessiblePages' => true,
			'homepageId' => 5,
		];

		$result = BreadcrumbsWidget::getFormFields($this->mockBackendView, null, null, $settings);

		$this->assertTrue($result['settings.includeHomepage']['checked']);
		$this->assertTrue($result['settings.includeCurrentPage']['checked']);
		$this->assertTrue($result['settings.showOnHomepage']['checked']);
		$this->assertTrue($result['settings.includeInaccessiblePages']['checked']);
		$this->assertSame(5, $result['settings.homepageId']['value']);
	}


	/**
	 * Test render method with default settings
	 *
	 * @return void
	 * @see \Awyiss\Widget\BreadcrumbsWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRenderWithDefaultSettings(): void {
		$settings = [];
		$entity = $this->createStub(Entity::class);
		$mediaRenderOptions = $this->createStub(MediaRenderOptions::class);

		// Stub homepage entity
		$mockHomepage = $this->createStub(Page::class);
		$mockHomepage->id = 1;

		// Stub request and path
		$mockRequest = $this->createStub(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/about/team');
		Router::setRequest($mockRequest);

		// Stub pages table query chain
		$this->mockQuery->expects($this->atLeastOnce())->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('where')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('first')->willReturn($mockHomepage);
		$this->mockQuery->expects($this->atLeastOnce())->method('all')->willReturn($this->createStub(ResultSet::class));

		$this->mockPagesTable->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);
		$this->mockPagesTable->expects($this->never())->method('get');

		$this->mockFrontendView->expects($this->once())->method('element')->willReturn('<nav class="breadcrumbs">Rendered breadcrumbs</nav>');

		$result = BreadcrumbsWidget::render(
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
	 * @see \Awyiss\Widget\BreadcrumbsWidget::render()
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

		$mockRequest = $this->createStub(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/products/global_contents');
		Router::setRequest($mockRequest);

		$this->mockPagesTable->expects($this->atLeastOnce())->method('get')->with(5)->willReturn($mockHomepage);

		// Stub the query chain for path pages
		$this->mockQuery->expects($this->never())->method('where');

		$this->mockPagesTable->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/breadcrumbs',
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

		$result = BreadcrumbsWidget::render(
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
	 * @see \Awyiss\Widget\BreadcrumbsWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRenderInPreviewMode(): void {
		$settings = [];

		$reflection = new ReflectionClass(BreadcrumbsWidget::class);
		$property = $reflection->getProperty('isPreview');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		// Reset the static property to null
		$property->setValue(null, null);

		// Stub session to simulate preview mode
		$mockSession = $this->createMock(Session::class);
		$mockSession->expects($this->atLeastOnce())->method('read')->with('previewMode.enabled', false)->willReturn(true);

		$mockRequest = $this->createStub(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/news');
		$mockRequest->method('getSession')->willReturn($mockSession);
		Router::setRequest($mockRequest);

		$mockHomepage = $this->createStub(Page::class);
		$mockHomepage->id = 1;

		// In preview mode, the query should NOT call find('published')
		$this->mockQuery->expects($this->atLeastOnce())->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('first')->willReturn($mockHomepage);

		$this->mockPagesTable->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->atLeastOnce())->method('element')->willReturn('<nav>Preview breadcrumbs</nav>');

		$result = BreadcrumbsWidget::render(
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
	 * @see \Awyiss\Widget\BreadcrumbsWidget::getHomepageOptions()
	 * @throws \ReflectionException
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetHomepageOptions(): void {
		// Use the real table locator in this test
		$this->getTableLocator()->clear();

		$result = $this->callProtectedMethod(
			BreadcrumbsWidget::class,
			'getHomepageOptions',
			[],
			$this->mockPagesTable
		);

		$this->assertSame([
			1 => 'Startseite',
			2 => 'Über uns',
			3 => '- Unternehmensgeschichte',
			4 => '- Mission und Vision',
			5 => '- Teamvorstellung',
			6 => '- Zertifikate und Auszeichnungen',
			7 => '- Aktuelles',
			8 => 'Dienstleistungen',
			9 => '- Seefracht',
			10 => '- Luftfracht',
			11 => '- Landtransport',
			12 => '- Lagerung und Logistik',
			13 => '- Zollabwicklung',
			14 => 'Flotte',
			15 => '- Übersicht der Schiffe',
			16 => '- Technische Daten',
			17 => '- Sicherheitsstandards',
			18 => '- Umweltfreundlichkeit',
			19 => 'Kundenbereich',
			20 => '- Anmeldung/Registrierung',
			22 => '- Dokumentenverwaltung',
			23 => '- Rechnungsübersicht',
			24 => 'Karriere',
			25 => '- Offene Stellen',
			26 => '- Ausbildungsprogramme',
			27 => '- Mitarbeiterbenefits',
			28 => '- Bewerbungsprozess',
			29 => 'Kontakt',
			30 => 'Impressum',
			31 => 'Datenschutzrichtlinien',
			32 => 'Fehler 404',
			33 => 'Fehler 410',
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
			BreadcrumbsWidget::class,
			'getHomepageOptions',
			[],
			$this->mockPagesTable
		);

		$this->assertSame([50 => 'Startseite (Spanish)'], $result);
	}


	/**
	 * Test render method with empty path
	 *
	 * @return void
	 * @see \Awyiss\Widget\BreadcrumbsWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRenderWithEmptyPath(): void {
		$settings = ['includeHomepage' => true];

		$mockHomepage = new Page();
		$mockHomepage->id = 1;

		$mockRequest = $this->createStub(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/');
		Router::setRequest($mockRequest);

		$this->mockQuery->expects($this->atLeastOnce())->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('where')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('first')->willReturn($mockHomepage);

		$this->mockPagesTable->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/breadcrumbs',
			$this->callback(function (array $params): bool {
				// With empty path, pages array should only contain homepage
				return $params['homepageId'] === 1 && count($params['pages']) === 1;
			})
		)->willReturn('<nav class="breadcrumbs home">Home breadcrumbs</nav>');

		$result = BreadcrumbsWidget::render(
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
	 * @see \Awyiss\Widget\BreadcrumbsWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRenderExcludingHomepageAndCurrentPage(): void {
		$settings = [
			'includeHomepage' => false,
			'includeCurrentPage' => false,
		];

		$mockHomepage = new Page();
		$mockHomepage->id = 1;

		$mockRequest = $this->createStub(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/about/team/john');
		Router::setRequest($mockRequest);

		$this->mockQuery->expects($this->atLeastOnce())->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('where')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('first')->willReturn($mockHomepage);
		$this->mockQuery->expects($this->atLeastOnce())->method('all')->willReturn($this->createStub(ResultSet::class));

		$this->mockPagesTable->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/breadcrumbs',
			$this->callback(function ($params) {
				return $params['includeHomepage'] === false && $params['includeCurrentPage'] === false;
			})
		)->willReturn('<nav class="breadcrumbs minimal">Minimal breadcrumbs</nav>');

		$result = BreadcrumbsWidget::render(
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
	 * @see \Awyiss\Widget\BreadcrumbsWidget::render()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRender(): void {
		// Use the real table locator in this test
		$this->getTableLocator()->clear();

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

		$result = BreadcrumbsWidget::render(
			[],
			new FrontendView(),
			null
		);

		// Test basic structure
		$this->assertStringContainsString('<div class="Widget-Breadcrumbs">', $result);
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


	/**
	 * Test registerCrumb method
	 *
	 * @return void
	 * @see \Awyiss\Widget\BreadcrumbsWidget::registerCrumb()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRegisterCrumb(): void {
		BreadcrumbsWidget::registerCrumb(
			'Dashboard',
			'/customer-center/dashboard'
		);

		$crumbs = BreadcrumbsWidget::getAdditionalCrumbs();

		$this->assertCount(1, $crumbs);
		$this->assertSame('Dashboard', $crumbs[0]['title']);
		$this->assertSame('/customer-center/dashboard', $crumbs[0]['url']);
	}


	/**
	 * Test clearCrumbs method
	 *
	 * @return void
	 * @see \Awyiss\Widget\BreadcrumbsWidget::clearCrumbs()
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testClearCrumbs(): void {
		BreadcrumbsWidget::registerCrumb('Title 1', '/url1');
		BreadcrumbsWidget::registerCrumb('Title 2', '/url2');

		$this->assertCount(2, BreadcrumbsWidget::getAdditionalCrumbs());

		BreadcrumbsWidget::clearCrumbs();

		$this->assertEmpty(BreadcrumbsWidget::getAdditionalCrumbs());
	}


	/**
	 * Test render method with additional registered crumbs
	 *
	 * @return void
	 * @see \Awyiss\Widget\BreadcrumbsWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testRenderWithAdditionalCrumbs(): void {
		// Register a custom crumb for customer center
		BreadcrumbsWidget::registerCrumb(
			'Dashboard',
			'/de/kundenbereich/dashboard'
		);

		$settings = [
			'includeHomepage' => true,
			'includeCurrentPage' => true,
		];

		$mockHomepage = new Page();
		$mockHomepage->id = 1;
		$mockHomepage->slug = '';

		$mockRequest = $this->createStub(ServerRequest::class);
		$mockRequest->method('getPath')->willReturn('/de/kundenbereich/dashboard');
		Router::setRequest($mockRequest);

		$this->mockQuery->expects($this->atLeastOnce())->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('where')->willReturn($this->mockQuery);
		$this->mockQuery->expects($this->atLeastOnce())->method('first')->willReturn($mockHomepage);

		// Stub the kundenbereich page
		$mockKundenbereich = new Page();
		$mockKundenbereich->id = 19;
		$mockKundenbereich->slug = 'kundenbereich';
		$mockKundenbereich->title = 'Kundenbereich';

		$mockResultSet = $this->createStub(ResultSet::class);
		$mockResultSet->method('indexBy')->willReturn($mockResultSet);
		$mockResultSet->method('toArray')->willReturn([19 => $mockKundenbereich]);

		$this->mockQuery->expects($this->atLeastOnce())->method('all')->willReturn($mockResultSet);
		$this->mockPagesTable->expects($this->atLeastOnce())->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'widget/breadcrumbs',
			$this->callback(function (array $params): bool {
				// Should have homepage and kundenbereich page
				$this->assertCount(2, $params['pages']);

				// Check that all additional crumbs are passed to the template
				$this->assertArrayHasKey('additionalCrumbs', $params);
				$this->assertCount(1, $params['additionalCrumbs']);
				$this->assertSame('Dashboard', $params['additionalCrumbs'][0]['title']);
				$this->assertSame('/de/kundenbereich/dashboard', $params['additionalCrumbs'][0]['url']);

				return true;
			})
		)->willReturn('<nav class="breadcrumbs">Breadcrumbs with dashboard</nav>');

		$result = BreadcrumbsWidget::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<nav class="breadcrumbs">Breadcrumbs with dashboard</nav>', $result);
	}
}
