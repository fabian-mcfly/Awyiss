<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Menu;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Customer;
use Awyiss\Model\Entity\CustomerGroup;
use Awyiss\Model\Entity\CustomerGroupAccessSetting;
use Awyiss\Model\Entity\CustomerGroupAssignment;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Model\Enum\CustomerGroupAccessType;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\FrontendMenu;
use Awyiss\Utility\Menu\FrontendMenuItem;
use Awyiss\Utility\Menu\Menu;
use Awyiss\Utility\Menu\MenuItemLink;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\Routing\Exception\MissingRouteException;
use Cake\TestSuite\IntegrationTestTrait;
use Customer\Utility\Menu\FrontendMenu as CustomFrontendMenu;
use Customer\Utility\Menu\FrontendMenuItem as CustomFrontendMenuItem;
use RuntimeException;
use stdClass;


/**
 * Test case for FrontendMenuItem class.
 *
 * @see \Awyiss\Utility\Menu\FrontendMenuItem
 */
class FrontendMenuItemTest extends TestCase {
	use IntegrationTestTrait;


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
	 * @var \PHPUnit\Framework\MockObject\MockObject&\Awyiss\Model\Entity\MenuEntry
	 */
	protected MenuEntry $menuEntry;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		Awyiss::loadConfiguration('xy', 'yx');
		$this->loadRoutes();

		$request = new ServerRequest([
			'url' => '/xy/dummy/view/',
			'params' => [
				'lang' => 'xy',
				'controller' => 'Dummy',
				'action' => 'view',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$this->menuEntry = new MenuEntry([
			'id' => 1,
			'active' => true,
			'title' => 'Test Menu Item',
		]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithMinimalProperties(): void {
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		$this->assertTrue($menuItem->getActive());
		$this->assertSame(1, $menuItem->getIdentifier());
		$this->assertSame('Test Menu Item', $menuItem->getTitle());
		$this->assertNull($menuItem->getLink());
		$this->assertNull($menuItem->getChildren());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithLink(): void {
		$link = new stdClass();
		$link->url = '/test-url';

		$this->menuEntry->link = $link;
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->assertSame('/test-url/', $menuItem->getLink()->getUrl());

		$this->menuEntry->link = '/foobar-url';
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->assertSame('/foobar-url/', $menuItem->getLink()->getUrl());

		$this->menuEntry->link = '//www.example.com';
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->assertSame('//www.example.com', $menuItem->getLink()->getUrl());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithLinkForExistingRoute(): void {
		$link = new stdClass();
		$link->url = new stdClass();
		$link->url->slug = 'dummy-slug';
		$this->menuEntry->link = $link;
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->assertSame('/xy/dummy-slug/', $menuItem->getLink()->getUrl());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithLinkForNonExistingRoute(): void {
		$link = new stdClass();
		$link->url = new stdClass();
		$link->url->controller = 'Dummy';
		$link->url->action = 'view';
		$this->menuEntry->link = $link;
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertInstanceOf(MenuItemLink::class, $menuItem->getLink());
		$this->expectException(MissingRouteException::class);
		$menuItem->getLink()->getUrl();
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithChildren(): void {
		$childEntry = new MenuEntry([
			'id' => 2,
			'active' => true,
			'title' => 'Child Menu Item',
		]);

		$this->menuEntry->children = [$childEntry];

		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		$this->assertInstanceOf(Menu::class, $menuItem->getChildren());
		$this->assertTrue($menuItem->hasChildren());

		// Check that child is properly created and accessible
		$children = iterator_to_array($menuItem->children(), false);
		$this->assertInstanceOf(FrontendMenuItem::class, reset($children));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::__construct()
	 * @throws \ReflectionException
	 */
	public function testPublicationDates(): void {
		// Test with future publication start date
		$this->menuEntry->publicationStart = new DateTime()->modify('+1 day');
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertFalse($menuItem->getActive());

		// Test with past publication end date
		$this->menuEntry->publicationStart = null;
		$this->menuEntry->publicationEnd = new DateTime()->modify('-1 day');
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertFalse($menuItem->getActive());

		// Test with valid publication dates
		$this->menuEntry->publicationStart = new DateTime()->modify('-1 day');
		$this->menuEntry->publicationEnd = new DateTime()->modify('+1 day');
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertTrue($menuItem->getActive());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::setAccessible()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::isAccessible()
	 * @throws \ReflectionException
	 */
	public function testIsAccessibleWithoutAccess(): void {
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		// Default behavior - should return true
		$this->assertTrue($menuItem->isAccessible());

		// Explicitly set false
		$menuItem->setAccessible(false);
		$this->assertFalse($menuItem->isAccessible());

		// Explicitly set true
		$menuItem->setAccessible(true);
		$this->assertTrue($menuItem->isAccessible());

		// Explicitly set null
		$menuItem->setAccessible(null);
		$this->assertTrue($menuItem->isAccessible()); // Default is true for frontend items
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::setAccessible()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::isAccessible()
	 * @throws \ReflectionException
	 */
	public function testIsAccessibleWithAccess(): void {
		// Create a menu entry with customer group access control
		$menuEntryWithAccess = new MenuEntry([
			'id' => 1,
			'active' => true,
			'title' => 'Test Item',
		]);

		$menuEntryWithAccess->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		$menuEntryWithAccess->customerGroupAssignments = [
			new CustomerGroupAssignment([
				'customerGroupId' => 1,
			]),
		];

		// Create customer with the required group
		$customerGroup = new CustomerGroup(['id' => 1, 'name' => 'Test Group']);
		$customer = $this->createMock(Customer::class);
		$customer->method('getGroups')->willReturn([$customerGroup]);

		$menuItem = new FrontendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($customer);

		// With identity that has access - should return true
		$this->assertTrue($menuItem->isAccessible());

		// Explicitly set false
		$menuItem->setAccessible(false);
		$this->assertFalse($menuItem->isAccessible());

		// Explicitly set true
		$menuItem->setAccessible(true);
		$this->assertTrue($menuItem->isAccessible());

		// Explicitly set null - should use the identity-based calculation (true)
		$menuItem->setAccessible(null);
		$this->assertTrue($menuItem->isAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::setVisible()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::isVisible()
	 * @throws \ReflectionException
	 */
	public function testIsVisibleWithoutAccess(): void {
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		// Default behavior - should return true
		$this->assertTrue($menuItem->isVisible());

		// Explicitly set false
		$menuItem->setVisible(false);
		$this->assertFalse($menuItem->isVisible());

		// Explicitly set true
		$menuItem->setVisible(true);
		$this->assertTrue($menuItem->isVisible());

		// Explicitly set null
		$menuItem->setVisible(null);
		$this->assertTrue($menuItem->isVisible()); // Default is true for frontend items
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::setVisible()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::isVisible()
	 * @throws \ReflectionException
	 */
	public function testIsVisibleWithAccess(): void {
		// Create a menu entry with customer group access control
		$menuEntryWithAccess = new MenuEntry([
			'id' => 2,
			'active' => true,
			'title' => 'Test Item',
		]);

		$menuEntryWithAccess->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		$menuEntryWithAccess->customerGroupAssignments = [
			new CustomerGroupAssignment([
				'customerGroupId' => 1,
			]),
		];

		// Create customer with the required group
		$customerGroup = new CustomerGroup(['id' => 1, 'name' => 'Test Group']);
		$customer = $this->createMock(Customer::class);
		$customer->method('getGroups')->willReturn([$customerGroup]);

		$menuItem = new FrontendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($customer);

		// With identity that has access - should return true
		$this->assertTrue($menuItem->isVisible());

		// Explicitly set false
		$menuItem->setVisible(false);
		$this->assertFalse($menuItem->isVisible());

		// Explicitly set true
		$menuItem->setVisible(true);
		$this->assertTrue($menuItem->isVisible());

		// Explicitly set null - should use the identity-based calculation (true)
		$menuItem->setVisible(null);
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::getLabel()
	 * @throws \ReflectionException
	 */
	public function testGetLabel(): void {
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertSame('Test Menu Item', $menuItem->getLabel());

		// Test with inactive item
		$this->menuEntry->active = false;
		$inactiveMenuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertStringContainsString('inactive', $inactiveMenuItem->getLabel());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::isCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testIsCurrentRoute(): void {
		// Set up a link
		$link = new stdClass();
		$link->url = '/test-url';
		$this->menuEntry->link = $link;

		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		// Test non-matching route
		$this->assertFalse($menuItem->isCurrentRoute('/other-url'));
		$this->assertFalse($menuItem->isCurrentRoute('/other-url/'));

		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		// Test matching route
		$this->assertTrue($menuItem->isCurrentRoute('/test-url'));
		$this->assertTrue($menuItem->isCurrentRoute('/test-url/'));

		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		// Test parameterized route
		$this->assertTrue($menuItem->isCurrentRoute('/test-url/id:5/'));
		$this->assertTrue($menuItem->isCurrentRoute('/test-url/id:5'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::getIdentifier()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::getLevel()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::__get()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::offsetExists()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::offsetGet()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::offsetSet()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::offsetUnset()
	 * @throws \ReflectionException
	 */
	public function testGettersAndArrayAccess(): void {
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		// Test getters
		$this->assertSame(1, $menuItem->getIdentifier());
		$this->assertSame(1, $menuItem->getLevel());

		// Test ArrayAccess
		$this->assertTrue(isset($menuItem['title']));
		$this->assertSame('Test Menu Item', $menuItem['title']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::offsetSet()
	 * @throws \ReflectionException
	 */
	public function testOffsetSetIsDisabled(): void {
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		// Test exceptions
		$this->expectException(RuntimeException::class);
		$menuItem['title'] = 'New Title';
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::offsetUnset()
	 * @throws \ReflectionException
	 */
	public function testOffsetUnsetIsDisabled(): void {
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		// Test exceptions
		$this->expectException(RuntimeException::class);
		unset($menuItem['title']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::getTitle()
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::convertTitle()
	 * @throws \ReflectionException
	 */
	public function testObjectTitleTranslation(): void {
		$titleObj = new stdClass();
		$titleObj->translate = ['Pages', 'headline_overview'];

		$this->menuEntry->title = $titleObj;

		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		$this->assertSame('Pages::headline_overview', $menuItem->getTitle());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibility(): void {
		// Test with link - should be visible
		$this->menuEntry->link = '/test-url';
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertTrue($menuItem->determineVisibility(true));
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityWithAccess(): void {
		// Test with customer group access control
		$menuEntryWithAccess = new MenuEntry([
			'id' => 3,
			'active' => true,
			'title' => 'Test Item',
		]);

		$menuEntryWithAccess->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		$menuEntryWithAccess->customerGroupAssignments = [
			new CustomerGroupAssignment([
				'customerGroupId' => 1,
			]),
		];

		// Create customer with the required group
		$customerGroup = new CustomerGroup(['id' => 1, 'name' => 'Test Group']);
		$customer = $this->createMock(Customer::class);
		$customer->method('getGroups')->willReturn([$customerGroup]);

		$menuItem = new FrontendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($customer);

		// Should be visible because customer has the required group
		$this->assertTrue($menuItem->determineVisibility(true));
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityNoLink(): void {
		// Item with no link should be visible unless explicitly set otherwise
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);
		$this->assertTrue($menuItem->determineVisibility(true));
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityWithLinkAndAccess(): void {
		// Create menu item with link and customer group access control
		$menuEntryWithAccess = new MenuEntry([
			'id' => 4,
			'active' => true,
			'title' => 'Test Item',
			'link' => '/test-url',
		]);

		$menuEntryWithAccess->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		$menuEntryWithAccess->customerGroupAssignments = [
			new CustomerGroupAssignment([
				'customerGroupId' => 1,
			]),
		];

		// Create customer with the required group
		$customerGroup = new CustomerGroup(['id' => 1, 'name' => 'Test Group']);
		$customer = $this->createMock(Customer::class);
		$customer->method('getGroups')->willReturn([$customerGroup]);

		$menuItem = new FrontendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($customer);

		// Should be visible because customer has the required group
		$this->assertTrue($menuItem->determineVisibility(true));
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityWithIdentityAllowed(): void {
		// Create a customer identity with groups
		$customerGroup = new CustomerGroup(['id' => 1, 'name' => 'Test Group']);
		$customer = $this->createMock(Customer::class);
		$customer->method('getGroups')->willReturn([$customerGroup]);

		// Create menu entry with customer group access settings
		$menuEntryWithAccess = new MenuEntry([
			'id' => 5,
			'active' => true,
			'title' => 'Protected Item',
			'link' => '/protected',
		]);

		// Set up customer group access settings - accessible to specific groups
		$menuEntryWithAccess->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		// Assign the customer group that the identity belongs to
		$menuEntryWithAccess->customerGroupAssignments = [
			new CustomerGroupAssignment([
				'customerGroupId' => 1,
			]),
		];

		$menuItem = new FrontendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($customer);

		$this->assertTrue($menuItem->determineVisibility());
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityWithIdentityDenied(): void {
		// Create a customer identity with a different group
		$customerGroup = new CustomerGroup(['id' => 2, 'name' => 'Other Group']);
		$customer = $this->createMock(Customer::class);
		$customer->method('getGroups')->willReturn([$customerGroup]);

		// Create menu entry with customer group access settings
		$menuEntryWithAccess = new MenuEntry([
			'id' => 6,
			'active' => true,
			'title' => 'Protected Item',
			'link' => '/protected',
		]);

		// Set up customer group access settings - accessible only to group 1
		$menuEntryWithAccess->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		// Assign customer group 1, but identity has group 2
		$menuEntryWithAccess->customerGroupAssignments = [
			new CustomerGroupAssignment([
				'customerGroupId' => 1,
			]),
		];

		$menuItem = new FrontendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($customer);

		$this->assertFalse($menuItem->determineVisibility());
		$this->assertFalse($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityParentWithAccessibleChild(): void {
		// Create a customer identity with group 1
		$customerGroup = new CustomerGroup(['id' => 1, 'name' => 'Test Group']);
		$customer = $this->createMock(Customer::class);
		$customer->method('getGroups')->willReturn([$customerGroup]);

		// Child with customer group access control
		$childEntry = new MenuEntry([
			'id' => 7,
			'active' => true,
			'title' => 'Child Item',
			'link' => '/child',
		]);

		$childEntry->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		$childEntry->customerGroupAssignments = [
			new CustomerGroupAssignment([
				'customerGroupId' => 1,
			]),
		];

		// Parent without link
		$parentEntry = new MenuEntry([
			'id' => 8,
			'active' => true,
			'title' => 'Parent Item',
			'children' => [$childEntry],
		]);

		$parentItem = new FrontendMenuItem($parentEntry, $this->menuConfig);
		$parentItem->setIdentity($customer);

		// Parent should be visible because child is visible
		$this->assertTrue($parentItem->determineVisibility());
		$this->assertTrue($parentItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityParentWithInaccessibleChild(): void {
		// Create a customer identity with group 2
		$customerGroup = new CustomerGroup(['id' => 2, 'name' => 'Other Group']);
		$customer = $this->createMock(Customer::class);
		$customer->method('getGroups')->willReturn([$customerGroup]);

		// Child with customer group access control - requires group 1
		$childEntry = new MenuEntry([
			'id' => 9,
			'active' => true,
			'title' => 'Child Item',
			'link' => '/child',
		]);

		$childEntry->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		$childEntry->customerGroupAssignments = [
			new CustomerGroupAssignment([
				'customerGroupId' => 1,
			]),
		];

		// Parent without link
		$parentEntry = new MenuEntry([
			'id' => 10,
			'active' => true,
			'title' => 'Parent Item',
			'children' => [$childEntry],
		]);

		$parentItem = new FrontendMenuItem($parentEntry, $this->menuConfig);
		$parentItem->setIdentity($customer);

		// Parent should be invisible because child is invisible and parent has no link
		$this->assertFalse($parentItem->determineVisibility());
		$this->assertFalse($parentItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityReset(): void {
		$this->menuEntry->link = '/test-url';
		$menuItem = new FrontendMenuItem($this->menuEntry, $this->menuConfig);

		// Set visible to false explicitly
		$menuItem->setVisible(false);

		// Without reset, should keep current value
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->assertFalse($menuItem->determineVisibility(false));
		$this->assertFalse($menuItem->isVisible());

		// With reset, should recalculate (true because item has a link)
		$this->assertTrue($menuItem->determineVisibility(true));
		$this->assertTrue($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityWithMixedChildrenVisibility(): void {
		// Create parent with multiple children having different visibility
		$visibleChild = new MenuEntry([
			'id' => 20,
			'active' => true,
			'title' => 'Visible Child',
			'link' => '/visible-child',
		]);

		$invisibleChild = new MenuEntry([
			'id' => 21,
			'active' => true,
			'title' => 'Invisible Child',
			'link' => '/invisible-child',
		]);

		$parentEntry = new MenuEntry([
			'id' => 22,
			'active' => true,
			'title' => 'Parent Item',
			'children' => [$visibleChild, $invisibleChild],
		]);

		$parentItem = new FrontendMenuItem($parentEntry, $this->menuConfig);
		$parentItem->getChildren()->getItem(21)->setAccessible(false);

		// Parent should be visible because at least one child is visible
		$this->assertTrue($parentItem->determineVisibility());
		$this->assertTrue($parentItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityWithDeepNesting(): void {
		// Create a deeply nested structure: grandparent -> parent -> child
		$childEntry = new MenuEntry([
			'id' => 30,
			'active' => true,
			'title' => 'Child Item',
			'link' => '/child',
		]);

		$parentEntry = new MenuEntry([
			'id' => 31,
			'active' => true,
			'title' => 'Parent Item',
			'children' => [$childEntry],
		]);

		$grandparentEntry = new MenuEntry([
			'id' => 32,
			'active' => true,
			'title' => 'Grandparent Item',
			'children' => [$parentEntry],
		]);

		$grandparentItem = new FrontendMenuItem($grandparentEntry, $this->menuConfig);

		// Grandparent should be visible
		$this->assertTrue($grandparentItem->determineVisibility());
		$this->assertTrue($grandparentItem->isVisible());

		// Make the deepest child invisible
		$parentItem = $grandparentItem->getChildren()->getItem(31);
		$childItem = $parentItem->getChildren()->getItem(30);
		$childItem->setAccessible(false);

		// Recalculate visibility - entire chain should be invisible
		$this->assertFalse($grandparentItem->determineVisibility(true));
		$this->assertFalse($grandparentItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::determineVisibility()
	 * @throws \ReflectionException
	 */
	public function testDetermineVisibilityComplexHierarchy(): void {
		// Create a complex hierarchy with multiple branches
		$leaf1 = new MenuEntry([
			'id' => 41,
			'active' => true,
			'title' => 'Leaf 1',
			'link' => '/leaf1',
		]);

		$leaf2 = new MenuEntry([
			'id' => 42,
			'active' => true,
			'title' => 'Leaf 2',
			'link' => '/leaf2',
		]);

		$leaf3 = new MenuEntry([
			'id' => 43,
			'active' => true,
			'title' => 'Leaf 3',
			'link' => '/leaf3',
		]);

		$leaf4 = new MenuEntry([
			'id' => 44,
			'active' => true,
			'title' => 'Leaf 4',
			'link' => '/leaf4',
		]);

		$branch1 = new MenuEntry([
			'id' => 31,
			'active' => true,
			'title' => 'Branch 1',
			'children' => [$leaf1, $leaf2],
		]);

		$branch2 = new MenuEntry([
			'id' => 32,
			'active' => true,
			'title' => 'Branch 2',
			'children' => [$leaf3, $leaf4],
		]);

		$root = new MenuEntry([
			'id' => 20,
			'active' => true,
			'title' => 'Root Item',
			'children' => [$branch1, $branch2],
		]);

		$rootItem = new FrontendMenuItem($root, $this->menuConfig);

		// Root should be visible because both branches have visible leaves
		$this->assertTrue($rootItem->determineVisibility());
		$this->assertTrue($rootItem->isVisible());

		// Root should still be visible because branch2 has a visible leaf
		$this->assertTrue($rootItem->determineVisibility(true));
		$this->assertTrue($rootItem->isVisible());

		// Make one branch invisible by making its leaf inaccessible
		$branch1Item = $rootItem->getChildren()->getItem(31);
		$leaf1Item = $branch1Item->getChildren()->getItem(41);
		$leaf1Item->setAccessible(false);

		// Root should still be visible because branch2 has a visible leaf
		$this->assertTrue($rootItem->determineVisibility(true));
		$this->assertTrue($rootItem->isVisible());

		// Make all leaves invisible
		$leaf3Item = $rootItem->getChildren()->getItem(32)->getChildren()->getItem(43);
		$leaf3Item->setAccessible(false);
		$leaf4Item = $rootItem->getChildren()->getItem(32)->getChildren()->getItem(44);
		$leaf4Item->setAccessible(false);

		// Root should still be visible
		$this->assertTrue($rootItem->determineVisibility(true));
		$this->assertTrue($rootItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::setIdentity()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testSetIdentityResetsVisibility(): void {
		// Create a menu entry with customer group access settings
		$menuEntryWithAccess = new MenuEntry([
			'id' => 50,
			'active' => true,
			'title' => 'Protected Item',
			'link' => '/protected',
		]);

		$menuEntryWithAccess->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		$menuEntryWithAccess->customerGroupAssignments = [
			new CustomerGroupAssignment([
				'customerGroupId' => 1,
			]),
		];

		// Create identity with group 1 that grants access
		$customerGroup1 = new CustomerGroup(['id' => 1, 'name' => 'Group 1']);
		$identity = $this->createMock(Customer::class);
		$identity->method('getGroups')->willReturn([$customerGroup1]);

		$menuItem = new FrontendMenuItem($menuEntryWithAccess, $this->menuConfig);
		$menuItem->setIdentity($identity);

		// Initially should be visible
		$this->assertTrue($menuItem->isVisible());

		// Change identity to one with group 2 that denies access
		$customerGroup2 = new CustomerGroup(['id' => 2, 'name' => 'Group 2']);
		$identityDenied = $this->createMock(Customer::class);
		$identityDenied->method('getGroups')->willReturn([$customerGroup2]);

		$menuItem->setIdentity($identityDenied);

		// Should now be invisible
		$this->assertFalse($menuItem->isVisible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::hasCurrentRoute()
	 * @throws \ReflectionException
	 */
	public function testHasCurrentRoute(): void {
		// Create parent with a child that matches a route
		$childEntry = new MenuEntry([
			'id' => 50,
			'active' => true,
			'title' => 'Child Item',
			'link' => '/route/subroute',
		]);

		$parentEntry = new MenuEntry([
			'id' => 51,
			'active' => true,
			'title' => 'Parent Item',
			'children' => [$childEntry],
			'link' => '/route',
		]);

		$parentItem = new FrontendMenuItem($parentEntry, $this->menuConfig);

		// Parent should have the current route because its child has it
		$this->assertTrue($parentItem->hasCurrentRoute('/route/subroute'));

		// Create parent with no children matching the route
		$noMatchEntry = new MenuEntry([
			'id' => 52,
			'active' => true,
			'title' => 'No Match Item',
			'link' => '/other-route',
			'children' => [],
		]);

		$noMatchItem = new FrontendMenuItem($noMatchEntry, $this->menuConfig);
		$this->assertFalse($noMatchItem->hasCurrentRoute('/route/subroute'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::setChildren()
	 * @throws \ReflectionException
	 */
	public function testSetChildren(): void {
		// Create parent with multiple children
		$child1 = new MenuEntry([
			'id' => 60,
			'active' => true,
			'title' => 'Child 1',
			'link' => '/child1',
		]);

		$child2 = new MenuEntry([
			'id' => 61,
			'active' => true,
			'title' => 'Child 2',
			'link' => '/child2',
		]);

		$parent = new MenuEntry([
			'id' => 62,
			'active' => true,
			'title' => 'Parent',
		]);

		$parentItem = new FrontendMenuItem($parent, $this->menuConfig);

		// Set children manually
		$parentItem->setChildren([$child1, $child2]);

		// Verify children were set correctly
		$this->assertTrue($parentItem->hasChildren());
		$this->assertInstanceOf(Menu::class, $parentItem->getChildren());
		$this->assertCount(2, iterator_to_array($parentItem->children(), false));
		$this->assertSame(60, $parentItem->getChildren()->getItem(60)->getIdentifier());
		$this->assertSame(61, $parentItem->getChildren()->getItem(61)->getIdentifier());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\FrontendMenuItem::getLevel()
	 * @throws \ReflectionException
	 */
	public function testLevelPropagation(): void {
		// Create a deeply nested structure: root -> parent -> child -> grandchild
		$grandchildEntry = new MenuEntry([
			'id' => 70,
			'active' => true,
			'title' => 'Grandchild',
			'link' => '/grandchild',
		]);

		$childEntry = new MenuEntry([
			'id' => 71,
			'active' => true,
			'title' => 'Child',
			'link' => '/child',
			'children' => [$grandchildEntry],
		]);

		$parentEntry = new MenuEntry([
			'id' => 72,
			'active' => true,
			'title' => 'Parent',
			'link' => '/parent',
			'children' => [$childEntry],
		]);

		$rootEntry = new MenuEntry([
			'id' => 73,
			'active' => true,
			'title' => 'Root',
			'children' => [$parentEntry],
		]);

		// Initialize with level 1
		$rootItem = new FrontendMenuItem($rootEntry, $this->menuConfig);

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
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testMenuClassAndMenuItemClass(): void {
		$childEntry = new MenuEntry([
			'id' => 71,
			'active' => true,
			'title' => 'Child',
			'link' => '/child',
		]);

		$parentEntry = new MenuEntry([
			'id' => 72,
			'active' => true,
			'title' => 'Parent',
			'link' => '/parent',
			'children' => [$childEntry],
		]);

		$rootEntry = new MenuEntry([
			'id' => 73,
			'active' => true,
			'title' => 'Root',
			'children' => [$parentEntry],
		]);

		// Initialize with level 1
		$rootItem = new FrontendMenuItem($rootEntry, [
			'menuClass' => CustomFrontendMenu::class,
			'menuItemClass' => CustomFrontendMenuItem::class,
		]);

		// Verify menu class
		$this->assertInstanceOf(CustomFrontendMenu::class, $rootItem->getChildren());
		$this->assertInstanceOf(CustomFrontendMenuItem::class, $rootItem->getChildren()->getItem(72));
		$this->assertInstanceOf(CustomFrontendMenuItem::class, $rootItem->getChildren()->getItem(72)->getChildren()->getItem(71));
	}
}
