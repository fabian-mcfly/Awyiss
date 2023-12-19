<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Audit Entity
 *
 * @property int $id
 * @property string $type
 * @property string $scope
 * @property array|NULL $data_old
 * @property array|NULL $data_new
 * @property array|NULL $diff
 * @property int $parent_id
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 */
class Audit extends Entity {
	/**
	 * @inheritDoc
	 */
	 protected $_accessible = [
		'type' => TRUE,
		'scope' => TRUE,
		'parent_id' => TRUE,
		'data_old' => TRUE,
		'data_new' => TRUE,
		'diff' => TRUE,
		'created_by' => TRUE,
		'created_on' => TRUE,
	];
}
