<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Backend;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\UrlsNotFound;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use ReflectionClass;


/**
 * UrlsNotFoundStatusCellTest class
 */
class UrlsNotFoundStatusCellTest extends TestCase {
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
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

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
				'plugin' => null,
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
		$property->setValue(null, null);

		$this->fetchTable('UrlsNotFound')->deleteAll([]);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\UrlsNotFoundStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithoutData(): void {
		$user = $this->login();
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/UrlsNotFoundStatus');

		$this->assertStringContainsString('<fieldset class="Overview-Fieldset Fieldset-UrlsNotFoundStatus StatusCell Collapsible">', $output);
		$this->assertStringNotContainsString('<td', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\UrlsNotFoundStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithData(): void {
		$user = $this->login();
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$urlsNotFoundTable = $this->fetchTable('UrlsNotFound');
		$urlsNotFoundTable->save(new UrlsNotFound([
			'url' => 'test',
			'created_on' => (new DateTime())->subMinutes(5),
		]));

		$output = (string)$this->view->cell('Backend/UrlsNotFoundStatus');

		$this->assertStringContainsString('<fieldset class="Overview-Fieldset Fieldset-UrlsNotFoundStatus StatusCell Collapsible">', $output);
		$this->assertStringContainsString('<td class="TableCell-Url" title="test">test</td>', $output);
		$this->assertStringContainsString('<form class="Actions" method="post" action="/backend/xy/url-history/add/', $output);
		$this->assertStringContainsString('<input type="hidden" name="url" value="test">', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\UrlsNotFoundStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithoutDataWithUnauthorizedUser(): void {
		$user = $this->login(2);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/UrlsNotFoundStatus');

		$this->assertEmpty('', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\UrlsNotFoundStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithDataWithUnauthorizedUser(): void {
		$user = $this->login(2);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$urlsNotFoundTable = $this->fetchTable('UrlsNotFound');
		$urlsNotFoundTable->save(new UrlsNotFound([
			'url' => 'test',
			'created_on' => (new DateTime())->subMinutes(5),
		]));

		$output = (string)$this->view->cell('Backend/UrlsNotFoundStatus');

		$this->assertStringNotContainsString('<', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\UrlsNotFoundStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithoutDataWithAccessDeniedUser(): void {
		$user = $this->login(3);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/UrlsNotFoundStatus');

		$this->assertEmpty('', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\UrlsNotFoundStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithDataWithAccessDeniedUser(): void {
		$user = $this->login(3);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$urlsNotFoundTable = $this->fetchTable('UrlsNotFound');
		$urlsNotFoundTable->save(new UrlsNotFound([
			'url' => 'test',
			'created_on' => (new DateTime())->subMinutes(5),
		]));

		$output = (string)$this->view->cell('Backend/UrlsNotFoundStatus');

		$this->assertStringNotContainsString('<', $output);
	}
}
