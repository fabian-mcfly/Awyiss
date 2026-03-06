<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Frontend;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\GlobalContent;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Awyiss\View\Cell\Frontend\GlobalContentsCell;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\CellTrait;


/**
 * GlobalContentsCellTest class
 */
class GlobalContentsCellTest extends TestCase {
	use CellTrait;
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\View\Cell\Frontend\GlobalContentsCell
	 */
	protected GlobalContentsCell $cell;
	/**
	 * @var \Cake\Http\Response
	 */
	protected Response $response;
	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected ServerRequest $request;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $view;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
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

		$this->view = new FrontendView($this->request);
		$this->cell = new GlobalContentsCell($this->request, $this->response, null, [
			'action' => 'display',
			'view' => $this->view,
		]);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::initCellOptions()
	 * @throws \ReflectionException
	 */
	public function testInitCellOptions() {
		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', []);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => false,
			'viewVars' => [],
			'fullWidth' => null,
			'singleColumnBreakpoint' => null,
		], $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::initCellOptions()
	 * @throws \ReflectionException
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
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::initCellOptions()
	 * @throws \ReflectionException
	 */
	public function testFindFullWidthIfNotSet() {
		$this->cell = $this->getMockBuilder(GlobalContentsCell::class)->disableOriginalConstructor()->onlyMethods(['findFullWidth'])->getMock();

		$this->cell->expects($this->once())->method('findFullWidth')->willReturn(123.00);

		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', []);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => false,
			'viewVars' => [],
			'fullWidth' => 123.00,
			'singleColumnBreakpoint' => null,
		], $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::initCellOptions()
	 * @throws \ReflectionException
	 */
	public function testNotFindFullWidthIfSet() {
		$this->cell = $this->getMockBuilder(GlobalContentsCell::class)->disableOriginalConstructor()->onlyMethods(['findFullWidth'])->getMock();

		$this->cell->expects($this->never())->method('findFullWidth');

		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', [
			'fullWidth' => 234.00,
		]);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => false,
			'viewVars' => [],
			'fullWidth' => 234.00,
			'singleColumnBreakpoint' => null,
		], $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::initCellOptions()
	 * @throws \ReflectionException
	 */
	public function testFindSingleColumnBreakpointIfNotSet() {
		$this->cell = $this->getMockBuilder(GlobalContentsCell::class)->disableOriginalConstructor()->onlyMethods(['findSingleColumnBreakpoint'])->getMock();

		$this->cell->expects($this->once())->method('findSingleColumnBreakpoint')->willReturn(1234.00);

		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', []);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => false,
			'viewVars' => [],
			'fullWidth' => null,
			'singleColumnBreakpoint' => 1234.00,
		], $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::initCellOptions()
	 * @throws \ReflectionException
	 */
	public function testNotFindSingleColumnBreakpointIfSet() {
		$this->cell = $this->getMockBuilder(GlobalContentsCell::class)->disableOriginalConstructor()->onlyMethods(['findSingleColumnBreakpoint'])->getMock();

		$this->cell->expects($this->never())->method('findSingleColumnBreakpoint');

		$options = $this->callProtectedMethod($this->cell, 'initCellOptions', [
			'singleColumnBreakpoint' => 1234.00,
		]);

		$this->assertEquals([
			'columnWidth' => 100,
			'includeWrapper' => false,
			'viewVars' => [],
			'fullWidth' => null,
			'singleColumnBreakpoint' => 1234.00,
		], $options);
	}


	/**
	 * @return array
	 */
	public static function dataThreadedGlobalContentsDataProvider(): array {
		return [
			['dummyMultiRow', 3],
			['dummyNested', 1],
			['dummyRowOverflow', 4],
			['dummySingleRow', 2],
		];
	}


	/**
	 * @dataProvider dataThreadedGlobalContentsDataProvider
	 * @param string $identifier
	 * @param int $expectedCount
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::getThreadedGlobalContents()
	 * @throws \ReflectionException
	 */
	public function testGetThreadedGlobalContents(string $identifier, int $expectedCount): void {
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', $identifier);

		$this->assertInstanceOf(CollectionInterface::class, $globalContents);
		$this->assertCount($expectedCount, $globalContents);

		if ($expectedCount > 0) {
			// Check if all first level items have no parent id
			$globalContents->each(function (GlobalContent $globalContent) {
				$this->assertEmpty($globalContent->parentId);
			});

			// Make sure the collection is nested to check all elements
			$globalContents = $globalContents->listNested()->compile(false);

			// Make sure only active and only those with the given identifier are returned
			$globalContents = $globalContents->filter(function (GlobalContent $globalContent) use ($identifier) {
				return $globalContent->get('active') && $globalContent->get('identifier') === $identifier;
			});

			$this->assertCount($expectedCount, $globalContents);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::getThreadedGlobalContents()
	 * @throws \ReflectionException
	 */
	public function testGetThreadedGlobalContentsReturnsEmptyCollectionForUnknownIdentifier(): void {
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'unknown_dentifier');

		$this->assertInstanceOf(CollectionInterface::class, $globalContents);
		$this->assertCount(0, $globalContents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::getThreadedGlobalContents()
	 * @throws \ReflectionException
	 */
	public function testGetThreadedGlobalContentsContainsInactiveElementsWhenPreviewIsEnabled(): void {
		/** @var \Cake\Collection\CollectionInterface $globalContents */
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', true);

		$globalContents = $globalContents->listNested()->compile(false);

		$this->assertCount(4, $globalContents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::getThreadedGlobalContents()
	 * @throws \ReflectionException
	 */
	public function testGetThreadedGlobalContentsNotContainsInactiveElementsWhenPreviewIsDisabled(): void {
		/** @var \Cake\Collection\CollectionInterface $globalContents */
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', false);

		$globalContents = $globalContents->listNested()->compile(false);

		$this->assertCount(1, $globalContents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::getThreadedGlobalContents()
	 * @throws \ReflectionException
	 */
	public function testGetThreadedGlobalContentsContainsUnpublishedElementsWhenPreviewIsEnabled(): void {
		/** @var \Cake\Collection\CollectionInterface $globalContents */
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyMultiRow', true);

		$globalContents = $globalContents->listNested()->compile(false);

		$this->assertCount(5, $globalContents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::getThreadedGlobalContents()
	 * @throws \ReflectionException
	 */
	public function testGetThreadedGlobalContentsNotContainsUnpublishedElementsWhenPreviewIsDisabled(): void {
		/** @var \Cake\Collection\CollectionInterface $globalContents */
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyMultiRow', false);

		$globalContents = $globalContents->listNested()->compile(false);

		$this->assertCount(3, $globalContents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::cacheAssignedMediaItems()
	 * @throws \ReflectionException
	 */
	public function testCacheAssignedMediaItems(): void {
		ResizedImageManager::clear();

		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested');

		$files = $this->callProtectedMethod($this->cell, 'cacheAssignedMediaItems', $globalContents, 'global_contents');
		$this->assertCount(1, $files);

		$files = ResizedImageManager::getMediaItems();

		$this->assertCount(1, $files);
		$this->assertArrayHasKey(2, $files);

		$resizedFiles = ResizedImageManager::getResizedItems();
		$this->assertCount(0, $resizedFiles);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::cacheAssignedMediaItems()
	 * @throws \ReflectionException
	 */
	public function testCacheAssignedMediaItemsIncludingInactiveItemsInPreviewMode(): void {
		ResizedImageManager::clear();

		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', true);

		$files = $this->callProtectedMethod($this->cell, 'cacheAssignedMediaItems', $globalContents, 'global_contents');
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
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::findFullWidth()
	 * @throws \ReflectionException
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
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::findSingleColumnBreakpoint()
	 * @throws \ReflectionException
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
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::prepareEntities()
	 * @throws \ReflectionException
	 */
	public function testPrepareEntitiesSetsLevel(): void {
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$globalContents = $globalContents->listNested()->compile(false);

		$globalContents->each(function (GlobalContent $globalContent) {
			$this->assertEquals(0, $globalContent->get('level'));
		});

		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$globalContents = $globalContents->listNested()->compile(false);

		$level0 = $globalContents->filter(function (GlobalContent $globalContent) {
			return $globalContent->get('level') === 0;
		});
		$this->assertCount(2, $level0);

		$level1 = $globalContents->filter(function (GlobalContent $globalContent) {
			return $globalContent->get('level') === 1;
		});
		$this->assertCount(1, $level1);

		$level2 = $globalContents->filter(function (GlobalContent $globalContent) {
			return $globalContent->get('level') === 2;
		});
		$this->assertCount(1, $level2);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::prepareEntities()
	 * @throws \ReflectionException
	 */
	public function testPrepareEntitiesSetsParentGlobalContents(): void {
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$globalContents = $globalContents->listNested()->compile(false);

		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		foreach ($globalContents as $globalContent) {
			if (in_array($globalContent->id, [4, 6])) {
				/** @noinspection PhpUndefinedFieldInspection */
				$this->assertSame(0, $globalContent->level);
				$this->assertSame([], $globalContent->parentGlobalContents);
			}
			elseif ($globalContent->id === 7) {
				/** @noinspection PhpUndefinedFieldInspection */
				$this->assertSame(1, $globalContent->level);
				$this->assertCount(1, $globalContent->parentGlobalContents);
				$this->assertSame([4], array_column($globalContent->parentGlobalContents, 'id'));
			}
			elseif ($globalContent->id === 15) {
				/** @noinspection PhpUndefinedFieldInspection */
				$this->assertSame(2, $globalContent->level);
				$this->assertCount(2, $globalContent->parentGlobalContents);
				$this->assertSame([4, 7], array_column($globalContent->parentGlobalContents, 'id'));
			}
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::prepareEntities()
	 * @throws \ReflectionException
	 */
	public function testPrepareEntitiesSetCssClasses(): void {
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$globalContents = $globalContents->listNested()->compile(false);

		$this->assertSame([
			'GlobalContentElement Template-Standard Column-100 GlobalContent-DummyNested',
		], array_column($globalContents->toList(), 'cssClass'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::prepareEntities()
	 * @throws \ReflectionException
	 */
	public function testPrepareEntitiesSetCssClassesForInactiveElements(): void {
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$globalContents = $globalContents->listNested()->compile(false);

		$this->assertSame([
			'GlobalContentElement Template-Standard Column-100 GlobalContent-DummyNested AwyissFrontendPreview-InactiveElement',
			'GlobalContentElement Template-Standard Column-40 GlobalContent-DummyNested',
			'GlobalContentElement Template-Standard Column-67 GlobalContent-DummyNested',
			'GlobalContentElement Template-Standard Column-100 GlobalContent-DummyNested',
		], array_column($globalContents->toList(), 'cssClass'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::prepareEntities()
	 * @throws \ReflectionException
	 */
	public function testPrepareEntitiesSetsRealColumnWidth(): void {
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$globalContents = $globalContents->listNested()->compile(false);

		$this->assertSame([
			100.0,
			40.0,
			26.6667, // 1 * .4 * .67
			100.0,
		], array_column($globalContents->toList(), 'realColumnWidth'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::prepareEntities()
	 * @throws \ReflectionException
	 */
	public function testPrepareEntitiesSetsRealColumnWidthWithDifferentBaseWidth(): void {
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents, 75.00);

		$globalContents = $globalContents->listNested()->compile(false);

		$this->assertSame([
			75.0,
			30.0,
			20.0, // .75 * .4 * .67
			75.0,
		], array_column($globalContents->toList(), 'realColumnWidth'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::prepareEntities()
	 * @throws \ReflectionException
	 */
	public function testPrepareEntitiesSetsTemplate(): void {
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$globalContents = $globalContents->listNested()->compile(false)->toList();

		$this->assertSame('standard', $globalContents[0]->globalContentTemplate->fileName);
		$this->assertSame('GlobalContent7', $globalContents[1]->globalContentTemplate->fileName);
		$this->assertSame('standard', $globalContents[2]->globalContentTemplate->fileName);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::setViewVars()
	 * @throws \ReflectionException
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
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::buildContents()
	 * @throws \ReflectionException
	 */
	public function testBuildContentsWithNestedAndInactiveContents(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyNested', true);
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $globalContents->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'GlobalContent-DummyNested.txt', $contents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::buildContents()
	 * @throws \ReflectionException
	 */
	public function testBuildContentsWithSingleRow(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummySingleRow');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $globalContents->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'GlobalContent-DummySingleRow.txt', $contents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::buildContents()
	 * @throws \ReflectionException
	 */
	public function testBuildContentsWithMultiRow(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyMultiRow', true);
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $globalContents->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'GlobalContent-DummyMultiRow.txt', $contents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::buildContents()
	 * @throws \ReflectionException
	 */
	public function testBuildContentsWithRowOverflow(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummyRowOverflow');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $globalContents->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'GlobalContent-DummyRowOverflow.txt', $contents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\ContentsCell::buildContents()
	 * @throws \ReflectionException
	 */
	public function testGlobalContentSpecificTemplate(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'customTemplate');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $globalContents->toArray());

		$this->assertStringContainsString('Content of Custom Template Global Content', $contents);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\ContentsCell::buildContents()
	 * @throws \ReflectionException
	 */
	public function testGlobalContentsCanSetContentRowClass(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'customTemplate');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $globalContents->toArray());

		$this->assertStringContainsString('<div class="ContentRow FlexRow">', $contents);
		$this->assertSame(1, substr_count($contents, 'FlexRow'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::parseAwyissImageTags()
	 * @noinspection HtmlRequiredAltAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testParseAwyissImageTag(): void {
		$output = (string)$this->cell('Frontend/GlobalContents', [
			'inlineImg',
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);

		$this->assertStringContainsString('<p>Global Content with inline img tag</p><p><picture>', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1152].avif"', $output);
		$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w768].avif 1x, _resized/dummypath/logo-awyiss-[w1536].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<source media="(width <= 1280px)" data-srcset="_resized/dummypath/logo-awyiss-[w1024].avif 1x, _resized/dummypath/logo-awyiss-[w2048].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1152].avif"', $output);
		$this->assertStringContainsString('</picture></p><p>between two paragraphs</p>', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::parseAwyissImageTags()
	 * @noinspection HtmlRequiredAltAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testParseAwyissImageTagWithColumnWidth(): void {
		$output = (string)$this->cell('Frontend/GlobalContents', [
			'inlineImg',
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'columnWidth' => 50,
			],
		]);

		$this->assertStringContainsString('<p>Global Content with inline img tag</p><p><picture>', $output);
		$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w768].avif 1x, _resized/dummypath/logo-awyiss-[w1536].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<source media="(width <= 1280px)" data-srcset="../awyiss/Command/Media/TestFiles/_resized/logo-awyiss-[w512].avif 1x, _resized/dummypath/logo-awyiss-[w1024].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w576].avif"', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w576].avif"', $output);
		$this->assertStringContainsString('</picture></p><p>between two paragraphs</p>', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::parseWidgets()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testParseWidgetReplacesWidgetTagsWithRenderedOutput() {
		$entity = new GlobalContent();
		$entity->text = '<div>Some content</div><widget data-identifier="test">{"key":"value"}</widget><div>Some other content</div>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseWidgets($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div>Rendered Output (and key is `value`)<div>Some other content</div>', $entity->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::parseWidgets()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testParseWidgetIgnoresNonWidgetTags() {
		$entity = new GlobalContent();
		$entity->text = '<div>Some content</div>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseWidgets($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div>', $entity->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::parseWidgets()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testParseWidgetHandlesMissingWidgetsGracefully() {
		$entity = new GlobalContent();
		$entity->text = '<div>Some content</div><widget data-identifier="missingWidget">{"key":"value"}</widget><div>Some other content</div>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseWidgets($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div><widget data-identifier="missingWidget">{"key":"value"}</widget><div>Some other content</div>', $entity->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::parseWidgets()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testParseWidgetRemovesWidgetTagsWithEmptyOutput() {
		$entity = new GlobalContent();
		$entity->text = '<div>Some content</div><widget data-identifier="empty">{"key":"value"}</widget><div>Some other content</div>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseWidgets($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div><div>Some other content</div>', $entity->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::parseWidgets()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testParseWidgetHandlesMalformedHtmlGracefully() {
		$entity = new GlobalContent();
		$entity->text = '<div>Some content</div><p><widget data-identifier="test">{"key":"other_value"}</widget>e other content</p>';

		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$this->cell->parseWidgets($entity, $mediaRenderOptions);

		$this->assertSame('<div>Some content</div><p>Rendered Output (and key is `other_value`)e other content</p>', $entity->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::renderElement()
	 * @throws \ReflectionException
	 */
	public function testRenderElement(): void {
		/** @var \Cake\Collection\Collection $globalContents */
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummySingleRow');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$entity = $globalContents->firstMatch(['id' => 13]);

		$output = $this->callProtectedMethod($this->cell, 'renderElement', $entity, '');

		$this->assertStringContainsString('class="GlobalContentElement Template-Standard Column-50 GlobalContent-DummySingleRow"', $output);
		$this->assertStringContainsString('id="GlobalContent13"', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::renderElement()
	 * @throws \ReflectionException
	 */
	public function testRenderElementAddsFullWidthMissingInfo(): void {
		/** @var \Cake\Collection\Collection $globalContents */
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummySingleRow');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$entity = $globalContents->firstMatch(['id' => 13]);

		$output = $this->callProtectedMethod($this->cell, 'renderElement', $entity, '');

		$this->assertStringContainsString('<!-- Full width is missing. Please add the `fullWidth`-option to the global content cell. -->', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::renderElement()
	 * @throws \ReflectionException
	 */
	public function testRenderElementNotAddsFullWidthMissingInfoWhenSet(): void {
		/** @var \Cake\Collection\Collection $globalContents */
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummySingleRow');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$entity = $globalContents->firstMatch(['id' => 13]);

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
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::renderElement()
	 * @throws \ReflectionException
	 */
	public function testRenderElementRendersParsesWidget() {
		/** @var \Cake\Collection\Collection $globalContents */
		$globalContents = $this->callProtectedMethod($this->cell, 'getThreadedGlobalContents', 'dummySingleRow');
		$this->callProtectedMethod($this->cell, 'prepareEntities', $globalContents);

		$entity = $globalContents->firstMatch(['id' => 13]);
		$entity->text = '<div>Some content</div><widget data-identifier="test">{"key":"value"}</widget><div>Some other content</div>';

		$output = $this->callProtectedMethod($this->cell, 'renderElement', $entity, '');

		$this->assertStringContainsString('<div>Some content</div>Rendered Output (and key is `value`)<div>Some other content</div>', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::renderContentRow()
	 * @throws \ReflectionException
	 */
	public function testRenderContentRow(): void {
		$output = $this->callProtectedMethod($this->cell, 'renderContentRow', 'Lorem ipsum');

		$this->assertStringContainsString('<div class="ContentRow">', $output);
		$this->assertStringContainsString('Lorem ipsum', $output);
		$this->assertStringContainsString('</div>', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::display()
	 */
	public function testDisplay(): void {
		$output = (string)$this->cell('Frontend/GlobalContents', [
			'dummyRowOverflow',
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);
		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'GlobalContent-DummyRowOverflow.txt', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\GlobalContentsCell::display()
	 */
	public function testDisplayWithColumnWidth(): void {
		$output = (string)$this->cell('Frontend/GlobalContents', [
			'dummyNarrow',
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'columnWidth' => 60,
			],
		]);

		$this->assertStringContainsString('ColumnWidth of the Global Content 16: 60', $output);
		$this->assertStringContainsString('ColumnWidth of the Global Content 17: 30', $output);
	}
}
