<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\Policy\AbstractPolicy;


/**
 * Permission for the Media Folders scope of the backend
 */
class MediaPolicy extends AbstractPolicy {
	/**
	 * @var PermissionOptionCollection
	 */
	protected static PermissionOptionCollection $permissionOptionCollection;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * Use the basic CRUD permissions and remove the 'update' permission
	 *
	 * @inheritDoc
	 */
	protected static function loadPermissionOptions(): PermissionOptionCollection {
		$lo_permissionOptions = parent::loadPermissionOptions();

		$lo_permissionOptions->unload('update');

		return $lo_permissionOptions;
	}
}
