<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Middleware;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * AuthorizationMiddleware Test Case
 *
 * @see \Awyiss\Middleware\AuthorizationMiddleware
 */
class AuthorizationMiddlewareTest extends TestCase {
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
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		static::$oldLocale = I18n::getLocale();
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
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\AuthorizationMiddleware::process()
	 */
	public function testProcessSetsAuthenticationService(): void {
		$this->get('/backend/');
		$request = $this->_controller->getRequest();

		$this->assertInstanceOf(AuthorizationService::class, $request->getAttribute('authorization'));

		$authorizationService = $request->getAttribute('authorization');
		$authenticationService = $request->getAttribute('authentication');

		$this->assertSame($authenticationService, $authorizationService->getAuthenticationService());
	}
}
