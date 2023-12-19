<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\ORM\Behavior;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\Utility\Hash;
use RuntimeException;


class AccessBehavior extends Behavior  {
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'defaultAccessible' => FALSE,
		'enabled' => TRUE,
		'failSilently' => TRUE,
		'implementedEvents' => [
			'Model.buildRules' => 'buildRules',
			'Model.beforeFind' => 'handleEvent',
			//'Model.beforeCreate' => 'handleEvent',
			//'Model.beforeUpdate' => 'handleEvent',
			'Model.beforeSoftDelete' => 'handleEvent',
			'Model.beforeDelete' => 'handleEvent',
		],
		'implementedMethods' => [
			'setAuthorizationService' => 'setAuthorizationService',
			'getAuthorizationService' => 'getAuthorizationService',
			'getPolicyClass' => 'getPolicyClass',
			'setPolicyClass' => 'setPolicyClass',
			'skipAccessCheck' => 'skip',
			'skipAccessCheckOnce' => 'skipOnce',
		],
		'identifiers' => [
			'Entity.create' => 'create',
			'Entity.update' => 'update',
			'Model.beforeFind' => ['create', 'update', 'delete'],
			'Model.beforeSoftDelete' => 'delete',
			'Model.beforeDelete' => 'delete',
		],
		'policyClass' => NULL,
		'scope' => NULL,
		'skip' => FALSE,
		'skipOnce' => FALSE,
	];
	protected AuthorizationServiceInterface $authorizationService;


	/**
	 * @noinspection PhpUnused
	 */
	public function setAuthorizationService (AuthorizationServiceInterface $ao_authorizationService): self {
		$this->authorizationService = $ao_authorizationService;

		return $this;
	}


	public function getAuthorizationService (): AuthorizationServiceInterface {
		if ( ! isset($this->authorizationService)) {
			$this->table()->dispatchEvent('Model.requestAuthorizationService');
		}

		return $this->authorizationService;
	}


	public function getScope (): string {
		$ls_scope = $this->getConfig('scope');

		if ( ! $ls_scope) {
			$ls_scope = \Cake\Utility\Inflector::underscore($this->table()->getTable());
			$this->setConfig('scope', $ls_scope);
		}

		return $ls_scope;
	}


	public function setScope (string $as_scope): self {
		$this->setConfig('scope', $as_scope);

		return $this;
	}


	public function getPolicyClass (): string|AnonymousPolicy|NULL {
		return $this->getConfig('policyClass');
	}


	/**
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public function setPolicyClass (string|AnonymousPolicy|NULL $ax_policyClass): self {
		if (is_string($ax_policyClass)) {
			$lo_reflection = new \ReflectionClass($ax_policyClass);

			if ( ! $lo_reflection->implementsInterface(AnonymousPolicy::class)) {
				throw new \RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ax_policyClass, AnonymousPolicy::class));
			}
		}


		$this->setConfig('policyClass', $ax_policyClass);

		return $this;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function skip (bool $ab_skip = TRUE): self {
		$this->setConfig('skip', $ab_skip);

		return $this;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function skipOnce (bool $ab_skip = TRUE): self {
		$this->setConfig('skipOnce', $ab_skip);

		return $this;
	}


	public function buildRules (EventInterface $ao_event, RulesChecker $ao_rules): RulesChecker {
		if ( ! $this->getConfig('enabled')) {
			return $ao_rules;
		}

		$lx_call = NULL;
		if ($this->getConfig($ao_event->getName())) {
			$lx_call = $this->getConfig($ao_event->getName());
			if ( ! is_callable($lx_call)) {
				throw new RuntimeException(sprintf('Expected option for `%s` to be `callable`, `%s` given', $ao_event->getName(), gettype($lx_call)));
			}
		}

		$ao_rules->add(function(EntityInterface $ao_entity, array $aa_options) use ($lx_call): ?bool {
			$la_options = Hash::merge($this->getConfig(), Hash::get($aa_options, 'access'));

			if ($la_options['skip'] === TRUE) {
				return TRUE;
			}

			if ($this->getConfig('skipOnce')) {
				$this->setConfig('skipOnce', FALSE);
				return TRUE;
			}

			$lb_accessible = $this->handle($ao_entity->isNew() ? 'Entity.create' : 'Entity.update', $ao_entity, $la_options);

			if ($lx_call) {
				$lb_accessible = call_user_func($lx_call, $ao_entity, $la_options, $this, $lb_accessible);
			}

			if ($lb_accessible === FALSE || ($lb_accessible === NULL && $this->getConfig('defaultAccessible') === FALSE)) {
				return FALSE;
			}

			return TRUE;
		}, 'access', [
			'errorField' => '_general',
			'message' => __('::scope_not_accessible'),
		]);

		return $ao_rules;
	}

	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 */
	public function handleEvent (EventInterface $ao_event, Query|EntityInterface $ao_subject, \ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'access'));

		if ($la_options['skip'] === TRUE) {
			return;
		}

		if ($this->getConfig('skipOnce')) {
			$this->setConfig('skipOnce', FALSE);
			return;
		}

		$lb_accessible = $this->handle($ao_event->getName(), $ao_subject, $la_options);

		if ($this->getConfig($ao_event->getName())) {
			$lx_call = $this->getConfig($ao_event->getName());
			if (!is_callable($lx_call)) {
				throw new RuntimeException(sprintf('Expected option for `%s` to be `callable`, `%s` given', $ao_event->getName(), gettype($lx_call)));
			}

			$lb_accessible = call_user_func($lx_call, $ao_event, $ao_subject, $la_options, $this, $lb_accessible);
		}

		if ($lb_accessible === FALSE || ($lb_accessible === NULL && $this->getConfig('defaultAccessible') === FALSE)) {
			if ($la_options['failSilently'] === FALSE) {
				throw new \Cake\Http\Exception\ForbiddenException();
			}

			$ao_event->stopPropagation();
			$ao_event->setResult(FALSE);

			if ($ao_subject instanceof Query) {
				$ao_subject->setResult(new \Cake\Datasource\ResultSetDecorator([]));
			}
		}
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function handle (string $as_identifier, Query|EntityInterface $ao_subject, array $aa_options = []): ?bool {
		$ls_scope = $aa_options['scope'] ?? $this->table()->getTable();

		$lx_identifier = $aa_options['identifiers'][ $as_identifier ] ?? NULL;
		if (!is_string($lx_identifier) && !is_array($lx_identifier)) {
			throw new RuntimeException(sprintf('The identifier for `%s` is invalid. Expected `string|array`, `%s` given', $as_identifier, gettype($lx_identifier)));
		}

		if (!is_array($lx_identifier)) {
			$lx_identifier = [$lx_identifier];
		}

		return $this->isAccessible($ls_scope, NULL, $lx_identifier);
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection DuplicatedCode
	 */
	public function isAccessible (string $as_scope, ?IdentityPermissionsInterface $ao_identity = NULL, string|array ...$ax_identifier): ?bool {
		$ls_scope = \Cake\Utility\Inflector::underscore($as_scope);

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getAuthorizationService();

		//The policyClass set in the config has the highest priority
		$lx_policyClass = $this->getPolicyClass();
		if (!$lx_policyClass) {
			//No policyClass set in config? Try the \Awyiss\Authorization\AuthorizationService
			$lx_policyClass = $lo_authorizationService?->getPolicy($ls_scope, $this->getConfig('policiesType'));

			if (!$lx_policyClass) {
				//Still no policyClass found? Dispatch an event.
				$this->table()->dispatchEvent('Model.requestPolicyClass', [
					'authorizationService' => $lo_authorizationService,
					'scope' => $ls_scope,
				]);

				//Maybe the event handler has set a class.
				//This is my Last Resort!
				$lx_policyClass = $this->getPolicyClass();
			}
		}

		//No policy found means we cannot continue
		if ( ! $lx_policyClass) {
			return NULL;
		}

		$lo_identity = $ao_identity ?? $lo_authorizationService->getAuthenticationService()->getIdentity();


		$la_accesses = [];
		foreach ($ax_identifier as $lx_identifier) {
			$la_accesses[] = $this->getAccess($lx_policyClass, $lx_identifier, $lo_identity->getAccess()->getScope($ls_scope));
		}

		if (in_array(FALSE, $la_accesses, TRUE) || ( ! in_array(TRUE, $la_accesses, TRUE) && ! $this->getConfig('defaultAccessible', FALSE))) {
			return FALSE;
		}


		return TRUE;
	}


	/**
	 *
	 * @param string|AnonymousPolicy $ax_policyClass
	 * @param string|array $ax_identifier
	 * @param null|array $aa_access
	 *
	 * @return null|bool
	 *
	 * @throws \Exception
	 *
	 * @noinspection DuplicatedCode
	 */
	protected function getAccess (string|AnonymousPolicy $ax_policyClass, string|array $ax_identifier, ?array $aa_access): ?bool {

		/** @var PolicyInterface|AnonymousPolicy $ax_policyClass */
		if (is_string($ax_identifier)) {
			$lo_permission = is_string($ax_policyClass) ? $ax_policyClass::getPermission($ax_identifier) : $ax_policyClass->getPermission($ax_identifier);
			return $lo_permission?->isAccessible($aa_access) ?? $this->getConfig('defaultAccessible', FALSE);
		}

		$la_accesses = [];
		foreach ($ax_identifier as $ls_identifier) {
			$lo_permission = is_string($ax_policyClass) ? $ax_policyClass::getPermission($ls_identifier) : $ax_policyClass->getPermission($ls_identifier);
			$la_accesses[] = $lo_permission?->isAccessible($aa_access);
		}

		if (in_array(TRUE, $la_accesses, TRUE)) {
			return TRUE;
		}

		return $this->getConfig('defaultAccessible', FALSE);
	}
}