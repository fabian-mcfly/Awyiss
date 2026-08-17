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
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function callback(
		?bool $accessible,
		mixed $access,
		mixed $settings,
		array $additionalData,
		PermissionCollection $permissionCollection
	): ?bool {
		// Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (!$accessible) {
			return $accessible;
		}

		if (!array_key_exists('scope', $additionalData)) {
			throw new RuntimeException(sprintf('CallbackPermission in `%s` requires additional data (`scope`)', static::class));
		}

		if (!$additionalData['scope']) {
			return $accessible;
		}

		$scope = Inflector::camelize($additionalData['scope']);

		if ($scope !== 'System') {
			// Form elements are accessible if the user has access to the Forms scope
			if ($scope === 'FormElements') {
				$scope = 'Forms';
			}
			// Menu entries are accessible if the user has access to the Menus scope
			elseif ($scope === 'MenuEntries') {
				$scope = 'Menus';
			}

			$accessible = $permissionCollection->scopeIsAccessible($scope, [], 'configure');
		}


		return $accessible;
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
	public static function callbackForEntity(
		?bool $accessible,
		mixed $access,
		mixed $settings,
		array $additionalData,
		PermissionCollection $permissionCollection
	): ?bool {
		// Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (!$accessible) {
			return $accessible;
		}

		$entity = $additionalData['subject'] ?? null;
		if (!$entity instanceof Configuration) {
			throw new RuntimeException(
				sprintf(
					'Callback `callbackForEntity` in `%s` requires a subject of type `%s`. `%s` given.',
					static::class,
					Configuration::class,
					is_object($entity) ? get_class($entity) : gettype($entity)
				)
			);
		}

		$scope = strtolower($entity->scope);
		if ($scope !== 'system') {
			$accessible = $permissionCollection->scopeIsAccessible($scope, [], 'configure');
		}


		return $accessible;
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

		$query = $additionalData['subject'] ?? null;
		if (!$query instanceof SelectQuery) {
			throw new RuntimeException(
				sprintf(
					'Callback `callbackForFind` in `%s` requires a subject of type `%s`. `%s` given.',
					static::class,
					SelectQuery::class,
					is_object($query) ? get_class($query) : gettype($query)
				)
			);
		}

		// Apply a mapReduce call that'll remove all entities from the query, except those that are re-added using the `emit()`-method
		$query->mapReduce(function (Configuration|array $entity, int $key, MapReduce $mapReduce) use ($permissionCollection): void {
			if (!$entity instanceof Configuration || strtolower($entity->scope) === 'System') {
				$mapReduce->emit($entity);

				return;
			}

			static $checkedScopes = [];

			$scope = AuthorizationService::sanitizeScope($entity->scope);

			if (!array_key_exists($scope, $checkedScopes)) {
				$checkedScopes[ $scope ] = $permissionCollection->scopeIsAccessible($entity->scope, [], 'configure');
			}

			//If the scope is accessible, append it to the final list of results
			if ($checkedScopes[ $scope ]) {
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
		$permissions = new PermissionOptionCollection(static::getScope());

		$callbacks = [
			'general' => static::callback(...),
			'Entity.create' => static::callbackForEntity(...),
			'Entity.update' => static::callbackForEntity(...),
			'Model.beforeFind' => static::callbackForFind(...),
			'Model.beforeSoftDelete' => static::callbackForEntity(...),
			'Model.beforeDelete' => static::callbackForEntity(...),
		];

		$permissions->load('read', [
			'className' => CallbackPermissionOption::class,
			'callbacks' => $callbacks,
		]);

		$permissions->load('create', [
			'className' => CallbackPermissionOption::class,
			'callbacks' => $callbacks,
		]);

		$permissions->load('update', [
			'className' => CallbackPermissionOption::class,
			'callbacks' => $callbacks,
		]);

		$permissions->load('delete', [
			'className' => CallbackPermissionOption::class,
			'callbacks' => $callbacks,
		]);


		return $permissions;
	}
}
