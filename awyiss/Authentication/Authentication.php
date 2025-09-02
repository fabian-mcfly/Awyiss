<?php declare(strict_types=1);


namespace Awyiss\Authentication;


use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Authenticator\FormAuthenticator;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\Resolver\OrmResolver;
use Awyiss\Authentication\Authenticator\SessionAuthenticator;
use Awyiss\Awyiss;
use Awyiss\Event\EventDispatcherTrait;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\User;
use Awyiss\Routing\Router;
use Cake\I18n\DateTime;
use Exception;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Authentication class that registers and provides access to instances of
 * - AuthenticatorInterface
 * - IdentifierInterface
 */
class Authentication implements AuthenticationServiceProviderInterface {
	use EventDispatcherTrait;


	/**
	 * @var AuthenticationServiceInterface
	 */
	protected AuthenticationServiceInterface $service;
	/**
	 * @var string
	 */
	protected string $realm;
	/**
	 * @var array
	 */
	protected static array $authenticators = [];
	/**
	 * @var bool
	 */
	protected static bool $disableDefaultAuthenticators = false;
	/**
	 * @var bool
	 */
	protected static bool $disableDefaultIdentifiers = false;
	/**
	 * @var array
	 */
	protected static array $identifiers = [];


	/**
	 * @param string $realm
	 */
	public function __construct(string $realm) {
		$this->realm = $realm;
	}


	/**
	 * @param ServerRequestInterface $request
	 * @return AuthenticationServiceInterface
	 * @throws Exception
	 */
	public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface {
		if ($this->realm === Awyiss::REALM_BACKEND) {
			if (!isset($this->service)) {
				$this->service = $this->getBackendAuthenticationService($request);
			}


			return $this->service;
		}

		throw new Exception(__d('authenticator', 'unknown_authentication'));
	}


	/**
	 * On sleep/serialize, do not serialize instances of this class
	 *
	 * @return array
	 */
	public function __sleep(): array {
		return [];
	}


	/**
	 * Registers default Authenticators if not disabled, sorts all Authenticators by priority and adds them to the `AuthenticationServiceInterface`
	 *
	 * @param AuthenticationServiceInterface $service
	 * @param ServerRequestInterface $request
	 * @throws Exception
	 * @see AuthenticationServiceInterface::loadAuthenticator
	 */
	protected function loadAuthenticators(AuthenticationServiceInterface $service, ServerRequestInterface $request): void {
		if (!static::$disableDefaultAuthenticators) {
			$this->addDefaultAuthenticators($service, $request);
		}

		$la_authenticators = static::$authenticators;
		usort($la_authenticators, function (array $a, array $b): int {
			return $a['priority'] <=> $b['priority'];
		});

		foreach ($la_authenticators as $la_authenticator) {
			$lx_authenticator = $la_authenticator['authenticator'];

			/*
			 * If $la_authenticator is not callable, the `addAuthenticator`-method has set the `name` and `config` keys.
			 * If it's a callable, we need to check those keys here.
			 */
			if (is_callable($lx_authenticator)) {
				$lx_authenticator = $lx_authenticator();
				if (!isset($lx_authenticator['name'])) {
					throw new Exception(__d('authenticator', 'authenticator_name_missing'));
				}

				if (!isset($lx_authenticator['config'])) {
					$lx_authenticator['config'] = [];
				}
				elseif (!is_array($lx_authenticator['config'])) {
					throw new Exception(__d('authenticator', 'authenticator_config_not_array'));
				}
			}

			if (!$service->authenticators()->has($lx_authenticator['name'])) {
				$service->loadAuthenticator($lx_authenticator['name'], $lx_authenticator['config']);
			}
		}
	}


	/**
	 * Register the default authenticators for Session and Form
	 *
	 * @throws Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function addDefaultAuthenticators(AuthenticationServiceInterface $service, ServerRequestInterface $request): void {
		$this->addAuthenticator(SessionAuthenticator::class, [
			'identify' => function (User $user): bool {
				//Set last_login
				$lo_checkTime = DateTime::now()->subMinutes(1);
				if ($lo_checkTime >= $user->lastLogin) {
					$user->set('lastLogin', DateTime::now());

					return true;
				}

				return false;
			},
			'identifier' => $this->getIdentifiers(),
		], 10);

		$this->addAuthenticator(FormAuthenticator::class, [
			'fields' => [
				AbstractIdentifier::CREDENTIAL_USERNAME => 'username',
				AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
			],
			'loginUrl' => $this->dispatchEvent('Authentication.requestLoginUrl', [], $this)->getResult(),
			'identifier' => $this->getIdentifiers(),
		], 20);
	}


	/**
	 * Registers the default identifiers if not disabled, sorts all Identifiers by priority and adds them to the `AuthenticationServiceInterface`
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getIdentifiers(): array {
		if (!static::$disableDefaultIdentifiers) {
			$this->addDefaultIdentifiers();
		}

		usort(static::$identifiers, function (array $a, array $b): int {
			return $a['priority'] <=> $b['priority'];
		});

		$la_identifiers = [];
		foreach (static::$identifiers as $la_identifier) {
			$lx_identifier = $la_identifier['identifier'];

			/*
			 * If $lx_identifier is not callable, the `addIdentifier`-method set the `name` and `config` keys
			 * If it's a callable, we need to check those keys here.
			 */
			if (is_callable($lx_identifier)) {
				$lx_identifier = $lx_identifier();
				if (!isset($lx_identifier['name'])) {
					throw new Exception(__d('authenticator', 'identifier_name_missing'));
				}

				if (!isset($lx_identifier['config'])) {
					$lx_identifier['config'] = [];
				}
				if (!is_array($lx_identifier['config'])) {
					throw new Exception(__d('authenticator', 'identifier_config_not_array'));
				}
			}

			$la_identifiers[ $lx_identifier['name'] ] = $lx_identifier['config'];
		}

		return $la_identifiers;
	}


	/**
	 * Register the default identifiers for backend users#
	 *
	 * @return void
	 */
	protected function addDefaultIdentifiers(): void {
		$this->addIdentifier('Authentication.Password', [
			'resolver' => [
				'className' => OrmResolver::class,
				/** @see \Awyiss\Model\Table\UsersTable::findActive() */
				'finder' => 'active',
			],
		]);
	}


	/**
	 * Returns a backend-specific AuthenticationServiceInterface
	 *
	 * @param ServerRequestInterface $request
	 * @return AuthenticationServiceInterface
	 * @throws Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function getBackendAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface {
		$lo_service = new AuthenticationService();

		// Define where users should be redirected to when they are not authenticated
		$lo_service->setConfig([
			'unauthenticatedRedirect' => Router::url([
				'_name' => Awyiss::REALM_BACKEND,
				'action' => 'login',
				'controller' => 'Users',
				'lang' => LocaleMiddleware::getLanguage()->shortcode,
				'prefix' => false,
				'plugin' => null,
			]),
			'queryParam' => null,
		]);

		$this->loadAuthenticators($lo_service, $request);

		return $lo_service;
	}


	/**
	 * Registers an Authenticators to the list of available Authenticators.
	 *
	 * @param callable|string $authenticator
	 * @param array $config
	 * @param int $priority
	 */
	public static function addAuthenticator(string|callable $authenticator, array $config = [], int $priority = 100): void {
		if (is_string($authenticator)) {
			static::$authenticators[] = [
				'authenticator' => [
					'name' => $authenticator,
					'config' => $config,
				],
				'priority' => $priority,
			];
		}
		else {
			static::$authenticators[] = [
				'authenticator' => $authenticator,
				'priority' => $priority,
			];
		}
	}


	/**
	 * Disable the default authenticators for Session and Form
	 *
	 * @param bool $disableDefaultAuthenticators
	 * @noinspection PhpUnused
	 */
	public static function disableDefaultAuthenticators(bool $disableDefaultAuthenticators): void {
		static::$disableDefaultAuthenticators = $disableDefaultAuthenticators;
	}


	/**
	 * Registers an identifier
	 *
	 * @param callable|string $identifier
	 * @param array $config
	 * @param int $priority
	 */
	public static function addIdentifier(string|callable $identifier, array $config = [], int $priority = 100): void {
		if (is_string($identifier)) {
			static::$identifiers[] = [
				'identifier' => [
					'name' => $identifier,
					'config' => $config,
				],
				'priority' => $priority,
			];
		}
		else {
			static::$identifiers[] = [
				'identifier' => $identifier,
				'priority' => $priority,
			];
		}
	}


	/**
	 * Disable the default identifier (PasswordIdentifier)
	 *
	 * @param bool $disableDefaultIdentifiers
	 * @noinspection PhpUnused
	 */
	public static function disableDefaultIdentifiers(bool $disableDefaultIdentifiers): void {
		static::$disableDefaultIdentifiers = $disableDefaultIdentifiers;
	}
}
