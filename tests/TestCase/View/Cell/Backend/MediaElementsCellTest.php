<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Backend;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * MediaElementsCellTest class
 *
 * @see \Awyiss\View\Cell\Backend\MediaElementsCell
 */
class MediaElementsCellTest extends TestCase {
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
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		$this->loadRoutes();

		$this->request = new ServerRequest([
			'url' => '/dummy',
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

		Router::setRequest($this->request);

		$this->response = $this->createMock(Response::class);

		Configure::write('Awyiss.News.Backend.mediaFolders.autoCreate', true);
	}


	/**
	 * @return array
	 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpVariableNamingConventionInspection
	 */
	public static function displayDataProvider(): array {
		// Required because this method is called before setUpBeforeClass()
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		$tableLocator = FactoryLocator::get('Table');

		return [
			[fn() => $tableLocator->get('Contents')->get(1, 'mediaAssignments'), 'multi'],
			[fn() => $tableLocator->get('Contents')->get(9, 'mediaAssignments'), 'single'],
			[fn() => $tableLocator->get('Contents')->newDefaultEntity(), false],
			[fn() => $tableLocator->get('Cars')->newDefaultEntity(), 'single'],
			[fn() => $tableLocator->get('Pages')->get(1, 'mediaAssignments'), false],
			[fn() => $tableLocator->get('Pages')->newDefaultEntity(), false],
			[fn() => $tableLocator->get('News')->get(21, 'mediaAssignments'), 'hidden_folder'],
			[fn() => $tableLocator->get('News')->newDefaultEntity(), 'false'],
			[fn() => $tableLocator->get('Widgets')->get(1, 'mediaAssignments'), 'single'],
			[fn() => $tableLocator->get('Widgets')->get(13, 'mediaAssignments'), 'single'],
			[fn() => $tableLocator->get('Widgets')->newDefaultEntity(), false],
		];
	}


	/**
	 * @dataProvider displayDataProvider
	 * @param callable $entityProvider
	 * @param string|false $type
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MediaElementsCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testDisplayWithUnauthorizedUser(callable $entityProvider, string|false $type): void {
		$entity = $entityProvider();

		$user = $this->login(2);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);

		$this->view = new BackendView($this->request, $this->response);

		$output = trim((string)$this->view->cell('Backend/MediaElements', [$entity]));

		$this->assertSame('', $output);
	}


	/**
	 * @dataProvider displayDataProvider
	 * @param callable $entityProvider
	 * @param string|false $type
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MediaElementsCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithAuthorizedUser(callable $entityProvider, string|false $type): void {
		$entity = $entityProvider();

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$user = $this->login(1);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = trim((string)$this->view->cell('Backend/MediaElements', [$entity]));

		if ($type === 'multi') {
			$this->assertStringContainsString('<div class="FormInput FormInputType-MediaSelector MediaSelector MediaSelector-MultiFile', $output);
		}
		elseif ($type === 'single') {
			$this->assertStringContainsString('<div class="FormInput FormInputType-MediaSelector MediaSelector MediaSelector-SingleFile', $output);
		}
		elseif ($type === 'hidden_folder') {
			$this->assertStringContainsString('<input type="hidden" name="media_assignments[1][hidden_folder][id]"', $output);
			$this->assertStringContainsString('<input type="hidden" name="media_assignments[1][hidden_folder][media_folder_id]"', $output);
		}
		else {
			$this->assertSame('', $output);
		}
	}


	/**
	 * @dataProvider displayDataProvider
	 * @param callable $entityProvider
	 * @param string|false $type
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MediaElementsCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testDisplayWithAccessDeniedUser(callable $entityProvider, string|false $type): void {
		$entity = $entityProvider();

		$user = $this->login(3);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = trim((string)$this->view->cell('Backend/MediaElements', [$entity]));

		$this->assertSame('', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MediaElementsCell::display()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayRebuildsMediaAssignmentsWhenDirty(): void {
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $this->fetchTable('Contents')->get(16, 'mediaAssignments');

		$entity->mediaAssignments = [
			$this->fetchTable('MediaAssignments')->newDefaultEntity([
				'id' => 9,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'media',
				'media_id' => 2,
				'media_folder_id' => null,
				'scope' => 'contents',
				'foreign_key' => 16,
				'system_order' => 1,
				'deleted' => 0,
			])->setNew(false),
		];

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$user = $this->login(1);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		(string)$this->view->cell('Backend/MediaElements', [$entity]);

		$this->assertNotEmpty($entity->mediaAssignments['standard']['media']);
	}


	/**
	 * @return array
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function elementAssignmentsDataProvider(): array {
		$tableLocator = FactoryLocator::get('Table');

		return [
			[fn() => $tableLocator->get('ContentTemplates')->get(1, 'mediaElementAssignments'), true],
			[fn() => $tableLocator->get('ContentTemplates')->newDefaultEntity(), false],
			[fn() => $tableLocator->get('Datatables')->get(3, 'mediaElementAssignments'), true],
			[fn() => $tableLocator->get('Datatables')->newDefaultEntity(), false],
			[fn() => $tableLocator->get('Cars')->newDefaultEntity(), false],
			[fn() => $tableLocator->get('PageTemplates')->get(1, 'mediaElementAssignments'), true],
			[fn() => $tableLocator->get('PageTemplates')->get(2, 'mediaElementAssignments'), true],
			[fn() => $tableLocator->get('PageTemplates')->newDefaultEntity(), false],
			[fn() => $tableLocator->get('WidgetTemplates')->get(1, 'mediaElementAssignments'), true],
			[fn() => $tableLocator->get('WidgetTemplates')->newDefaultEntity(), false],
		];
	}


	/**
	 * @dataProvider elementAssignmentsDataProvider
	 * @param callable $entityProvider
	 * @param bool $assignmentsAvailable
	 * @return void
	 * @see \Awyiss\View\Cell\Backend\MediaElementsCell::elementAssignments()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @see \Awyiss\View\Cell\Backend\MediaElementsCell::elementAssignments
	 */
	public function testElementAssignments(callable $entityProvider, bool $assignmentsAvailable) {
		$entity = $entityProvider();

		$this->view = new BackendView($this->request, $this->response);

		$output = trim((string)$this->view->cell('Backend/MediaElements::elementAssignments', [$entity]));

		if ($assignmentsAvailable) {
			$this->assertStringContainsString('<fieldset class="Fieldset-MediaElementAssignments">', $output);
			$this->assertStringContainsString('<fieldset class="Fieldset-MediaElements-Available Collapsible">', $output);
			$this->assertStringContainsString('<fieldset class="Fieldset-MediaElements-Assigned Collapsible">', $output);
			$this->assertStringContainsString('id="MediaElement-Element-Standard"', $output);
			$this->assertStringContainsString('id="MediaElement-Element-TitleAndTeaserImage"', $output);
			$this->assertStringContainsString('id="MediaElement-Element-Gallery"', $output);
			$this->assertStringNotContainsString('id="MediaElement-Element-HiddenFolder"', $output);
		}
		else {
			$this->assertSame('', $output);
		}
	}
}
