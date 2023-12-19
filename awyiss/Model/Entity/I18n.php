<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * Audit Entity
 *
 * @property int $id
 * @property string $type
 * @property string $model
 * @property array|null $data_old
 * @property array|null $data_new
 * @property array|null $diff
 * @property int $parent_id
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 */
class I18n extends \Awyiss\Model\Entity {
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
