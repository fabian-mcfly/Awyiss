<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Backend;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\PagesNotFound;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use ReflectionClass;


/**
 * PagesNotFoundStatusCellTest class
 */
class PagesNotFoundStatusCellTest extends TestCase {
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

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		Awyiss::loadConfiguration('xy', 'yx');

		$this->loadRoutes();

		$this->request = (new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'TheController',
				'action' => 'theAction',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
			],
		]))->withAttribute('authorization', new AuthorizationService('Backend'));

		$session = $this->request->getSession();
		$session->write('Backend.lastLogin', (new DateTime())->subMinutes(20));


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

		$this->fetchTable('PagesNotFound')->deleteAll([]);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithoutUser(): void {
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/PagesNotFoundStatus');

		$this->assertSame('', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithoutData(): void {
		$user = $this->login();
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/PagesNotFoundStatus');

		$this->assertStringContainsString('<fieldset class="Overview-Fieldset Fieldset-PagesNotFoundStatus StatusCell Collapsible">', $output);
		$this->assertStringNotContainsString('<td', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithData(): void {
		$user = $this->login();
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$pagesNotFoundTable = $this->fetchTable('PagesNotFound');
		$pagesNotFoundTable->save(new PagesNotFound([
			'slug' => 'test',
			'created_on' => (new DateTime())->subMinutes(5),
		]));

		$output = (string)$this->view->cell('Backend/PagesNotFoundStatus');

		$this->assertStringContainsString('<fieldset class="Overview-Fieldset Fieldset-PagesNotFoundStatus StatusCell Collapsible">', $output);
		$this->assertStringContainsString('<td class="TableCell-Slug" title="test">test</td>', $output);
		$this->assertStringContainsString('<form class="Actions" method="post" action="/backend/xy/slug-history/add/', $output);
		$this->assertStringContainsString('<input type="hidden" name="slug"  value="test">', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testDisplayWithoutDataWithUnauthorizedUser(): void {
		$user = $this->login(2);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/PagesNotFoundStatus');

		$this->assertEmpty('', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithDataWithUnauthorizedUser(): void {
		$user = $this->login(2);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$pagesNotFoundTable = $this->fetchTable('PagesNotFound');
		$pagesNotFoundTable->save(new PagesNotFound([
			'slug' => 'test',
			'created_on' => (new DateTime())->subMinutes(5),
		]));

		$output = (string)$this->view->cell('Backend/PagesNotFoundStatus');

		$this->assertStringNotContainsString('<', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testDisplayWithoutDataWithAccessDeniedUser(): void {
		$user = $this->login(3);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/PagesNotFoundStatus');

		$this->assertEmpty('', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithDataWithAccessDeniedUser(): void {
		$user = $this->login(3);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$pagesNotFoundTable = $this->fetchTable('PagesNotFound');
		$pagesNotFoundTable->save(new PagesNotFound([
			'slug' => 'test',
			'created_on' => (new DateTime())->subMinutes(5),
		]));

		$output = (string)$this->view->cell('Backend/PagesNotFoundStatus');

		$this->assertStringNotContainsString('<', $output);
	}
}
