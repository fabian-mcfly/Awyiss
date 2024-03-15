<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\PermissionOption\CallbackPermissionOption;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;
use RuntimeException;


/**
 * Permission for the Configuration scope of the backend
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
	 * @param bool|null $ab_accessible
	 * @param mixed $ax_access
	 * @param mixed $ax_settings
	 * @param array $aa_additionalData
	 * @param PermissionCollection $ao_permissionCollection
	 * @return bool|null
	 * @throws \ReflectionException
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function callback(?bool $ab_accessible, mixed $ax_access, mixed $ax_settings, array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool {
		$lb_accessible = $ab_accessible;

		//Only if the identifier itself is accessible, we must check the scope. So exit here already if it's not accessible.
		if (!$lb_accessible) {
			return $lb_accessible;
		}

		if (!array_key_exists('scope', $aa_additionalData)) {
			throw new RuntimeException(sprintf('CallbackPermission in `%s` requires additional data (`scope`)', static::class));
		}

		$ls_scope = $aa_additionalData['scope'];
		if (!$ls_scope) {
			return $lb_accessible;
		}

		if (strtolower($ls_scope) !== 'system') {
			$lb_accessible = $ao_permissionCollection->scopeIsAccessible($ls_scope, [], 'configure');
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
