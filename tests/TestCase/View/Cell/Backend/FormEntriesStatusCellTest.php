<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Backend;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use ReflectionClass;


/**
 * FormEntriesStatusCellTest class
 */
class FormEntriesStatusCellTest extends TestCase {
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

		$this->request = new ServerRequest([
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
		])->withAttribute('authorization', new AuthorizationService('Backend'));

		$session = $this->request->getSession();
		$session->write('Backend.lastLogin', new DateTime()->subMinutes(20));

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

		$this->fetchTable('FormEntries')->deleteAll(['id >' => 3]);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\FormEntriesStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithoutData(): void {
		$user = $this->login();
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/FormEntriesStatus');

		$this->assertStringContainsString('<fieldset class="Overview-Fieldset Fieldset-FormEntriesStatus StatusCell Collapsible">', $output);
		$this->assertStringNotContainsString('<td', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\FormEntriesStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithData(): void {
		$user = $this->login();
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntriesTable->save(new FormEntry([
			'form_id' => 1,
			'page_id' => 1,
			'subject' => 'Test Subject',
			'body' => 'Test body content',
			'ip_hash' => '127.0.0.1',
			'post_hash' => 'testhash',
			'identifier' => 'test-identifier',
			'created_on' => new DateTime()->subMinutes(10),
		]));

		$output = (string)$this->view->cell('Backend/FormEntriesStatus');

		$this->assertStringContainsString('<fieldset class="Overview-Fieldset Fieldset-FormEntriesStatus StatusCell Collapsible">', $output);
		$this->assertStringContainsString('<td class="TableCell-Form" title="Kontaktformular">Kontaktformular</td>', $output);
		$this->assertStringContainsString('<td class="TableCell-Subject" title="Test Subject">Test Subject</td>', $output);
		$this->assertStringContainsString('href="/backend/xy/form-entries/view/id:', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\FormEntriesStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithoutDataWithUnauthorizedUser(): void {
		$user = $this->login(2);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/FormEntriesStatus');

		$this->assertEmpty('', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\FormEntriesStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithDataWithUnauthorizedUser(): void {
		$user = $this->login(2);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntriesTable->save(new FormEntry([
			'form_id' => 1,
			'page_id' => 1,
			'subject' => 'Test Subject',
			'body' => 'Test body content',
			'ip_hash' => '127.0.0.1',
			'post_hash' => 'testhash',
			'identifier' => 'test-identifier',
			'created_on' => new DateTime()->subMinutes(10),
		]));

		$output = (string)$this->view->cell('Backend/FormEntriesStatus');

		$this->assertStringNotContainsString('<', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\FormEntriesStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithoutDataWithAccessDeniedUser(): void {
		$user = $this->login(3);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/FormEntriesStatus');

		$this->assertEmpty('', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\FormEntriesStatusCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithDataWithAccessDeniedUser(): void {
		$user = $this->login(3);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$formEntriesTable = $this->fetchTable('FormEntries');
		$formEntriesTable->save(new FormEntry([
			'form_id' => 2,
			'page_id' => 1,
			'subject' => 'Test Subject',
			'body' => 'Test body content',
			'ip_hash' => '127.0.0.1',
			'post_hash' => 'testhash',
			'identifier' => 'test-identifier',
			'created_on' => new DateTime()->subMinutes(10),
		]));

		$output = (string)$this->view->cell('Backend/FormEntriesStatus');

		$this->assertStringNotContainsString('<', $output);
	}
}
