<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\PermissionOption\CallbackPermissionOption;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;
use Awyiss\Model\Entity\Configuration;
use Cake\Collection\Iterator\MapReduce;
use Cake\ORM\Query;
use RuntimeException;


/**
 * Permission for the Configuration scope of the backend
 */
class ConfigurationPolicy extends AbstractPolicy {
	protected static PermissionOptionCollection $permissionOptionCollection;
	protected static string $scope;

	/**
	 * Creates a `PermissionOptionCollection` and four `CallbackPermission`
	 * for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
	 *
	 * @return \Awyiss\Authorization\PermissionOption\PermissionOptionCollection
	 * @throws \Exception
	 */
	protected static function loadPermissionOptions (): PermissionOptionCollection {
		$lo_permissions = new PermissionOptionCollection(static::getScope());

		$la_callbacks = [
			'general' => [static::class, 'callback'],
			'Entity.create' => [static::class, 'callbackForEntity'],
			'Entity.update' => [static::class, 'callbackForEntity'],
			'Model.beforeFind' => [static::class, 'callbackForFind'],
			'Model.beforeSoftDelete' => [static::class, 'callbackForEntity'],
			'Model.beforeDelete' => [static::class, 'callbackForEntity'],
		];

		$lo_permissions->load('read', [
			'className' => CallbackPermissionOption::class,
			'callbacks' => $la_callbacks,
		]);

		$lo_permissions->load('create', [
			'className' => CallbackPermissionOption::class,
			'callbacks' => $la_callbacks,
		]);

		$lo_permissions->load('update', [
			'className' => CallbackPermissionOption::class,
			'callbacks' => $la_callbacks,
		]);

		$lo_permissions->load('delete', [
			'className' => CallbackPermissionOption::class,
			'callbacks' => $la_callbacks,
		]);

		return $lo_permissions;
	}


	/**
	 * @param null|bool $ab_accessible
	 * @param array $aa_access
	 * @param array $aa_additionalData
	 * @param \Awyiss\Authorization\Permission\PermissionCollection $ao_permissionCollection
	 *
	 * @return null|bool
	 * @throws \Exception
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function callback (?bool $ab_accessible, mixed $ax_access, mixed $ax_settings, array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool {
		$lb_accessible = $ab_accessible;
		//Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (! $lb_accessible) {
			return $lb_accessible;
		}

		if ( ! array_key_exists('scope', $aa_additionalData)) {
			throw new RuntimeException(sprintf('CallbackPermission in `%s` requires additional data (`scope`)', static::class));
		}


		$ls_scope = $aa_additionalData['scope'];
		if ( ! $ls_scope) {
			return $lb_accessible;
		}

		if ($ls_scope !== 'system') {
			$lb_accessible = $ao_permissionCollection->scopeIsAccessible($ls_scope, [], 'configure');
		}

		return $lb_accessible;
	}


	/**
	 * @param null|bool $ab_accessible
	 * @param array $aa_access
	 * @param array $aa_additionalData
	 * @param \Awyiss\Authorization\Permission\PermissionCollection $ao_permissionCollection
	 *
	 * @return null|bool
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function callbackForEntity (?bool $ab_accessible, mixed $ax_access, mixed $ax_settings, array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool {
		$lb_accessible = $ab_accessible;
		//Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (! $lb_accessible) {
			return $lb_accessible;
		}

		$lo_entity = $aa_additionalData['subject'] ?? NULL;
		if (empty($lo_entity) || ! ($lo_entity instanceof Configuration)) {
			throw new RuntimeException(sprintf('Callback `callbackForEntity` in `%s` requires a subject of type `%s`. `%s` given.', static::class, Configuration::class, is_object($lo_entity) ? get_class($lo_entity) : gettype($lo_entity)));
		}

		$ls_scope = $lo_entity->scope;
		if ($ls_scope !== 'system') {
			$lb_accessible = $ao_permissionCollection->scopeIsAccessible($ls_scope, [], 'configure');
		}

		return $lb_accessible;
	}


	/**
	 * @param null|bool $ab_accessible
	 * @param array $aa_access
	 * @param array $aa_additionalData
	 * @param \Awyiss\Authorization\Permission\PermissionCollection $ao_permissionCollection
	 *
	 * @return null|bool
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function callbackForFind (?bool $ab_accessible, mixed $ax_access, mixed $ax_settings, array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool {
		//Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (! $ab_accessible) {
			return $ab_accessible;
		}

		$lo_query = $aa_additionalData['subject'] ?? NULL;
		if (empty($lo_query) || ! ($lo_query instanceof Query)) {
			throw new RuntimeException(sprintf('Callback `callbackForFind` in `%s` requires a subject of type `%s`. `%s` given.', static::class, Query::class, is_object($lo_query) ? get_class($lo_query) : gettype($lo_query)));
		}

		//Apply a mapReduce call that'll remove all entities from the query, except those that are re-added using the `emit()`-method
		$lo_query->mapReduce(function(Configuration|array $ao_entity, int $ai_key, MapReduce $ao_mapReduce) use ($ao_permissionCollection) {
			if ( ! $ao_entity instanceof Configuration || $ao_entity->scope === 'system') {
				$ao_mapReduce->emit($ao_entity);
				return;
			}

			static $la_checkedScopes = [];

			if ( ! array_key_exists($ao_entity->scope, $la_checkedScopes)) {
				$la_checkedScopes[ $ao_entity->scope ] = $ao_permissionCollection->scopeIsAccessible($ao_entity->scope, [], 'configure');
			}

			//If the scope 'system' or if it's accessible, append it to the final list of results
			if ($la_checkedScopes[ $ao_entity->scope ]) {
				$ao_mapReduce->emit($ao_entity);
			}
		});

		return TRUE;
	}
}