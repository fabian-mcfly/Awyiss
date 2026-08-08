<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Frontend;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Menu;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\FrontendMenuItem;
use Awyiss\View\Cell\Frontend\MenuCell;
use Awyiss\View\FrontendView;
use Cake\Collection\CollectionInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\CellTrait;
use Cake\View\StringTemplate;
use Customer\Utility\Menu\FrontendMenu as CustomFrontendMenu;
use Customer\Utility\Menu\FrontendMenuItem as CustomFrontendMenuItem;
use PHPUnit\Framework\Attributes\DataProvider;


/**
 * MenuCellTest class
 */
class MenuCellTest extends TestCase {
	use CellTrait;


	/**
	 * @var \Awyiss\View\Cell\Frontend\MenuCell
	 */
	protected MenuCell $cell;
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
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function setUp(): void {
		parent::setUp();

		Awyiss::setRealm('Frontend');
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);

		Awyiss::loadConfiguration('xy', 'yx');

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

		$this->response = $this->createStub(Response::class);

		$this->view = new FrontendView($this->request);
		$this->cell = new MenuCell($this->request, $this->response);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::getMenu()
	 * @throws \ReflectionException
	 */
	public function testGetMenu(): void {
		$menu = $this->callProtectedMethod($this->cell, 'getMenu', 'main');

		$this->assertInstanceOf(Menu::class, $menu);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::getMenu()
	 * @throws \ReflectionException
	 */
	public function testGetMenuWithInvalidIdentifier(): void {
		$menu = $this->callProtectedMethod($this->cell, 'getMenu', 'unknown_identifier');

		$this->assertNull($menu);
	}


	/**
	 * @return array<array>
	 */
	public static function menuPreviewDataProvider(): array {
		return [
			['main', false, true],
			['main', true, true],
			['footer', false, false],
			['footer', true, true],
			['legal', false, false],
			['legal', true, true],
		];
	}


	/**
	 * @param string $identifier
	 * @param bool $isPreview
	 * @param bool $expectedAvailability
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::getMenu()
	 * @throws \ReflectionException
	 */
	#[DataProvider('menuPreviewDataProvider')]
	public function testGetMenuWithPreview(string $identifier, bool $isPreview, bool $expectedAvailability): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn($isPreview);

		$menu = $this->callProtectedMethod($cell, 'getMenu', $identifier);

		if (!$expectedAvailability) {
			$this->assertNull($menu);

			return;
		}

		$this->assertInstanceOf(Menu::class, $menu);

		$this->assertEquals($identifier, $menu->identifier);
	}


	/**
	 * @return array<array>
	 */
	public static function menuEntriesDataProvider(): array {
		return [
			['main', false, true, 5, 21],
			['main', true, true, 6, 28],
			['footer', false, false, 0, 0],
			['footer', true, true, 2, 12],
			['legal', false, false, 0, 0],
			['legal', true, true, 3, 3],
		];
	}


	/**
	 * @param string $identifier
	 * @param bool $isPreview
	 * @param bool $expectedAvailability
	 * @param int $firstLevelEntries
	 * @param int $totalEntries
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::getMenuEntries()
	 * @throws \ReflectionException
	 */
	#[DataProvider('menuEntriesDataProvider')]
	public function testGetMenuEntries(string $identifier, bool $isPreview, bool $expectedAvailability, int $firstLevelEntries, int $totalEntries): void {
		$cell = $this->getStubBuilder(MenuCell::class)->onlyMethods(['isPreview'])->disableOriginalConstructor()->getStub();

		$cell->method('isPreview')->willReturnOnConsecutiveCalls($isPreview, $isPreview);

		$menu = $this->callProtectedMethod($cell, 'getMenu', $identifier);

		if (!$expectedAvailability) {
			$this->assertNull($menu);

			return;
		}

		$entries = $this->callProtectedMethod($cell, 'getMenuEntries', $menu, 'de');

		$this->assertInstanceOf(CollectionInterface::class, $entries);

		// Check if all first level items have no parent id
		$entries->each(function (MenuEntry $menuEntry) {
			$this->assertEmpty($menuEntry->parentId);
		});

		$this->assertCount($firstLevelEntries, $entries);

		$entriey = $entries->listNested()->compile(false);

		$this->assertCount($totalEntries, $entriey);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderList()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderList(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(false);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('list', [
			'level' => 1,
			'menuConfig' => ['active' => false],
			'title' => 'Test Content',
			'isPreview' => '',
		])->willReturn('Test Content');

		$data = [
			'level' => 1,
			'menuConfig' => ['active' => false],
			'title' => 'Test Content',
		];

		$this->callProtectedMethod($cell, 'renderList', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderList()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderListWithPreview(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(true);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('list', [
			'level' => 1,
			'menuConfig' => ['active' => false],
			'title' => 'Test Content',
			'isPreview' => ' ' . FrontendView::getPreviewModeElementClass(),
		])->willReturn('Test Content');

		$data = [
			'level' => 1,
			'menuConfig' => ['active' => false],
			'title' => 'Test Content',
		];

		$this->callProtectedMethod($cell, 'renderList', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderList()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderListWithPreviewNotSetsPreviewForNotLevel1(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(true);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('list', [
			'level' => 2,
			'menuConfig' => ['active' => false],
			'title' => 'Test Content',
			'isPreview' => '',
		])->willReturn('Test Content');

		$data = [
			'level' => 2,
			'menuConfig' => ['active' => false],
			'title' => 'Test Content',
		];

		$this->callProtectedMethod($cell, 'renderList', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderItem()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderItem(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(false);

		/** @var \Awyiss\Model\Entity\MenuEntry $entity */
		$entity = $this->fetchTable('MenuEntries')->get(19);
		$item = new FrontendMenuItem($entity);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('item', [
			'level' => 1,
			'title' => 'Test Content',
			'isPreview' => '',
			'item' => $item,
			'id' => 19,
			'identifier' => 'TestContent',
		])->willReturn('Test Content');

		$data = [
			'level' => 1,
			'title' => 'Test Content',
			'item' => $item,
		];

		$this->callProtectedMethod($cell, 'renderItem', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderItem()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderItemWithInactiveItem(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(true);

		/** @var \Awyiss\Model\Entity\MenuEntry $entity */
		$entity = $this->fetchTable('MenuEntries')->get(31);
		$item = new FrontendMenuItem($entity);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('item', [
			'level' => 1,
			'title' => 'Test Content',
			'isPreview' => ' ' . FrontendView::getPreviewModeElementClass(),
			'item' => $item,
			'id' => 31,
			'identifier' => 'TestContent',
		])->willReturn('Test Content');

		$data = [
			'level' => 1,
			'title' => 'Test Content',
			'item' => $item,
		];

		$this->callProtectedMethod($cell, 'renderItem', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderItem()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderItemWithUnpublishedItem(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(true);

		/** @var \Awyiss\Model\Entity\MenuEntry $entity */
		$entity = $this->fetchTable('MenuEntries')->get(27);
		$item = new FrontendMenuItem($entity);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('item', [
			'level' => 1,
			'title' => 'Test Content',
			'isPreview' => ' ' . FrontendView::getPreviewModeElementClass(),
			'item' => $item,
			'id' => 27,
			'identifier' => 'TestContent',
		])->willReturn('Test Content');

		$data = [
			'level' => 1,
			'title' => 'Test Content',
			'item' => $item,
		];

		$this->callProtectedMethod($cell, 'renderItem', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderItem()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderItemWithChildren(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(false);

		/** @var \Awyiss\Model\Entity\MenuEntry $entity */
		$entity = $this->fetchTable('MenuEntries')->get(19);
		$item = new FrontendMenuItem($entity);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('item', [
			'level' => 1,
			'title' => 'Test Content',
			'isPreview' => '',
			'item' => $item,
			'id' => 19,
			'identifier' => 'TestContent',
			'children' => '<ul><li>Child 1</li><li>Child 2</li></ul>',
			'submenuTrigger' => '<input type="checkbox" id="SubmenuTrigger-19" class="SubmenuTrigger Level1" />' . PHP_EOL .
				'<label for="SubmenuTrigger-19" class="SubmenuTrigger-Label Level1">submenu_trigger</label>' . PHP_EOL,
		])->willReturn('Test Content');

		$data = [
			'level' => 1,
			'title' => 'Test Content',
			'item' => $item,
			'children' => '<ul><li>Child 1</li><li>Child 2</li></ul>',
		];

		$this->callProtectedMethod($cell, 'renderItem', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderContent()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderContentNoLink(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(false);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('noLink', [
			'level' => 1,
			'title' => 'Test Content',
			'url' => null,
			'identifier' => 'TestContent',
			'tabindex' => '',
		])->willReturn('Test Content');

		$data = [
			'level' => 1,
			'title' => 'Test Content',
			'url' => null,
		];

		$this->callProtectedMethod($cell, 'renderContent', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderContent()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderContentWithLink(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(false);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('link', [
			'level' => 1,
			'title' => 'Test Content',
			'url' => '/test',
			'identifier' => 'TestContent',
			'tabindex' => '',
		])->willReturn('Test Content');

		$data = [
			'level' => 1,
			'title' => 'Test Content',
			'url' => '/test',
		];

		$this->callProtectedMethod($cell, 'renderContent', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::renderContent()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRenderContentWithSpecialCharacters(): void {
		$cell = $this->getStubBuilder(MenuCell::class)
			->onlyMethods(['isPreview'])
			->disableOriginalConstructor()
			->getStub();

		$cell->method('isPreview')->willReturn(false);

		$template = $this->createMock(StringTemplate::class);
		$template->expects($this->once())->method('format')->with('link', [
			'level' => 1,
			'title' => 'Test Content & ÖÄÜß <> & More',
			'url' => '/test',
			'identifier' => 'TestContentOEAEUessMore',
			'tabindex' => '',
		])->willReturn('Test Content & More');

		$data = [
			'level' => 1,
			'title' => 'Test Content & ÖÄÜß <> & More',
			'url' => '/test',
		];

		$this->callProtectedMethod($cell, 'renderContent', $data, $template);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::display()
	 */
	public function testDisplay(): void {
		$output = (string)$this->cell('Frontend/Menu', [
			'main',
			'de',
			$this->view,
		]);
		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Menu-Main.txt', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\MenuCell::display()
	 */
	public function testDisplayUsesCustomerClasses(): void {
		$cell = $this->cell('Frontend/Menu', [
			'main',
			'de',
			$this->view,
		]);

		/** @noinspection PhpUnusedLocalVariableInspection */
		$output = (string)$cell; // phpcs:ignore

		$menu = $cell->viewBuilder()->getVar('menu');
		$this->assertInstanceOf(CustomFrontendMenu::class, $menu);

		$item25 = $menu->getItem(25);
		$this->assertInstanceOf(CustomFrontendMenuItem::class, $item25);

		$this->assertInstanceOf(CustomFrontendMenu::class, $item25->getChildren());

		$item28 = $menu->getItem(28);
		$this->assertInstanceOf(CustomFrontendMenuItem::class, $item28);
	}
}
