<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Middleware;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * RoutingMiddleware Test Case
 *
 * @see \Awyiss\Middleware\RoutingMiddleware
 */
class RoutingMiddlewareTest extends TestCase {
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
	 * @see \Awyiss\Middleware\RoutingMiddleware::process()
	 */
	public function testProcessSetsParamsAsQueryParams(): void {
		$this->get('/zu/users/overview/foo:bar/baz:qux');

		$request = $this->_controller->getRequest();

		$this->assertSame([
			'lang' => 'zu',
			'slug' => 'users/overview',
			'parts' => [
				'foo' => 'bar',
				'baz' => 'qux',
			],
			'pass' => [],
			'fullSlug' => 'foo:bar/baz:qux',
			'prefix' => 'Frontend',
			'controller' => 'Frontend',
			'action' => 'index',
			'plugin' => null,
			'foo' => 'bar',
			'baz' => 'qux',
			'_name' => 'Frontend',
			'_matchedRoute' => '/{lang}/{slug}/*',
			'_ext' => null,
		], $request->getAttribute('params', []));

		$queryParams = $request->getQueryParams();
		$this->assertSame([
			'foo' => 'bar',
			'baz' => 'qux',
		], $queryParams);
	}
}
