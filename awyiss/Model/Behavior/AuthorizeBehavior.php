<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\Permission\Permission;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Awyiss\Model\Table;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetDecorator;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Log\LogTrait;
use Cake\ORM\Association;
use Cake\ORM\Query\DeleteQuery;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * Authorization related behavior.
 *
 * Uses the following config items:
 *
 * - `enabled` Should this behavior be enabled or not
 * - `failSilently` Boolean value. If `TRUE`, this behavior will not throw an exception when no access was granted. But
 * it will still return no result/stop the event
 * - `implementedEvents` An array of events this behavior will listen to.
 * Format `[EventName1 => methodName1, EventName2 => methodName2]`
 * - `implementedMethods` An array of methods that can be accessed as methods inside the table.
 * Format `[externalMethodName1 => internalMethodName1, externalMethodName2 => internalMethodName2]`
 * - `identifiers` An array of EventNames and which identifiers this behavior must check.
 * The identifiers can be a string or an array. See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how the identifier is used.
 * Format `[EventName1 => identifier1, EventName2 => [identifier2, identifier3]]`
 * - `identity` The identity used to retreive the permissions from
 * - `scope` The scope to check the permissions for
 * - `skip` Skip the authorization check until manually turned back on (by settings this flag to `FALSE`)
 * - `skipOnce` Skip the authorization check once and turning it right back on
 *
 */
class AuthorizeBehavior extends Behavior {
	use LogTrait;


	protected ?AuthorizationServiceInterface $authorizationService = NULL;
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'additionalData' => [],
		'enabled' => TRUE,
		'failSilently' => !TRUE, //if TRUE, a ForbiddenException will be thrown for inaccessible scopes
		'implementedEvents' => [
			'Model.buildRules' => 'buildRules',
			'Model.beforeFind' => 'handleEvent',
			'Model.beforeSoftDelete' => 'handleEvent',
			'Model.beforeDelete' => 'handleEvent',
		],
		'implementedMethods' => [
			'skipAuthorizationCheck' => 'skip',
			'skipAuthorizationCheckOnce' => 'skipOnce',
		],
		'identifiers' => [
			'Entity.create' => 'create',
			'Entity.update' => 'update',
			'Model.beforeFind' => [['read', 'create', 'update', 'delete']],
			'Model.beforeSoftDelete' => 'delete',
			'Model.beforeDelete' => 'delete',
		],
		'identity' => NULL,
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
	 * @param AuthorizationServiceInterface $ao_authorizationService
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
	 * @return NULL|AuthorizationServiceInterface
	 */
	public function getAuthorizationService (): ?AuthorizationServiceInterface {
		if ( ! isset($this->authorizationService)) {
			$lo_event = $this->table()->dispatchEvent('Authorization.requestAuthorizationService');
			$this->authorizationService = $lo_event->getResult();
		}

		return $this->authorizationService;
	}


	/**
	 * Returns the identity set in the config
	 *
	 * @return null|IdentityPermissionsInterface
	 */
	public function getIdentity (): ?IdentityPermissionsInterface {
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
	 * @param IdentityPermissionsInterface $ao_identity
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
	 * Sets the scope to check the authorization for
	 *
	 * @return string
	 */
	public function getScope (): string {
		$ls_scope = $this->getConfig('scope');

		if ( ! $ls_scope) {
			$ls_scope = Inflector::pluralize(Inflector::underscore($this->table()->getTable()));
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
		$this->setConfig('scope', Inflector::pluralize(Inflector::underscore($as_scope)));

		return $this;
	}


	/**
	 * Calling this method with `TRUE`, the authorization check will be skipped until turned back on by passing `FALSE`
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
	 * Calling this method will skip the next authorization check
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
	 * @param EventInterface $ao_event
	 * @param RulesChecker   $ao_rules
	 *
	 * @return RulesChecker
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
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$ao_rules->add(function(EntityInterface $ao_entity, array $aa_options) use ($lx_call): ?bool {
			$la_options = Hash::merge($this->getConfig(), Hash::get($aa_options, 'authorize'));

			//Skipping the check means the rule returns TRUE.
			if ($la_options['skip'] === TRUE || $la_options['skipOnce'] === TRUE) {
				$this->setConfig('skipOnce', FALSE);

				return TRUE;
			}

			//Call the authorization check, depending on whether the entity is new
			$lb_accessible = $this->handle($ao_entity->isNew() ? 'Entity.create' : 'Entity.update', $ao_entity, $la_options);

			//If the event name is a callable config item, call it and set the return value as the accessible status
			if ($lx_call) {
				$lb_accessible = call_user_func($lx_call, $ao_entity, $la_options, $this, $lb_accessible);
			}

			if ($lb_accessible === FALSE || ($lb_accessible === NULL && Permission::DEFAULT_PERMISSION === FALSE)) {
				return FALSE;
			}

			return TRUE;
		}, 'scopeAccessible', [
			'errorField' => '_general',
			'message' => __dfx($this->table()->getI18nDomain(), 'validation', $this->table()->getTable(), 'error_scope_accessible'),
		]);

		return $ao_rules;
	}


	/**
	 * General function that gets called for events.
	 *
	 * @param EventInterface $ao_event
	 * @param SelectQuery|DeleteQuery|EntityInterface $ao_subject
	 * @param ArrayObject $ao_options
	 *
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function handleEvent (EventInterface $ao_event, SelectQuery|DeleteQuery|EntityInterface $ao_subject, ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'authorize'));

		//Skipping the check means the event does nothing.
		if ($la_options['skip'] === TRUE || $la_options['skipOnce'] === TRUE) {
			$this->setConfig('skipOnce', FALSE);

			if (!($ao_subject instanceof SelectQuery)) {
				return;
			}

			//Disable
			foreach ($ao_subject->getRepository()->associations() AS $lo_association) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				if ($lo_association->type() !== Association::MANY_TO_MANY || ! $lo_association->hasThrough()) {
					continue;
				}

				/**
				 * @var BelongsToMany $lo_association
				 * @var Table $lo_junctionTable
				 */
				$lo_junctionTable = $lo_association->junction();
				if (! $lo_junctionTable->hasBehavior('Authorize')) {
					return;
				}

				//Disable the authorization check for junction tables once
				$lo_junctionTable->skipAuthorizationCheckOnce();
			}

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

		if ($lb_accessible === FALSE || ($lb_accessible === NULL && Permission::DEFAULT_PERMISSION === FALSE)) {
			if ($ao_subject instanceof SelectQuery || $ao_subject instanceof DeleteQuery) {
				$this->log(sprintf('Permission denied in event `%s` for table `%s`. Scope: `%s`.' . PHP_EOL . 'Query: %s.' . PHP_EOL . 'Params: %s',
					$ao_event->getName(),
					$ao_subject->getRepository()->getTable(),
					$this->getScope(),
					$ao_subject->sql(),
					var_export($ao_subject->getValueBinder()->bindings(), TRUE)),
					'error');
			}
			else {
				$this->log(sprintf('Permission denied in event `%s`', $ao_event->getName()), 'error');
			}

			if ($la_options['failSilently'] === FALSE) {
				throw new ForbiddenException(sprintf('Permission denied in event `%s` for table `%s`.', $ao_event->getName(), $ao_subject->getRepository()->getTable()));
			}

			$ao_event->stopPropagation();
			$ao_event->setResult(FALSE);

			if ($ao_subject instanceof SelectQuery || $ao_subject instanceof DeleteQuery) {
				$ao_subject->setResult(new ResultSetDecorator([]));
			}

		}
	}


	/**
	 * Internal function used by `beforeFind` and `handleEvent`
	 *
	 * @param string $as_identifier
	 * @param SelectQuery|DeleteQuery|EntityInterface $ao_subject
	 * @param array $aa_options
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function handle (string $as_identifier, SelectQuery|DeleteQuery|EntityInterface $ao_subject, array $aa_options = []): bool {
		$ls_scope = $aa_options['scope'] ?? $this->getScope();

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

		//if (!$this->scopeIsAccessible($ls_scope, $la_additionalData, ...$lx_identifier)) {
			//dd(__LINE__, __FILE__, $ao_subject, $ls_scope, debug_backtrace(2));
		//}

		return $this->scopeIsAccessible($ls_scope, $la_additionalData, ...$lx_identifier);
	}


	/**
	 * For a list of given identifiers, return TRUE or FALSE whether they're accessible inside the current scope
	 * for the current identity.
	 *
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $ax_identifier is used.
	 *
	 * @param string|array ...$ax_identifier
	 *
	 * @return bool
	 * @throws \Exception
	 *
	 * @see \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible()
	 *
	 * @noinspection PhpUnused
	 */
	public function isAccessible (string|array ...$ax_identifier): bool {
		return $this->scopeIsAccessible($this->getScope(), NULL, ...$ax_identifier);
	}


	/**
	 * For a list of given identifiers, return TRUE or FALSE whether they're accessible inside the given scope
	 * for the given identity.
	 *
	 * See \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible() how $ax_identifier is used.
	 *
	 * @param string       $as_scope
	 * @param null|array   $aa_additionalData
	 * @param string|array ...$ax_identifier
	 *
	 * @return bool
	 * @throws \ReflectionException
	 * @see \Awyiss\Authorization\Permission\PermissionCollection::scopeIsAccessible()
	 */
	public function scopeIsAccessible (string $as_scope, ?array $aa_additionalData = NULL, string|array ...$ax_identifier): bool {
		//Get the currently assigned permissions from the identity object, resp. their permission collection
		$lo_identity = $this->getIdentity();
		$lo_permissionCollection = $lo_identity?->getPermissionCollection();

		if ( ! $lo_permissionCollection) {
			return FALSE;
		}

		$la_additionalData = $aa_additionalData ?? $this->getConfig('additionalData');

		return $lo_permissionCollection->scopeIsAccessible($as_scope, $la_additionalData, ...$ax_identifier);
	}


	/**
	 * Retreive the AuthorizationServiceInterface using getAuthorizationService.
	 * Then retreive the AuthenticationServiceInterface from the AuthorizationServiceInterface
	 * Then retreive the IdentityInterface from AuthenticationServiceInterface.
	 */
	protected function _getIdentity (): ?IdentityPermissionsInterface {
		/** @var AuthorizationService $lo_authorizationService */
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
		if ($lo_identity && ! ($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}

		return $lo_identity;
	}
}
