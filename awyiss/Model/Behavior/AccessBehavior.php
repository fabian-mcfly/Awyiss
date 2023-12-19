<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Awyiss\ORM\Behavior;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetDecorator;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\Query;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use ReflectionClass;
use RuntimeException;


/**
 * @todo describe everything
 *
 * Access related behavior.
 *
 * Uses the following config items:
 *
 * - `defaultAccessible` Fallback to this boolean value if no access is set. Default: `FALSE`
 * - `enabled` Should this behavior be enabled or not
 * - `failSilently` Boolean value. If `TRUE`, this behavior will not throw an exception when no access was granted. But
 * it will still return no result/stop the event
 * - `implementedEvents` An array of events this behavior will listen to.
 * Format `[EventName1 => methodName1, EventName2 => methodName2]`
 * - `implementedMethods` An array of methods that can be accessed as methods inside the table.
 * Format `[externalMethodName1 => internalMethodName1, externalMethodName2 => internalMethodName2]`
 * - `identifiers` An array of EventNames and which identifiers this behavior must check.
 * The identifiers can be a string or an array. See \Awyiss\Authorization\AccessCollection::scopeIsAccessible() how the identifier is used.
 * Format `[EventName1 => identifier1, EventName2 => [identifier2, identifier3]]`
 * - `identity` The identity used to retreive the accesses from
 * - `policyClass` The policy class that's used to check the accesses with
 * - `scope` The scope to check the accesses for
 * - `skip` Skip the access check until manually turned back on (by settings this flag to `FALSE`)
 * - `skipOnce` Skip the access check once and turning it right back on
 *
 */
class AccessBehavior extends Behavior {
	protected ?AuthorizationServiceInterface $authorizationService = NULL;
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'additionalData' => [],
		'defaultAccessible' => FALSE,
		'enabled' => TRUE,
		'failSilently' => !TRUE, //if TRUE, a ForbiddenException will be thrown for inaccessible scopes
		'implementedEvents' => [
			'Model.buildRules' => 'buildRules',
			'Model.beforeFind' => 'handleEvent',
			'Model.beforeSoftDelete' => 'handleEvent',
			'Model.beforeDelete' => 'handleEvent',
		],
		'implementedMethods' => [
			'skipAccessCheck' => 'skip',
			'skipAccessCheckOnce' => 'skipOnce',
		],
		'identifiers' => [
			'Entity.create' => 'create',
			'Entity.update' => 'update',
			'Model.beforeFind' => [['read', 'create', 'update', 'delete']],
			'Model.beforeSoftDelete' => 'delete',
			'Model.beforeDelete' => 'delete',
		],
		'identity' => NULL,
		'policyClass' => NULL,
		'scope' => NULL,
		'skip' => FALSE,
		'skipOnce' => FALSE,
	];


	/**
	 * @return array
	 *
	 * @noinspection PhpUnused
	 */
	public function getAdditionalData (): array {
		return $this->getConfig('additionalData');
	}


	/**
	 * @param array $aa_data
	 *
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function setAdditionalData (array $aa_data): static {
		$this->setConfig('additionalData', $aa_data, FALSE);

		return $this;
	}


	/**
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function resetAdditionalData (): static {
		$this->setConfig('additionalData', [], FALSE);

		return $this;
	}


	/**
	 * Sets the authorization service that's used to retreive the AuthenticationService
	 *
	 * @param \Awyiss\Authorization\AuthorizationServiceInterface $ao_authorizationService
	 *
	 * @return $this
	 */
	public function setAuthorizationService (AuthorizationServiceInterface $ao_authorizationService): static {
		$this->authorizationService = $ao_authorizationService;

		return $this;
	}


	/**
	 * Returns the currently set AuthorizationService that's used to retreive the AuthenticationService
	 *
	 * @return NULL|\Awyiss\Authorization\AuthorizationServiceInterface
	 */
	public function getAuthorizationService (): ?AuthorizationServiceInterface {
		if ( ! isset($this->authorizationService)) {
			$lo_event = $this->table()->dispatchEvent('Access.requestAuthorizationService');
			$this->authorizationService = $lo_event->getResult();
		}

		return $this->authorizationService;
	}


	/**
	 * Returns the identity set in the config
	 *
	 * @return \Awyiss\Authorization\IdentityPermissionsInterface
	 */
	public function getIdentity (): IdentityPermissionsInterface {
		$lo_identity = $this->getConfig('identity');

		if ( ! $lo_identity) {
			$lo_identity = $this->_getIdentity();
			$this->setConfig('identity', $lo_identity);
		}

		return $lo_identity;
	}


	/**
	 * Save the given identity to the config
	 *
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $ao_identity
	 *
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function setIdentity (IdentityPermissionsInterface $ao_identity): static {
		$this->setConfig('identity', $ao_identity);

		return $this;
	}


	/**
	 * Returns the name of the policy class set in the config
	 *
	 * @return NULL|string|\Awyiss\Authorization\Policy\PolicyInterface|\Awyiss\Authorization\Policy\AnonymousPolicy
	 */
	public function getPolicyClass (): string|PolicyInterface|AnonymousPolicy|NULL {
		return $this->getConfig('policyClass');
	}


	/**
	 * Saves the given value as policyClass config item
	 * If $ax_policyClass is a string, it needs to be the name of a class that implements PolicyInterface
	 *
	 * @param string|\Awyiss\Authorization\Policy\AnonymousPolicy|\Awyiss\Authorization\Policy\PolicyInterface|NULL $ax_policyClass
	 *
	 * @return $this
	 *
	 * @throws \ReflectionException
	 *
	 * @see PolicyInterface::class
	 * @see AnonymousPolicy::class
	 *
	 * @noinspection PhpUnused
	 */
	public function setPolicyClass (string|AnonymousPolicy|PolicyInterface $ax_policyClass = NULL): static {
		if (is_string($ax_policyClass)) {
			$lo_reflection = new ReflectionClass($ax_policyClass);

			if ( ! $lo_reflection->implementsInterface(PolicyInterface::class)) {
				throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ax_policyClass, PolicyInterface::class));
			}
		}

		$this->setConfig('policyClass', $ax_policyClass);

		return $this;
	}


	/**
	 * Sets the scope to check the access for
	 *
	 * @return string
	 */
	public function getScope (): string {
		$ls_scope = $this->getConfig('scope');

		if ( ! $ls_scope) {
			$ls_scope = Inflector::underscore($this->table()->getTable());
			$this->setConfig('scope', $ls_scope);
		}

		return $ls_scope;
	}


	/**
	 * Returns the currently set scope
	 *
	 * @param string $as_scope
	 *
	 * @return $this
	 */
	public function setScope (string $as_scope): static {
		$this->setConfig('scope', $as_scope);

		return $this;
	}


	/**
	 * Calling this method with `TRUE`, the access check will be skipped until turned back on by passing `FALSE`
	 *
	 * @param bool $ab_skip
	 *
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function skip (bool $ab_skip = TRUE): static {
		$this->setConfig('skip', $ab_skip);

		return $this;
	}


	/**
	 * Calling this method will skip the next access check
	 *
	 * @param bool $ab_skip
	 *
	 * @return $this
	 *
	 * @noinspection PhpUnused
	 */
	public function skipOnce (bool $ab_skip = TRUE): static {
		$this->setConfig('skipOnce', $ab_skip);

		return $this;
	}


	/**
	 * Called when building the rules for each table class
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\ORM\RulesChecker $ao_rules
	 *
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules (EventInterface $ao_event, RulesChecker $ao_rules): RulesChecker {
		if ( ! $this->getConfig('enabled')) {
			return $ao_rules;
		}

		//If a config item with the name of the event (`Entity.create`, `Entity.update`) exists, make sure it's callable.
		if ($lx_call = $this->getConfig($ao_event->getName())) {
			if ( ! is_callable($lx_call)) {
				throw new RuntimeException(sprintf('Expected option for `%s` to be `callable`, `%s` given', $ao_event->getName(), gettype($lx_call)));
			}
		}

		//Add a rule that returns TRUE or FALSE whether the entity can be created resp. updated
		$ao_rules->add(function(EntityInterface $ao_entity, array $aa_options) use ($lx_call): ?bool {
			$la_options = Hash::merge($this->getConfig(), Hash::get($aa_options, 'access'));

			//Skipping the check means the rule returns TRUE.
			if ($la_options['skip'] === TRUE || $la_options['skipOnce'] === TRUE) {
				$this->setConfig('skipOnce', FALSE);

				return TRUE;
			}

			//Call the access check, depending on whether the entity is new
			$lb_accessible = $this->handle($ao_entity->isNew() ? 'Entity.create' : 'Entity.update', $ao_entity, $la_options);

			//If the event name is a callable config item, call it and set the return value as the accessible status
			if ($lx_call) {
				$lb_accessible = call_user_func($lx_call, $ao_entity, $la_options, $this, $lb_accessible);
			}

			//TODO: check if it's possible to get defaultAccessible from AccessCollection
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
	 * General function that gets called for events.
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\ORM\Query|\Cake\Datasource\EntityInterface $ao_subject
	 * @param \ArrayObject $ao_options
	 *
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function handleEvent (EventInterface $ao_event, Query|EntityInterface $ao_subject, ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'access'));

		//Skipping the check means the event does nothing.
		if ($la_options['skip'] === TRUE || $la_options['skipOnce'] === TRUE) {
			$this->setConfig('skipOnce', FALSE);

			return;
		}

		//Call the access check, depending on whether the entity is new
		$lb_accessible = $this->handle($ao_event->getName(), $ao_subject, $la_options);

		//If the event name is a callable config item, call it and set the return value as the accessible status
		if ($lx_call = $this->getConfig($ao_event->getName())) {
			if ( ! is_callable($lx_call)) {
				throw new RuntimeException(sprintf('Expected option for `%s` to be `callable`, `%s` given', $ao_event->getName(), gettype($lx_call)));
			}

			$lb_accessible = call_user_func($lx_call, $ao_event, $ao_subject, new ArrayObject($la_options), $this, $lb_accessible);
		}

		//TODO: check if it's possible to get defaultAccessible from AccessCollection
		if ($lb_accessible === FALSE || ($lb_accessible === NULL && $this->getConfig('defaultAccessible') === FALSE)) {
			if ($la_options['failSilently'] === FALSE) {
				throw new ForbiddenException();
			}

			$ao_event->stopPropagation();
			$ao_event->setResult(FALSE);

			if ($ao_subject instanceof Query) {
				$ao_subject->setResult(new ResultSetDecorator([]));
			}
		}
	}


	/**
	 * Internal function used by `beforeFind` and `handleEvent`
	 *
	 * @param string $as_identifier
	 * @param \Cake\ORM\Query|\Cake\Datasource\EntityInterface $ao_subject
	 * @param array $aa_options
	 *
	 * @return null|bool
	 * @throws \Exception
	 */
	protected function handle (string $as_identifier, Query|EntityInterface $ao_subject, array $aa_options = []): ?bool {
		$ls_scope = $aa_options['scope'] ?? $this->table()->getTable();

		$lx_identifier = $aa_options['identifiers'][ $as_identifier ] ?? NULL;
		if ( ! is_string($lx_identifier) && ! is_array($lx_identifier)) {
			throw new RuntimeException(sprintf('The identifier for `%s` is invalid. Expected `string|array`, `%s` given', $as_identifier, gettype($lx_identifier)));
		}

		if ( ! is_array($lx_identifier)) {
			$lx_identifier = [$lx_identifier];
		}

		$la_additionalData = [
			'event' => $as_identifier,
			'subject' => $ao_subject,
		];

		return $this->scopeIsAccessible($ls_scope, NULL, $la_additionalData, ...$lx_identifier);
	}


	/**
	 * For a list of given identifiers, return TRUE or FALSE whether they're accessible inside the current scope
	 * for the current identity.
	 *
	 * See \Awyiss\Authorization\AccessCollection::scopeIsAccessible() how $ax_identifier is used.
	 *
	 * @param string|array ...$ax_identifier
	 *
	 * @return bool
	 * @throws \Exception
	 *
	 * @see \Awyiss\Authorization\AccessCollection::scopeIsAccessible()
	 *
	 * @noinspection PhpUnused
	 */
	public function isAccessible (string|array ...$ax_identifier): bool {
		$ls_scope = $this->getScope();

		return $this->scopeIsAccessible($ls_scope, NULL, NULL, ...$ax_identifier);
	}


	/**
	 * For a list of given identifiers, return TRUE or FALSE whether they're accessible inside the given scope
	 * for the given identity.
	 *
	 * See \Awyiss\Authorization\AccessCollection::scopeIsAccessible() how $ax_identifier is used.
	 *
	 * @param string $as_scope
	 * @param null|\Awyiss\Authorization\IdentityPermissionsInterface $ao_identity
	 * @param null|array $aa_additionalData
	 * @param string|array ...$ax_identifier
	 *
	 * @return null|bool
	 * @throws \Exception
	 * @see \Awyiss\Authorization\AccessCollection::scopeIsAccessible()
	 */
	public function scopeIsAccessible (string $as_scope, ?IdentityPermissionsInterface $ao_identity = NULL, array $aa_additionalData = NULL, string|array ...$ax_identifier): ?bool {
		//Get the currently assigned accesses from the identity object, resp. their access collection
		$lo_identity = $ao_identity ?? $this->getIdentity();
		$lo_accessCollection = $lo_identity->getAccess();

		$la_additionalData = $aa_additionalData ?? $this->getConfig('additionalData');

		return $lo_accessCollection->scopeIsAccessible($as_scope, $this->getPolicyClass(), $la_additionalData, ...$ax_identifier);
	}


	/**
	 * Retreive the AuthorizationServiceInterface using getAuthorizationService.
	 * Then retreive the AuthenticationServiceInterface from the AuthorizationServiceInterface
	 * Then retreive the IdentityInterface from AuthenticationServiceInterface.
	 */
	protected function _getIdentity (): IdentityPermissionsInterface {
		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getAuthorizationService();
		if ( ! $lo_authorizationService) {
			throw new RuntimeException(sprintf('Could not retreive `AuthorizationService` in `%s`.', static::class));
		}

		$lo_authenticationService = $lo_authorizationService->getAuthenticationService();
		if ( ! $lo_authenticationService) {
			throw new RuntimeException(sprintf('Object `%s` does not have an authentication service set.', get_class($lo_authorizationService)));
		}
		/** @var IdentityPermissionsInterface|User|UsersExternal $lo_identity */
		$lo_identity = $lo_authenticationService->getIdentity();
		if ( ! ($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}

		return $lo_identity;
	}
}