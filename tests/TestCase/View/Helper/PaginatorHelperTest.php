<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\PaginatorHelper;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Http\ServerRequest;
use Cake\ORM\ResultSet;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\View;


/**
 * PaginatorHelperTest class
 */
class PaginatorHelperTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Cake\Datasource\Paging\PaginatedResultSet
	 */
	protected PaginatedResultSet $paginatedResult;
	/**
	 * @var \Awyiss\View\Helper\PaginatorHelper
	 */
	protected PaginatorHelper $paginator;
	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $view;


	/**
	 * @return void
	 */
	protected function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		Awyiss::setRealm('Backend');

		$this->loadRoutes();

		$request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'TheController',
				'action' => 'theAction',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [
					'testparam' => 'testvalue',
					'sortDefault' => 'default_label',
					'directionDefault' => 'default_direction',
				],
				'pass' => [],
				'plugin' => null,
			],
		]);

		Router::setRequest($request);

		$this->view = new BackendView($request);
		$this->paginator = new PaginatorHelper($this->view, [
			'templates' => 'paginator_templates',
		]);
	}

	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::__construct()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testConstructorSetsConfig() {
		// Mock the View object
		$view = $this->createMock(View::class);

		// Mock the Request object and its getParam method
		$request = $this->createMock(ServerRequest::class);
		$request->method('getParam')->willReturnMap([
			['parts', [], ['sort' => 'name', 'testparam' => 'testvalue']],
			['pass', [], ['controller' => 'Users', 'action' => 'index']],
		]);

		// Mock the _View property to return the mocked Request
		$view->method('getRequest')->willReturn($request);

		// Instantiate the PaginatorHelper with the mocked View
		$paginatorHelper = new PaginatorHelper($view);

		// Assert that the configuration options are set correctly
		$this->assertEquals('name', $paginatorHelper->getConfig('params.sort'));

		$this->assertEquals(
			[
				'controller' => 'Users',
				'action' => 'index',
				'page' => false,
				'limit' => false,
				'sort' => false,
				'direction' => false,
				'?' => [],
				'testparam' => 'testvalue',
			],
			$paginatorHelper->getConfig('options.url')
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::meta()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMetaReturnsNullWhenNotPaginated() {
		// Mock the View object
		$view = $this->createMock(View::class);

		// Mock the Request object and its getParam method
		$request = $this->createMock(ServerRequest::class);
		$request->method('getParam')->willReturnMap([
			['parts', [], []],
			['pass', [], []],
		]);

		// Mock the _View property to return the mocked Request
		$view->method('getRequest')->willReturn($request);

		// Instantiate the PaginatorHelper with the mocked View
		$paginatorHelper = new PaginatorHelper($view);

		// Assert that the meta method returns null when paginated is not set
		$this->assertNull($paginatorHelper->meta());
	}


	/**
	 * @dataProvider dataMetaProvider
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::meta()
	 */
	public function testMeta($page, $prevPage, $nextPage, $pageCount, $options, $expected) {
		$this->setPaginatedResult([
			'currentPage' => $page,
			'hasPrevPage' => $prevPage,
			'hasNextPage' => $nextPage,
			'pageCount' => $pageCount,
		]);

		$result = $this->paginator->meta($options);

		$this->assertSame($expected, $result);
	}


	/**
	 * Test data for meta()
	 *
	 * @return array
	 */
	public static function dataMetaProvider(): array {
		return [
			// Verifies that no next and prev links are created for single page results.
			[1, false, false, 1, [], ''],
			// Verifies that first and last pages are created for single page results.
			[
				1,
				false,
				false,
				1,
				['first' => true, 'last' => true],
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="first">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="last">',
			],
			// Verifies that first page is created for single page results.
			[1, false, false, 1, ['first' => true], '<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="first">'],
			// Verifies that last page is created for single page results.
			[1, false, false, 1, ['last' => true], '<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="last">'],
			// Verifies that page 1 only has a next link.
			[1, false, true, 2, [], '<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" rel="next">'],
			// Verifies that page 1 only has next, first and last link.
			[
				1,
				false,
				true,
				2,
				['first' => true, 'last' => true],
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" rel="next">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="first">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" rel="last">',
			],
			// Verifies that page 1 only has next and first link.
			[
				1,
				false,
				true,
				2,
				['first' => true],
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" rel="next">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="first">',
			],
			// Verifies that page 1 only has next and last link.
			[
				1,
				false,
				true,
				2,
				['last' => true],
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" rel="next">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" rel="last">',
			],
			// Verifies that the last page only has a prev link.
			[2, true, false, 2, [], '<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="prev">'],
			// Verifies that the last page only has a prev, first and last link.
			[
				2,
				true,
				false,
				2,
				['first' => true, 'last' => true],
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="prev">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="first">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" rel="last">',
			],
			// Verifies that a page in the middle has both links.
			[
				5,
				true,
				true,
				10,
				[],
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:4/" rel="prev">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:6/" rel="next">',
			],
			// Verifies that a page in the middle has both links.
			[
				5,
				true,
				true,
				10,
				['first' => true, 'last' => true],
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:4/" rel="prev">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:6/" rel="next">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/" rel="first">' .
				'<link href="http://localhost/backend/xy/the-controller/the-action/testparam:testvalue/page:10/" rel="last">',
			],
		];
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::sort()
	 */
	public function testSortLink(): void {
		$this->setPaginatedResult([
			'sort' => 'id',
			'direction' => 'asc',
		], false);

		$result = $this->paginator->sort('_never_translated_key_');

		$expected = [
			'a' => ['class', 'href' => '/backend/xy/the-controller/the-action/testparam:testvalue/sort:never-translated-key/direction:asc/'],
			'the_controller::_never_translated_key_',
			'/a',
		];

		$this->assertHtml($expected, $result, true);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::sort()
	 */
	public function testSortLinkAscending(): void {
		$this->setPaginatedResult([
			'sort' => 'id',
			'direction' => 'desc',
		], false);

		$result = $this->paginator->sort('id');

		$expected = [
			'a' => ['class' => 'preg:/.*Sort-Desc.*/', 'href' => '/backend/xy/the-controller/the-action/testparam:testvalue/sort:id/direction:asc/'],
			'the_controller::id',
			'/a',
		];

		$this->assertHtml($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::sort()
	 */
	public function testSortLinkDescending(): void {
		$this->setPaginatedResult([
			'sort' => 'id',
			'direction' => 'asc',
		], false);

		$result = $this->paginator->sort('id');

		$expected = [
			'a' => ['class' => 'preg:/.*Sort-Asc.*/', 'href' => '/backend/xy/the-controller/the-action/testparam:testvalue/sort:id/direction:desc/'],
			'the_controller::id',
			'/a',
		];

		$this->assertHtml($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::sort()
	 */
	public function testSortLinkWithTitle(): void {
		$this->setPaginatedResult([
			'sort' => 'id',
			'direction' => 'asc',
		], false);

		$result = $this->paginator->sort('title', 'TestTitle');

		$expected = [
			'a' => ['class', 'href' => '/backend/xy/the-controller/the-action/testparam:testvalue/sort:title/direction:asc/'],
			'TestTitle',
			'/a',
		];

		$this->assertHtml($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::sort()
	 */
	public function testSortLinkWithAscendingTitle(): void {
		$this->setPaginatedResult([
			'sort' => 'id',
			'direction' => 'asc',
		], false);

		$result = $this->paginator->sort('title', ['asc' => 'ascending']);

		$expected = [
			'a' => ['class' => 'preg:/.*Sort-Title.*/', 'href' => '/backend/xy/the-controller/the-action/testparam:testvalue/sort:title/direction:asc/'],
			'ascending',
			'/a',
		];

		$this->assertHtml($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::sort()
	 */
	public function testSortLinkDescendingTitle(): void {
		$this->setPaginatedResult([
			'sort' => 'id',
			'direction' => 'asc',
		], false);

		$this->setPaginatedResult([
			'sort' => 'title',
		]);

		$result = $this->paginator->sort('title', ['desc' => 'descending']);

		$expected = [
			'a' => ['class' => 'preg:/.*Sort-Asc.*/', 'href' => '/backend/xy/the-controller/the-action/testparam:testvalue/sort:title/direction:desc/'],
			'descending',
			'/a',
		];

		$this->assertHtml($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::sort()
	 */
	public function testSortLinkForAssociation(): void {
		$this->setPaginatedResult([
			'sort' => 'id',
			'direction' => 'asc',
		], false);

		$result = $this->paginator->sort('association.title');

		$expected = [
			'a' => ['class', 'href' => '/backend/xy/the-controller/the-action/testparam:testvalue/sort:association.title/direction:asc/'],
			'the_controller::association.title',
			'/a',
		];

		$this->assertHtml($expected, $result, true);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::sort()
	 */
	public function testSortLinkForAssociationAscending(): void {
		$this->setPaginatedResult([
			'sort' => 'association.title',
			'direction' => 'desc',
		], false);

		$result = $this->paginator->sort('association.title');

		$expected = [
			'a' => ['class' => 'preg:/.*Sort-Desc.*/', 'href' => '/backend/xy/the-controller/the-action/testparam:testvalue/sort:association.title/direction:asc/'],
			'the_controller::association.title',
			'/a',
		];

		$this->assertHtml($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::sort()
	 */
	public function testSortLinkForAssociationDescending(): void {
		$this->setPaginatedResult([
			'sort' => 'association.title',
			'direction' => 'asc',
		], false);

		$result = $this->paginator->sort('association.title');

		$expected = [
			'a' => ['class' => 'preg:/.*Sort-Asc.*/', 'href' => '/backend/xy/the-controller/the-action/testparam:testvalue/sort:association.title/direction:desc/'],
			'the_controller::association.title',
			'/a',
		];

		$this->assertHtml($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::isCurrentSortKey()
	 */
	public function testIsCurrentSortKeySucceeds(): void {
		$this->setPaginatedResult([
			'sort' => 'title',
			'direction' => 'asc',
		], false);

		$this->assertTrue($this->paginator->isCurrentSortKey('title'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::isCurrentSortKey()
	 */
	public function testIsCurrentSortKeyFails(): void {
		$this->setPaginatedResult([
			'sort' => 'title',
			'direction' => 'asc',
		], false);

		$this->assertFalse($this->paginator->isCurrentSortKey('name'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::isCurrentSortKey()
	 */
	public function testIsCurrentSortKeySucceedsWithDotKey(): void {
		$this->setPaginatedResult([
			'sort' => 'association.title',
			'direction' => 'asc',
		], false);

		$this->assertTrue($this->paginator->isCurrentSortKey('association.title'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::isCurrentSortKey()
	 */
	public function testIsCurrentSortKeyFailsWithDotKey(): void {
		$this->setPaginatedResult([
			'sort' => 'association.title',
			'direction' => 'asc',
		], false);

		$this->assertFalse($this->paginator->isCurrentSortKey('association.name'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::currentSortKey()
	 */
	public function testCurrentSortKeySucceedsWithSort(): void {
		$this->setPaginatedResult([
			'sort' => 'title',
			'direction' => 'asc',
		], false);

		$this->assertEquals('title', $this->paginator->currentSortKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::currentSortKey()
	 */
	public function testCurrentSortKeyFailsWithSort(): void {
		$this->setPaginatedResult([
			'sort' => 'name',
			'direction' => 'asc',
		], false);

		$this->assertNotEquals('title', $this->paginator->currentSortKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::currentSortKey()
	 */
	public function testCurrentSortKeyFailsWithoutSort(): void {
		$this->setPaginatedResult([
			'sort' => '',
			'direction' => 'asc',
		], false);

		$this->assertNull($this->paginator->currentSortKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::currentSortKey()
	 */
	public function testCurrentSortKeySucceedsWithSortDefault(): void {
		$this->setPaginatedResult([
			'sort' => '',
			'sortDefault' => 'label',
			'direction' => 'asc',
		], false);

		$this->assertEquals('label', $this->paginator->currentSortKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::currentSortKey()
	 */
	public function testCurrentSortKeyFailsWithSortDefault(): void {
		$this->setPaginatedResult([
			'sort' => '',
			'direction' => 'asc',
		], false);

		$this->assertNull($this->paginator->currentSortKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::currentSortKey()
	 */
	public function testCurrentSortKeySucceedsWithAliasedFields(): void {
		$this->setPaginatedResult([
			'sort' => '',
			'sortDefault' => 'aliasKey',
			'direction' => 'asc',
		], false);

		$this->paginator->setConfig('aliasedFields', [
			'aliasKey' => 'originalKey',
			'nonAliasKey' => 'originalKey',
		]);

		$this->assertEquals('originalKey', $this->paginator->currentSortKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::currentSortKey()
	 */
	public function testCurrentSortKeyFailsWithAliasedFields(): void {
		$this->setPaginatedResult([
			'sort' => '',
			'sortDefault' => 'nonAliasKey',
			'direction' => 'asc',
		], false);

		$this->paginator->setConfig('aliasedFields', [
			'aliasKey' => 'originalKey',
			'nonAliasKey' => null,
		]);

		$this->assertEquals('nonAliasKey', $this->paginator->currentSortKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::generateUrlParams()
	 */
	public function testGenerateUrlParams(): void {
		$result = $this->paginator->generateUrlParams([
			'sort' => 'title',
			'page' => 5,
			'direction' => 'asc',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
		]);

		$this->assertSame([
			'testparam' => 'testvalue',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'sort' => 'title',
			'direction' => 'asc',
			'page' => 5,
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::generateUrlParams()
	 */
	public function testGenerateUrlParamsSetsFirstPageToFalseOnFirstPage(): void {
		$result = $this->paginator->generateUrlParams([
			'sort' => 'title',
			'page' => 1,
			'direction' => 'asc',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
		]);

		$this->assertSame([
			'testparam' => 'testvalue',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'sort' => 'title',
			'direction' => 'asc',
			'page' => false,
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::generateUrlParams()
	 */
	public function testGenerateUrlParamsRemovesSortAndDirectionIfBothAreDefault(): void {
		$result = $this->paginator->generateUrlParams([
			'sort' => 'default_label',
			'direction' => 'default_direction',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
		]);

		$this->assertSame([
			'testparam' => 'testvalue',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'sort' => false,
			'direction' => false,
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::generateUrlParams()
	 */
	public function testGenerateUrlParamsDoesNotRemoveSortAndDirectionIfOneIsNotDefault(): void {
		$result = $this->paginator->generateUrlParams([
			'sort' => 'default_label',
			'direction' => 'not_default_direction',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
		]);

		$this->assertSame([
			'testparam' => 'testvalue',
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3',
			'sort' => 'default-label',
			'direction' => 'not-default-direction',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::limitControl()
	 */
	public function testLimitControl(): void {
		$this->setPaginatedResult([
			'perPage' => 10,
		], false);

		$result = $this->paginator->limitControl();

		$this->assertStringContainsString('<option value="10" title="10" selected="selected">10</option>', $result);
		$this->assertStringContainsString('<option value="20" title="20">20</option>', $result);
		$this->assertStringContainsString('<option value="50" title="50">50</option>', $result);
		$this->assertStringContainsString('<option value="100" title="100">100</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::limitControl()
	 */
	public function testLimitControlWithPerPageMatchingDefault(): void {
		$this->setPaginatedResult([
			'perPage' => 20,
		], false);

		$result = $this->paginator->limitControl();

		$this->assertStringContainsString('<option value="20" title="20" selected="selected">20</option>', $result);
		$this->assertStringContainsString('<option value="50" title="50">50</option>', $result);
		$this->assertStringContainsString('<option value="100" title="100">100</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::limitControl()
	 */
	public function testLimitControlWithPerPageBetweenDefaultOptions(): void {
		$this->setPaginatedResult([
			'perPage' => 35,
		], false);

		$result = $this->paginator->limitControl();

		$this->assertStringContainsString('<option value="20" title="20">20</option><option value="35" title="35" selected="selected">35</option><option value="50" title="50">50</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::limitControl()
	 */
	public function testLimitControlWithCustomLimits(): void {
		$this->setPaginatedResult([
			'perPage' => 22,
		], false);

		$result = $this->paginator->limitControl([
			11 => 11,
			22 => 22,
			33 => 33,
			44 => 44,
		]);

		$this->assertStringContainsString('<option value="11" title="11">11</option>', $result);
		$this->assertStringContainsString('<option value="22" title="22" selected="selected">22</option>', $result);
		$this->assertStringContainsString('<option value="33" title="33">33</option>', $result);
		$this->assertStringContainsString('<option value="44" title="44">44</option>', $result);
	}


	/**
	 * Test that the render method generates the correct pagination links.
	 * Also tests that PaginatorHelper::_numbers() is called correctly.
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::render()
	 * @see \Awyiss\View\Helper\PaginatorHelper::_numbers()
	 */
	public function testRender(): void {
		$this->setPaginatedResult([]);

		$result = $this->paginator->render();

		$this->assertStringNotContainsString('pagination::first', $result);
		$this->assertStringContainsString('<li class="Sort-Prev Disabled"><span class="Arrow Arrow-Prev">pagination::previous</span></li>', $result);
		$this->assertStringContainsString('<li class="Sort-Number Sort-Current"><span class="Number">1</span></li>', $result);
		$this->assertStringContainsString('<li class="Sort-Number"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" class="Number" title="pagination::page 2">2</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Next"><a rel="next" href="/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" class="Arrow Arrow-Next">pagination::next</a></li>', $result);
		$this->assertStringContainsString('<li class="Sort-Last"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/page:7/" class="Arrow Arrow-Last">pagination::last</a>', $result);
	}


	/**
	 * Test that the render method generates an empty string
	 * when there is no paginated result.
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::render()
	 */
	public function testRenderWithoutPaginatedResult(): void {
		$result = $this->paginator->render();

		$this->assertSame('', $result);
	}


	/**
	 * Test that the render method generates an empty string
	 * when there is only one page.
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::render()
	 */
	public function testRenderForSinglePage(): void {
		$this->setPaginatedResult([
			'currentPage' => 1,
			'count' => 9,
			'totalCount' => 9,
			'hasPrevPage' => false,
			'hasNextPage' => false,
			'pageCount' => 1,
		]);

		$result = $this->paginator->render();

		$this->assertSame('', $result);
	}


	/**
	 * Test that the render method generates the correct pagination links
	 * when the first page is not the current page.
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::render()
	 */
	public function testRenderForFirstPageAndLastPageIfNotCurrent(): void {
		$this->setPaginatedResult([
			'currentPage' => 3,
			'count' => 9,
			'totalCount' => 62,
			'hasPrevPage' => true,
			'hasNextPage' => true,
			'pageCount' => 7,
		]);

		$result = $this->paginator->render();

		$this->assertStringContainsString('<li class="Sort-First"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/" class="Arrow Arrow-First">pagination::first</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Prev"><a rel="prev" href="/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" class="Arrow Arrow-Prev">pagination::previous</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Number"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/" class="Number" title="pagination::page 1">1</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Number Sort-Current"><span class="Number">3</span>', $result);
		$this->assertStringContainsString('<li class="Sort-Number"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/page:4/" class="Number" title="pagination::page 4">4</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Next"><a rel="next" href="/backend/xy/the-controller/the-action/testparam:testvalue/page:4/" class="Arrow Arrow-Next">pagination::next</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Last"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/page:7/" class="Arrow Arrow-Last">pagination::last</a>', $result);
	}


	/**
	 * Test that the render method generates the correct pagination links
	 * when the last page is the current page.
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::render()
	 */
	public function testRenderForLastPageIfCurrent(): void {
		$this->setPaginatedResult([
			'currentPage' => 7,
			'count' => 9,
			'totalCount' => 62,
			'hasPrevPage' => true,
			'hasNextPage' => false,
			'pageCount' => 7,
		]);

		$result = $this->paginator->render();

		$this->assertStringContainsString('<li class="Sort-First"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/" class="Arrow Arrow-First">pagination::first</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Prev"><a rel="prev" href="/backend/xy/the-controller/the-action/testparam:testvalue/page:6/" class="Arrow Arrow-Prev">pagination::previous</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Number"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/page:6/" class="Number" title="pagination::page 6">6</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Number Sort-Current"><span class="Number">7</span>', $result);
		$this->assertStringContainsString('<li class="Sort-Next Disabled"><span class="Arrow Arrow-Next">pagination::next</span>', $result);
		$this->assertStringNotContainsString('pagination::last', $result);
	}


	/**
	 * Test that the casting the class instance to a string
	 * generates the correct pagination links.
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\PaginatorHelper::__toString()
	 */
	public function testToString(): void {
		$this->setPaginatedResult([]);

		$result = (string)$this->paginator;

		$this->assertStringNotContainsString('pagination::first', $result);
		$this->assertStringContainsString('<li class="Sort-Prev Disabled"><span class="Arrow Arrow-Prev">pagination::previous</span></li>', $result);
		$this->assertStringContainsString('<li class="Sort-Number Sort-Current"><span class="Number">1</span></li>', $result);
		$this->assertStringContainsString('<li class="Sort-Number"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" class="Number" title="pagination::page 2">2</a>', $result);
		$this->assertStringContainsString('<li class="Sort-Next"><a rel="next" href="/backend/xy/the-controller/the-action/testparam:testvalue/page:2/" class="Arrow Arrow-Next">pagination::next</a></li>', $result);
		$this->assertStringContainsString('<li class="Sort-Last"><a href="/backend/xy/the-controller/the-action/testparam:testvalue/page:7/" class="Arrow Arrow-Last">pagination::last</a>', $result);
	}


	/**
	 * @param array $params
	 * @param bool $merge
	 * @return void
	 */
	protected function setPaginatedResult(array $params, bool $merge = true): void {
		if ($merge) {
			$params += [
				'currentPage' => 1,
				'count' => 9,
				'totalCount' => 62,
				'hasPrevPage' => false,
				'hasNextPage' => true,
				'pageCount' => 7,
			];
		}

		$this->paginatedResult = new PaginatedResultSet(new ResultSet([]), $params);

		$this->paginator->setPaginated($this->paginatedResult);
	}
}
