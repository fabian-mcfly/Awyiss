<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\PermissionOption\CallbackPermissionOption;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Utility\Inflector;
use Cake\Collection\Iterator\MapReduce;
use Cake\ORM\Query\SelectQuery;
use RuntimeException;


/**
 * Permission for the Configuration scope of the backend
 */
class ConfigurationPolicy extends AbstractPolicy {
	/**
	 * @var PermissionOptionCollection
	 */
	protected static PermissionOptionCollection $permissionOptionCollection;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @param bool|null $accessible
	 * @param mixed $access
	 * @param mixed $settings
	 * @param array $additionalData
	 * @param PermissionCollection $permissionCollection
	 * @return bool|null
	 * @throws \ReflectionException
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function callback(?bool $accessible, mixed $access, mixed $settings, array $additionalData, PermissionCollection $permissionCollection): ?bool {
		$lb_accessible = $accessible;
		//Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (!$lb_accessible) {
			return $lb_accessible;
		}

		if (!array_key_exists('scope', $additionalData)) {
			throw new RuntimeException(sprintf('CallbackPermission in `%s` requires additional data (`scope`)', static::class));
		}

		$ls_scope = $additionalData['scope'];
		if (!$ls_scope) {
			return $lb_accessible;
		}

		$ls_scope = Inflector::underscore($ls_scope);

		if (strtolower($ls_scope) !== 'system') {
			// Form elements are accessible if the user has access to the Forms scope
			if ($ls_scope === 'form_elements') {
				$ls_scope = 'forms';
			}
			// Menu entries are accessible if the user has access to the Menus scope
			elseif ($ls_scope === 'menu_entries') {
				$ls_scope = 'menus';
			}

			$lb_accessible = $permissionCollection->scopeIsAccessible($ls_scope, [], 'configure');
		}


		return $lb_accessible;
	}


	/**
	 * @param bool|null $accessible
	 * @param mixed $access
	 * @param mixed $settings
	 * @param array $additionalData
	 * @param PermissionCollection $permissionCollection
	 * @return bool|null
	 * @throws \ReflectionException
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function callbackForEntity(
		?bool $accessible,
		mixed $access,
		mixed $settings,
		array $additionalData,
		PermissionCollection $permissionCollection
	): ?bool {
		$lb_accessible = $accessible;
		//Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (!$lb_accessible) {
			return $lb_accessible;
		}

		$lo_entity = $additionalData['subject'] ?? null;
		if (empty($lo_entity) || !($lo_entity instanceof Configuration)) {
			throw new RuntimeException(
				sprintf(
					'Callback `callbackForEntity` in `%s` requires a subject of type `%s`. `%s` given.',
					static::class,
					Configuration::class,
					is_object($lo_entity) ? get_class($lo_entity) : gettype($lo_entity)
				)
			);
		}

		$ls_scope = $lo_entity->scope;
		if (strtolower($ls_scope) !== 'system') {
			$lb_accessible = $permissionCollection->scopeIsAccessible($ls_scope, [], 'configure');
		}


		return $lb_accessible;
	}


	/**
	 * @param bool|null $accessible
	 * @param mixed $access
	 * @param mixed $settings
	 * @param array $additionalData
	 * @param PermissionCollection $permissionCollection
	 * @return bool|null
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function callbackForFind(
		?bool $accessible,
		mixed $access,
		mixed $settings,
		array $additionalData,
		PermissionCollection $permissionCollection
	): ?bool {
		//Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (!$accessible) {
			return $accessible;
		}

		$lo_query = $additionalData['subject'] ?? null;
		if (empty($lo_query) || !($lo_query instanceof SelectQuery)) {
			throw new RuntimeException(
				sprintf(
					'Callback `callbackForFind` in `%s` requires a subject of type `%s`. `%s` given.',
					static::class,
					SelectQuery::class,
					is_object($lo_query) ? get_class($lo_query) : gettype($lo_query)
				)
			);
		}

		$lo_permissionCollection = $permissionCollection;
		//Apply a mapReduce call that'll remove all entities from the query, except those that are re-added using the `emit()`-method
		$lo_query->mapReduce(function (Configuration|array $entity, int $key, MapReduce $mapReduce) use ($lo_permissionCollection): void {
			if (!$entity instanceof Configuration || strtolower($entity->scope) === 'system') {
				$mapReduce->emit($entity);

				return;
			}

			static $la_checkedScopes = [];

			$ls_scope = AuthorizationService::sanitizeScope($entity->scope);

			if (!array_key_exists($ls_scope, $la_checkedScopes)) {
				$la_checkedScopes[ $ls_scope ] = $lo_permissionCollection->scopeIsAccessible($entity->scope, [], 'configure');
			}

			//If the scope is accessible, append it to the final list of results
			if ($la_checkedScopes[ $ls_scope ]) {
				$mapReduce->emit($entity);
			}
		});


		return true;
	}


	/**
	 * Creates a `PermissionOptionCollection` and four `CallbackPermission`
	 * for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
	 *
	 * @return PermissionOptionCollection
	 * @throws \Exception
	 */
	protected static function loadPermissionOptions(): PermissionOptionCollection {
		$lo_permissions = new PermissionOptionCollection(static::getScope());

		$la_callbacks = [
			'general' => static::callback(...),
			'Entity.create' => static::callbackForEntity(...),
			'Entity.update' => static::callbackForEntity(...),
			'Model.beforeFind' => static::callbackForFind(...),
			'Model.beforeSoftDelete' => static::callbackForEntity(...),
			'Model.beforeDelete' => static::callbackForEntity(...),
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
}
