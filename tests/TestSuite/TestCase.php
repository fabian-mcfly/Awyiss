<?php declare(strict_types=1);


namespace Awyiss\Test\TestSuite;


use Awyiss\Authentication\Authenticator\SessionAuthenticator;
use Awyiss\Authentication\Identifier\IdentifierCollection;
use Awyiss\Awyiss;
use Awyiss\Event\EventManager;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\User;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\EventDispatcherTrait;
use Cake\Http\ServerRequest;
use Cake\Routing\RoutingApplicationInterface;
use Cake\TestSuite\TestCase as BaseTestCase;
use LogicException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Process\Process;


/**
 * @inheritDoc
 */
class TestCase extends BaseTestCase {
	use EventDispatcherTrait;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		EventManager::instance(new EventManager(true));
		$this->_eventManager = new EventManager(true);

		/** @noinspection PhpVariableNamingConventionInspection */
		$request = new ServerRequest();
		Router::setRequest($request);
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public static function setUpBeforeClass(): void {
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		if (is_dir(TESTS . 'customer' . DS . 'config' . DS . CONFIG_ENV)) {
			(new Process(['rm', '-rf', TESTS . 'customer' . DS . 'config' . DS . CONFIG_ENV]))->run();
		}
	}


	/**
	 * Overridden to use $this->_appClass
	 *
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function loadRoutes(?array $appArgs = null): void {
		$appArgs ??= [rtrim(CONFIG, DIRECTORY_SEPARATOR)];
		/** @var class-string $className */
		$className = $this->_appClass ?? Configure::read('App.namespace') . '\\Application';
		try {
			$reflect = new ReflectionClass($className);
			$app = $reflect->newInstanceArgs($appArgs);
			assert($app instanceof RoutingApplicationInterface);
		}
		catch (ReflectionException $e) {
			throw new LogicException(sprintf('Cannot load `%s` to load routes from.', $className), 0, $e);
		}
		$builder = Router::createRouteBuilder('/');
		$app->routes($builder);
	}


	/**
	 * @param int $userId
	 * @return \Awyiss\Model\Entity\User
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function login(int $userId = 1): User {
		$users = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $users->get($userId);

		$this->dispatchEvent('Authentication.afterAuthenticate', [
			'authenticator' => new SessionAuthenticator(new IdentifierCollection()),
			'identity' => $user,
		], $this);

		return $user;
	}


	/**
	 * @param object $object
	 * @param string $methodName
	 * @param mixed ...$args
	 * @return mixed
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function callProtectedMethod(object $object, string $methodName, mixed ...$args): mixed {
		$reflection = new ReflectionClass($object);
		$method = $reflection->getMethod($methodName);
		/** @noinspection PhpExpressionResultUnusedInspection */
		$method->setAccessible(true);

		return $method->invokeArgs($object, $args);
	}
}
