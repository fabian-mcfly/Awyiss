<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Middleware;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * EventListenersMiddleware Test Case
 *
 * @see \Awyiss\Middleware\EventListenersMiddleware
 */
class EventListenersMiddlewareTest extends TestCase {
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
	 * @see \Awyiss\Middleware\EventListenersMiddleware::process()
	 */
	public function testProcessLoadsGeneralEventListener(): void {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);

		$eventManager = $this->getEventManager();

		$this->assertEmpty($eventManager->listeners('Model.beforeSave'));

		$this->get('/zu/users/overview/foo:bar/baz:qux');

		$this->assertNotEmpty($eventManager->listeners('Model.beforeSave'));
	}
}
