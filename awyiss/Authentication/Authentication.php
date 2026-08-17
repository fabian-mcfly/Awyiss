<?php

/** @noinspection PhpClassConstantAccessedViaChildClassInspection */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Authentication;


use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Authenticator\FormAuthenticator;
use Authentication\Identifier\Resolver\OrmResolver;
use Awyiss\Authentication\Authenticator\SessionAuthenticator;
use Awyiss\Authentication\Identifier\PasswordIdentifier;
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
	 * @var string
	 */
	protected string $realm;
	/**
	 * @var AuthenticationServiceInterface
	 */
	protected AuthenticationServiceInterface $service;


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

		throw new Exception(__d('Authenticator', 'unknown_authentication'));
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
					throw new Exception(__d('Authenticator', 'authenticator_name_missing'));
				}

				if (!isset($authenticator['config'])) {
					$authenticator['config'] = [];
				}
				elseif (!is_array($authenticator['config'])) {
					throw new Exception(__d('Authenticator', 'authenticator_config_not_array'));
				}
			}

			if (
				!$service
					->authenticators()
					->has($authenticator['name'])
			) {
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
		$this->addAuthenticator(Awyiss::REALM_BACKEND, SessionAuthenticator::class, [
			'fields' => [
				PasswordIdentifier::CREDENTIAL_USERNAME => 'username',
			],
			'identifier' => [
				'className' => PasswordIdentifier::class,
				'resolver' => [
					'className' => OrmResolver::class,
					/** @see \Awyiss\Model\Table\UsersTable::findActive() */
					'finder' => 'active',
				],
			],
			'identify' => function (User $user): bool {
				// Set last_login
				$checkTime = DateTime::now()
					->subMinutes(1)
				;
				if ($checkTime >= $user->lastLogin) {
					$user->set('lastLogin', DateTime::now());

					return true;
				}

				return false;
			},
			'sessionKey' => Awyiss::getRealm() . '.Auth',
		], 10);

		$this->addAuthenticator(Awyiss::REALM_BACKEND, FormAuthenticator::class, [
			'fields' => [
				PasswordIdentifier::CREDENTIAL_USERNAME => 'username',
				PasswordIdentifier::CREDENTIAL_PASSWORD => 'password',
			],
			'identifier' => [
				'className' => PasswordIdentifier::class,
				'resolver' => [
					'className' => OrmResolver::class,
					/** @see \Awyiss\Model\Table\UsersTable::findActive() */
					'finder' => 'active',
				],
			],
			'loginUrl' => $this
				->dispatchEvent('Authentication.requestLoginUrl', [], $this)
				->getResult(),
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
		$this->addAuthenticator(Awyiss::REALM_FRONTEND, SessionAuthenticator::class, [
			'fields' => [
				PasswordIdentifier::CREDENTIAL_USERNAME => 'email',
			],
			'identify' => function (Customer $customer): bool {
				// Set last_login
				$checkTime = DateTime::now()
					->subMinutes(1)
				;
				if ($checkTime >= $customer->lastLogin) {
					$customer->set('lastLogin', DateTime::now());

					return true;
				}

				return false;
			},
			'identifier' => [
				'className' => PasswordIdentifier::class,
				'fields' => [
					PasswordIdentifier::CREDENTIAL_USERNAME => 'email',
					PasswordIdentifier::CREDENTIAL_PASSWORD => 'password',
				],
				'resolver' => [
					'className' => OrmResolver::class,
					'userModel' => 'Customers',
					/** @see \Awyiss\Model\Table\CustomersTable::findActive() */
					'finder' => 'active',
				],
			],
			'sessionKey' => Awyiss::getRealm() . '.Auth',
		], 10);

		$this->addAuthenticator(Awyiss::REALM_FRONTEND, FormAuthenticator::class, [
			'fields' => [
				PasswordIdentifier::CREDENTIAL_USERNAME => 'email',
				PasswordIdentifier::CREDENTIAL_PASSWORD => 'password',
			],
			'identifier' => [
				'className' => PasswordIdentifier::class,
				'fields' => [
					PasswordIdentifier::CREDENTIAL_USERNAME => 'email',
					PasswordIdentifier::CREDENTIAL_PASSWORD => 'password',
				],
				'resolver' => [
					'className' => OrmResolver::class,
					'userModel' => 'Customers',
					/** @see \Awyiss\Model\Table\CustomersTable::findActive() */
					'finder' => 'active',
				],
			],
			'loginUrl' => $this
				->dispatchEvent('Authentication.requestLoginUrl', [], $this)
				->getResult(),
		], 20);
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
		$service = new AuthenticationService([
			'identityAttribute' => Awyiss::REALM_BACKEND . 'Identity',
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
		$service = new AuthenticationService([
			'identityAttribute' => Awyiss::REALM_FRONTEND . 'Identity',
			'unauthenticatedRedirect' => null,
			'queryParam' => null,
		]);

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
}
