<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Routing;


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Exception\MissingRouteException;
use Cake\TestSuite\IntegrationTestTrait;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Test case for Router
 *
 * @see \Awyiss\Routing\Router
 */
class RouterTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var array<string, array<string, mixed>>
	 */
	protected array $params = [
		Awyiss::REALM_FRONTEND => [
			'url' => '/de/dummy',
			'params' => [
				'lang' => 'de',
				'slug' => 'dummy',
				'controller' => 'Frontend',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		],
		Awyiss::REALM_BACKEND => [
			'url' => '/backend/de/users/add',
			'params' => [
				'lang' => 'de',
				'controller' => 'Users',
				'action' => 'add',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		],
	];
	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected ServerRequest $request;


	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);
	}


	/**
	 * @param string $realm
	 * @return \Psr\Http\Message\ServerRequestInterface
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setRequestRealm(string $realm): ServerRequestInterface {
		Awyiss::setRealm($realm);

		$request = new ServerRequest($this->params[ $realm ]);
		Router::setRequest($request);

		$this->loadRoutes();

		return $request;
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlWithStringUrl(): void {
		$url = '/test/path';
		$result = Router::url($url);

		$this->assertEquals($url, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlWithStringUrlFull(): void {
		$url = '/test/path';
		$result = Router::url($url, true);

		$this->assertEquals('http://localhost/test/path', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlWithNullUrl(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = Router::url(null);

		$this->assertEquals('/', $result);
	}



	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlWithNullUrlFull(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = Router::url(null, true);

		$this->assertEquals('http://localhost/', $result);
	}


	/**
	 * When _name is not set and no plugin is specified,
	 * the realm should be set to the current realm.
	 *
	 * @return void
	 * @see \Awyiss\Awyiss::getRealm()
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlWithArrayUrlWithoutNameOrPluginUsesCurrentRealm(): void {
		$this->setRequestRealm(Awyiss::REALM_BACKEND);

		$url = ['lang' => 'en', 'controller' => 'Pages', 'action' => 'edit', 'slug' => 'test-page'];

		$result = Router::url($url);

		$this->assertSame('/backend/en/pages/edit/slug:test-page/', $result);

		$this->setRequestRealm(Awyiss::REALM_FRONTEND);

		$result = Router::url($url);

		$this->assertSame('/en/test-page/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlWithArrayUrlWithName(): void {
		$url = ['_name' => 'custom_route', 'controller' => 'Pages'];

		$this->expectException(MissingRouteException::class);
		$this->expectExceptionMessage('A route matching `custom_route` could not be found.');
		// This will throw an exception because 'custom_route' is not defined in the routes.
		Router::url($url);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlWithArrayUrlForOppositeRealm(): void {
		$this->setRequestRealm(Awyiss::REALM_BACKEND);

		$url = ['_name' => Awyiss::REALM_FRONTEND, 'lang' => 'en', 'controller' => 'Pages', 'action' => 'edit', 'slug' => 'test-page'];

		$result = Router::url($url);

		// The URL should be generated for the frontend realm
		$this->assertSame('/en/test-page/', $result);

		$this->setRequestRealm(Awyiss::REALM_FRONTEND);

		$url = ['_name' => Awyiss::REALM_BACKEND, 'lang' => 'en', 'controller' => 'Pages', 'action' => 'edit', 'slug' => 'test-page'];

		$result = Router::url($url);

		// The URL should be generated for the backend realm
		$this->assertSame('/backend/en/pages/edit/slug:test-page/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlWithArrayUrlWithEmptyName(): void {
		$this->setRequestRealm(Awyiss::REALM_BACKEND);

		$url = ['lang' => 'en', 'controller' => 'Pages', 'action' => 'edit', 'slug' => 'test-page', '_name' => null];

		$result = Router::url($url);

		// No name set will keep the current prefix (backend)
		$this->assertSame('/backend/en/pages/edit/slug:test-page', $result);

		$this->setRequestRealm(Awyiss::REALM_FRONTEND);

		$this->expectException(MissingRouteException::class);
		// Will throw an exception because no route matches the URL without a name
		Router::url($url);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlFrontendRealmWithoutSlugOrLang(): void {
		$this->setRequestRealm(Awyiss::REALM_FRONTEND);

		$url = ['controller' => 'Pages', 'action' => 'edit'];

		$result = Router::url($url);

		// Keeps the current language and slug
		$this->assertSame('/de/dummy/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlFrontendRealmWithSlug(): void {
		$this->setRequestRealm(Awyiss::REALM_FRONTEND);

		$url = ['controller' => 'Pages', 'action' => 'edit', 'slug' => 'test-page'];

		$result = Router::url($url);

		// Keeps the current language
		$this->assertSame('/de/test-page/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlFrontendRealmWithLang(): void {
		$this->setRequestRealm(Awyiss::REALM_FRONTEND);

		$url = ['controller' => 'Pages', 'action' => 'view', 'lang' => 'en'];

		$result = Router::url($url);

		// Keeps the current slug
		$this->assertSame('/en/dummy/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlBackendRealmWithoutController(): void {
		$this->setRequestRealm(Awyiss::REALM_BACKEND);

		$url = ['lang' => 'en', 'action' => 'delete'];

		$result = Router::url($url);

		// Keeps the current controller and action
		$this->assertSame('/backend/en/users/delete/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlBackendRealmWithController(): void {
		$this->setRequestRealm(Awyiss::REALM_BACKEND);

		$url = ['lang' => 'en', 'controller' => 'News', 'action' => 'delete'];

		$result = Router::url($url);

		// Keeps the current language and action
		$this->assertSame('/backend/en/news/delete/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlBackendRealmWithoutAction(): void {
		$this->setRequestRealm(Awyiss::REALM_BACKEND);

		$url = ['lang' => 'en', 'controller' => 'News'];

		$result = Router::url($url);

		// Keeps the action from the request
		$this->assertSame('/backend/en/news/add/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlBackendRealmWithAction(): void {
		$this->setRequestRealm(Awyiss::REALM_BACKEND);

		$url = ['lang' => 'en', 'controller' => 'News', 'action' => 'delete'];

		$result = Router::url($url);

		$this->assertSame('/backend/en/news/delete/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlFrontendRealmWithLanguageShortcodeDisabled(): void {
		Configure::write('Route.includeLanguageShortcode', false);

		$this->setRequestRealm(Awyiss::REALM_FRONTEND);

		$url = ['_name' => Awyiss::REALM_FRONTEND, 'slug' => 'dummy-slug', 'lang' => 'en'];

		$result = Router::url($url);

		$this->assertSame('/dummy-slug/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Routing\Router::url()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testUrlFrontendRealmWithLanguageShortcodeEnabled(): void {
		Configure::write('Route.includeLanguageShortcode', true);

		$this->setRequestRealm(Awyiss::REALM_FRONTEND);

		$url = ['_name' => Awyiss::REALM_FRONTEND, 'slug' => 'dummy-slug', 'lang' => 'en'];

		$result = Router::url($url);

		$this->assertSame('/en/dummy-slug/', $result);
	}
}
