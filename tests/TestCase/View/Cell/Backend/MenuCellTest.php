<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Backend;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\BackendMenuItem;
use Awyiss\View\BackendView;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\CellTrait;
use ReflectionClass;


/**
 * MenuCellTest class
 */
class MenuCellTest extends TestCase {
	use CellTrait;
	use IntegrationTestTrait;


	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected ServerRequest $request;
	/**
	 * @var \Cake\Http\Response
	 */
	protected Response $response;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		$this->loadRoutes();

		$this->request = new ServerRequest([
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
		])->withAttribute('authorization', new AuthorizationService('Backend'));

		Router::setRequest($this->request);

		$this->response = $this->createMock(Response::class);
	}


	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		$reflection = new ReflectionClass(BackendView::class);

		$property = $reflection->getProperty('twig');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, null);

		$reflection = new ReflectionClass(BackendMenuItem::class);

		$property = $reflection->getProperty('testUrl');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, null);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MenuCell::display()
	 */
	public function testDisplayWithAuthorizedUser(): void {
		$user = $this->login();
		$this->request = $this->request->withAttribute('BackendIdentity', $user);
		Router::setRequest($this->request);

		$output = (string)$this->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Dashboard">', $output);
		$this->assertStringContainsString('<a href="/backend/xy/dashboard/overview/" class="Level1 MenuItem-Dashboard">Dashboard::menu_title</a>', $output);

		$this->assertStringContainsString('<li class="Level1 MenuItem-System">', $output);
		$this->assertStringContainsString('<a href="/backend/xy/system/overview/" class="Level1 MenuItem-System">System::menu_title</a>', $output);
		$this->assertStringContainsString('<a href="/backend/xy/menus/overview/" class="Level2 MenuItem-Menus">Menus::menu_title</a>', $output);
		$this->assertStringContainsString('<ul class="Level2">', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MenuCell::display()
	 */
	public function testDisplayWithUnauthorizedUser(): void {
		$user = $this->login(2);
		$this->request = $this->request->withAttribute('BackendIdentity', $user);
		Router::setRequest($this->request);

		$output = (string)$this->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Dashboard">', $output);
		$this->assertStringContainsString('<a href="/backend/xy/dashboard/overview/" class="Level1 MenuItem-Dashboard">Dashboard::menu_title</a>', $output);

		$this->assertStringNotContainsString('<a href="/backend/xy/menus/overview/" class="Level2 MenuItem-Menus">Menus::menu_title</a>', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MenuCell::display()
	 */
	public function testDisplayWithAccessDeniedUser(): void {
		$user = $this->login(3);
		$this->request = $this->request->withAttribute('BackendIdentity', $user);
		Router::setRequest($this->request);

		$output = (string)$this->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Dashboard">', $output);
		$this->assertStringContainsString('<a href="/backend/xy/dashboard/overview/" class="Level1 MenuItem-Dashboard">Dashboard::menu_title</a>', $output);

		$this->assertStringNotContainsString('<a href="/backend/xy/menus/overview/" class="Level2 MenuItem-Menus">Menus::menu_title</a>', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MenuCell::display()
	 */
	public function testDisplaySavesMenuStructureToSession(): void {
		$session = $this->request->getSession();
		$session->delete('Backend.menu.de');

		$this->assertEmpty($session->read('Backend.menu.de'));

		$user = $this->login();
		$this->request = $this->request->withAttribute('BackendIdentity', $user);
		Router::setRequest($this->request);

		(string)$this->cell('Backend/Menu');

		$menu = $session->read('Backend.menu.de');

		$this->assertNotEmpty($menu);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MenuCell::display()
	 */
	public function testDisplayReloadsWhenMenuIsOutdated(): void {
		$session = $this->request->getSession();
		$session->write('Backend.menu.de', json_encode([
			'time' => new DateTime()->subMinutes(20)->format('Y-m-d H:i:s'),
			'menuData' => [],
		]));

		$table = $this->fetchTable('BackendMenuEntries');
		$entity = $table->newEntity([
			'title' => 'foobar',
			'createdOn' => new DateTime()->subMinutes(10),
		]);
		$table->save($entity);

		$user = $this->login();
		$this->request = $this->request->withAttribute('BackendIdentity', $user);
		Router::setRequest($this->request);

		(string)$this->cell('Backend/Menu');

		$menu = $session->read('Backend.menu.de');

		$this->assertNotEmpty($menu);

		$data = json_decode($menu, true);
		$this->assertNotEmpty($data['menuData']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MenuCell::display()
	 */
	public function testDisplayReloadsWhenUserIsOutdated(): void {
		$session = $this->request->getSession();
		$session->write('Backend.menu.de', json_encode([
			'time' => new DateTime()->subMinutes(20)->format('Y-m-d H:i:s'),
			'menuData' => [],
		]));

		$user = $this->login();
		$user->changedOn = new DateTime()->subMinutes(10);
		$this->request = $this->request->withAttribute('BackendIdentity', $user);
		Router::setRequest($this->request);

		(string)$this->cell('Backend/Menu');

		$menu = $session->read('Backend.menu.de');

		$this->assertNotEmpty($menu);

		$data = json_decode($menu, true);
		$this->assertNotEmpty($data['menuData']);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MenuCell::display()
	 */
	public function testDisplayWithActiveItem(): void {
		$user = $this->login();

		$this->request = new ServerRequest([
			'url' => '/backend/xy/dashboard/overview',
			'params' => [
				'lang' => 'xy',
				'controller' => 'dashboard',
				'action' => 'overview',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$this->request = $this->request->withAttribute('authorization', new AuthorizationService('Backend'));
		$this->request = $this->request->withAttribute('BackendIdentity', $user);

		Router::setRequest($this->request);

		$output = (string)$this->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 Active MenuItem-Dashboard">', $output);
		$this->assertStringContainsString('<a href="/backend/xy/dashboard/overview/" class="Level1 Active MenuItem-Dashboard">Dashboard::menu_title</a>', $output);

		$this->assertStringContainsString('<a href="/backend/xy/system/overview/" class="Level1 MenuItem-System">System::menu_title</a>', $output);
		$this->assertStringContainsString('<li class="Level1 HasSubmenu MenuItem-Components">', $output);
		$this->assertStringContainsString('<a href="/backend/xy/menus/overview/" class="Level2 MenuItem-Menus">Menus::menu_title</a>', $output);
		$this->assertStringContainsString('<ul class="Level2">', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MenuCell::display()
	 */
	public function testDisplayWithActiveItemForCurrentController(): void {
		$user = $this->login();

		$this->request = new ServerRequest([
			'url' => '/backend/xy/pages/edit',
			'params' => [
				'lang' => 'xy',
				'controller' => 'pages',
				'action' => 'edit',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$this->request = $this->request->withAttribute('authorization', new AuthorizationService('Backend'));
		$this->request = $this->request->withAttribute('BackendIdentity', $user);

		Router::setRequest($this->request);

		$output = (string)$this->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 Active HasSubmenu MenuItem-Pages">', $output);
		$this->assertStringContainsString('<a href="/backend/xy/pages/overview/" class="Level1 Active MenuItem-Pages">Pages::menu_title</a>', $output);
	}
}
