<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\CallableMock;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\BackendMenu;
use Awyiss\Utility\Menu\BackendMenuItem;
use Awyiss\Utility\Menu\MenuLoader;
use Awyiss\Utility\Menu\MenuRenderer;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Symfony\Component\Process\Process;


/**
 * Test case for MenuRenderer class.
 *
 * @see \Awyiss\Utility\Menu\MenuRenderer()
 */
class MenuRendererTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var IdentityPermissionsInterface
	 */
	protected IdentityPermissionsInterface $identity;
	/**
	 * Test directory for temporary files
	 */
	protected string $testDir;


	/**
	 * Setup method to create test directory
	 *
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a temporary directory for test files
		$this->testDir = TMP . 'menu_renderer_tests' . DS;
		if (!is_dir($this->testDir)) {
			mkdir($this->testDir);
		}

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
		Awyiss::loadConfiguration('xy', 'yx');
		$this->loadRoutes();

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

		// Mock dependencies
		$this->identity = $this->createMock(IdentityPermissionsInterface::class);
	}


	/**
	 * Teardown method to clean up test files
	 */
	public function tearDown(): void {
		// Clean up test files
		if (is_dir($this->testDir)) {
			new Process(['rm', '-r', $this->testDir])->run();
		}

		parent::tearDown();
	}


	/**
	 * Test rendering an empty menu
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderEmptyMenu(): void {
		$menu = new BackendMenu([], [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu);
		$result = $renderer->render();

		$this->assertSame('', $result);
	}


	/**
	 * Test rendering a menu with items
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderMenuWithItems(): void {
		$data = json_decode(json_encode([
			'item1' => ['title' => 'Item 1', 'link' => 'https://example.com'],
			'item2' => ['title' => 'Item 2', 'link' => 'https://example.org'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu);
		$result = $renderer->render();

		$this->assertStringContainsString('<nav id="Menu-Default">', $result);
		$this->assertStringContainsString('<ul class="Level1 Menu-Default">', $result);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Item1">', $result);
		$this->assertStringContainsString('<a href="https://example.com" class="Level1 MenuItem-Item1">Item 1</a>', $result);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Item2">', $result);
		$this->assertStringContainsString('<a href="https://example.org" class="Level1 MenuItem-Item2">Item 2</a>', $result);
	}


	/**
	 * Test rendering a menu with nested items
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderMenuWithNestedItems(): void {
		$data = json_decode(json_encode([
			'item1' => [
				'title' => 'Item 1',
				'link' => 'https://example.com',
				'children' => [
					'subitem1' => ['title' => 'Subitem 1', 'link' => 'https://example.com/subitem1'],
					'subitem2' => ['title' => 'Subitem 2', 'link' => 'https://example.com/subitem2'],
				],
			],
			'item2' => ['title' => 'Item 2', 'link' => 'https://example.org'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu);
		$result = $renderer->render();

		$this->assertStringContainsString('<nav id="Menu-Default">', $result);
		$this->assertStringContainsString('<ul class="Level1 Menu-Default">', $result);
		$this->assertStringContainsString('<li class="Level1 HasSubmenu MenuItem-Item1">', $result);
		$this->assertStringContainsString('<a href="https://example.com" class="Level1 MenuItem-Item1">Item 1</a>', $result);
		$this->assertStringContainsString('<ul class="Level2">', $result);
		$this->assertStringContainsString('<li class="Level2 MenuItem-Subitem1">', $result);
		$this->assertStringContainsString('<a href="https://example.com/subitem1" class="Level2 MenuItem-Subitem1">Subitem 1</a>', $result);
		$this->assertStringContainsString('<li class="Level2 MenuItem-Subitem2">', $result);
		$this->assertStringContainsString('<a href="https://example.com/subitem2" class="Level2 MenuItem-Subitem2">Subitem 2</a>', $result);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Item2">', $result);
		$this->assertStringContainsString('<a href="https://example.org" class="Level1 MenuItem-Item2">Item 2</a>', $result);
	}


	/**
	 * Test rendering a menu with inaccessible items
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderMenuWithInaccessibleItems(): void {
		$this->identity->method('scopeIsAccessible')->willReturnCallback(function ($scope, $data, $identifier) {
			// Make 'item2' inaccessible
			return $identifier !== 'item2';
		});

		$data = json_decode(json_encode([
			'item1' => ['title' => 'Item 1', 'link' => 'https://example.com'],
			'item2' => [
				'title' => 'Item 2',
				'link' => 'https://example.org',
				'access' => [
					'scope' => 'Test',
					'identifier' => 'item2',
				],
			],
			'item3' => ['title' => 'Item 3', 'link' => 'https://example.net'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu);
		$result = $renderer->render();

		$this->assertIsString($result);
		$this->assertStringContainsString('<nav id="Menu-Default">', $result);

		$this->assertStringContainsString('<li class="Level1 MenuItem-Item1">', $result);
		$this->assertStringNotContainsString('<li class="Level1 MenuItem-Item2">', $result);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Item3">', $result);
	}


	/**
	 * Test rendering with custom CSS classes
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderWithCustomClasses(): void {
		$data = json_decode(json_encode([
			'item1' => ['title' => 'Item 1', 'link' => 'https://example.com'],
			'item2' => ['title' => 'Item 2', 'link' => 'https://example.org'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		/** @noinspection HtmlUnknownAttribute */
		/** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
		$renderer = new MenuRenderer($menu, [
			'templates' => [
				'menu' => '<nav id="CustomMenu-{{identifier}}">' . PHP_EOL . '{{list}}</nav>' . PHP_EOL,
				'list' => '<ul class="Level{{level}} Custom{{identifier}}">' . PHP_EOL . '{{content}}</ul>' . PHP_EOL,
				'item' => '<li class="Level{{level}}{{active}}{{hasSubmenu}} CustomMenuItem-{{identifier}}">' . PHP_EOL . '{{link}}{{children}}</li>' . PHP_EOL,
				'link' => '<a href="{{url}}" class="Level{{level}}{{active}} CustomMenuLink-{{identifier}}"{{attributes}}>{{title}}</a>' . PHP_EOL,
				'noLink' => '<span class="Level{{level}}{{active}}">{{title}}</span>' . PHP_EOL,
			],
		]);

		$result = $renderer->render();

		$this->assertStringContainsString('<nav id="CustomMenu-Default">', $result);
		$this->assertStringContainsString('<ul class="Level1 Custom Menu-Default">', $result);
		$this->assertStringContainsString('<li class="Level1 CustomMenuItem-Item1">', $result);
		$this->assertStringContainsString('<a href="https://example.com" class="Level1 CustomMenuLink-Item1">Item 1</a>', $result);
	}


	/**
	 * Test current route highlighting
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderWithCurrentRoute(): void {
		$data = json_decode(json_encode([
			'item1' => ['title' => 'Item 1', 'link' => 'https://example.com'],
			'item2' => ['title' => 'Item 2', 'link' => 'https://example.org'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		// Set the current route to match item1
		$renderer = new MenuRenderer($menu);
		$renderer->setCurrentRoute('https://example.com');

		$result = $renderer->render();

		$this->assertStringContainsString('class="Level1 Active MenuItem-Item1"', $result);
		$this->assertStringNotContainsString('class="Level1 Active MenuItem-Item2"', $result);
	}


	/**
	 * Test current route highlighting
	 *
	 * @return void
	 * @see\Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderWithCurrentRouteInChildren(): void {
		$data = json_decode(json_encode([
			'item1' => [
				'title' => 'Item 1',
				'link' => 'https://example.com',
				'children' => [
					'subitem1' => ['title' => 'Subitem 1', 'link' => 'https://example.com/subitem1'],
					'subitem2' => ['title' => 'Subitem 2', 'link' => 'https://example.com/subitem2'],
				],
			],
			'item2' => ['title' => 'Item 2', 'link' => 'https://example.org'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		// Set the current route to match subitem1
		$renderer = new MenuRenderer($menu);
		$renderer->setCurrentRoute('https://example.com/subitem1');

		$result = $renderer->render();

		$this->assertStringContainsString('class="Level1 Active MenuItem-Item1"', $result);
		$this->assertStringContainsString('class="Level2 Active MenuItem-Subitem1"', $result);
		$this->assertStringContainsString('class="Level2 MenuItem-Subitem2"', $result);
		$this->assertStringContainsString('class="Level1 MenuItem-Item2"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::optimizeUrl()
	 */
	public function testRenderLinkAttributes(): void {
		$data = json_decode(json_encode([
			'item1' => ['title' => 'Item 1', 'link' => (object)['url' => 'https://example.com', 'target' => '_blank']],
			'item2' => ['title' => 'Item 2', 'link' => 'https://example.org'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu);
		$result = $renderer->render();

		$this->assertStringContainsString('<a href="https://example.com" class="Level1 MenuItem-Item1" target="_blank">Item 1</a>', $result);
	}


	/**
	 * Test that links to the homepage are rendered as `/`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::optimizeUrl()
	 */
	public function testRenderHomepageLink(): void {
		$request = new ServerRequest([
			'url' => '/',
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

		$data = json_decode(json_encode([
			'item1' => ['title' => 'Home', 'link' => '/backend/xy/dummy/view'],
			'item2' => ['title' => 'About', 'link' => '/backend/xy/dummy/add'],
			'item3' => ['title' => 'Contact', 'link' => '/backend/xy/dummy/edit/id:3'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu);
		$renderer->setCurrentRoute('/backend/xy/dummy/view');

		$result = $renderer->render();

		$this->assertStringContainsString('<li class="Level1 Active MenuItem-Item1">', $result);
		$this->assertStringContainsString('<a href="http://localhost/" class="Level1 Active MenuItem-Item1">Home</a>', $result);

		$this->assertStringContainsString('<li class="Level1 MenuItem-Item2">', $result);
		$this->assertStringContainsString('<a href="/backend/xy/dummy/add/" class="Level1 MenuItem-Item2">About</a>', $result);
	}


	/**
	 * Test that links to the homepage are rendered as `/`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::optimizeUrl()
	 */
	public function testRenderNonHomepageLink(): void {
		$request = new ServerRequest([
			'url' => '/backend/xy/dummy/view',
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

		$data = json_decode(json_encode([
			'item1' => ['title' => 'Home', 'link' => '/backend/xy/dummy/view'],
			'item2' => ['title' => 'About', 'link' => '/backend/xy/dummy/add'],
			'item3' => ['title' => 'Contact', 'link' => '/backend/xy/dummy/edit/id:3'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu);
		$renderer->setCurrentRoute('/backend/xy/dummy/view');

		$result = $renderer->render();

		$this->assertStringContainsString('<li class="Level1 Active MenuItem-Item1">', $result);
		$this->assertStringContainsString('<a href="/backend/xy/dummy/view/" class="Level1 Active MenuItem-Item1">Home</a>', $result);

		$this->assertStringContainsString('<li class="Level1 MenuItem-Item2">', $result);
		$this->assertStringContainsString('<a href="/backend/xy/dummy/add/" class="Level1 MenuItem-Item2">About</a>', $result);
	}


	/**
	 * Test that title is escaped correctly in rendered menu items
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::optimizeUrl()
	 */
	public function testRenderEscapesTitle(): void {
		$data = json_decode(json_encode([
			'item1' => ['title' => 'Home <script>alert("XSS")</script>', 'link' => '/backend/xy/dummy/view'],
			'item2' => ['title' => 'About', 'link' => '/backend/xy/dummy/add'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu, [
			'escapeTitle' => true,
		]);
		$renderer->setCurrentRoute('/backend/xy/dummy/view');

		$result = $renderer->render();

		$this->assertStringContainsString('<li class="Level1 Active MenuItem-Item1">', $result);
		$this->assertStringContainsString('<a href="/backend/xy/dummy/view/" class="Level1 Active MenuItem-Item1">Home &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</a>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderActiveOnly(): void {
		$data = json_decode(json_encode([
			'item1' => ['title' => 'Item 1', 'link' => 'https://example.com'],
			'item2' => ['title' => 'Item 2', 'link' => 'https://example.org', 'active' => false],
			'item3' => ['title' => 'Item 2', 'link' => 'https://example.org'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu, [
			'activeOnly' => true,
		]);

		$result = $renderer->render();

		$this->assertStringContainsString('<li class="Level1 MenuItem-Item1">', $result);
		$this->assertStringNotContainsString('<li class="Level1 MenuItem-Item2">', $result);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Item3">', $result);

		$renderer = new MenuRenderer($menu, [
			'activeOnly' => false,
		]);

		$result = $renderer->render();

		$this->assertStringContainsString('<li class="Level1 MenuItem-Item1">', $result);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Item2">', $result);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Item3">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderMaxLevel(): void {
		$data = json_decode(json_encode([
			'item1' => [
				'title' => 'Item 1',
				'link' => 'https://example.com',
				'children' => [
					'subitem1' => ['title' => 'Subitem 1', 'link' => 'https://example.com/subitem1'],
					'subitem2' => ['title' => 'Subitem 2', 'link' => 'https://example.com/subitem2'],
				],
			],
			'item2' => ['title' => 'Item 2', 'link' => 'https://example.org'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$renderer = new MenuRenderer($menu, [
			'maxLevel' => 1,
		]);

		$result = $renderer->render();

		$this->assertStringContainsString('<nav id="Menu-Default">', $result);
		$this->assertStringContainsString('<ul class="Level1 Menu-Default">', $result);
		// Ensure subitems are not rendered
		$this->assertStringNotContainsString('<ul class="Level2">', $result);

		$renderer = new MenuRenderer($menu, [
			'maxLevel' => 2,
		]);

		$result = $renderer->render();

		$this->assertStringContainsString('<nav id="Menu-Default">', $result);
		$this->assertStringContainsString('<ul class="Level1 Menu-Default">', $result);
		$this->assertStringContainsString('<ul class="Level2">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuRenderer::render()
	 */
	public function testRenderUsesFormatters(): void {
		$data = json_decode(json_encode([
			'item1' => ['title' => 'Item 1', 'link' => 'https://example.com'],
			'item2' => ['title' => 'Item 2', 'link' => 'https://example.org'],
			'item3' => ['title' => 'Item 3', 'link' => null],
		]));

		$menu = MenuLoader::fromObject($data, [
			'identity' => $this->identity,
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		// Create mock formatters that return predictable values
		$menuFormatter = $this->getMockBuilder(CallableMock::class)->getMock();
		$menuFormatter->expects($this->once())->method('__invoke')->willReturn('MENU_FORMATTED');

		$listFormatter = $this->getMockBuilder(CallableMock::class)->getMock();
		$listFormatter->expects($this->once())->method('__invoke')->willReturn('LIST_FORMATTED');

		$itemFormatter = $this->getMockBuilder(CallableMock::class)->getMock();
		$itemFormatter->expects($this->exactly(3))->method('__invoke')->willReturn('ITEM_FORMATTED');

		$linkFormatter = $this->getMockBuilder(CallableMock::class)->getMock();
		$linkFormatter->expects($this->exactly(2))->method('__invoke')->willReturn('LINK_FORMATTED');

		$noLinkFormatter = $this->getMockBuilder(CallableMock::class)->getMock();
		$noLinkFormatter->expects($this->once())->method('__invoke')->willReturn('NO_LINK_FORMATTED');

		$renderer = new MenuRenderer($menu, [
			'formatters' => [
				'menu' => $menuFormatter,
				'list' => $listFormatter,
				'item' => $itemFormatter,
				'link' => $linkFormatter,
				'noLink' => $noLinkFormatter,
			],
		]);

		$result = $renderer->render();

		$this->assertStringContainsString('MENU_FORMATTED', $result);
	}
}
