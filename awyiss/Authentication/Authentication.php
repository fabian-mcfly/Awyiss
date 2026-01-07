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
use Awyiss\Model\Entity\Customer;
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
	 * @var array<string, array>
	 */
	protected static array $authenticators = [
		Awyiss::REALM_BACKEND => [],
		Awyiss::REALM_FRONTEND => [],
	];
	/**
	 * @var array<string, bool>
	 */
	protected static array $disableDefaultAuthenticators = [
		Awyiss::REALM_BACKEND => false,
		Awyiss::REALM_FRONTEND => false,
	];
	/**
	 * @var array<string, bool>
	 */
	protected static array $disableDefaultIdentifiers = [
		Awyiss::REALM_BACKEND => false,
		Awyiss::REALM_FRONTEND => false,
	];
	/**
	 * @var array<string, array>
	 */
	protected static array $identifiers = [
		Awyiss::REALM_BACKEND => [],
		Awyiss::REALM_FRONTEND => [],
	];


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

		if ($this->realm === Awyiss::REALM_FRONTEND) {
			if (!isset($this->service)) {
				$this->service = $this->getFrontendAuthenticationService($request);
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
	 * Load the registered Authenticators for the given realm
	 *
	 * @param string $realm
	 * @param AuthenticationServiceInterface $service
	 * @throws Exception
	 * @see AuthenticationServiceInterface::loadAuthenticator
	 */
	protected function loadAuthenticators(string $realm, AuthenticationServiceInterface $service): void {
		$authenticators = static::$authenticators[ $realm ];
		usort($authenticators, function (array $a, array $b): int {
			return $a['priority'] <=> $b['priority'];
		});

		foreach ($authenticators as $authenticator) {
			$authenticator = $authenticator['authenticator'];

			/*
			 * If $authenticator is not callable, the `addAuthenticator`-method has set the `name` and `config` keys.
			 * If it's a callable, we need to check those keys here.
			 */
			if (is_callable($authenticator)) {
				$authenticator = $authenticator();
				if (!isset($authenticator['name'])) {
					throw new Exception(__d('authenticator', 'authenticator_name_missing'));
				}

				if (!isset($authenticator['config'])) {
					$authenticator['config'] = [];
				}
				elseif (!is_array($authenticator['config'])) {
					throw new Exception(__d('authenticator', 'authenticator_config_not_array'));
				}
			}

			if (!$service->authenticators()->has($authenticator['name'])) {
				$service->loadAuthenticator($authenticator['name'], $authenticator['config']);
			}
		}
	}


	/**
	 * Add the default authenticators for Session and Form
	 * for the Backend
	 *
	 * @throws Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function addDefaultBackendAuthenticators(AuthenticationServiceInterface $service, ServerRequestInterface $request): void {
		$identifiers = $this->getIdentifiers(Awyiss::REALM_BACKEND);

		$this->addAuthenticator(Awyiss::REALM_BACKEND, SessionAuthenticator::class, [
			'identify' => function (User $user): bool {
				//Set last_login
				$checkTime = DateTime::now()->subMinutes(1);
				if ($checkTime >= $user->lastLogin) {
					$user->set('lastLogin', DateTime::now());

					return true;
				}

				return false;
			},
			'identifier' => $identifiers,
			'sessionKey' => Awyiss::getRealm() . '.Auth',
		], 10);

		$this->addAuthenticator(Awyiss::REALM_BACKEND, FormAuthenticator::class, [
			'fields' => [
				AbstractIdentifier::CREDENTIAL_USERNAME => 'username',
				AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
			],
			'loginUrl' => $this->dispatchEvent('Authentication.requestLoginUrl', [], $this)->getResult(),
			'identifier' => $identifiers,
		], 20);
	}


	/**
	 * Register the default authenticators for Session and Form
	 * for the Frontend
	 *
	 * @throws Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function addDefaultFrontendAuthenticators(AuthenticationServiceInterface $service, ServerRequestInterface $request): void {
		$identifiers = $this->getIdentifiers(Awyiss::REALM_FRONTEND);

		$this->addAuthenticator(Awyiss::REALM_FRONTEND, SessionAuthenticator::class, [
			'fields' => [
				AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
			],
			'identify' => function (Customer $customer): bool {
				//Set last_login
				$checkTime = DateTime::now()->subMinutes(1);
				if ($checkTime >= $customer->lastLogin) {
					$customer->set('lastLogin', DateTime::now());

					return true;
				}

				return false;
			},
			'identifier' => $identifiers,
			'sessionKey' => Awyiss::getRealm() . '.Auth',
		], 10);

		$this->addAuthenticator(Awyiss::REALM_FRONTEND, FormAuthenticator::class, [
			'fields' => [
				AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
				AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
			],
			'loginUrl' => $this->dispatchEvent('Authentication.requestLoginUrl', [], $this)->getResult(),
			'identifier' => $identifiers,
		], 20);
	}


	/**
	 * Get the registered identifiers sorted by priority
	 *
	 * @param string $realm
	 * @return array
	 * @throws Exception
	 */
	protected function getIdentifiers(string $realm): array {
		usort(static::$identifiers[ $realm ], function (array $a, array $b): int {
			return $a['priority'] <=> $b['priority'];
		});

		$identifiers = [];
		foreach (static::$identifiers[ $realm ] as $identifier) {
			$identifier = $identifier['identifier'];

			/*
			 * If $identifier is not callable, the `addIdentifier`-method set the `name` and `config` keys
			 * If it's a callable, we need to check those keys here.
			 */
			if (is_callable($identifier)) {
				$identifier = $identifier();
				if (!isset($identifier['name'])) {
					throw new Exception(__d('authenticator', 'identifier_name_missing'));
				}

				if (!isset($identifier['config'])) {
					$identifier['config'] = [];
				}
				if (!is_array($identifier['config'])) {
					throw new Exception(__d('authenticator', 'identifier_config_not_array'));
				}
			}

			$identifiers[ $identifier['name'] ] = $identifier['config'];
		}

		return $identifiers;
	}


	/**
	 * Add the default identifier for the Backend
	 *
	 * @return void
	 */
	protected function addDefaultBackendIdentifiers(): void {
		$this->addIdentifier(Awyiss::REALM_BACKEND, 'Authentication.Password', [
			'resolver' => [
				'className' => OrmResolver::class,
				/** @see \Awyiss\Model\Table\UsersTable::findActive() */
				'finder' => 'active',
			],
		]);
	}


	/**
	 * Add the default identifier for the Frontend
	 *
	 * @return void
	 */
	protected function addDefaultFrontendIdentifiers(): void {
		$this->addIdentifier(Awyiss::REALM_FRONTEND, 'Authentication.Password', [
			'fields' => [
				AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
				AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
			],
			'resolver' => [
				'className' => OrmResolver::class,
				'userModel' => 'Customers',
				/** @see \Awyiss\Model\Table\CustomersTable::findActive() */
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
		$service = new AuthenticationService();

		// Define where users should be redirected to when they are not authenticated
		$service->setConfig([
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

		if (!static::$disableDefaultIdentifiers[ Awyiss::REALM_BACKEND ]) {
			$this->addDefaultBackendIdentifiers();
		}

		if (!static::$disableDefaultAuthenticators[ Awyiss::REALM_BACKEND ]) {
			$this->addDefaultBackendAuthenticators($service, $request);
		}

		$this->loadAuthenticators(Awyiss::REALM_BACKEND, $service);

		return $service;
	}


	/**
	 * Returns a frontend-specific AuthenticationServiceInterface
	 *
	 * @param ServerRequestInterface $request
	 * @return AuthenticationServiceInterface
	 * @throws Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function getFrontendAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface {
		$service = new AuthenticationService();

		// Define where customers should be redirected to when they are not authenticated
		$service->setConfig([
			'unauthenticatedRedirect' => null,
			'queryParam' => null,
		]);

		if (!static::$disableDefaultIdentifiers[ Awyiss::REALM_FRONTEND ]) {
			$this->addDefaultFrontendIdentifiers();
		}

		if (!static::$disableDefaultAuthenticators[ Awyiss::REALM_FRONTEND ]) {
			$this->addDefaultFrontendAuthenticators($service, $request);
		}

		$this->loadAuthenticators(Awyiss::REALM_FRONTEND, $service);

		return $service;
	}


	/**
	 * Registers an Authenticators to the list of available Authenticators.
	 *
	 * @param string $realm
	 * @param callable|string $authenticator
	 * @param array $config
	 * @param int $priority
	 */
	public static function addAuthenticator(string $realm, string|callable $authenticator, array $config = [], int $priority = 100): void {
		if (is_string($authenticator)) {
			static::$authenticators[ $realm ][] = [
				'authenticator' => [
					'name' => $authenticator,
					'config' => $config,
				],
				'priority' => $priority,
			];

			return;
		}

		static::$authenticators[ $realm ][] = [
			'authenticator' => $authenticator,
			'priority' => $priority,
		];
	}


	/**
	 * Disable the default Backend authenticators for Session and Form
	 *
	 * @param bool $disableDefaultAuthenticators
	 * @noinspection PhpUnused
	 */
	public static function disableDefaultBackendAuthenticators(bool $disableDefaultAuthenticators): void {
		static::$disableDefaultAuthenticators[ Awyiss::REALM_BACKEND ] = $disableDefaultAuthenticators;
	}


	/**
	 * Disable the default Frontend authenticators for Session and Form
	 *
	 * @param bool $disableDefaultAuthenticators
	 * @noinspection PhpUnused
	 */
	public static function disableDefaultFrontendAuthenticators(bool $disableDefaultAuthenticators): void {
		static::$disableDefaultAuthenticators[ Awyiss::REALM_FRONTEND ] = $disableDefaultAuthenticators;
	}


	/**
	 * Registers an identifier
	 *
	 * @param string $realm
	 * @param callable|string $identifier
	 * @param array $config
	 * @param int $priority
	 */
	public static function addIdentifier(string $realm, string|callable $identifier, array $config = [], int $priority = 100): void {
		if (is_string($identifier)) {
			static::$identifiers[ $realm ][] = [
				'identifier' => [
					'name' => $identifier,
					'config' => $config,
				],
				'priority' => $priority,
			];

			return;
		}

		static::$identifiers[ $realm ][] = [
			'identifier' => $identifier,
			'priority' => $priority,
		];
	}


	/**
	 * Disable the default Backend identifier (PasswordIdentifier)
	 *
	 * @param bool $disableDefaultIdentifiers
	 * @noinspection PhpUnused
	 */
	public static function disableDefaultBackendIdentifiers(bool $disableDefaultIdentifiers): void {
		static::$disableDefaultIdentifiers[ Awyiss::REALM_BACKEND ] = $disableDefaultIdentifiers;
	}


	/**
	 * Disable the default Frontend identifier (PasswordIdentifier)
	 *
	 * @param bool $disableDefaultIdentifiers
	 * @noinspection PhpUnused
	 */
	public static function disableDefaultFrontendIdentifiers(bool $disableDefaultIdentifiers): void {
		static::$disableDefaultIdentifiers[ Awyiss::REALM_FRONTEND ] = $disableDefaultIdentifiers;
	}
}
