<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Model\Entity;
use Cake\View\Helper;
use RuntimeException;


/**
 * Helpers related to Permissions-logic
 */
class PermissionHelper extends Helper {
	/**
	 * Renders an element containing all options for a specific permission.
	 *
	 * If no filename was provided, use the type and the preferred input of the permission.
	 * For example `element\authorization\permission\simple_radio`
	 *
	 * @param \Awyiss\Authorization\PermissionOption\PermissionOptionInterface $ao_permission
	 * @param null|\Awyiss\Model\Entity $ao_entity
	 * @param null|string $as_fileName
	 * @param null|string $as_subDir
	 *
	 * @return string
	 */
	public function options (PermissionOptionInterface $ao_permission, ?Entity $ao_entity = NULL, ?string $as_fileName = NULL, ?string $as_subDir = NULL): string {
		$ls_subDir = 'authorization' . DS . 'permission_option';
		if ( ! empty($as_subDir)) {
			$ls_subDir = trim($as_subDir, DS) . DS . $ls_subDir;
		}

		$ls_fileName = $as_fileName;
		if (empty($ls_fileName)) {
			$ls_fileName = $ao_permission->getType();
			$ls_fileName .= '_' . $ao_permission->getConfig('preferredInput')->value;
		}

		//This should never happen, but you never know.
		if ( ! $ao_permission->getConfig('identifier')) {
			throw new RuntimeException(sprintf('Permission `%s` requires an identifier to be representable.', $ao_permission::class));
		}

		$la_viewData = [
			'ao_permission' => $ao_permission,
			'ao_entity' => $ao_entity,
			'as_scope' => $ao_permission->getPermissionOptionCollection()->getScope(),
			'as_identifier' => $ao_permission->getConfig('identifier'),
		];

		return $this->getView()->element($ls_subDir . DS . $ls_fileName, $la_viewData);
	}
}