<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Middleware;


use Awyiss\Awyiss;
use Awyiss\Middleware\ConfigMiddleware;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Exception;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * ConfigMiddleware Test Case
 *
 * @see \Awyiss\Middleware\ConfigMiddleware
 */
class ConfigMiddlewareTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var string
	 */
	protected static string $oldLocale;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$languagesTable = $this->fetchTable('Languages');
		$languagesTable->updateAll(['active' => true], []);

		LocaleMiddleware::resetLanguages();
	}


	/**
	 * @inheritDoc
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		static::$oldLocale = I18n::getLocale();

		LocaleMiddleware::resetLanguages();
	}


	/**
	 * @inheritDoc
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		ini_set('intl.default_locale', static::$oldLocale);
		I18n::setLocale(static::$oldLocale);
		setlocale(LC_ALL, static::$oldLocale . '.utf8');
		TypeFactory::build('datetime')->setUserTimezone(null);

		$tableLocator = FactoryLocator::get('Table');

		$languagesTable = $tableLocator->get('Languages');
		$languagesTable->updateAll(['active' => true], []);
		LocaleMiddleware::resetLanguages();

		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\ConfigMiddleware::process()
	 * @throws \Exception
	 */
	public function testProcessLoadsConfiguration(): void {
		$this->assertFalse(Configure::check('Awyiss'));

		$request = new ServerRequest([
			'url' => '/zu/users/overview/foo:bar/baz:qux',
			'params' => [
				'controller' => 'Users',
				'action' => 'overview',
				'lang' => 'zu',
			],
		]);

		$sessionIdentifier = LocaleMiddleware::getSessionIdentifier(Awyiss::REALM_BACKEND);
		$request->getSession()->write($sessionIdentifier, 'en');

		Router::setRequest($request);

		$middleware = new ConfigMiddleware();

		$request = new ServerRequest([
			'url' => '/',
		]);
		$handler = $this->createStub(RequestHandlerInterface::class);
		$handler->method('handle')->willReturn(new Response());

		$response = $middleware->process($request, $handler);
		$this->assertInstanceOf(Response::class, $response);

		$this->assertTrue(Configure::check('Awyiss'));
		$this->assertIsArray(Configure::read('Awyiss'));
		$this->assertSame('dummy zu', Configure::read('Awyiss.System.Frontend.dummyConfig'));
		$this->assertSame('dummy en', Configure::read('Awyiss.System.Backend.dummyConfig'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\ConfigMiddleware::process()
	 * @throws \Exception
	 */
	public function testProcessThrowsExceptionWithoutFrontendLanguage(): void {
		$tableLocator = FactoryLocator::get('Table');

		$languagesTable = $tableLocator->get('Languages');
		$languagesTable->updateAll(['active' => false], ['realm' => Awyiss::REALM_FRONTEND]);
		LocaleMiddleware::resetLanguages();

		$middleware = new ConfigMiddleware();

		$request = new ServerRequest([
			'url' => '/',
		]);
		$handler = $this->createStub(RequestHandlerInterface::class);
		$handler->method('handle')->willReturn(new Response());

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('No frontend language found');

		$middleware->process($request, $handler);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\ConfigMiddleware::process()
	 * @throws \Exception
	 */
	public function testProcessThrowsExceptionWithoutBackendLanguage(): void {
		$tableLocator = FactoryLocator::get('Table');

		$languagesTable = $tableLocator->get('Languages');
		$languagesTable->updateAll(['active' => false], ['realm' => Awyiss::REALM_BACKEND]);
		LocaleMiddleware::resetLanguages();

		$middleware = new ConfigMiddleware();

		$request = new ServerRequest([
			'url' => '/',
		]);
		$handler = $this->createStub(RequestHandlerInterface::class);
		$handler->method('handle')->willReturn(new Response());

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('No backend language found');

		$middleware->process($request, $handler);
	}
}
