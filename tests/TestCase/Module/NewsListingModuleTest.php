<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Module;


use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Awyiss\Module\NewsListingModule;
use Awyiss\ORM\Locator\TableLocator;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\ResultSet;
use Customer\Model\Enum\PageRole;
use ReflectionClass;


/**
 * Test case for NewsListingModule
 *
 * @see \Awyiss\Module\NewsListingModule
 */
class NewsListingModuleTest extends TestCase {
	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $mockBackendView;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $mockFrontendView;
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $mockNewsTable;
	/**
	 * @var \Cake\ORM\Query\SelectQuery
	 */
	protected SelectQuery $mockQuery;
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
		$this->mockNewsTable = $this->createMock(Table::class);
		$this->mockQuery = $this->createMock(SelectQuery::class);

		// Set up the table locator
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->tableLocator = FactoryLocator::get('Table');

		// Mock the FactoryLocator to return our mock table
		$mockTableLocator = $this->createMock(TableLocator::class);
		$mockTableLocator->method('get')->with('News')->willReturn($this->mockNewsTable);
		FactoryLocator::add('Table', $mockTableLocator);

		// Mock Router request
		$mockRequest = $this->createMock(ServerRequest::class);
		$mockRequest->method('getQueryParams')->willReturn([]);
		Router::setRequest($mockRequest);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		parent::tearDown();

		// Reset static properties
		$reflection = new ReflectionClass(NewsListingModule::class);
		$property = $reflection->getProperty('isPreview');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, null);
	}


	/**
	 * Test getTitle method returns 'News-Listing'
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::getTitle()
	 */
	public function testGetTitle(): void {
		$result = NewsListingModule::getTitle();

		$this->assertSame('News-Listing', $result);
	}


	/**
	 * Test getFormFields method with default settings
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::getFormFields()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetFormFieldsWithDefaults(): void {
		// Mock the news table for getCategoriesField
		$this->mockNewsTable->method('hasBehavior')->with('Categories')->willReturn(false);

		$result = NewsListingModule::getFormFields($this->mockBackendView);

		$this->assertIsArray($result);

		// Test basic form fields
		$this->assertArrayHasKey('settings.titleTag', $result);
		$this->assertArrayHasKey('settings.paginate', $result);
		$this->assertArrayHasKey('settings.items', $result);
		$this->assertArrayHasKey('settings.offset', $result);

		// Test default values
		$this->assertSame('h3', $result['settings.titleTag']['value']);
		$this->assertSame('select', $result['settings.titleTag']['type']);
		$this->assertSame('checkbox', $result['settings.paginate']['type']);
		$this->assertSame('number', $result['settings.items']['type']);
		$this->assertSame('number', $result['settings.offset']['type']);

		// Test title tag options
		$expectedOptions = ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6'];
		$this->assertSame($expectedOptions, $result['settings.titleTag']['options']);

		// Test items and offset field properties
		$this->assertSame(20, $result['settings.items']['max']);
		$this->assertSame(1, $result['settings.items']['min']);
		$this->assertSame(3, $result['settings.items']['placeholder']);
		$this->assertTrue($result['settings.items']['required']);

		$this->assertSame(20, $result['settings.offset']['max']);
		$this->assertSame(1, $result['settings.offset']['min']);
		$this->assertSame(0, $result['settings.offset']['placeholder']);
	}


	/**
	 * Test getFormFields method with pagination enabled
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::getFormFields()
	 */
	public function testGetFormFieldsWithPaginationEnabled(): void {
		$settings = ['paginate' => true];

		// Mock the news table for getCategoriesField
		$this->mockNewsTable->method('hasBehavior')->with('Categories')->willReturn(false);

		$result = NewsListingModule::getFormFields($this->mockBackendView, null, null, $settings);

		// Should have itemsPerPage field instead of items and offset
		$this->assertArrayHasKey('settings.itemsPerPage', $result);
		$this->assertArrayNotHasKey('settings.items', $result);
		$this->assertArrayNotHasKey('settings.offset', $result);

		// Test itemsPerPage field properties
		$this->assertSame('number', $result['settings.itemsPerPage']['type']);
		$this->assertSame(100, $result['settings.itemsPerPage']['max']);
		$this->assertSame(1, $result['settings.itemsPerPage']['min']);
		$this->assertSame(9, $result['settings.itemsPerPage']['placeholder']);
		$this->assertTrue($result['settings.itemsPerPage']['required']);
		$this->assertSame(6, $result['settings.itemsPerPage']['columnSpan']);
	}


	/**
	 * Test getFormFields method with custom settings
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::getFormFields()
	 */
	public function testGetFormFieldsWithCustomSettings(): void {
		$settings = [
			'titleTag' => 'h2',
			'items' => 5,
			'offset' => 2,
			'itemsPerPage' => 15,
		];

		// Mock the news table for getCategoriesField
		$this->mockNewsTable->method('hasBehavior')->with('Categories')->willReturn(false);

		$result = NewsListingModule::getFormFields($this->mockBackendView, null, null, $settings);

		$this->assertSame('h2', $result['settings.titleTag']['value']);
		$this->assertSame(5, $result['settings.items']['value']);
		$this->assertSame(2, $result['settings.offset']['value']);
	}


	/**
	 * Test getFormFields method with categories enabled
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::getFormFields()
	 */
	public function testGetFormFieldsWithCategoriesEnabled(): void {
		$settings = ['categories' => [30]];

		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		$newsTable = $this->tableLocator->get('News');
		$newsTable->getBehavior('Categories')->setConfig([
			'enabled' => true,
			'categories' => [
				11 => 'Technology',
				30 => 'Business',
				42 => 'Sports',
			],
			'useDatasource' => false,
		]);

		$result = NewsListingModule::getFormFields($this->mockBackendView, null, null, $settings);

		// Basic fields should always be present
		$this->assertArrayHasKey('settings.titleTag', $result);
		$this->assertArrayHasKey('settings.paginate', $result);
		$this->assertArrayHasKey('settings.items', $result);
		$this->assertArrayHasKey('settings.offset', $result);

		// Categories field should be present with proper structure
		$this->assertArrayHasKey('settings.categories', $result);
		$this->assertSame('select', $result['settings.categories']['type']);
		$this->assertTrue($result['settings.categories']['multiple']);
		$this->assertSame([30], $result['settings.categories']['value']);
		$this->assertSame([11 => 'Technology', 30 => 'Business', 42 => 'Sports'], $result['settings.categories']['options']);
	}


	/**
	 * Test getCategoriesField method with no categories available
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::getCategoriesField()
	 */
	public function testGetCategoriesFieldWithNoCategoriesAvailable(): void {
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		$newsTable = $this->tableLocator->get('News');
		$newsTable->getBehavior('Categories')->setConfig([
			'enabled' => true,
			'categories' => [],
			'useDatasource' => false,
		]);

		$result = NewsListingModule::getFormFields($this->mockBackendView);

		// Categories field should not be present with proper structure
		$this->assertArrayNotHasKey('settings.categories', $result);
	}


	/**
	 * Test isAvailable method
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::isAvailable()
	 */
	public function testIsAvailable(): void {
		$result = NewsListingModule::isAvailable();

		$this->assertTrue($result);
	}


	/**
	 * Test render method with default settings - using real table
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithDefaultSettings(): void {
		$settings = [];

		// Use the real table locator for this test
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		// Mock the view element method to capture what gets passed to it
		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/news_listing',
			$this->callback(function (array $params): bool {
				$this->assertArrayHasKey('entity', $params);
				$this->assertArrayHasKey('frontendLanguage', $params);
				$this->assertArrayHasKey('mediaRenderOptions', $params);
				$this->assertArrayHasKey('news', $params);
				$this->assertArrayHasKey('paginate', $params);
				$this->assertArrayHasKey('items', $params);
				$this->assertArrayHasKey('itemsPerPage', $params);
				$this->assertArrayHasKey('offset', $params);
				$this->assertArrayHasKey('settings', $params);

				// Test default values
				$this->assertFalse($params['paginate']);
				$this->assertSame(3, $params['items']);
				$this->assertSame(9, $params['itemsPerPage']);
				$this->assertSame(0, $params['offset']);

				return true;
			})
		)->willReturn('<div class="news-listing">Rendered news</div>');

		$result = NewsListingModule::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<div class="news-listing">Rendered news</div>', $result);
	}


	/**
	 * Test render method with pagination enabled
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithPaginationEnabled(): void {
		$settings = [
			'paginate' => true,
			'itemsPerPage' => 15,
		];

		// Use the real table locator
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/news_listing',
			$this->callback(function (array $params): bool {
				$this->assertTrue($params['paginate']);
				$this->assertSame(15, $params['itemsPerPage']);
				// 5 items, since one is inactive
				$this->assertCount(5, $params['news']);

				// Verify that the news items contains no inactive items
				$this->assertEquals(
					0,
					$params['news']->items()->extract('active')->filter(function ($active) {
						return $active !== true;
					})->count()
				);

				return true;
			})
		)->willReturn('<div class="news-listing paginated">Paginated news</div>');

		$result = NewsListingModule::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<div class="news-listing paginated">Paginated news</div>', $result);
	}


	/**
	 * Test render method with custom items and offset
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::render()
	 */
	public function testRenderWithCustomItemsAndOffset(): void {
		$settings = [
			'items' => 2,
			'offset' => 3,
		];

		// Use the real table locator
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		$this->mockFrontendView->method('element')->with(
			'module/news_listing',
			$this->callback(function (array $params): bool {
				$this->assertSame(2, $params['items']);
				$this->assertSame(3, $params['offset']);
				// 2 items should be returned
				$this->assertCount(2, $params['news']);

				return true;
			})
		)->willReturn('<div>Custom news</div>');

		$result = NewsListingModule::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<div>Custom news</div>', $result);
	}


	/**
	 * Test render method with categories filter - proper test with query verification
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithCategoriesFilter(): void {
		$settings = [
			'categories' => [1, 2, 3],
		];

		// Mock the query chain and verify where clause is called correctly
		$this->mockQuery->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->method('limit')->willReturn($this->mockQuery);
		$this->mockQuery->method('offset')->willReturn($this->mockQuery);

		// This is the critical test - verify where is called with correct parameters
		$this->mockQuery->expects($this->once())->method('where')->with(['parent_id IN' => [1, 2, 3]])->willReturn($this->mockQuery);

		$mockResultSet = $this->createMock(ResultSet::class);
		$mockResultSet->method('toArray')->willReturn([]);
		$this->mockQuery->method('all')->willReturn($mockResultSet);

		$this->mockNewsTable->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/news_listing',
			$this->callback(function (array $params): bool {
				$this->assertArrayHasKey('news', $params);
				$this->assertArrayHasKey('settings', $params);
				$this->assertSame([1, 2, 3], $params['settings']['categories']);

				return true;
			})
		)->willReturn('<div>Filtered news</div>');

		$result = NewsListingModule::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<div>Filtered news</div>', $result);
	}


	/**
	 * Test render method with news category page entity - proper test with query verification
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderWithNewsCategoryPageEntity(): void {
		$settings = [];

		// Mock a page entity with news category role
		$mockPage = new Page();
		$mockPage->id = 5;
		$mockPage->pageRoleId = PageRole::Newscategory;

		// Mock the query chain and verify where clause is called correctly
		$this->mockQuery->method('find')->willReturn($this->mockQuery);
		$this->mockQuery->method('orderBy')->willReturn($this->mockQuery);
		$this->mockQuery->method('limit')->willReturn($this->mockQuery);
		$this->mockQuery->method('offset')->willReturn($this->mockQuery);

		// This is the critical test - verify where is called with correct entity ID
		$this->mockQuery->expects($this->once())->method('where')->with(['parent_id' => 5])->willReturn($this->mockQuery);

		$mockResultSet = $this->createMock(ResultSet::class);
		$mockResultSet->method('toArray')->willReturn([]);
		$this->mockQuery->method('all')->willReturn($mockResultSet);

		$this->mockNewsTable->method('find')->willReturn($this->mockQuery);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/news_listing',
			$this->callback(function (array $params): bool {
				$this->assertArrayHasKey('entity', $params);
				$this->assertInstanceOf(Page::class, $params['entity']);
				$this->assertSame(5, $params['entity']->id);

				return true;
			})
		)->willReturn('<div>Category news</div>');

		$result = NewsListingModule::render(
			$settings,
			$this->mockFrontendView,
			null,
			$mockPage
		);

		$this->assertSame('<div>Category news</div>', $result);
	}


	/**
	 * Test render method in preview mode - proper test
	 *
	 * @return void
	 * @see \Awyiss\Module\NewsListingModule::render()
	 * @throws \ReflectionException
	 */
	public function testRenderInPreviewMode(): void {
		$settings = ['items' => 15];

		// Set preview mode using reflection
		$reflection = new ReflectionClass(NewsListingModule::class);
		$property = $reflection->getProperty('isPreview');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, true);

		// Use the real table locator
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		$this->mockFrontendView->expects($this->once())->method('element')->with(
			'module/news_listing',
			$this->callback(function (array $params): bool {
				$this->assertArrayHasKey('news', $params);
				$this->assertFalse($params['paginate']);
				$this->assertSame(15, $params['items']);
				// 6 items in preview mode
				$this->assertCount(6, $params['news']);

				// Verify that the news items contains one inactive item
				$this->assertEquals(
					1,
					$params['news']->extract('active')->filter(function ($active) {
						return $active !== true;
					})->count()
				);

				return true;
			})
		)->willReturn('<div>Preview news</div>');

		$result = NewsListingModule::render(
			$settings,
			$this->mockFrontendView,
			null
		);

		$this->assertSame('<div>Preview news</div>', $result);
	}
}
