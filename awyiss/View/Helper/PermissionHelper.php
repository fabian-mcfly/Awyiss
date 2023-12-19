<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Authorization\Permission\PermissionInterface;
use Awyiss\Model\Entity;


class PermissionHelper extends \Cake\View\Helper {
	public function options (PermissionInterface $ao_permission, ?Entity $ao_entity = NULL, ?string $as_fileName = NULL, ?string $as_subDir = NULL): string {
		$ls_subDir = 'authorization';
		if (!empty($as_subDir)) {
			$ls_subDir = trim($as_subDir, DS) . DS . 'authorization';
		}

		$ls_fileName = $as_fileName;
		if (empty($ls_fileName)) {
			$ls_fileName = $ao_permission->getType();
			$ls_fileName .= '_' . $ao_permission->getConfig('preferredInput');
		}

		if (!$ao_permission->getConfig('identifier')) {
			throw new \RuntimeException(sprintf('Permission `%s` requires an identifier to be representable.', $ao_permission::class));
		}

		$la_viewData = [
			'ao_permission' => $ao_permission,
			'ao_entity' => $ao_entity,
			'as_scope' => $ao_permission->getPermissionCollection()?->getScope(),
			'as_identifier' => $ao_permission->getConfig('identifier'),
		];

		return $this->getView()->element($ls_subDir . DS . 'permission' . DS . $ls_fileName, $la_viewData);
	}
}