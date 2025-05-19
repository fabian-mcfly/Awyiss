<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Backend;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
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
	 * @noinspection PhpVariableNamingConventionInspection
	 * @throws \PHPUnit\Framework\MockObject\Exception
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
			],
		]);

		$this->request = $this->request->withAttribute('authorization', new AuthorizationService('Backend'));

		Router::setRequest($this->request);

		$this->response = $this->createMock(Response::class);

		Configure::write('Awyiss.News.Backend.mediaFolders.autoCreate', true);
	}


	/**
	 * @return array
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public static function displayDataProvider(): array {
		// Required because this method is called before setUpBeforeClass()
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		return [
			[FactoryLocator::get('Table')->get('Contents')->get(1, 'mediaAssignments'), 'multi'],
			[FactoryLocator::get('Table')->get('Contents')->get(9, 'mediaAssignments'), 'single'],
			[FactoryLocator::get('Table')->get('Contents')->newDefaultEntity(), false],
			[FactoryLocator::get('Table')->get('Cars')->newDefaultEntity(), 'single'],
			[FactoryLocator::get('Table')->get('Pages')->get(1, 'mediaAssignments'), false],
			[FactoryLocator::get('Table')->get('Pages')->newDefaultEntity(), false],
			[FactoryLocator::get('Table')->get('News')->get(21, 'mediaAssignments'), 'hidden_folder'],
			[FactoryLocator::get('Table')->get('News')->newDefaultEntity(), 'false'],
			[FactoryLocator::get('Table')->get('Widgets')->get(1, 'mediaAssignments'), 'single'],
			[FactoryLocator::get('Table')->get('Widgets')->get(13, 'mediaAssignments'), 'single'],
			[FactoryLocator::get('Table')->get('Widgets')->newDefaultEntity(), false],
		];
	}


	/**
	 * @dataProvider displayDataProvider
	 * @param \Awyiss\Model\Entity $entity
	 * @param string|false $type
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testDisplayWithoutUser(Entity $entity, string|false $type): void {
		$this->view = new BackendView($this->request, $this->response);

		$output = (string)$this->view->cell('Backend/MediaElements', [$entity]);

		$this->assertSame('', $output);
	}


	/**
	 * @dataProvider displayDataProvider
	 * @param \Awyiss\Model\Entity $entity
	 * @param string|false $type
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testDisplayWithUnauthorizedUser(Entity $entity, string|false $type): void {
		$user = $this->login(2);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = trim((string)$this->view->cell('Backend/MediaElements', [$entity]));

		$this->assertSame('', $output);
	}


	/**
	 * @dataProvider displayDataProvider
	 * @param \Awyiss\Model\Entity $entity
	 * @param string|false $type
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayWithAuthorizedUser(Entity $entity, string|false $type): void {
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
	 * @param \Awyiss\Model\Entity $entity
	 * @param string|false $type
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function testDisplayWithAccessDeniedUser(Entity $entity, string|false $type): void {
		$user = $this->login(3);
		$this->request = $this->request->withAttribute('identity', $user);
		Router::setRequest($this->request);
		$this->view = new BackendView($this->request, $this->response);

		$output = trim((string)$this->view->cell('Backend/MediaElements', [$entity]));

		$this->assertSame('', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
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
	 */
	public static function elementAssignmentsDataProvider(): array {
		//Awyiss::setRealm(Awyiss::REALM_BACKEND);

		dump(Awyiss::getRealm());

		return [
			[FactoryLocator::get('Table')->get('ContentTemplates')->get(1, 'mediaElementAssignments'), true],
			[FactoryLocator::get('Table')->get('ContentTemplates')->newDefaultEntity(), false],
			[FactoryLocator::get('Table')->get('Datatables')->get(3, 'mediaElementAssignments'), true],
			[FactoryLocator::get('Table')->get('Datatables')->newDefaultEntity(), false],
			[FactoryLocator::get('Table')->get('Cars')->newDefaultEntity(), false],
			[FactoryLocator::get('Table')->get('PageTemplates')->get(1, 'mediaElementAssignments'), true],
			[FactoryLocator::get('Table')->get('PageTemplates')->get(2, 'mediaElementAssignments'), true],
			[FactoryLocator::get('Table')->get('PageTemplates')->newDefaultEntity(), false],
			[FactoryLocator::get('Table')->get('WidgetTemplates')->get(1, 'mediaElementAssignments'), true],
			[FactoryLocator::get('Table')->get('WidgetTemplates')->newDefaultEntity(), false],
		];
	}


	/**
	 * @dataProvider elementAssignmentsDataProvider
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testElementAssignments(Entity $entity, bool $assignmentsAvailable) {
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
