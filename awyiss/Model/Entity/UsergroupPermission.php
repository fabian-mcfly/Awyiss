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
 *
 * @property \Awyiss\Model\Entity\Usergroup $usergroup
 */
class UsergroupPermission extends \Awyiss\Model\Entity {
	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array
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
	public function _setSettings (mixed $ax_value): array {
		return is_array($ax_value) ? $ax_value : [$ax_value];
	}
}
