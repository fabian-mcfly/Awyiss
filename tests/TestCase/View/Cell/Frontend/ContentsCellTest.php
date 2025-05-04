<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Frontend;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Content;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Awyiss\View\Cell\Frontend\ContentsCell;
use Cake\Collection\CollectionInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\CellTrait;


/**
 * ContentsCellTest class
 */
class ContentsCellTest extends TestCase {
	use CellTrait;
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\View\Cell\Frontend\ContentsCell
	 */
	protected ContentsCell $cell;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
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
			],
		]);

		$this->response = $this->createMock(Response::class);

		$this->cell = new ContentsCell($this->request, $this->response, null, ['action' => 'display']);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitCellOptions(): void {
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
	public function testInitCellOptionsWithCustomOptions(): void {
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
	public function testInitCellOptionsCallsFindFullWidthIfNotSet(): void {
		$this->cell = $this->getMockBuilder(ContentsCell::class)->disableOriginalConstructor()->onlyMethods(['findFullWidth'])->getMock();

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
	public function testInitCellOptionsNotCallsFindFullWidthIfSet(): void {
		$this->cell = $this->getMockBuilder(ContentsCell::class)->disableOriginalConstructor()->onlyMethods(['findFullWidth'])->getMock();

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
	public function testInitCellOptionsCallsFindSingleColumnBreakpointIfNotSet(): void {
		$this->cell = $this->getMockBuilder(ContentsCell::class)->disableOriginalConstructor()->onlyMethods(['findSingleColumnBreakpoint'])->getMock();

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
	public function testInitCellOptionsNotCallsFindSingleColumnBreakpointIfSet(): void {
		$this->cell = $this->getMockBuilder(ContentsCell::class)->disableOriginalConstructor()->onlyMethods(['findSingleColumnBreakpoint'])->getMock();

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
	public static function dataThreadedContentsDataProvider(): array {
		return [
			[1, 6, 21],
			[3, 2, 3],
			[25, 1, 3],
		];
	}


	/**
	 * @dataProvider dataThreadedContentsDataProvider
	 * @param string $identifier
	 * @param int $expectedFirstLevelCount
	 * @param int $expectedTotalCount
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetThreadedContents(int $pageId, int $expectedFirstLevelCount, int $expectedTotalCount): void {
		$page = $this->getTableLocator()->get('Pages')->get($pageId);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea');

		$this->assertInstanceOf(CollectionInterface::class, $contents);
		$this->assertCount($expectedFirstLevelCount, $contents);

		if ($expectedFirstLevelCount > 0) {
			// Check if all first level items have no parent id
			$contents->each(function (Content $content) {
				$this->assertEmpty($content->parentId);
			});

			// Make sure the collection is nested to check all elements
			$contents = $contents->listNested()->compile(false);

			$this->assertCount($expectedTotalCount, $contents);

			// Make sure only active and only those with the given identifier are returned
			$contents = $contents->filter(function (Content $content) {
				return $content->get('active') && $content->get('contentArea')->get('identifier') === 'ContentArea';
			});

			$this->assertCount($expectedTotalCount, $contents);
		}
	}


	/**
	 * @return array
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public static function dataThreadedContentsForUnknownContentAreaDataProvider(): array {
		return [
			[1],
			[1],
			[3],
			[3],
			[25],
			[25],
		];
	}


	/**
	 * @dataProvider dataThreadedContentsForUnknownContentAreaDataProvider
	 * @param string $identifier
	 * @param int $expectedFirstLevelCount
	 * @param int $expectedTotalCount
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedContentsForUnknownContentArea(int $pageId): void {
		$page = $this->getTableLocator()->get('Pages')->get($pageId);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'UnknownArea');

		$this->assertInstanceOf(CollectionInterface::class, $contents);
		$this->assertCount(0, $contents);
	}


	/**
	 * @return array
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public static function dataThreadedContentsContainsInactiveElementsWhenPreviewIsEnabledDataProvider(): array {
		return [
			[1, 7, 24],
			[3, 2, 8],
			[25, 1, 3],
		];
	}


	/**
	 * @dataProvider dataThreadedContentsContainsInactiveElementsWhenPreviewIsEnabledDataProvider
	 * @param string $identifier
	 * @param int $expectedFirstLevelCount
	 * @param int $expectedTotalCount
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedContentsContainsInactiveElementsWhenPreviewIsEnabled(int $pageId, int $expectedFirstLevelCount, int $expectedTotalCount): void {
		$page = $this->getTableLocator()->get('Pages')->get($pageId);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', true);

		$this->assertInstanceOf(CollectionInterface::class, $contents);
		$this->assertCount($expectedFirstLevelCount, $contents);

		if ($expectedFirstLevelCount > 0) {
			// Check if all first level items have no parent id
			$contents->each(function (Content $content) {
				$this->assertEmpty($content->parentId);
			});

			// Make sure the collection is nested to check all elements
			$contents = $contents->listNested()->compile(false);

			$this->assertCount($expectedTotalCount, $contents);

			// Make sure only active and only those with the given identifier are returned
			$contents = $contents->filter(function (Content $content) {
				return $content->get('contentArea')->get('identifier') === 'ContentArea';
			});

			$this->assertCount($expectedTotalCount, $contents);
		}
	}


	/**
	 * @dataProvider dataThreadedContentsDataProvider
	 * @param string $identifier
	 * @param int $expectedFirstLevelCount
	 * @param int $expectedTotalCount
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedContentsNotContainsInactiveElementsWhenPreviewIsDisabled(int $pageId, int $expectedFirstLevelCount, int $expectedTotalCount): void {
		$page = $this->getTableLocator()->get('Pages')->get($pageId);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', false);

		$this->assertInstanceOf(CollectionInterface::class, $contents);
		$this->assertCount($expectedFirstLevelCount, $contents);

		if ($expectedFirstLevelCount > 0) {
			// Check if all first level items have no parent id
			$contents->each(function (Content $content) {
				$this->assertEmpty($content->parentId);
			});

			// Make sure the collection is nested to check all elements
			$contents = $contents->listNested()->compile(false);

			$this->assertCount($expectedTotalCount, $contents);

			// Make sure only active and only those with the given identifier are returned
			$contents = $contents->filter(function (Content $content) {
				return $content->get('active') && $content->get('contentArea')->get('identifier') === 'ContentArea';
			});

			$this->assertCount($expectedTotalCount, $contents);
		}
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedContentsContainsUnpublishedElementsWhenPreviewIsEnabled(): void {
		$page = $this->getTableLocator()->get('Pages')->get(7);

		/** @var \Cake\Collection\CollectionInterface $contents */
		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', true);

		$contents = $contents->listNested()->compile(false);

		$this->assertInstanceOf(CollectionInterface::class, $contents);
		$this->assertCount(2, $contents);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedContentsNotContainsUnpublishedElementsWhenPreviewIsDisabled(): void {
		$page = $this->getTableLocator()->get('Pages')->get(7);

		/** @var \Cake\Collection\CollectionInterface $contents */
		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', false);

		$contents = $contents->listNested()->compile(false);

		$this->assertInstanceOf(CollectionInterface::class, $contents);
		$this->assertCount(0, $contents);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetThreadedContentsLoadsContentsOfDuplicatedPage(): void {
		$page = $this->getTableLocator()->get('Pages')->get(15);

		/** @var \Cake\Collection\CollectionInterface $contents */
		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', false);
		$contents = $contents->listNested()->compile(false);

		$this->assertInstanceOf(CollectionInterface::class, $contents);
		$this->assertCount(21, $contents);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCacheAssignedMediaItems(): void {
		ResizedImageManager::clear();

		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea');

		$files = $this->callProtectedMethod($this->cell, 'cacheAssignedMediaItems', $contents, 'contents');
		$this->assertCount(2, $files);

		$files = ResizedImageManager::getMediaItems();

		$this->assertCount(2, $files);
		$this->assertArrayHasKey(3, $files);

		$resizedFiles = ResizedImageManager::getResizedItems();
		$this->assertCount(1, $resizedFiles);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testCacheAssignedMediaItemsIncludingInactiveItemsInPreviewMode(): void {
		ResizedImageManager::clear();

		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', true);

		$files = $this->callProtectedMethod($this->cell, 'cacheAssignedMediaItems', $contents, 'contents');
		$this->assertCount(3, $files);

		$files = ResizedImageManager::getMediaItems();

		$this->assertCount(3, $files);
		$this->assertArrayHasKey(2, $files);
		$this->assertArrayHasKey(3, $files);
		$this->assertArrayHasKey(4, $files);

		$resizedFiles = ResizedImageManager::getResizedItems();

		$this->assertArrayHasKey(4, $resizedFiles);
		$this->assertCount(16, $resizedFiles[4]);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddDuplicates(): void {
		$contents = $this->fetchTable('Contents')->find('all')->where(['id' => 31])->all();

		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $contents->first();
		$this->assertEmpty($content->children);

		$this->callProtectedMethod($this->cell, 'addDuplicates', $contents);

		$this->assertNotEmpty($content->children);
		$this->assertSame(34, $content->children[0]->id);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testAddDuplicatesNotAddsChildrenOfDuplicatedContentIfChildrenSet(): void {
		$contents = $this->fetchTable('Contents')->find('all')->where(['id' => 31])->all();

		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $contents->first();
		$content->children = [(object)['id' => 123]];
		$this->assertSame(123, $content->children[0]->id);

		$this->callProtectedMethod($this->cell, 'addDuplicates', $contents);

		$this->assertNotEmpty($content->children);
		$this->assertSame(123, $content->children[0]->id);
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
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$contents = $contents->listNested()->compile(false);

		$level0 = $contents->filter(function (Content $content) {
			return $content->get('level') === 0;
		});
		$this->assertCount(7, $level0);

		$level1 = $contents->filter(function (Content $content) {
			return $content->get('level') === 1;
		});
		$this->assertCount(14, $level1);

		$level2 = $contents->filter(function (Content $content) {
			return $content->get('level') === 2;
		});
		$this->assertCount(3, $level2);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrepareEntitiesSetsParentContents(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$contents = $contents->listNested()->compile(false);

		/** @var \Awyiss\Model\Entity\Content $content */
		foreach ($contents as $content) {
			if (in_array($content->id, [1, 2, 3, 4, 5, 6, 7])) {
				$this->assertSame(0, $content->level);
				$this->assertSame([], $content->parentContents);
			}
			elseif (in_array($content->id, [9, 11])) {
				$this->assertSame(1, $content->level);
				$this->assertCount(1, $content->parentContents);
				$this->assertSame([1], array_column($content->parentContents, 'id'));
			}
			elseif ($content->id === 13) {
				$this->assertSame(2, $content->level);
				$this->assertCount(2, $content->parentContents);
				$this->assertSame([1, 11], array_column($content->parentContents, 'id'));
			}
		}
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrepareEntitiesSetCssClasses(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$contents = $contents->listNested()->compile(false);

		$this->assertSame([
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Standard Column-50',
			'ContentElement Template-Standard Column-80',
			'ContentElement Template-Standard Column-50',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Standard Column-60 ColumnIndent-20',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Standard Column-33',
			'ContentElement Template-Standard Column-67',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-33',
			'ContentElement Template-Standard Column-33',
			'ContentElement Template-Standard Column-33',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-60 ColumnIndent-20',
		], array_column($contents->toList(), 'cssClass'));
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testPrepareEntitiesSetCssClassesForInactiveElements(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents, 100.0, true);

		$contents = $contents->listNested()->compile(false);

		$this->assertSame([
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Standard Column-50',
			'ContentElement Template-Standard Column-80',
			'ContentElement Template-Standard Column-50',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Standard Column-60 ColumnIndent-20',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Section Column-100 AwyissFrontendPreview-InactiveElement',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-100 AwyissFrontendPreview-InactiveElement',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-100',
			'ContentElement Template-Standard Column-33',
			'ContentElement Template-Standard Column-67',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-33',
			'ContentElement Template-Standard Column-33',
			'ContentElement Template-Standard Column-33',
			'ContentElement Template-Section Column-100',
			'ContentElement Template-Standard Column-60 ColumnIndent-20',
		], array_column($contents->toList(), 'cssClass'));
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrepareEntitiesSetsRealColumnWidth(): void {
		$page = $this->getTableLocator()->get('Pages')->get(3);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$contents = $contents->listNested()->compile(false);

		$this->assertSame([
			100.0,
			100.0,
			100.0,
			80.0,
			16.0,
			16.0,
			16.0,
			16.0,
		], array_column($contents->toList(), 'realColumnWidth'));
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testPrepareEntitiesSetsRealColumnWidthWithDifferentBaseWidth(): void {
		$page = $this->getTableLocator()->get('Pages')->get(3);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents, 75);

		$contents = $contents->listNested()->compile(false);

		$this->assertSame([
			75.0,
			75.0,
			75.0,
			60.0,
			12.0,
			12.0,
			12.0,
			12.0,
		], array_column($contents->toList(), 'realColumnWidth'));
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPrepareEntitiesSetsTemplate(): void {
		$page = $this->getTableLocator()->get('Pages')->get(3);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$contents = $contents->listNested()->compile(false)->toList();

		$this->assertSame('section', $contents[0]->contentTemplate->fileName);
		$this->assertSame('standard', $contents[1]->contentTemplate->fileName);
		$this->assertSame('section', $contents[2]->contentTemplate->fileName);
		$this->assertSame('standard', $contents[3]->contentTemplate->fileName);
		$this->assertSame('Content39', $contents[4]->contentTemplate->fileName);
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
	public function testBuildContents(): void {
		$options = $this->cell->initCellOptions([
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$this->callProtectedMethod($this->cell, 'setViewVars', $options);

		$page = $this->getTableLocator()->get('Pages')->get(3);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $contents->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Content-Page3.txt', $contents);
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

		$page = $this->getTableLocator()->get('Pages')->get(3);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea', true);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$contents = $this->callProtectedMethod($this->cell, 'buildContents', $contents->toArray());
		$contents = trim(preg_replace('/\s+/', ' ', $contents));
		$contents = str_replace('> ', '>' . PHP_EOL, $contents);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Content-Page3-Preview.txt', $contents);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testParseModuleReplacesModuleTagsWithRenderedOutput(): void {
		$entity = new Content();
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
	public function testParseModuleIgnoresNonModuleTags(): void {
		$entity = new Content();
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
	public function testParseModuleHandlesMissingModulesGracefully(): void {
		$entity = new Content();
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
	public function testParseModuleRemovesModuleTagsWithEmptyOutput(): void {
		$entity = new Content();
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
	public function testParseModuleHandlesMalformedHtmlGracefully(): void {
		$entity = new Content();
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
		/** @var \Cake\Collection\Collection $contents */

		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$entity = $contents->firstMatch(['id' => 3]);

		$output = $this->callProtectedMethod($this->cell, 'renderElement', $entity, '');

		$this->assertStringContainsString('class="ContentElement Template-Section Column-100"', $output);
		$this->assertStringContainsString('id="Content3"', $output);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testRenderElementAddsFullWidthMissingInfo(): void {
		/** @var \Cake\Collection\Collection $contents */
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$entity = $contents->firstMatch(['id' => 3]);

		$output = $this->callProtectedMethod($this->cell, 'renderElement', $entity, '');

		$this->assertStringContainsString('<!-- Full width is missing. Please add the `fullWidth`-option to the content cell. -->', $output);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testRenderElementNotAddsFullWidthMissingInfoWhenSet(): void {
		/** @var \Cake\Collection\Collection $contents */
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea');

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$entity = $contents->firstMatch(['id' => 3]);

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
	public function testRenderElementRendersParsesModule(): void {
		/** @var \Cake\Collection\Collection $contents */
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$contents = $this->callProtectedMethod($this->cell, 'getThreadedContents', $page, 'ContentArea');
		$contents = $contents->listNested()->compile(false);

		$this->callProtectedMethod($this->cell, 'prepareEntities', $contents);

		$entity = $contents->firstMatch(['id' => 9]);
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
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$output = (string)$this->cell('Frontend/Contents', [
			'ContentArea',
			$page,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);
		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Content-Page1.txt', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayNotContainsWrapperWhenNotSet(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$output = (string)$this->cell('Frontend/Contents', [
			'ContentArea',
			$page,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'includeWrapper' => false,
			],
		]);

		$this->assertStringNotContainsString('<div id="ContentArea">', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithColumnWidth(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$output = (string)$this->cell('Frontend/Contents', [
			'ContentArea',
			$page,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'columnWidth' => 60,
			],
		]);

		$this->assertStringNotContainsString('data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $output);
		$this->assertStringContainsString('data-src="../awyiss/Command/Media/TestFiles/_resized/logo-awyiss-[w864].avif"', $output);
		$this->assertStringContainsString('ColumnWidth of the content 10: 30', $output);
		$this->assertStringContainsString('ColumnWidth of the content 12: 24', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testDisplayRendersContentOfDuplicatedContents(): void {
		$page = $this->getTableLocator()->get('Pages')->get(29);

		$output = (string)$this->cell('Frontend/Contents', [
			'ContentArea',
			$page,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);

		$this->assertStringNotContainsString('Heuer bei uns an!</h1>', $output);
		$this->assertStringContainsString('Titel H1</h1>', $output);
		$this->assertStringContainsString('id="Content34"', $output);
	}
}
