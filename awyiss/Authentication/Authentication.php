<?php declare(strict_types=1);


namespace Awyiss\Authentication;


use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Authenticator\FormAuthenticator;
use Authentication\Identifier\IdentifierInterface;
use Authentication\Identifier\PasswordIdentifier;
use Authentication\Identifier\Resolver\OrmResolver;
use Awyiss\Authentication\Authenticator\SessionAuthenticator;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Cake\Event\EventDispatcherTrait;
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;
use Exception;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Authentication class that registers and provides access to instances of
 * - AuthenticatorInterface
 * - IdentifierInterface
 */
class Authentication implements AuthenticationServiceProviderInterface {
	use EventDispatcherTrait;


	protected AuthenticationServiceInterface $service;
	protected string $type;
	protected static bool $disableDefaultAuthenticators = FALSE;
	protected static bool $disableDefaultIdentifiers = FALSE;
	protected static array $authenticators = [];
	protected static array $identifiers = [];


	/**
	 * @param string $as_type
	 */
	public function __construct (string $as_type) {
		$this->type = $as_type;
	}


	/**
	 * Registers an Authenticators to the list of available Authenticators.
	 *
	 * @param string|callable $ax_authenticator
	 * @param array $aa_config
	 * @param int $ai_priority
	 */
	public static function addAuthenticator (string|callable $ax_authenticator, array $aa_config = [], int $ai_priority = 100): void {
		if (is_string($ax_authenticator)) {
			static::$authenticators[] = [
				'authenticator' => [
					'name' => $ax_authenticator,
					'config' => $aa_config,
				],
				'priority' => $ai_priority,
			];
		}
		else {
			static::$authenticators[] = [
				'authenticator' => $ax_authenticator,
				'priority' => $ai_priority,
			];
		}
	}


	/**
	 * Registers default Authenticators if not disabled, sorts all Authenticators by priority and adds them to the `AuthenticationServiceInterface`
	 *
	 * @param \Authentication\AuthenticationServiceInterface $ao_service
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request
	 *
	 * @throws \Exception
	 *
	 * @see \Authentication\AuthenticationServiceInterface::loadAuthenticator()
	 */
	protected function loadAuthenticators (AuthenticationServiceInterface $ao_service, ServerRequestInterface $ao_request): void {
		if ( ! static::$disableDefaultAuthenticators) {
			$this->addDefaultAuthenticators($ao_service, $ao_request);
		}

		$la_authenticators = static::$authenticators;
		usort($la_authenticators, function($aa_a, $aa_b) {
			return $aa_a['priority'] <=> $aa_b['priority'];
		});

		foreach ($la_authenticators as $la_authenticator) {
			$lx_authenticator = $la_authenticator['authenticator'];

			/*
			 * If $la_authenticator is not callable, the `addAuthenticator`-method set the `name` and `config` keys
			 * If it's a callable, we need to check those keys here.
			 */
			if (is_callable($lx_authenticator)) {
				$lx_authenticator = $lx_authenticator();
				if ( ! isset($lx_authenticator['name'])) {
					throw new Exception(__('::authenticator_name_missing'));
				}

				if ( ! isset($lx_authenticator['config'])) {
					$lx_authenticator['config'] = [];
				}

				if ( ! is_array($lx_authenticator['config'])) {
					throw new Exception(__('::authenticator_config_not_array'));
				}
			}

			$ao_service->loadAuthenticator($lx_authenticator['name'], $lx_authenticator['config']);
		}
	}


	/**
	 * Register the default authenticators for Session and Form
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \Exception
	 */
	protected function addDefaultAuthenticators (AuthenticationServiceInterface $ao_service, ServerRequestInterface $ao_request): void {
		$this->addAuthenticator(SessionAuthenticator::class, [
			'identify' => function($lx_user) {
				if ($lx_user instanceof User || $lx_user instanceof UsersExternal) {
					//Set last_login
					$lo_check = FrozenTime::now()->subMinutes(10);
					if ($lo_check > $lx_user->last_login) {
						$lx_user->set('last_login', FrozenTime::now());

						if ($lx_user instanceof UsersExternal) {
							return [
								'id' => $lx_user->id,
								'provider_id' => $lx_user->provider_id,
								'username' => $lx_user->username,
							];
						}

						return TRUE;
					}
				}

				return FALSE;
			},
		], 10);

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $ao_request->getAttribute('locale');
		$ls_lang = $lo_locale->getLanguageFromUrl(TRUE)?->shortcode ?? NULL;

		$this->addAuthenticator(FormAuthenticator::class, [
			'fields' => [
				IdentifierInterface::CREDENTIAL_USERNAME => 'username',
				IdentifierInterface::CREDENTIAL_PASSWORD => 'password',
			],
			'loginUrl' => $this->dispatchEvent('Authentication.requestLoginUrl', [
				'lang' => $ls_lang,
			], $this)->getResult(),
		], 20);
	}


	/**
	 * Disable the default authenticators for Session and Form
	 *
	 * @param bool $ab_disableDefaultAuthenticators
	 *
	 * @noinspection PhpUnused
	 */
	public static function disableDefaultAuthenticators (bool $ab_disableDefaultAuthenticators): void {
		static::$disableDefaultAuthenticators = $ab_disableDefaultAuthenticators;
	}


	/**
	 * Registers an identifier
	 *
	 * @param string|callable $ax_identifier
	 * @param array $aa_config
	 * @param int $ai_priority
	 */
	public static function addIdentifier (string|callable $ax_identifier, array $aa_config = [], int $ai_priority = 100): void {
		if (is_string($ax_identifier)) {
			static::$identifiers[] = [
				'identifier' => [
					'name' => $ax_identifier,
					'config' => $aa_config,
				],
				'priority' => $ai_priority,
			];
		}
		else {
			static::$identifiers[] = [
				'identifier' => $ax_identifier,
				'priority' => $ai_priority,
			];
		}
	}


	/**
	 * Registers the default identifiers if not disabled, sorts all Identifiers by priority and adds them to the `AuthenticationServiceInterface`
	 *
	 * @param \Authentication\AuthenticationServiceInterface $ao_service
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request
	 *
	 * @throws \Exception
	 */
	protected function loadIdentifiers (AuthenticationServiceInterface $ao_service, ServerRequestInterface $ao_request): void {
		if ( ! static::$disableDefaultIdentifiers) {
			$this->addDefaultIdentifiers($ao_service, $ao_request);
		}

		$la_identifiers = static::$identifiers;
		usort($la_identifiers, function($aa_a, $aa_b) {
			return $aa_a['priority'] <=> $aa_b['priority'];
		});

		foreach ($la_identifiers as $la_identifier) {
			$lx_identifier = $la_identifier['identifier'];

			/*
			 * If $lx_identifier is not callable, the `addIdentifier`-method set the `name` and `config` keys
			 * If it's a callable, we need to check those keys here.
			 */
			if (is_callable($lx_identifier)) {
				$lx_identifier = $lx_identifier();
				if ( ! isset($lx_identifier['name'])) {
					throw new Exception(__('::identifier_name_missing'));
				}

				if ( ! isset($lx_identifier['config'])) {
					$lx_identifier['config'] = [];
				}
				if ( ! is_array($lx_identifier['config'])) {
					throw new Exception(__('::identifier_config_not_array'));
				}
			}

			$ao_service->loadIdentifier($lx_identifier['name'], $lx_identifier['config']);
		}
	}


	/**
	 * Register the default identifiers for backend users
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function addDefaultIdentifiers (AuthenticationServiceInterface $ao_service, ServerRequestInterface $ao_request): void {
		$this->addIdentifier(PasswordIdentifier::class, [
			'resolver' => [
				'className' => OrmResolver::class,
				'finder' => ['active' => ['authorization' => ['skip' => TRUE]]],
			],
		]);
	}


	/**
	 * Disable the default identifier (PasswordIdentifier)
	 *
	 * @param bool $ab_disableDefaultIdentifiers
	 *
	 * @noinspection PhpUnused
	 */
	public static function disableDefaultIdentifiers (bool $ab_disableDefaultIdentifiers): void {
		static::$disableDefaultIdentifiers = $ab_disableDefaultIdentifiers;
	}


	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request
	 *
	 * @return \Authentication\AuthenticationServiceInterface
	 * @throws \Exception
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getAuthenticationService (ServerRequestInterface $ao_request): AuthenticationServiceInterface {
		/*if (!$ao_request && ! $this->service) {
			throw new \RuntimeException(sprintf('Cannot retrieve authentication service without a request object in `%s`', static::class));
		}*/

		if ($this->type === 'backend') {
			if ( ! isset($this->service)) {
				$this->service = $this->getBackendAuthenticationService($ao_request);
			}

			return $this->service;
		}

		throw new Exception(__('::unknown_authentication'));
	}


	/**
	 * Returns a backend-specific AuthenticationServiceInterface
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request
	 *
	 * @return \Authentication\AuthenticationServiceInterface
	 * @throws \Exception
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function getBackendAuthenticationService (ServerRequestInterface $ao_request): AuthenticationServiceInterface {
		$lo_service = new AuthenticationService();

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $ao_request->getAttribute('locale');
		$ls_lang = $lo_locale->getLanguageFromUrl(TRUE)?->shortcode ?? NULL;

		//$lb_isLogoutPage = strtolower(Router::getRequest()->getParam('controller') . '/' . Router::getRequest()->getParam('action')) === 'users/logout';
		// Define where users should be redirected to when they are not authenticated
		$lo_service->setConfig([
			'unauthenticatedRedirect' => Router::url([
				'_name' => 'backend',
				'lang' => $ls_lang,
				'controller' => 'Users',
				'action' => 'login',
			]),
			/*'unauthenticatedRedirect' => '/backend/de/users/login/',*/ //'queryParam' => $lb_isLogoutPage ? NULL : 'redirect',
			'queryParam' => NULL,
		]);

		$this->loadAuthenticators($lo_service, $ao_request);
		$this->loadIdentifiers($lo_service, $ao_request);

		return $lo_service;
	}
}