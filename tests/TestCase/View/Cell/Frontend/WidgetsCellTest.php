<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Frontend;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Widget;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Awyiss\View\Cell\Frontend\WidgetsCell;
use Cake\Collection\CollectionInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\CellTrait;


/**
 * WidgetsCellTest class
 */
class WidgetsCellTest extends TestCase {
	use CellTrait;
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\View\Cell\Frontend\WidgetsCell
	 */
	protected WidgetsCell $cell;
	/**
	 * @var mixed
	 */
	protected mixed $response;
	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected ServerRequest $request;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm('Frontend');
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		Awyiss::loadConfiguration('xy', 'yx');

		$this->loadRoutes();

		$this->request = new ServerRequest([
			'url' => '/dummy',
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

		$this->response = $this->createMock(Response::class);

		$this->cell = new WidgetsCell($this->request, $this->response, null, ['action' => 'display']);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitCellOptions() {
		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', []);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => true,
			'viewVars' => [],
			'fullWidth' => null,
			'singleColumnBreakpoint' => null,
		], $options);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitCellOptionsWithCustomOptions() {
		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', [
			'columnWidth' => 50,
			'includeWrapper' => false,
			'viewVars' => ['foo' => 'bar'],
		]);

		$this->assertEquals([
			'columnWidth' => 50,
			'includeWrapper' => false,
			'viewVars' => ['foo' => 'bar'],
			'fullWidth' => null,
			'singleColumnBreakpoint' => null,
		], $options);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testInitCellOptionsCallsFindFullWidthIfNotSet() {
		$this->cell = $this->getMockBuilder(WidgetsCell::class)->disableOriginalConstructor()->onlyMethods(['findFullWidth'])->getMock();

		$this->cell->expects($this->once())->method('findFullWidth')->willReturn(123.00);

		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', []);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => true,
			'viewVars' => [],
			'fullWidth' => 123.00,
			'singleColumnBreakpoint' => null,
		], $options);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testInitCellOptionsNotCallsFindFullWidthIfSet() {
		$this->cell = $this->getMockBuilder(WidgetsCell::class)->disableOriginalConstructor()->onlyMethods(['findFullWidth'])->getMock();

		$this->cell->expects($this->never())->method('findFullWidth');

		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', [
			'fullWidth' => 123.00,
		]);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => true,
			'viewVars' => [],
			'fullWidth' => 123.00,
			'singleColumnBreakpoint' => null,
		], $options);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testInitCellOptionsCallsFindSingleColumnBreakpointIfNotSet() {
		$this->cell = $this->getMockBuilder(WidgetsCell::class)->disableOriginalConstructor()->onlyMethods(['findSingleColumnBreakpoint'])->getMock();

		$this->cell->expects($this->once())->method('findSingleColumnBreakpoint')->willReturn(1234.00);

		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', []);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => true,
			'viewVars' => [],
			'fullWidth' => null,
			'singleColumnBreakpoint' => 1234.00,
		], $options);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testInitCellOptionsNotCallsFindSingleColumnBreakpointIfSet() {
		$this->cell = $this->getMockBuilder(WidgetsCell::class)->disableOriginalConstructor()->onlyMethods(['findSingleColumnBreakpoint'])->getMock();

		$this->cell->expects($this->never())->method('findSingleColumnBreakpoint');

		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', [
			'singleColumnBreakpoint' => 1234.00,
		]);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => true,
			'viewVars' => [],
			'fullWidth' => null,
			'singleColumnBreakpoint' => 1234.00,
		], $options);
	}


	/**
	 * @return array
	 */
	public static function dataThreadedWidgetsDataProvider(): array {
		return [
			['dummy_multi_row', 3],
			['dummy_nested', 1],
			['dummy_row_overflow', 4],
			['dummy_single_row', 2],
		];
	}


	/**
	 * @dataProvider dataThreadedWidgetsDataProvider
	 * @param string $identifier
	 * @param int $expectedCount
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetThreadedWidgets(string $identifier, int $expectedCount): void {
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', $identifier);

		$this->assertInstanceOf(CollectionInterface::class, $widgets);
		$this->assertCount($expectedCount, $widgets);

		if ($expectedCount > 0) {
			// Check if all first level items have no parent id
			$widgets->each(function (Widget $widget) {
				$this->assertEmpty($widget->parentId);
			});

			// Make sure the collection is nested to check all elements
			$widgets = $widgets->listNested()->compile(false);

			// Make sure only active and only those with the given identifier are returned
			$widgets = $widgets->filter(function (Widget $widget) use ($identifier) {
				return $widget->get('active') && $widget->get('identifier') === $identifier;
			});

			$this->assertCount($expectedCount, $widgets);
		}
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedWidgetsReturnsEmptyCollectionForUnknownIdentifier(): void {
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'unknown_dentifier');

		$this->assertInstanceOf(CollectionInterface::class, $widgets);
		$this->assertCount(0, $widgets);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedWidgetsContainsInactiveElementsWhenPreviewIsEnabled(): void {
		/** @var \Cake\Collection\CollectionInterface $widgets */
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', true);

		$widgets = $widgets->listNested()->compile(false);

		$this->assertInstanceOf(CollectionInterface::class, $widgets);
		$this->assertCount(4, $widgets);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedWidgetsNotContainsInactiveElementsWhenPreviewIsDisabled(): void {
		/** @var \Cake\Collection\CollectionInterface $widgets */
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', false);

		$widgets = $widgets->listNested()->compile(false);

		$this->assertInstanceOf(CollectionInterface::class, $widgets);
		$this->assertCount(1, $widgets);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedWidgetsContainsUnpublishedElementsWhenPreviewIsEnabled(): void {
		/** @var \Cake\Collection\CollectionInterface $widgets */
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_multi_row', true);

		$widgets = $widgets->listNested()->compile(false);

		$this->assertInstanceOf(CollectionInterface::class, $widgets);
		$this->assertCount(5, $widgets);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedWidgetsNotContainsUnpublishedElementsWhenPreviewIsDisabled(): void {
		/** @var \Cake\Collection\CollectionInterface $widgets */
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_multi_row', false);

		$widgets = $widgets->listNested()->compile(false);

		$this->assertInstanceOf(CollectionInterface::class, $widgets);
		$this->assertCount(3, $widgets);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCacheAssignedMediaItems(): void {
		ResizedImageManager::clear();

		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested');

		$files = $this->callProtectedMethod($this->cell, 'cacheAssignedMediaItems', $widgets, 'widgets');
		$this->assertCount(1, $files);

		$files = ResizedImageManager::getMediaItems();

		$this->assertCount(1, $files);
		$this->assertArrayHasKey(2, $files);

		$resizedFiles = ResizedImageManager::getResizedItems();
		$this->assertCount(0, $resizedFiles);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testCacheAssignedMediaItemsIncludingInactiveItemsInPreviewMode(): void {
		ResizedImageManager::clear();

		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', true);

		$files = $this->callProtectedMethod($this->cell, 'cacheAssignedMediaItems', $widgets, 'widgets');
		$this->assertCount(3, $files);

		$files = ResizedImageManager::getMediaItems();

		$this->assertCount(3, $files);
		$this->assertArrayHasKey(2, $files);
		$this->assertArrayHasKey(3, $files);
		$this->assertArrayHasKey(4, $files);

		$resizedFiles = ResizedImageManager::getResizedItems();

		$this->assertArrayHasKey(4, $resizedFiles);
		$this->assertCount(27, $resizedFiles[4]);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindFullWidth(): void {
		$fullWidth = $this->callProtectedMethod($this->cell, 'findFullWidth', [
			'viewVars' => [
				'fullWidth' => false,
				'designSettings' => [
					'pageWidth' => 0,
				],
			],
		]);

		$this->assertEquals(null, $fullWidth);

		$fullWidth = $this->callProtectedMethod($this->cell, 'findFullWidth', [
			'viewVars' => [
				'fullWidth' => 1280,
				'designSettings' => [
					'pageWidth' => 1440,
				],
			],
		]);

		$this->assertEquals(1280.00, $fullWidth);

		$fullWidth = $this->callProtectedMethod($this->cell, 'findFullWidth', [
			'viewVars' => [
				'fullWidth' => null,
				'designSettings' => [
					'pageWidth' => 1440,
				],
			],
		]);

		$this->assertEquals(1440.00, $fullWidth);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindSingleColumnBreakpoint(): void {
		$singleColumnBreakpoint = $this->callProtectedMethod($this->cell, 'findSingleColumnBreakpoint', [
			'viewVars' => [
				'singleColumnBreakpoint' => false,
				'designSettings' => [
					'singleColumnBreakpoint' => 0,
				],
			],
		]);

		$this->assertEquals(null, $singleColumnBreakpoint);

		$singleColumnBreakpoint = $this->callProtectedMethod($this->cell, 'findSingleColumnBreakpoint', [
			'viewVars' => [
				'singleColumnBreakpoint' => 1280,
				'designSettings' => [
					'singleColumnBreakpoint' => 1440,
				],
			],
		]);

		$this->assertEquals(1280.00, $singleColumnBreakpoint);

		$singleColumnBreakpoint = $this->callProtectedMethod($this->cell, 'findSingleColumnBreakpoint', [
			'viewVars' => [
				'singleColumnBreakpoint' => null,
				'designSettings' => [
					'singleColumnBreakpoint' => 1440,
				],
			],
		]);

		$this->assertEquals(1440.00, $singleColumnBreakpoint);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrepareEntitiesSetsLevel(): void {
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$widgets = $widgets->listNested()->compile(false);

		$widgets->each(function (Widget $widget) {
			$this->assertEquals(0, $widget->get('level'));
		});

		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$widgets = $widgets->listNested()->compile(false);

		$level0 = $widgets->filter(function (Widget $widget) {
			return $widget->get('level') === 0;
		});
		$this->assertCount(2, $level0);

		$level1 = $widgets->filter(function (Widget $widget) {
			return $widget->get('level') === 1;
		});
		$this->assertCount(1, $level1);

		$level2 = $widgets->filter(function (Widget $widget) {
			return $widget->get('level') === 2;
		});
		$this->assertCount(1, $level2);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrepareEntitiesSetsParentWidgets(): void {
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$widgets = $widgets->listNested()->compile(false);

		/** @var \Awyiss\Model\Entity\Widget $widget */
		foreach ($widgets as $widget) {
			if (in_array($widget->id, [4, 6])) {
				$this->assertSame(0, $widget->level);
				$this->assertSame([], $widget->parentWidgets);
			}
			elseif ($widget->id === 7) {
				$this->assertSame(1, $widget->level);
				$this->assertCount(1, $widget->parentWidgets);
				$this->assertSame([4], array_column($widget->parentWidgets, 'id'));
			}
			elseif ($widget->id === 15) {
				$this->assertSame(2, $widget->level);
				$this->assertCount(2, $widget->parentWidgets);
				$this->assertSame([4, 7], array_column($widget->parentWidgets, 'id'));
			}
		}
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrepareEntitiesSetCssClasses(): void {
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$widgets = $widgets->listNested()->compile(false);

		$this->assertSame([
			'WidgetElement Template-Standard Column-100 Widget-DummyNested',
		], array_column($widgets->toList(), 'cssClass'));
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testPrepareEntitiesSetCssClassesForInactiveElements(): void {
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$widgets = $widgets->listNested()->compile(false);

		$this->assertSame([
			'WidgetElement Template-Standard Column-100 Widget-DummyNested AwyissFrontendPreview-InactiveElement',
			'WidgetElement Template-Standard Column-40 Widget-DummyNested',
			'WidgetElement Template-Standard Column-67 Widget-DummyNested',
			'WidgetElement Template-Standard Column-100 Widget-DummyNested',
		], array_column($widgets->toList(), 'cssClass'));
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrepareEntitiesSetsRealColumnWidth(): void {
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$widgets = $widgets->listNested()->compile(false);

		$this->assertSame([
			100.0,
			40.0,
			26.6667, // 1 * .4 * .67
			100.0,
		], array_column($widgets->toList(), 'realColumnWidth'));
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testPrepareEntitiesSetsRealColumnWidthWithDifferentBaseWidth(): void {
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets, 75.00);

		$widgets = $widgets->listNested()->compile(false);

		$this->assertSame([
			75.0,
			30.0,
			20.0, // .75 * .4 * .67
			75.0,
		], array_column($widgets->toList(), 'realColumnWidth'));
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrepareEntitiesSetsTemplate(): void {
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$widgets = $widgets->listNested()->compile(false)->toList();

		$this->assertSame('standard', $widgets[0]->widgetTemplate->fileName);
		$this->assertSame('Widget7', $widgets[1]->widgetTemplate->fileName);
		$this->assertSame('standard', $widgets[2]->widgetTemplate->fileName);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetViewVars(): void {
		/** @var \Cake\View\View $view */
		$view = $this->callProtectedMethod($this->cell, 'getView');

		$options = [
			'columnWidth' => 50,
			'includeWrapper' => false,
			'viewVars' => ['foo' => 'bar'],
			'fullWidth' => 123.00,
			'singleColumnBreakpoint' => 1234.00,
		];

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$vars = $view->getVars();

		$this->assertArrayNotHasKey('columnWidth', $vars);
		$this->assertArrayNotHasKey('includeWrapper', $vars);
		$this->assertArrayNotHasKey('viewVars', $vars);
		$this->assertEquals('bar', $view->get('foo'));
		$this->assertEquals(123.00, $view->get('fullWidth'));
		$this->assertEquals(1234.00, $view->get('singleColumnBreakpoint'));
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testBuildContentsWithNestedAndInactiveContents(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_nested', true);
		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $widgets->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Widget-DummyNested.txt', $contents);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildContentsWithSingleRow(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_single_row');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $widgets->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Widget-DummySingleRow.txt', $contents);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildContentsWithMultiRow(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_multi_row', true);
		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $widgets->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Widget-DummyMultiRow.txt', $contents);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildContentsWithRowOverflow(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_row_overflow');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $widgets->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Widget-DummyRowOverflow.txt', $contents);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testParseModuleReplacesModuleTagsWithRenderedOutput() {
		$entity = new Widget();
		$entity->text = '<div>Some content</div><module data-identifier="testModule">{"key":"value"}</module><div>Some other content</div>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseModule($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div>Rendered Output (and key is `value`)<div>Some other content</div>', $entity->text);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testParseModuleIgnoresNonModuleTags() {
		$entity = new Widget();
		$entity->text = '<div>Some content</div>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseModule($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div>', $entity->text);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testParseModuleHandlesMissingModulesGracefully() {
		$entity = new Widget();
		$entity->text = '<div>Some content</div><module data-identifier="missingModule">{"key":"value"}</module><div>Some other content</div>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseModule($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div><module data-identifier="missingModule">{"key":"value"}</module><div>Some other content</div>', $entity->text);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testParseModuleRemovesModuleTagsWithEmptyOutput() {
		$entity = new Widget();
		$entity->text = '<div>Some content</div><module data-identifier="emptyModule">{"key":"value"}</module><div>Some other content</div>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseModule($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div><div>Some other content</div>', $entity->text);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testParseModuleHandlesMalformedHtmlGracefully() {
		$entity = new Widget();
		$entity->text = '<div>Some content</div><p><module data-identifier="testModule">{"key":"other_value"}</module>e other content</div>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseModule($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div><p>Rendered Output (and key is `other_value`)e other content</div>', $entity->text);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderElement(): void {
		/** @var \Cake\Collection\Collection $widgets */
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_single_row');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$entity = $widgets->firstMatch(['id' => 13]);

		$output = $this->callProtectedMethod($this->cell, 'renderElement', $entity, '');

		$this->assertStringContainsString('class="WidgetElement Template-Standard Column-50 Widget-DummySingleRow"', $output);
		$this->assertStringContainsString('id="Widget13"', $output);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testRenderElementAddsFullWidthMissingInfo(): void {
		/** @var \Cake\Collection\Collection $widgets */
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_single_row');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$entity = $widgets->firstMatch(['id' => 13]);

		$output = $this->callProtectedMethod($this->cell, 'renderElement', $entity, '');

		$this->assertStringContainsString('<!-- Full width is missing. Please add the `fullWidth`-option to the widget cell. -->', $output);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testRenderElementNotAddsFullWidthMissingInfoWhenSet(): void {
		/** @var \Cake\Collection\Collection $widgets */
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_single_row');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$entity = $widgets->firstMatch(['id' => 13]);

		$options = [
			'columnWidth' => 50,
			'includeWrapper' => false,
			'viewVars' => ['foo' => 'bar'],
			'fullWidth' => 123.00,
			'singleColumnBreakpoint' => 1234.00,
		];

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$output = $this->callProtectedMethod($this->cell, 'renderElement', $entity, '', 100.0);

		$this->assertStringNotContainsString('Full width is missing.', $output);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderElementRendersParsesModule() {
		/** @var \Cake\Collection\Collection $widgets */
		$widgets = $this->callProtectedMethod($this->cell, 'getThreadedWidgets', 'dummy_single_row');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $widgets);

		$entity = $widgets->firstMatch(['id' => 13]);
		$entity->text = '<div>Some content</div><module data-identifier="testModule">{"key":"value"}</module><div>Some other content</div>';

		$output = $this->callProtectedMethod($this->cell, 'renderElement', $entity, '');

		$this->assertStringContainsString('<div>Some content</div>Rendered Output (and key is `value`)<div>Some other content</div>', $output);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderContentRow(): void {
		$output = $this->callProtectedMethod($this->cell, 'renderContentRow', 'Lorem ipsum');

		$this->assertStringContainsString('<div class="ContentRow">', $output);
		$this->assertStringContainsString('Lorem ipsum', $output);
		$this->assertStringContainsString('</div>', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplay(): void {
		$output = (string)$this->cell('Frontend/Widgets', [
			'dummy_row_overflow',
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);
		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Widget-DummyRowOverflow.txt', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithColumnWidth(): void {
		$output = (string)$this->cell('Frontend/Widgets', [
			'dummy_narrow',
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'columnWidth' => 60,
			],
		]);

		$this->assertStringContainsString('ColumnWidth of the widget 16: 60', $output);
		$this->assertStringContainsString('ColumnWidth of the widget 17: 30', $output);
	}
}
