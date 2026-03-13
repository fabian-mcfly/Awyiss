<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\BackendMenu;
use Awyiss\Utility\Menu\BackendMenuItem;
use Awyiss\Utility\Menu\MenuItemLink;
use Cake\Http\ServerRequest;
use Cake\Routing\Exception\MissingRouteException;
use Cake\TestSuite\IntegrationTestTrait;
use Customer\Utility\Menu\BackendMenu as CustomBackendMenu;
use Customer\Utility\Menu\BackendMenuItem as CustomBackendMenuItem;
use ReflectionClass;
use RuntimeException;
use stdClass;


/**
 * Test case for BackendMenuItem class.
 *
 * @see \Awyiss\Utility\Menu\BackendMenuItem
 */
class BackendMenuItemTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var array
	 */
	protected array $menuConfig = [
		/** @see \Awyiss\Utility\Menu\BackendMenu::__construct */
		'menuClass' => BackendMenu::class,
		/** @see \Awyiss\Utility\Menu\BackendMenuItem::__construct */
		'menuItemClass' => BackendMenuItem::class,
	];
	/**
	 * @var BackendMenuEntry
	 */
	protected BackendMenuEntry $menuEntry;
	/**
	 * @var IdentityPermissionsInterface
	 */
	protected IdentityPermissionsInterface $identity;


	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
		Awyiss::loadConfiguration('xy', 'yx');
		$this->loadRoutes();

		// Reset static test URL before each test
		$this->setStaticProperty(BackendMenuItem::class, 'testUrl', null);

		$request = new ServerRequest([
			'url' => '/xy/dummy/view/',
			'params' => [
				'lang' => 'xy',
				'controller' => 'Dummy',
				'action' => 'view',
				'_name' => Awyiss::REALM_BACKEND,
				'prefix' => Awyiss::REALM_BACKEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$this->menuEntry = new BackendMenuEntry([
			'id' => 1,
			'identifier' => 'test-menu-item',
			'active' => true,
			'title' => 'Test Menu Item',
		]);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->identity = $this->login(1);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithMinimalProperties(): void {
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);

		$this->assertTrue($menuItem->getActive());
		$this->assertSame('test-menu-item', $menuItem->getIdentifier());
		$this->assertSame('Test Menu Item', $menuItem->getTitle());
		$this->assertNull($menuItem->getLink());
		$this->assertNull($menuItem->getChildren());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithObjectAndMinimalProperties(): void {
		$menuEntry = new stdClass();
		$menuEntry->id = 2;
		$menuEntry->identifier = 'another-menu-item';
		$menuEntry->active = true;
		$menuEntry->title = 'Another Menu Item';

		$menuItem = new BackendMenuItem($menuEntry, $this->menuConfig);

		$this->assertTrue($menuItem->getActive());
		$this->assertSame('another-menu-item', $menuItem->getIdentifier());
		$this->assertSame('Another Menu Item', $menuItem->getTitle());
		$this->assertNull($menuItem->getLink());
		$this->assertNull($menuItem->getChildren());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorUsesIdWhenIdentifierNotSet(): void {
		$menuEntry = new stdClass();
		$menuEntry->id = 3;
		$menuEntry->active = true;
		$menuEntry->title = 'Menu Item Without Identifier';

		$menuItem = new BackendMenuItem($menuEntry, $this->menuConfig);

		$this->assertTrue($menuItem->getActive());
		$this->assertSame(3, $menuItem->getIdentifier());
		$this->assertSame('Menu Item Without Identifier', $menuItem->getTitle());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithLink(): void {
		$link = new stdClass();
		$link->url = '/test-url';

		$this->menuEntry->link = $link;
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);

		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->assertSame('/test-url/', $menuItem->getLink()->getUrl());

		$this->menuEntry->link = '/foobar-url';
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->assertSame('/foobar-url/', $menuItem->getLink()->getUrl());

		$this->menuEntry->link = '//www.example.com';
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->assertSame('//www.example.com', $menuItem->getLink()->getUrl());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithLinkForExistingRoute(): void {
		$link = new stdClass();
		$link->url = new stdClass();
		$link->url->controller = 'Dummy';
		$link->url->action = 'view';
		$this->menuEntry->link = $link;
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->assertSame('/backend/xy/dummy/view/', $menuItem->getLink()->getUrl());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithLinkForNonExistingRoute(): void {
		$link = new stdClass();
		$link->url = new stdClass();
		$link->url->controller = false;
		$this->menuEntry->link = $link;
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->expectException(MissingRouteException::class);
		$menuItem->getLink()->getUrl();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::getChildren()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::hasChildren()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::children()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithChildren(): void {
		$childEntry = new stdClass();
		$childEntry->identifier = 'child-item';
		$childEntry->active = true;
		$childEntry->title = 'Child Menu Item';

		$this->menuEntry->children = [$childEntry];

		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);

		$this->assertInstanceOf(BackendMenu::class, $menuItem->getChildren());
		$this->assertTrue($menuItem->hasChildren());

		// Check that child is properly created and accessible
		$children = iterator_to_array($menuItem->children(), false);
		$this->assertInstanceOf(BackendMenuItem::class, reset($children));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::setAccessible()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::isAccessible()
	 * @throws \ReflectionException
	 */
	public function testIsAccessible(): void {
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);

		// Default behavior - should return true
		$this->assertNull($menuItem->isAccessible());

		// Explicitly set false
		$menuItem->setAccessible(false);
		$this->assertFalse($menuItem->isAccessible());

		// Explicitly set true
		$menuItem->setAccessible(true);
		$this->assertTrue($menuItem->isAccessible());

		// Explicitly set null
		$menuItem->setAccessible(null);
		$this->assertNull($menuItem->isAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::setVisible()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::isVisible()
	 * @throws \ReflectionException
	 */
	public function testIsVisible(): void {
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);

		// Default behavior - should return true
		$this->assertNull($menuItem->isVisible());

		// Explicitly set false
		$menuItem->setVisible(false);
		$this->assertFalse($menuItem->isVisible());

		// Explicitly set true
		$menuItem->setVisible(true);
		$this->assertTrue($menuItem->isVisible());

		// Explicitly set null
		$menuItem->setVisible(null);
		$this->assertNull($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::getLabel()
	 * @throws \ReflectionException
	 */
	public function testGetLabel(): void {
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertSame('Test Menu Item', $menuItem->getLabel());

		// Test with inactive item
		$this->menuEntry->active = false;
		$inactiveMenuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertStringContainsString('inactive', $inactiveMenuItem->getLabel());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::isCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testIsCurrentRoute(): void {
		// Set up a link
		$link = new stdClass();
		$link->url = '/test-url';
		$this->menuEntry->link = $link;

		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		// Test matching route
		$this->assertTrue($menuItem->isCurrentRoute('/test-url'));
		$this->assertTrue($menuItem->isCurrentRoute('/test-url/'));

		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		// Test non-matching route
		$this->assertFalse($menuItem->isCurrentRoute('/other-url'));
		$this->assertFalse($menuItem->isCurrentRoute('/other-url/'));

		$this->menuEntry->link = '/test-url/id:5';
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		// Test parameterized route
		$this->assertTrue($menuItem->isCurrentRoute('/test-url/id:5'));
		$this->assertTrue($menuItem->isCurrentRoute('/test-url/id:4/'));

		$link = new stdClass();
		$link->url = new stdClass();
		$link->url->controller = 'Dummy';
		$link->url->action = 'view';
		$link->url->id = 123;
		$this->menuEntry->link = $link;
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		// Test controller/action link
		$this->assertTrue($menuItem->isCurrentRoute('/backend/xy/dummy/view/id:123'));
	}


	/**
	 * Tests special controller mapping for Contents controller
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::isCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testIsCurrentRouteWithContentsController(): void {
		// Set up request with Contents controller
		$request = Router::getRequest()->withParam('controller', 'Contents')->withParam('lang', 'en');
		Router::setRequest($request);

		// Create a menu item that should match the page role
		$menuItem = new BackendMenuItem(
			new BackendMenuEntry([
				'id' => 201,
				'title' => 'Page Role Item',
				'active' => true,
				'link' => '/backend/en/news/overview',
			]),
			$this->menuConfig
		);

		// Test with a URL that contains the page role
		$this->assertTrue($menuItem->isCurrentRoute('/en/news/edit/id:123'));
	}


	/**
	 * Tests special controller mapping for FormElements controller
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::isCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testIsCurrentRouteWithFormElementsController(): void {
		// Set up request with FormElements controller
		$request = Router::getRequest()->withParam('controller', 'FormElements')->withParam('lang', 'en');
		Router::setRequest($request);

		// Create a menu item for Forms
		$formsItem = new BackendMenuItem(
			new BackendMenuEntry([
				'id' => 301,
				'title' => 'Forms Item',
				'active' => true,
				'link' => '/backend/en/forms/overview',
			]),
			$this->menuConfig
		);

		// Should match because FormElements maps to Forms
		$this->assertTrue($formsItem->isCurrentRoute('/en/form-elements/edit/id:123'));
	}


	/**
	 * Tests special controller mapping for MenuEntries controller
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::isCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testIsCurrentRouteWithMenuEntriesController(): void {
		// Set up request with MenuEntries controller
		$request = Router::getRequest()->withParam('controller', 'MenuEntries')->withParam('lang', 'en');
		Router::setRequest($request);

		// Create a menu item for Menus
		$menusItem = new BackendMenuItem(
			new BackendMenuEntry([
				'id' => 401,
				'title' => 'Menus Item',
				'active' => true,
				'link' => '/backend/en/menus/overview',
			]),
			$this->menuConfig
		);

		// Should match because MenuEntries maps to Menus
		$this->assertTrue($menusItem->isCurrentRoute('/en/menu-entries/edit/id:123'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::getIdentifier()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::getLevel()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::__get()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::offsetExists()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::offsetGet()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::offsetSet()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::offsetUnset()
	 * @throws \ReflectionException
	 */
	public function testGettersAndArrayAccess(): void {
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);

		// Test getters
		$this->assertSame('test-menu-item', $menuItem->getIdentifier());
		$this->assertSame(1, $menuItem->getLevel());

		// Test ArrayAccess
		$this->assertTrue(isset($menuItem['title']));
		$this->assertSame('Test Menu Item', $menuItem['title']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::offsetSet()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::offsetUnset()
	 * @throws \ReflectionException
	 */
	public function testOffsetSetIsDisabled(): void {
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);

		// Test exceptions
		$this->expectException(RuntimeException::class);
		$menuItem['title'] = 'New Title';
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::offsetSet()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::offsetUnset()
	 * @throws \ReflectionException
	 */
	public function testOffsetUnsetIsDisabled(): void {
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);

		// Test exceptions
		$this->expectException(RuntimeException::class);
		unset($menuItem['title']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::getTitle()
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::convertTitle()
	 * @throws \ReflectionException
	 */
	public function testObjectTitleTranslation(): void {
		$titleObj = new stdClass();
		$titleObj->translate = ['Pages', 'headline_overview'];

		$menuEntry = new stdClass();
		$menuEntry->id = 4;
		$menuEntry->active = true;
		$menuEntry->title = $titleObj;

		$menuItem = new BackendMenuItem($menuEntry, $this->menuConfig);

		$this->assertSame('Pages::headline_overview', $menuItem->getTitle());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibility(): void {
		// Test with link - should be visible
		$this->menuEntry->link = '/test-url';
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertNull($menuItem->determineVisibility(true));
		$this->assertNull($menuItem->isVisible());

		// Set access control to false
		$menuItem->setAccessible(false);
		$this->assertFalse($menuItem->determineVisibility(true));
		$this->assertFalse($menuItem->isVisible());

		// Set access control to true
		$menuItem->setAccessible(true);
		$this->assertTrue($menuItem->determineVisibility(true));
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityNoLink(): void {
		// Item with no link should be invisible
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertNull($menuItem->determineVisibility(true));
		$this->assertNull($menuItem->isVisible());

		// Set access control to false
		$menuItem->setAccessible(false);
		$this->assertFalse($menuItem->determineVisibility(true));
		$this->assertFalse($menuItem->isVisible());

		// Set access control to true
		$menuItem->setAccessible(true);
		$this->assertTrue($menuItem->determineVisibility(true));
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::determineVisibility()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityWithIdentityAllowed(): void {
		// Create menu item with access control
		$accessObj = new stdClass();
		$accessObj->scope = 'test-scope';
		$accessObj->identifier = 'test-permission';

		$menuEntryWithAccess = new BackendMenuEntry([
			'id' => 5,
			'active' => true,
			'title' => 'Protected Item',
			'link' => '/protected',
			'access' => $accessObj,
		]);

		// Create identity that grants access
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible')->with('test-scope', [], 'test-permission')->willReturn(true);

		$menuItem = new BackendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($identity);

		$this->assertTrue($menuItem->determineVisibility());
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::determineVisibility()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityWithIdentityDenied(): void {
		// Create menu item with access control
		$accessObj = new stdClass();
		$accessObj->scope = 'test-scope';
		$accessObj->identifier = 'test-permission';

		$menuEntryWithAccess = new BackendMenuEntry([
			'id' => 6,
			'active' => true,
			'title' => 'Protected Item',
			'link' => '/protected',
			'access' => $accessObj,
		]);

		// Create identity that denies access
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible')->willReturn(false);

		$menuItem = new BackendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($identity);

		$this->assertFalse($menuItem->determineVisibility());
		$this->assertFalse($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityReset(): void {
		$this->menuEntry->link = '/test-url';
		$menuItem = new BackendMenuItem($this->menuEntry, $this->menuConfig);

		// Set visible to false explicitly
		$menuItem->setVisible(false);

		// Without reset, should keep current value
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->assertFalse($menuItem->determineVisibility(false));
		$this->assertFalse($menuItem->isVisible());

		// With reset, should recalculate (null because item has no access control)
		$this->assertNull($menuItem->determineVisibility(true));
		$this->assertNull($menuItem->isVisible());
	}


	/**
	 * Data provider for complex hierarchy tests.
	 *
	 * @return array
	 */
	public static function complexHierarchyProvider(): array {
		// Create a complex hierarchy with multiple branches
		$leaf1 = new BackendMenuEntry([
			'id' => 41,
			'title' => 'Leaf 1',
			'active' => true,
			'link' => '/branch1/leaf1',
			'access' => (object)[
				'scope' => 'TestScope',
				'identifier' => 'test-permission',
			],
		]);

		$leaf2 = new BackendMenuEntry([
			'id' => 42,
			'title' => 'Leaf 2',
			'active' => true,
			'link' => '/branch1/leaf2',
			'access' => (object)[
				'scope' => 'TestScope',
				'identifier' => 'test-permission',
			],
		]);

		$leaf3 = new BackendMenuEntry([
			'id' => 43,
			'title' => 'Leaf 3',
			'active' => true,
			'link' => '/leaf3',
			'access' => (object)[
				'scope' => 'TestScope',
				'identifier' => 'test-permission',
			],
		]);

		$leaf4 = new BackendMenuEntry([
			'id' => 44,
			'title' => 'Leaf 4',
			'active' => true,
			'link' => '/leaf4',
			'access' => (object)[
				'scope' => 'TestScope',
				'identifier' => 'test-permission',
			],
		]);

		$leaf5 = new BackendMenuEntry([
			'id' => 45,
			'title' => 'Leaf 5',
			'active' => true,
			'link' => '/branch3/leaf5',
			'access' => (object)[
				'scope' => 'TestScope',
				'identifier' => 'test-permission',
			],
		]);

		$leaf6 = new BackendMenuEntry([
			'id' => 46,
			'title' => 'Leaf 6',
			'active' => true,
			'link' => '/branch3/leaf6',
			'access' => (object)[
				'scope' => 'TestScope',
				'identifier' => 'test-permission',
			],
		]);

		$branch1 = new BackendMenuEntry([
			'id' => 31,
			'title' => 'Branch 1',
			'active' => true,
			'children' => [$leaf1, $leaf2],
		]);

		$branch2 = new BackendMenuEntry([
			'id' => 32,
			'title' => 'Branch 2',
			'active' => true,
			'children' => [$leaf3, $leaf4],
		]);

		$branch3 = new BackendMenuEntry([
			'id' => 33,
			'title' => 'Branch 3',
			'active' => true,
			'link' => '/branch3',
			'children' => [$leaf5, $leaf6],
		]);

		$branch4 = new BackendMenuEntry([
			'id' => 34,
			'title' => 'Branch 4',
			'active' => true,
			'link' => '/branch4',
			// No children for this branch
		]);

		return [
			[
				new BackendMenuEntry([
					'id' => 20,
					'title' => 'Root Item',
					'active' => true,
					'children' => [$branch1, $branch2, $branch3, $branch4],
				]),
			],
		];
	}


	/**
	 * @dataProvider complexHierarchyProvider
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $root
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityComplexHierarchyDefault(BackendMenuEntry $root): void {
		$root = new BackendMenuItem($root, $this->menuConfig);

		// Get references to items for easier testing
		$branch1Item = $root->getChildren()->getItem(31);
		$branch2Item = $root->getChildren()->getItem(32);
		$branch3Item = $root->getChildren()->getItem(33);
		$branch4Item = $root->getChildren()->getItem(34);
		$leaf1Item = $branch1Item->getChildren()->getItem(41);
		$leaf2Item = $branch1Item->getChildren()->getItem(42);
		$leaf3Item = $branch2Item->getChildren()->getItem(43);
		$leaf4Item = $branch2Item->getChildren()->getItem(44);
		$leaf5Item = $branch3Item->getChildren()->getItem(45);
		$leaf6Item = $branch3Item->getChildren()->getItem(46);

		// Root has no link and no child is accessible, so it should not be visible
		$this->assertFalse($root->determineVisibility(true));
		$this->assertFalse($root->isVisible());

		// Branch 1 and 2 have no links, so they should not be visible
		$this->assertFalse($branch1Item->determineVisibility(true));
		$this->assertFalse($branch1Item->isVisible());

		$this->assertFalse($branch2Item->determineVisibility(true));
		$this->assertFalse($branch2Item->isVisible());

		// Branch 3 and 4 have links, so they should be visible
		$this->assertNull($branch3Item->determineVisibility(true));
		$this->assertNull($branch3Item->isVisible());

		$this->assertNull($branch4Item->determineVisibility(true));
		$this->assertNull($branch4Item->isVisible());

		// Leaf items have no links and are not accessible, so they should have null visibility
		$this->assertNull($leaf1Item->determineVisibility(true));
		$this->assertNull($leaf1Item->isVisible());

		$this->assertNull($leaf2Item->determineVisibility(true));
		$this->assertNull($leaf2Item->isVisible());

		$this->assertNull($leaf3Item->determineVisibility(true));
		$this->assertNull($leaf3Item->isVisible());

		$this->assertNull($leaf4Item->determineVisibility(true));
		$this->assertNull($leaf4Item->isVisible());

		$this->assertNull($leaf5Item->determineVisibility(true));
		$this->assertNull($leaf5Item->isVisible());

		$this->assertNull($leaf6Item->determineVisibility(true));
		$this->assertNull($leaf6Item->isVisible());
	}


	/**
	 * @dataProvider complexHierarchyProvider
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $root
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityComplexHierarchyFullyAccessibleLeafs(BackendMenuEntry $root): void {
		$root = new BackendMenuItem($root, $this->menuConfig);

		// Get references to items for easier testing
		$branch1Item = $root->getChildren()->getItem(31);
		$leaf1Item = $branch1Item->getChildren()->getItem(41);
		$leaf2Item = $branch1Item->getChildren()->getItem(42);

		$leaf1Item->setAccessible(true);
		$leaf2Item->setAccessible(true);

		// Root has no link, but all leafs are accessible, so it should be visible
		$this->assertTrue($root->determineVisibility(true));
		$this->assertTrue($root->isVisible());

		// Branch 1 has no link, but leafs are accessible, so it should be visible
		$this->assertTrue($branch1Item->determineVisibility(true));
		$this->assertTrue($branch1Item->isVisible());

		// Leaf items are accessible, so they should be visible
		$this->assertTrue($leaf1Item->determineVisibility(true));
		$this->assertTrue($leaf1Item->isVisible());

		$this->assertTrue($leaf2Item->determineVisibility(true));
		$this->assertTrue($leaf2Item->isVisible());
	}


	/**
	 * @dataProvider complexHierarchyProvider
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $root
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityComplexHierarchyMixedAccessibility(BackendMenuEntry $root): void {
		$root = new BackendMenuItem($root, $this->menuConfig);

		// Get references to items for easier testing
		$branch1Item = $root->getChildren()->getItem(31);
		$leaf1Item = $branch1Item->getChildren()->getItem(41);
		$leaf2Item = $branch1Item->getChildren()->getItem(42);

		$leaf1Item->setAccessible(true);
		$leaf2Item->setAccessible(false);

		// Root has no link, but one leaf is accessible, so it should be visible
		$this->assertTrue($root->determineVisibility(true));
		$this->assertTrue($root->isVisible());

		// Branch 1 has no link, but one leaf is accessible, so it should be visible
		$this->assertTrue($branch1Item->determineVisibility(true));
		$this->assertTrue($branch1Item->isVisible());

		// Leaf 1 is accessible, so it should be visible
		$this->assertTrue($leaf1Item->determineVisibility(true));
		$this->assertTrue($leaf1Item->isVisible());

		// Leaf 2 is not accessible, so it should not be visible
		$this->assertFalse($leaf2Item->determineVisibility(true));
		$this->assertFalse($leaf2Item->isVisible());
	}


	/**
	 * @dataProvider complexHierarchyProvider
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $root
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityComplexHierarchyNoAccessibleLeafsWithLinkAndAccess(BackendMenuEntry $root): void {
		$root = new BackendMenuItem($root, $this->menuConfig);

		// Get references to items for easier testing
		$branch3Item = $root->getChildren()->getItem(33);
		$leaf5Item = $branch3Item->getChildren()->getItem(45);
		$leaf6Item = $branch3Item->getChildren()->getItem(46);

		$branch3Item->setAccessible(true);
		$leaf5Item->setAccessible(false);
		$leaf6Item->setAccessible(false);

		// Root has an accessible branch with no accessible leafs, so it should be visible
		$this->assertTrue($root->determineVisibility(true));
		$this->assertTrue($root->isVisible());

		// Branch 3 has a link and is accessible, but no accessible leafs, so it should be visible
		$this->assertTrue($branch3Item->determineVisibility(true));
		$this->assertTrue($branch3Item->isVisible());

		// Leaf items are not accessible, so they should not be visible
		$this->assertFalse($leaf5Item->determineVisibility(true));
		$this->assertFalse($leaf5Item->isVisible());

		$this->assertFalse($leaf6Item->determineVisibility(true));
		$this->assertFalse($leaf6Item->isVisible());
	}


	/**
	 * @dataProvider complexHierarchyProvider
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $root
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityComplexHierarchyNoAccessibleLeafsWithLinkAndNoAccess(BackendMenuEntry $root): void {
		$root = new BackendMenuItem($root, $this->menuConfig);

		// Get references to items for easier testing
		$branch3Item = $root->getChildren()->getItem(33);
		$leaf5Item = $branch3Item->getChildren()->getItem(45);
		$leaf6Item = $branch3Item->getChildren()->getItem(46);

		$leaf5Item->setAccessible(false);
		$leaf6Item->setAccessible(false);

		// Root has no link and no accessible leafs, so it should not be visible
		$this->assertFalse($root->determineVisibility(true));
		$this->assertFalse($root->isVisible());

		// Branch 3 has a link but no accessible leafs, so it should not be visible
		$this->assertNull($branch3Item->determineVisibility(true));
		$this->assertNull($branch3Item->isVisible());

		// Leaf items are not accessible, so they should not be visible
		$this->assertFalse($leaf5Item->determineVisibility(true));
		$this->assertFalse($leaf5Item->isVisible());

		$this->assertFalse($leaf6Item->determineVisibility(true));
		$this->assertFalse($leaf6Item->isVisible());
	}


	/**
	 * @dataProvider complexHierarchyProvider
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $root
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityComplexHierarchyNoAccessibleLeafsWithoutLink(BackendMenuEntry $root): void {
		$root = new BackendMenuItem($root, $this->menuConfig);

		// Get references to items for easier testing
		$branch1Item = $root->getChildren()->getItem(31);
		$leaf1Item = $branch1Item->getChildren()->getItem(41);
		$leaf2Item = $branch1Item->getChildren()->getItem(42);

		$leaf1Item->setAccessible(false);
		$leaf2Item->setAccessible(false);

		// Root has no link and no accessible leafs, so it should not be visible
		$this->assertFalse($root->determineVisibility(true));
		$this->assertFalse($root->isVisible());

		// Branch 1 has no link and no accessible leafs, so it should not be visible
		$this->assertFalse($branch1Item->determineVisibility(true));
		$this->assertFalse($branch1Item->isVisible());

		// Leaf items are not accessible, so they should not be visible
		$this->assertFalse($leaf1Item->determineVisibility(true));
		$this->assertFalse($leaf1Item->isVisible());

		$this->assertFalse($leaf2Item->determineVisibility(true));
		$this->assertFalse($leaf2Item->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::setIdentity()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testSetIdentityResetsVisibility(): void {
		// Create a menu item with access control
		$accessObj = new stdClass();
		$accessObj->scope = 'test-scope';
		$accessObj->identifier = 'test-permission';

		$menuEntryWithAccess = new BackendMenuEntry([
			'id' => 50,
			'active' => true,
			'title' => 'Protected Item',
			'link' => '/protected',
			'access' => $accessObj,
		]);

		// Create identity that grants access
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible')->with('test-scope', [], 'test-permission')->willReturn(true);

		$menuItem = new BackendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($identity);

		// Initially should be visible
		$this->assertTrue($menuItem->isVisible());

		// Change identity to one that denies access
		$identityDenied = $this->createMock(IdentityPermissionsInterface::class);
		$identityDenied->expects($this->once())->method('scopeIsAccessible')->willReturn(false);

		$menuItem->setIdentity($identityDenied);

		// Should now be invisible
		$this->assertFalse($menuItem->isVisible());
	}


	/**
	 * @dataProvider complexHierarchyProvider
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $root
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityComplexHierarchyWithIdentity(BackendMenuEntry $root): void {
		$root = new BackendMenuItem($root, $this->menuConfig);

		// Get references to items for easier testing
		$branch1Item = $root->getChildren()->getItem(31);
		$branch2Item = $root->getChildren()->getItem(32);
		$leaf1Item = $branch1Item->getChildren()->getItem(41);
		$leaf2Item = $branch1Item->getChildren()->getItem(42);
		$leaf3Item = $branch2Item->getChildren()->getItem(43);
		$leaf4Item = $branch2Item->getChildren()->getItem(44);

		// Create identity that grants access to all items
		$identity = $this->getMockBuilder($this->identity::class)->onlyMethods(['scopeIsAccessible'])->getMock();
		$identity->expects($this->atLeastOnce())->method('scopeIsAccessible')->willReturn(true);

		// Set identity on root item
		/** @noinspection PhpParamsInspection */
		$root->setIdentity($identity);

		// All items should be visible because identity grants access
		$this->assertTrue($root->determineVisibility(true));
		$this->assertTrue($root->isVisible());

		$this->assertTrue($branch1Item->determineVisibility(true));
		$this->assertTrue($branch1Item->isVisible());

		$this->assertTrue($leaf1Item->determineVisibility(true));
		$this->assertTrue($leaf1Item->isVisible());

		$this->assertTrue($leaf2Item->determineVisibility(true));
		$this->assertTrue($leaf2Item->isVisible());

		$this->assertTrue($branch2Item->determineVisibility(true));
		$this->assertTrue($branch2Item->isVisible());

		$this->assertTrue($leaf3Item->determineVisibility(true));
		$this->assertTrue($leaf3Item->isVisible());

		$this->assertTrue($leaf4Item->determineVisibility(true));
		$this->assertTrue($leaf4Item->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::hasCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testHasCurrentRoute(): void {
		// Create parent with a child that matches a route
		$childEntry = new BackendMenuEntry([
			'id' => 50,
			'active' => true,
			'title' => 'Child Item',
			'link' => '/route/subroute',
		]);

		$parentEntry = new BackendMenuEntry([
			'id' => 51,
			'active' => true,
			'title' => 'Parent Item',
			'children' => [$childEntry],
			'link' => '/route',
		]);

		$parentItem = new BackendMenuItem($parentEntry, $this->menuConfig);

		// Parent should have the current route because its child has it
		$this->assertTrue($parentItem->hasCurrentRoute('/route/subroute'));

		// Create parent with no children matching the route
		$noMatchEntry = new BackendMenuEntry([
			'id' => 52,
			'active' => true,
			'title' => 'No Match Item',
			'link' => '/other-route',
			'children' => [],
		]);

		$noMatchItem = new BackendMenuItem($noMatchEntry, $this->menuConfig);
		$this->assertFalse($noMatchItem->hasCurrentRoute('/route/subroute'));
	}


	/**
	 * Test hasCurrentRoute with ContentsController in nested structure.
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::hasCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testHasCurrentRouteWithContentsController(): void {
		// Set up request with Contents controller
		$request = Router::getRequest()->withParam('controller', 'Contents')->withParam('lang', 'en');
		Router::setRequest($request);

		// Create a menu item that should match the page role
		$childItem = new BackendMenuEntry([
			'id' => 201,
			'title' => 'Page Role Item',
			'active' => true,
			'link' => '/backend/en/news/overview',
		]);

		$parentItem = new BackendMenuEntry([
			'id' => 200,
			'title' => 'Parent Item',
			'active' => true,
			'children' => [$childItem],
			'link' => '/backend/en/dummy/overview',
		]);

		$menuItem = new BackendMenuItem($parentItem, $this->menuConfig);

		// Test with a URL that contains the page role
		$this->assertFalse($menuItem->isCurrentRoute('/en/news/edit/id:123'));
		$this->assertTrue($menuItem->hasCurrentRoute('/en/news/edit/id:123'));
	}


	/**
	 * Test hasCurrentRoute with FormElementsController in nested structure.
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::hasCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testHasCurrentRouteWithFormElementsController(): void {
		// Set up request with FormElements controller
		$request = Router::getRequest()->withParam('controller', 'FormElements')->withParam('lang', 'en');
		Router::setRequest($request);

		// Create a menu item that should match the form elements role
		$childItem = new BackendMenuEntry([
			'id' => 301,
			'title' => 'Form Item',
			'active' => true,
			'link' => '/backend/en/forms/overview',
		]);

		$parentItem = new BackendMenuEntry([
			'id' => 300,
			'title' => 'Parent Item',
			'active' => true,
			'children' => [$childItem],
			'link' => '/backend/en/dummy/overview',
		]);

		$menuItem = new BackendMenuItem($parentItem, $this->menuConfig);

		// Test with a URL that contains the form elements role
		$this->assertFalse($menuItem->isCurrentRoute('/en/form-elements/edit/id:123'));
		$this->assertTrue($menuItem->hasCurrentRoute('/en/form-elements/edit/id:123'));
	}


	/**
	 * Test hasCurrentRoute with MenuEntriesController in nested structure.
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::hasCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testHasCurrentRouteWithMenuEntriesController(): void {
		// Set up request with MenuEntries controller
		$request = Router::getRequest()->withParam('controller', 'MenuEntries')->withParam('lang', 'en');
		Router::setRequest($request);

		// Create a menu item that should match the menu entries role
		$childItem = new BackendMenuEntry([
			'id' => 401,
			'title' => 'Menu Item',
			'active' => true,
			'link' => '/backend/en/menus/overview',
		]);

		$parentItem = new BackendMenuEntry([
			'id' => 400,
			'title' => 'Parent Item',
			'active' => true,
			'children' => [$childItem],
			'link' => '/backend/en/dummy/overview',
		]);

		$menuItem = new BackendMenuItem($parentItem, $this->menuConfig);

		// Test with a URL that contains the menu entries role
		$this->assertFalse($menuItem->isCurrentRoute('/en/menu-entries/edit/id:123'));
		$this->assertTrue($menuItem->hasCurrentRoute('/en/menu-entries/edit/id:123'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::setChildren()
	 * @throws \ReflectionException
	 */
	public function testSetChildren(): void {
		// Create parent with multiple children
		$child1 = new BackendMenuEntry([
			'id' => 60,
			'active' => true,
			'title' => 'Child 1',
			'link' => '/child1',
		]);

		$child2 = new BackendMenuEntry([
			'id' => 61,
			'active' => true,
			'title' => 'Child 2',
			'link' => '/child2',
		]);

		$parent = new BackendMenuEntry([
			'id' => 62,
			'active' => true,
			'title' => 'Parent',
		]);

		$parentItem = new BackendMenuItem($parent, $this->menuConfig);

		// Set children manually
		$parentItem->setChildren([$child1, $child2]);

		// Verify children were set correctly
		$this->assertTrue($parentItem->hasChildren());
		$this->assertInstanceOf(BackendMenu::class, $parentItem->getChildren());
		$this->assertCount(2, iterator_to_array($parentItem->children(), false));
		$this->assertSame(60, $parentItem->getChildren()->getItem(60)->getIdentifier());
		$this->assertSame(61, $parentItem->getChildren()->getItem(61)->getIdentifier());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuItem::getLevel()
	 * @throws \ReflectionException
	 */
	public function testLevelPropagation(): void {
		// Create a deeply nested structure: root -> parent -> child -> grandchild
		$grandchildEntry = new BackendMenuEntry([
			'id' => 70,
			'active' => true,
			'title' => 'Grandchild',
			'link' => '/grandchild',
		]);

		$childEntry = new BackendMenuEntry([
			'id' => 71,
			'active' => true,
			'title' => 'Child',
			'link' => '/child',
			'children' => [$grandchildEntry],
		]);

		$parentEntry = new BackendMenuEntry([
			'id' => 72,
			'active' => true,
			'title' => 'Parent',
			'link' => '/parent',
			'children' => [$childEntry],
		]);

		$rootEntry = new BackendMenuEntry([
			'id' => 73,
			'active' => true,
			'title' => 'Root',
			'children' => [$parentEntry],
		]);

		// Initialize with level 1
		$rootItem = new BackendMenuItem($rootEntry, $this->menuConfig);

		// Verify level propagation
		$this->assertEquals(1, $rootItem->getLevel());

		$parentItem = $rootItem->getChildren()->getItem(72);
		$this->assertEquals(2, $parentItem->getLevel());

		$childItem = $parentItem->getChildren()->getItem(71);
		$this->assertEquals(3, $childItem->getLevel());

		$grandchildItem = $childItem->getChildren()->getItem(70);
		$this->assertEquals(4, $grandchildItem->getLevel());
	}


	/**
	 * @dataProvider complexHierarchyProvider
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $root
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testMenuClassAndMenuItemClass(BackendMenuEntry $root): void {
		$root = new BackendMenuItem($root, [
			'menuClass' => CustomBackendMenu::class,
			'menuItemClass' => CustomBackendMenuItem::class,
		]);

		// Verify that the leafs are instances of the custom classes
		$branch1Item = $root->getChildren()->getItem(31);
		$leaf1Item = $branch1Item->getChildren()->getItem(41);
		$leaf2Item = $branch1Item->getChildren()->getItem(42);

		$this->assertInstanceOf(CustomBackendMenu::class, $root->getChildren());
		$this->assertInstanceOf(CustomBackendMenuItem::class, $branch1Item);
		$this->assertInstanceOf(CustomBackendMenu::class, $branch1Item->getChildren());
		$this->assertInstanceOf(CustomBackendMenuItem::class, $leaf1Item);
		$this->assertInstanceOf(CustomBackendMenuItem::class, $leaf2Item);
	}


	/**
	 * Helper method to set static property values for testing
	 *
	 * @param string $class
	 * @param string $property
	 * @param mixed $value
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function setStaticProperty(string $class, string $property, mixed $value): void {
		$reflectionClass = new ReflectionClass($class);
		$reflectionProperty = $reflectionClass->getProperty($property);
		/** @noinspection PhpExpressionResultUnusedInspection */
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue(null, $value);
	}
}
