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
use ReflectionClass;


/**
 * MenuCellTest class
 */
class MenuCellTest extends TestCase {
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
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $view;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
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
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function tearDown(): void {
		parent::tearDown();

		$reflection = new ReflectionClass(BackendView::class);

		$property = $reflection->getProperty('twig');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null);

		$property = $reflection->getProperty('twigInitialized');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(false);

		$reflection = new ReflectionClass(BackendMenuItem::class);

		$property = $reflection->getProperty('testUrl');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithoutUser(): void {
		$this->view = new BackendView($this->request, $this->response);

		$this->captureError(E_USER_WARNING, function () {
			$output = (string)$this->view->cell('Backend/Menu');
			$this->assertSame('', $output);
		});
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithAuthorizedUser(): void {
		$user = $this->login();
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Dashboard">', $output);
		$this->assertStringContainsString('<a href="http://localhost/backend/xy/dashboard/overview/" class="Level1 MenuItem-Dashboard">dashboard::menu_title</a>', $output);

		$this->assertStringContainsString('<li class="Level1 MenuItem-System">', $output);
		$this->assertStringContainsString('<a href="http://localhost/backend/xy/system/overview/" class="Level1 MenuItem-System">system::menu_title</a>', $output);
		$this->assertStringContainsString('<a href="http://localhost/backend/xy/menus/overview/" class="Level2 MenuItem-Menus">menus::menu_title</a>', $output);
		$this->assertStringContainsString('<ul class="Level2">', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithUnauthorizedUser(): void {
		$user = $this->login(2);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Dashboard">', $output);
		$this->assertStringContainsString('<a href="http://localhost/backend/xy/dashboard/overview/" class="Level1 MenuItem-Dashboard">dashboard::menu_title</a>', $output);

		$this->assertStringNotContainsString('<a href="http://localhost/backend/xy/menus/overview/" class="Level2 MenuItem-Menus">menus::menu_title</a>', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithAccessDeniedUser(): void {
		$user = $this->login(3);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 MenuItem-Dashboard">', $output);
		$this->assertStringContainsString('<a href="http://localhost/backend/xy/dashboard/overview/" class="Level1 MenuItem-Dashboard">dashboard::menu_title</a>', $output);

		$this->assertStringNotContainsString('<a href="http://localhost/backend/xy/menus/overview/" class="Level2 MenuItem-Menus">menus::menu_title</a>', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplaySavesMenuStructureToSession(): void {
		$session = $this->request->getSession();
		$session->delete('Backend.menu.de');

		$this->assertEmpty($session->read('Backend.menu.de'));

		$user = $this->login();
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		(string)$this->view->cell('Backend/Menu');

		$menu = $session->read('Backend.menu.de');

		$this->assertNotEmpty($menu);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayReloadsWhenMenuIsOutdated(): void {
		$session = $this->request->getSession();
		$session->write('Backend.menu.de', json_encode([
			'time' => (new DateTime())->subMinutes(20)->format('Y-m-d H:i:s'),
			'menuData' => [],
		]));

		$table = $this->fetchTable('BackendMenuEntries');
		$entity = $table->newEntity([
			'title' => 'foobar',
			'created_on' => (new DateTime())->subMinutes(10),
		]);
		$table->save($entity);

		$user = $this->login();
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		(string)$this->view->cell('Backend/Menu');

		$menu = $session->read('Backend.menu.de');

		$this->assertNotEmpty($menu);

		$data = json_decode($menu, true);
		$this->assertNotEmpty($data['menuData']);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayReloadsWhenUserIsOutdated(): void {
		$session = $this->request->getSession();
		$session->write('Backend.menu.de', json_encode([
			'time' => (new DateTime())->subMinutes(20)->format('Y-m-d H:i:s'),
			'menuData' => [],
		]));

		$user = $this->login();
		$user->changedOn = (new DateTime())->subMinutes(10);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		(string)$this->view->cell('Backend/Menu');

		$menu = $session->read('Backend.menu.de');

		$this->assertNotEmpty($menu);

		$data = json_decode($menu, true);
		$this->assertNotEmpty($data['menuData']);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->request = $this->request->withAttribute('identity', $user);

		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 Active MenuItem-Dashboard">', $output);
		$this->assertStringContainsString('<a href="http://localhost/backend/xy/dashboard/overview/" class="Level1 Active MenuItem-Dashboard">dashboard::menu_title</a>', $output);

		$this->assertStringContainsString('<a href="http://localhost/backend/xy/system/overview/" class="Level1 MenuItem-System">system::menu_title</a>', $output);
		$this->assertStringContainsString('<li class="Level1 HasSubmenu MenuItem-Components">', $output);
		$this->assertStringContainsString('<a href="http://localhost/backend/xy/menus/overview/" class="Level2 MenuItem-Menus">menus::menu_title</a>', $output);
		$this->assertStringContainsString('<ul class="Level2">', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
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
		$this->request = $this->request->withAttribute('identity', $user);

		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/Menu');

		$this->assertStringContainsString('<nav id="Menu-System">', $output);
		$this->assertStringContainsString('<li class="Level1 Active HasSubmenu MenuItem-Pages">', $output);
		$this->assertStringContainsString('<a href="http://localhost/backend/xy/pages/overview/" class="Level1 Active MenuItem-Pages">pages::menu_title</a>', $output);
	}
}
