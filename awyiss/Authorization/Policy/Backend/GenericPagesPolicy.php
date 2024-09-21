<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Authorization\Policy\AbstractGenericPolicy;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;


/**
 * Instances of this class are used for pages/page roles that have no own policy class.
 *
 * It provides four `SimplePermission` for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
 *
 * It needs to provide non-static methods, so it can be used for multiple pages/page roles at the same time.
 *
 * @see \Awyiss\Authorization\PermissionOption\SimplePermissionOption
 */
class GenericPagesPolicy extends AbstractGenericPolicy {
	/**
	 * Creates a `PermissionOptionCollection` and four `SimplePermission`
	 * for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
	 *
	 * @return \Awyiss\Authorization\PermissionOption\PermissionOptionCollection
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function loadPermissionOptions(): PermissionOptionCollection {
		$lo_permissions = parent::loadPermissionOptions();

		if (Configure::read('Awyiss.' . Inflector::camelize($this->getScope()) . '.Backend.contents.enabled')) {
			$lo_permissions->load('contents', [
				'className' => SimplePermissionOption::class,
			]);
		}


		return $lo_permissions;
	}
}
