<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Middleware;


use Awyiss\Authentication\AuthenticationService;
use Awyiss\Awyiss;
use Awyiss\Event\EventManager;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * AuthenticationMiddleware Test Case
 *
 * @see \Awyiss\Middleware\AuthenticationMiddleware
 */
class AuthenticationMiddlewareTest extends TestCase {
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		static::$oldLocale = I18n::getLocale();
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		ini_set('intl.default_locale', static::$oldLocale);
		I18n::setLocale(static::$oldLocale);
		setlocale(LC_ALL, static::$oldLocale . '.utf8');
		TypeFactory::build('datetime')->setUserTimezone(null);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\AuthorizationMiddleware::process()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessLoadsEventListeners(): void {
		$eventManager = EventManager::instance();

		$this->assertEmpty($eventManager->listeners('Authentication.afterAuthenticate'));

		$this->get('/backend/');

		$this->assertNotEmpty($eventManager->listeners('Authentication.afterAuthenticate'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\AuthorizationMiddleware::process()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessSetsAuthenticationAttribute(): void {
		$this->get('/backend/');
		$request = $this->_controller->getRequest();

		$this->assertInstanceOf(AuthenticationService::class, $request->getAttribute('authentication'));
	}
}
