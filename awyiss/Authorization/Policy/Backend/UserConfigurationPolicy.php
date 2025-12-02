<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\PermissionOption\CallbackPermissionOption;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;
use Awyiss\Utility\Inflector;
use RuntimeException;


/**
 * Permission for the User Configuration scope of the backend
 */
class UserConfigurationPolicy extends AbstractPolicy {
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
	public static function callback(?bool $accessible, mixed $access, mixed $settings, array $additionalData, PermissionCollection $permissionCollection): ?bool {
		//Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (!$accessible) {
			return $accessible;
		}

		if (!array_key_exists('scope', $additionalData)) {
			throw new RuntimeException(sprintf('CallbackPermission in `%s` requires additional data (`scope`)', static::class));
		}

		if (!$additionalData['scope']) {
			return $accessible;
		}

		$scope = Inflector::underscore($additionalData['scope']);

		if (!in_array($scope, ['contents', 'system'], true)) {
			// Form elements are accessible if the user has access to the Forms scope
			if ($scope === 'form_elements') {
				$scope = 'forms';
			}
			// Menu entries are accessible if the user has access to the Menus scope
			elseif ($scope === 'menu_entries') {
				$scope = 'menus';
			}

			$accessible = $permissionCollection->scopeIsAccessible($scope, [], ['read', 'create', 'update', 'configure']);
		}

		return $accessible;
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
