<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\Exception\MenuValidationException;
use Awyiss\Utility\Menu\FrontendMenu;
use Awyiss\Utility\Menu\FrontendMenuItem;
use Customer\Utility\Menu\FrontendMenu as CustomFrontendMenu;
use Customer\Utility\Menu\FrontendMenuItem as CustomFrontendMenuItem;
use ReflectionClass;


/**
 * Test case for the FrontendMenu class
 *
 * @see \Awyiss\Utility\Menu\FrontendMenu
 */
class FrontendMenuTest extends TestCase {
	/**
	 * @var array
	 */
	protected array $menuConfig = [
		/** @see \Awyiss\Utility\Menu\FrontendMenu::__construct */
		'menuClass' => FrontendMenu::class,
		/** @see \Awyiss\Utility\Menu\FrontendMenuItem::__construct */
		'menuItemClass' => FrontendMenuItem::class,
	];


	/**
	 * Tests basic construction with array of items
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::__construct()
	 */
	public function testConstructWithArray(): void {
		$menuData = [
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
		];

		$menu = new FrontendMenu($menuData, $this->menuConfig);

		$this->assertCount(2, $menu->getItems());
		$this->assertInstanceOf(FrontendMenuItem::class, $menu->getItem('item1'));
		$this->assertEquals('Item 1', $menu->getItem('item1')->getTitle());
		$this->assertInstanceOf(FrontendMenuItem::class, $menu->getItem('item2'));
		$this->assertEquals('Item 2', $menu->getItem('item2')->getTitle());
	}


	/**
	 * Tests construction with object
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::__construct()
	 */
	public function testConstructWithObject(): void {
		$menuData = (object)[
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
		];

		$menu = new FrontendMenu($menuData, $this->menuConfig);

		$this->assertCount(2, $menu->getItems());
		$this->assertInstanceOf(FrontendMenuItem::class, $menu->getItem('item1'));
		$this->assertEquals('Item 1', $menu->getItem('item1')->getTitle());
		$this->assertInstanceOf(FrontendMenuItem::class, $menu->getItem('item2'));
		$this->assertEquals('Item 2', $menu->getItem('item2')->getTitle());
	}


	/**
	 * Tests level setting in constructor
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::__construct()
	 */
	public function testConstructWithLevel(): void {
		$menuData = (object)[
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
		];

		$menu = new FrontendMenu($menuData, $this->menuConfig, 3);

		$this->assertSame(3, $menu->getItem('item1')->getLevel());
	}


	/**
	 * Tests getItem method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::getItem()
	 */
	public function testGetItem(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item2' => new MenuEntry([
				'title' => 'Item 2',
				'active' => true,
				'children' => [
					'item2_1' => new MenuEntry(['title' => 'Item 2.1', 'active' => true]),
				],
			]),
		], $this->menuConfig);

		// Get direct item
		$this->assertInstanceOf(FrontendMenuItem::class, $menu->getItem('item1'));
		$this->assertEquals('Item 1', $menu->getItem('item1')->getTitle());

		// Get nested item with deep=true (default)
		$this->assertInstanceOf(FrontendMenuItem::class, $menu->getItem('item2_1'));

		// Get nested item with deep=false
		$this->assertNull($menu->getItem('item2_1', false));

		// Get non-existent item
		$this->assertNull($menu->getItem('nonexistent'));
	}


	/**
	 * Tests getItems method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::getItems()
	 */
	public function testGetItems(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
		], $this->menuConfig);

		$items = $menu->getItems();
		$this->assertCount(2, $items);
		$this->assertInstanceOf(FrontendMenuItem::class, $items['item1']);
		$this->assertInstanceOf(FrontendMenuItem::class, $items['item2']);
	}


	/**
	 * Tests appendEntries method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::appendEntries()
	 * @throws \ReflectionException
	 */
	public function testAppendEntries(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'system' => new MenuEntry(['title' => 'System', 'active' => true]),
		], $this->menuConfig);

		$entries = [
			'item1_1' => new MenuEntry(['title' => 'Item 1.1', 'active' => true]),
		];

		$menu->appendEntries($entries, 'item1');

		$this->assertInstanceOf(FrontendMenuItem::class, $menu->getItem('item1_1'));
		$this->assertEquals('Item 1.1', $menu->getItem('item1_1')->getTitle());
	}


	/**
	 * Tests appendEntries fallback to system menu
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::appendEntries()
	 * @throws \ReflectionException
	 */
	public function testAppendEntriesFailsForNonexistentIdentifier(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'system' => new MenuEntry(['title' => 'System', 'active' => true]),
		], $this->menuConfig);

		$entries = [
			'item1_1' => new MenuEntry(['title' => 'Item 1.1', 'active' => true]),
		];

		$this->expectException(MenuValidationException::class);
		$this->expectExceptionMessage('Cannot append entries to an unknown identifier. `nonexistent` given.');
		$menu->appendEntries($entries, 'nonexistent');
	}


	/**
	 * Tests appendEntries with empty entries
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::appendEntries()
	 * @throws \ReflectionException
	 */
	public function testAppendEntriesWithEmptyEntries(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
		], $this->menuConfig);

		$this->expectException(MenuValidationException::class);
		$this->expectExceptionMessage('Cannot append empty entries.');

		$menu->appendEntries([], 'item1');
	}


	/**
	 * Tests insertEntriesAfter method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::insertEntriesAfter()
	 * @throws \ReflectionException
	 */
	public function testInsertEntriesAfter(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
		], $this->menuConfig);

		$entries = [
			'item3' => new MenuEntry(['title' => 'Item 3', 'active' => true]),
		];

		$menu->insertEntriesAfter($entries, 'item1');

		$items = $menu->getItems();
		$keys = array_keys($items);

		$this->assertSame(['item1', 'item3', 'item2'], $keys);
	}


	/**
	 * Tests insertEntriesAfter with null identifier (prepend)
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::insertEntriesAfter()
	 * @throws \ReflectionException
	 */
	public function testInsertEntriesAfterWithNullIdentifier(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
		], $this->menuConfig);

		$entries = [
			'item0' => new MenuEntry(['title' => 'Item 0', 'active' => true]),
		];

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$menu->insertEntriesAfter($entries, null);

		$items = $menu->getItems();
		$keys = array_keys($items);

		$this->assertSame(['item0', 'item1', 'item2'], $keys);
	}


	/**
	 * Tests insertEntriesAfter with un known identifier (append)
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::insertEntriesAfter()
	 * @throws \ReflectionException
	 */
	public function testInsertEntriesAfterWithNonexistentIdentifier(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
		], $this->menuConfig);

		$entries = [
			'item3' => new MenuEntry(['title' => 'Item 3', 'active' => true]),
		];

		$this->expectException(MenuValidationException::class);
		$this->expectExceptionMessage('Cannot insert entries after an unknown identifier. `nonexistent` given.');
		$menu->insertEntriesAfter($entries, 'nonexistent');
	}


	/**
	 * Tests insertEntriesAfter with deep identifier
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::insertEntriesAfter()
	 * @throws \ReflectionException
	 */
	public function testInsertEntriesAfterWithDeepIdentifier(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry([
				'title' => 'Item 1',
				'active' => true,
				'children' => [
					'item1_1' => new MenuEntry(['title' => 'Item 1.1', 'active' => true]),
					'item1_2' => new MenuEntry(['title' => 'Item 1.2', 'active' => true]),
				],
			]),
		], $this->menuConfig);

		$entries = [
			'item1_3' => new MenuEntry(['title' => 'Item 1.3', 'active' => true]),
		];

		$menu->insertEntriesAfter($entries, 'item1_1');

		$item1 = $menu->getItem('item1');
		$children = $item1->getChildren()->getItems();
		$keys = array_keys($children);

		$this->assertEquals(['item1_1', 'item1_3', 'item1_2'], $keys);
	}


	/**
	 * Tests extend method with appendTo
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::extend()
	 * @throws \ReflectionException
	 */
	public function testExtendWithAppendTo(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
		], $this->menuConfig);

		$menuData = [
			'appendTo' => [
				'item1' => [
					'item1_1' => new MenuEntry(['title' => 'Item 1.1', 'active' => true]),
				],
			],
		];

		$menu->extend($menuData);

		$this->assertInstanceOf(FrontendMenuItem::class, $menu->getItem('item1_1'));
	}


	/**
	 * Tests extend method with appendTo with a nonexistent identifier
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::extend()
	 * @throws \ReflectionException
	 */
	public function testExtendWithAppendToWithNonexistentIdentifier(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
		], $this->menuConfig);

		$menuData = [
			'appendTo' => [
				'nonexistent' => [
					'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
				],
			],
		];

		$this->expectException(MenuValidationException::class);
		$menu->extend($menuData);
	}


	/**
	 * Tests extend method with insertAfter
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::extend()
	 * @throws \ReflectionException
	 */
	public function testExtendWithInsertAfter(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item3' => new MenuEntry(['title' => 'Item 3', 'active' => true]),
		], $this->menuConfig);

		$menuData = [
			'insertAfter' => [
				'item1' => [
					'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
				],
			],
		];

		$menu->extend($menuData);

		$items = $menu->getItems();
		$keys = array_keys($items);

		$this->assertEquals(['item1', 'item2', 'item3'], $keys);
	}


	/**
	 * Tests extend method with insertAfter a nonexistent identifier
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::extend()
	 * @throws \ReflectionException
	 */
	public function testExtendWithInsertAfterWithNonexistentIdentifier(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry(['title' => 'Item 1', 'active' => true]),
			'item3' => new MenuEntry(['title' => 'Item 3', 'active' => true]),
		], $this->menuConfig);

		$menuData = [
			'insertAfter' => [
				'nonexistent' => [
					'item2' => new MenuEntry(['title' => 'Item 2', 'active' => true]),
				],
			],
		];

		$this->expectException(MenuValidationException::class);
		$this->expectExceptionMessage('Cannot insert entries after an unknown identifier. `nonexistent` given.');
		$menu->extend($menuData);
	}


	/**
	 * @return void
	 */
	public function testMenuClassAndMenuItemClass(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry([
				'title' => 'Item 1',
				'active' => true,
			]),
			'item2' => new MenuEntry([
				'title' => 'Item 2',
				'active' => true,
				'access' => ['scope' => 'test_scope', 'identifier' => 'test_identifier'],
				'children' => [
					'item2_1' => new MenuEntry([
						'title' => 'Item 2.1',
						'active' => true,
					]),
				],
			]),
		], [
			'menuClass' => CustomFrontendMenu::class,
			'menuItemClass' => CustomFrontendMenuItem::class,
		]);

		$this->assertInstanceOf(CustomFrontendMenuItem::class, $menu->getItem('item1'));
		$this->assertInstanceOf(CustomFrontendMenuItem::class, $menu->getItem('item2'));
		$this->assertInstanceOf(CustomFrontendMenu::class, $menu->getItem('item2')->getChildren());
	}


	/**
	 * Tests setIdentity method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::setIdentity()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testSetIdentity(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry([
				'title' => 'Item 1',
				'active' => true,
			]),
			'item2' => new MenuEntry([
				'title' => 'Item 2',
				'active' => true,
				'access' => ['scope' => 'test_scope', 'identifier' => 'test_identifier'],
				'children' => [
					'item2_1' => new MenuEntry([
						'title' => 'Item 2.1',
						'active' => true,
					]),
				],
			]),
		], $this->menuConfig);

		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible');

		$menu->setIdentity($identity);
	}


	/**
	 * Tests determineVisibility method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::determineVisibility()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testDetermineVisibility(): void {
		// Create mock MenuItem objects
		$item1 = $this->createMock(FrontendMenuItem::class);
		$item1->expects($this->once())->method('determineVisibility')->with(true);

		$item2 = $this->createMock(FrontendMenuItem::class);
		$item2->expects($this->once())->method('determineVisibility')->with(true);

		// Create a menu with mock items
		$menu = new FrontendMenu([], $this->menuConfig);

		$reflection = new ReflectionClass($menu);
		$property = $reflection->getProperty('items');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($menu, [
			'item1' => $item1,
			'item2' => $item2,
		]);

		// Call determineVisibility
		$menu->determineVisibility();
	}


	/**
	 * Tests items generator method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::items()
	 */
	public function testItemsGenerator(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry([
				'title' => 'Item 1',
				'active' => true,
			]),
			'item2' => new MenuEntry([
				'title' => 'Item 2',
				'active' => true,
				'children' => [
					'item2_1' => new MenuEntry([
						'title' => 'Item 2.1',
						'active' => true,
					]),
					'item2_2' => new MenuEntry([
						'title' => 'Item 2.2',
						'active' => true,
						'children' => [
							'item2_2_1' => new MenuEntry([
								'title' => 'Item 2.2.1',
								'active' => true,
							]),
						],
					]),
				],
			]),
		], $this->menuConfig);

		$items = iterator_to_array($menu->items());

		$this->assertCount(5, $items);
		$this->assertArrayHasKey('item1', $items);
		$this->assertArrayHasKey('item2', $items);
		$this->assertArrayHasKey('item2_1', $items);
		$this->assertArrayHasKey('item2_2', $items);
		$this->assertArrayHasKey('item2_2_1', $items);
	}


	/**
	 * Tests items generator with maxLevel parameter
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::items()
	 */
	public function testItemsGeneratorWithMaxLevel(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry([
				'title' => 'Item 1',
				'active' => true,
			]),
			'item2' => new MenuEntry([
				'title' => 'Item 2',
				'active' => true,
				'children' => [
					'item2_1' => new MenuEntry([
						'title' => 'Item 2.1',
						'active' => true,
					]),
					'item2_2' => new MenuEntry([
						'title' => 'Item 2.2',
						'active' => true,
						'children' => [
							'item2_2_1' => new MenuEntry([
								'title' => 'Item 2.2.1',
								'active' => true,
							]),
						],
					]),
				],
			]),
		], $this->menuConfig);

		// Limit to level 2 items
		$items = iterator_to_array($menu->items(2));

		$this->assertCount(4, $items);
		$this->assertArrayHasKey('item1', $items);
		$this->assertArrayHasKey('item2', $items);
		$this->assertArrayHasKey('item2_1', $items);
		$this->assertArrayHasKey('item2_2', $items);
		$this->assertArrayNotHasKey('item2_2_1', $items);
	}


	/**
	 * Tests toArray method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenu::toArray()
	 */
	public function testToArray(): void {
		$menu = new FrontendMenu([
			'item1' => new MenuEntry([
				'title' => 'Item 1',
				'active' => true,
			]),
			'item2' => new MenuEntry([
				'title' => 'Item 2',
				'active' => true,
				'children' => [
					'item2_1' => new MenuEntry([
						'title' => 'Item 2.1',
						'active' => true,
					]),
				],
			]),
		], $this->menuConfig);

		$array = $menu->toArray();

		$this->assertCount(3, $array);
		$this->assertArrayHasKey('item1', $array);
		$this->assertArrayHasKey('item2', $array);
		$this->assertArrayHasKey('item2_1', $array);
		$this->assertInstanceOf(FrontendMenuItem::class, $array['item1']);
		$this->assertInstanceOf(FrontendMenuItem::class, $array['item2']);
		$this->assertInstanceOf(FrontendMenuItem::class, $array['item2_1']);
	}
}
