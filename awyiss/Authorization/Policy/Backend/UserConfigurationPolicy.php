<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\PermissionOption\CallbackPermissionOption;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;
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

		if (!in_array(strtolower($ls_scope), ['contents', 'system'], true)) {
			$lb_accessible = $permissionCollection->scopeIsAccessible($ls_scope, [], ['read', 'create', 'update', 'configure']);
		}

		return $lb_accessible;
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
