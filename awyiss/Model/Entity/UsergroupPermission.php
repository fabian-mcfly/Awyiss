<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * UsergroupPermission Entity
 *
 * @property int $id
 * @property int $usergroup_id
 * @property string $scope
 * @property string $identifier
 * @property int $access
 * @property array|null $settings
 * @property \Awyiss\Model\Entity\Usergroup $usergroup
 */
class UsergroupPermission extends \Awyiss\Model\Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'usergroup_id' => TRUE,
		'scope' => TRUE,
		'identifier' => TRUE,
		'access' => TRUE,
		'settings' => TRUE,
	];


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setSettings (mixed $ax_value): ?array {
		if (empty($ax_value)) {
			return NULL;
		}

		return is_array($ax_value) ? $ax_value : [$ax_value];
	}
}
