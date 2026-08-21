<?php declare(strict_types=1);


namespace Awyiss\Test\TestSuite;


use Awyiss\Authentication\Authenticator\SessionAuthenticator;
use Awyiss\Awyiss;
use Awyiss\Event\EventManager;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\User;
use Awyiss\Routing\Router;
use Awyiss\Utility\Media\ResizedImageManager;
use Awyiss\View\AppView;
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
		ResizedImageManager::clear();

		parent::setUp();

		EventManager::instance(new EventManager(true));
		$this->_eventManager = new EventManager(true);

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
			new Process(['rm', '-rf', TESTS . 'customer' . DS . 'config' . DS . CONFIG_ENV])->run();
		}

		$reflection = new ReflectionClass(AppView::class);
		$property = $reflection->getProperty('twig');
		$property->setAccessible(true);
		$property->setValue(null, null);
	}


	/**
	 * @inheritDoc
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		$mediaResizedImagesTable = FactoryLocator::get('Table')->get('MediaResizedImages');
		// Delete all resized images created by tests (id > 27)
		$mediaResizedImagesTable->deleteAll(['id >' => 27]);
	}


	/**
	 * Overridden to use $this->_appClass
	 *
	 * @inheritDoc
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
	 */
	protected function login(int $userId = 1): User {
		$users = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $users->get($userId);

		$this->dispatchEvent('Authentication.afterAuthenticate', [
			'authenticator' => new SessionAuthenticator(null),
			'identity' => $user,
		], $this);

		return $user;
	}


	/**
	 * @param object|string $object
	 * @param string $methodName
	 * @param mixed ...$args
	 * @return mixed
	 * @throws \ReflectionException
	 */
	protected function callProtectedMethod(object|string $object, string $methodName, mixed ...$args): mixed {
		$reflection = new ReflectionClass($object);
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);

		// Handle static methods when $object is a class name (string)
		if (is_string($object) || $method->isStatic()) {
			return $method->invokeArgs(null, $args);
		}

		return $method->invokeArgs($object, $args);
	}
}
