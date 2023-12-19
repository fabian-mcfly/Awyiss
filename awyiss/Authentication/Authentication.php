<?php

declare(strict_types=1);


namespace Awyiss\Authentication;


use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Identifier\IdentifierInterface;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;


final class Authentication implements AuthenticationServiceProviderInterface {
	private string $ls_type;
	private static bool $lb_disableDefaultAuthenticators = FALSE;
	private static bool $lb_disableDefaultIdentifiers = FALSE;
	private static array $la_authenticators = [];
	private static array $la_identifiers = [];


	public function __construct (string $as_type) {
		$this->ls_type = $as_type;
	}


	/**
	 * @param string|callable $ax_authenticator
	 * @param array $aa_config
	 * @param int $ai_priority
	 */
	public static function addAuthenticator (string|callable $ax_authenticator, array $aa_config = [], int $ai_priority = 100): void {
		if (is_string($ax_authenticator)) {
			self::$la_authenticators[] = [
				'authenticator' => [
					'name' => $ax_authenticator,
					'config' => $aa_config,
				],
				'priority' => $ai_priority,
			];
		}
		else {
			self::$la_authenticators[] = [
				'authenticator' => $ax_authenticator,
				'priority' => $ai_priority,
			];
		}
	}


	/**
	 * @param \Authentication\AuthenticationServiceInterface $ao_servce
	 *
	 * @throws \Exception
	 */
	private function loadAuthenticators (AuthenticationServiceInterface $ao_servce): void {
		if ( ! self::$lb_disableDefaultAuthenticators) {
			$this->addDefaultAuthenticators();
		}

		$la_authenticators = self::$la_authenticators;
		usort($la_authenticators, function($aa_a, $aa_b) {
			return $aa_a['priority'] <=> $aa_b['priority'];
		});

		foreach ($la_authenticators as $la_authenticator) {
			$lx_authenticator = $la_authenticator['authenticator'];
			if (is_callable($lx_authenticator)) {
				$lx_authenticator = $lx_authenticator();
				if ( ! isset($lx_authenticator['name'])) {
					throw new \Exception(__('::authenticator_name_missing'));
				}

				if ( ! isset($lx_authenticator['config'])) {
					$lx_authenticator['config'] = [];
				}
				if ( ! is_array($lx_authenticator['config'])) {
					throw new \Exception(__('::authenticator_config_not_array'));
				}
			}

			$ao_servce->loadAuthenticator($lx_authenticator['name'], $lx_authenticator['config']);
		}
	}


	/**
	 * Register the default authenticators for Session and Form
	 */
	private function addDefaultAuthenticators (): void {
		$this->addAuthenticator(\Awyiss\Authentication\Authenticator\SessionAuthenticator::class, [
			'identify' => function($lx_user) {
				if (is_object($lx_user) && ($lx_user instanceof \Awyiss\Model\Entity\User || $lx_user instanceof \Awyiss\Model\Entity\UsersExternal)) {
					//Set last_login
					$lo_check = \Cake\I18n\Time::now()->subMinutes(10);
					if ($lo_check > $lx_user->last_login) {
						$lx_user->set('last_login', \Cake\I18n\Time::now());

						if ($lx_user instanceof \Awyiss\Model\Entity\UsersExternal) {
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

		$this->addAuthenticator(\Authentication\Authenticator\FormAuthenticator::class, [
			'fields' => [
				IdentifierInterface::CREDENTIAL_USERNAME => 'username',
				IdentifierInterface::CREDENTIAL_PASSWORD => 'password',
			],
			'loginUrl' => Router::url([
				'_name' => 'backend',
				'controller' => 'Users',
				'action' => 'login',
			]),
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
		self::$lb_disableDefaultAuthenticators = $ab_disableDefaultAuthenticators;
	}


	/**
	 * @param string|callable $ax_identifier
	 * @param array $aa_config
	 * @param int $ai_priority
	 */
	public static function addIdentifier (string|callable $ax_identifier, array $aa_config = [], int $ai_priority = 100): void {
		if (is_string($ax_identifier)) {
			self::$la_identifiers[] = [
				'identifier' => [
					'name' => $ax_identifier,
					'config' => $aa_config,
				],
				'priority' => $ai_priority,
			];
		}
		else {
			self::$la_identifiers[] = [
				'identifier' => $ax_identifier,
				'priority' => $ai_priority,
			];
		}
	}


	/**
	 * @param \Authentication\AuthenticationServiceInterface $ao_servce
	 *
	 * @throws \Exception
	 */
	private function loadIdentifiers (AuthenticationServiceInterface $ao_servce): void {
		if ( ! self::$lb_disableDefaultIdentifiers) {
			$this->addDefaultIdentifiers();
		}

		$la_identifiers = self::$la_identifiers;
		usort($la_identifiers, function($aa_a, $aa_b) {
			return $aa_a['priority'] <=> $aa_b['priority'];
		});

		foreach ($la_identifiers as $la_identifier) {
			$lx_identifier = $la_identifier['identifier'];
			if (is_callable($lx_identifier)) {
				$lx_identifier = $lx_identifier();
				if ( ! isset($lx_identifier['name'])) {
					throw new \Exception(__('::identifier_name_missing'));
				}

				if ( ! isset($lx_identifier['config'])) {
					$lx_identifier['config'] = [];
				}
				if ( ! is_array($lx_identifier['config'])) {
					throw new \Exception(__('::identifier_config_not_array'));
				}
			}

			$ao_servce->loadIdentifier($lx_identifier['name'], $lx_identifier['config']);
		}
	}


	/**
	 * Register the default identifiers for backend users
	 */
	private function addDefaultIdentifiers (): void {
		$this->addIdentifier(\Authentication\Identifier\PasswordIdentifier::class, [
			'resolver' => [
				'className' => \Authentication\Identifier\Resolver\OrmResolver::class,
				'finder' => 'active',
			],
		]);
	}


	/**
	 * Disable the default identifiers for Session and Form
	 *
	 * @param bool $ab_disableDefaultIdentifiers
	 */
	public static function disableDefaultIdentifiers (bool $ab_disableDefaultIdentifiers): void {
		self::$lb_disableDefaultIdentifiers = $ab_disableDefaultIdentifiers;
	}


	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request
	 *
	 * @return \Authentication\AuthenticationServiceInterface
	 * @throws \Exception
	 */
	public function getAuthenticationService (ServerRequestInterface $ao_request): AuthenticationServiceInterface {
		if ($this->ls_type === 'Backend') {
			return $this->getBackendAuthenticationService($ao_request);
		}

		throw new \Exception(__('::unknown_authentication'));
	}


	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request
	 *
	 * @return \Authentication\AuthenticationServiceInterface
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function getBackendAuthenticationService (ServerRequestInterface $ao_request): AuthenticationServiceInterface {
		$lo_service = new \Awyiss\Authentication\AuthenticationService();

		$lb_isLogoutPage = Router::getRequest()->getParam('controller') . '/' . Router::getRequest()->getParam('action') === 'Users/logout';
		// Define where users should be redirected to when they are not authenticated
		$lo_service->setConfig([
			'unauthenticatedRedirect' => Router::url([
				'_name' => 'backend',
				'controller' => 'Users',
				'action' => 'login',
			]),
			'queryParam' => $lb_isLogoutPage ? NULL : 'redirect',
		]);

		$this->loadAuthenticators($lo_service);
		$this->loadIdentifiers($lo_service);

		return $lo_service;
	}
}