<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Audit Entity
 *
 * @property int $id
 * @property string $type
 * @property string $model
 * @property array|NULL $data_old
 * @property array|NULL $data_new
 * @property array|NULL $diff
 * @property int $parent_id
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 */
class I18n extends Entity {
	/**
	 * @inheritDoc
	 */
	 protected $_accessible = [
		'locale' => TRUE,
		'model' => TRUE,
		'foreign_key' => TRUE,
		'field' => TRUE,
		'content' => TRUE,
	];
}
