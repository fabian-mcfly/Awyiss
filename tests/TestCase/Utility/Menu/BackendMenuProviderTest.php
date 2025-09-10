<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Menu;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\BackendMenu;
use Awyiss\Utility\Menu\BackendMenuItem;
use Awyiss\Utility\Menu\BackendMenuProvider;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * Test case for BackendMenuProvider class.
 *
 * @see \Awyiss\Utility\Menu\BackendMenuProvider()
 */
class BackendMenuProviderTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\Authorization\IdentityPermissionsInterface
	 */
	protected IdentityPermissionsInterface $identity;
	/**
	 * @var \Awyiss\Utility\Menu\BackendMenuProvider
	 */
	protected BackendMenuProvider $menuProvider;
	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected ServerRequest $request;
	/**
	 * @var \Cake\Http\Response
	 */
	protected Response $response;


	/**
	 * Setup for each test
	 *
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		$this->loadRoutes();

		$this->request = (new ServerRequest([
			'url' => '/backend/xy/dummy/overview/',
			'params' => [
				'lang' => 'xy',
				'controller' => 'dummy',
				'action' => 'overview',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]))->withAttribute('authorization', new AuthorizationService('Backend'));

		Router::setRequest($this->request);

		$this->response = $this->createMock(Response::class);

		// Mock dependencies
		$this->identity = $this->createMock(IdentityPermissionsInterface::class);
		$this->menuProvider = new BackendMenuProvider($this->identity);
	}


	/**
	 * Test that the menu is created correctly
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuProvider::__construct()
	 * @see \Awyiss\Utility\Menu\BackendMenuProvider::createMenu()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateMenu(): void {
		$menu = $this->menuProvider->getMenu();
		$this->assertInstanceOf(BackendMenu::class, $menu);
		$this->assertNotEmpty($menu->getItems());

		$this->assertCount(5, $menu->getItems());
		$this->assertSame([
			'dashboard',
			'pages',
			'media',
			'components',
			'system',
		], array_keys($menu->getItems()));

		// There should also be a dummy_entry1 entry under 'media'
		$mediaItem = $menu->getItem('media');
		$this->assertInstanceOf(BackendMenuItem::class, $mediaItem);
		$this->assertSame([
			'media_folders',
			'media_elements',
			'media_configure',
		], array_keys($mediaItem->getChildren()->getItems()));
	}


	/**
	 * Test that the custom menu is created correctly
	 * It must load the menu.json in the customer config directory.
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuProvider::__construct()
	 * @see \Awyiss\Utility\Menu\BackendMenuProvider::createCustomMenu()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateCustomMenu(): void {
		$customMenu = $this->menuProvider->getCustomMenu();
		$this->assertInstanceOf(BackendMenu::class, $customMenu);
		$this->assertNotEmpty($customMenu->getItems());

		$this->assertCount(6, $customMenu->getItems());
		$this->assertSame([
			'dashboard',
			'pages',
			'media',
			'dummy_entry2',
			'components',
			'system',
		], array_keys($customMenu->getItems()));

		// There should also be a dummy_entry1 entry under 'media'
		$mediaItem = $customMenu->getItem('media');
		$this->assertInstanceOf(BackendMenuItem::class, $mediaItem);
		$this->assertTrue($mediaItem->getChildren()->hasItem('dummy_entry1'));

		// Media should also still have `media_folders`
		$this->assertTrue($mediaItem->getChildren()->hasItem('media_folders'));

		$this->assertSame([
			'dummy_entry1',
			'media_folders',
			'media_elements',
			'media_configure',
		], array_keys($mediaItem->getChildren()->getItems()));
	}


	/**
	 * Test that the dynamic menu is created correctly
	 * It must load additional menu items from the database
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\BackendMenuProvider::__construct()
	 * @see \Awyiss\Utility\Menu\BackendMenuProvider::createDynamicMenu()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateDynamicMenu(): void {
		$dynamicMenu = $this->menuProvider->getDynamicMenu();
		$this->assertInstanceOf(BackendMenu::class, $dynamicMenu);
		$this->assertNotEmpty($dynamicMenu->getItems());

		$this->assertCount(9, $dynamicMenu->getItems());
		$this->assertSame([
			'dashboard',
			'pages',
			'media',
			'dummy_entry2',
			3, // Backend menu entries from the db have their id as identifier
			4, // Backend menu entries from the db have their id as identifier
			5, // Backend menu entries from the db have their id as identifier
			'components',
			'system',
		], array_keys($dynamicMenu->getItems()));

		// Menu should have a `dummy_entry1` and `1` entries under 'media'
		$mediaItem = $dynamicMenu->getItem('media');
		$this->assertInstanceOf(BackendMenuItem::class, $mediaItem);
		$this->assertTrue($mediaItem->getChildren()->hasItem('dummy_entry1'));
		$this->assertTrue($mediaItem->getChildren()->hasItem(1));

		// Media should also still have `media_folders`
		$this->assertTrue($mediaItem->getChildren()->hasItem('media_folders'));

		$this->assertSame([
			1,
			'dummy_entry1',
			'media_folders',
			'media_elements',
			'media_configure',
		], array_keys($mediaItem->getChildren()->getItems()));

		// Entry `1` should have a child `2`
		$entry1 = $mediaItem->getChildren()->getItem(1);
		$this->assertInstanceOf(BackendMenuItem::class, $entry1);
		$this->assertTrue($entry1->getChildren()->hasItem(2));
	}
}
