<?php

declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * Audit Entity
 *
 * @property int $id
 * @property string $type
 * @property string $model
 * @property int $parent_id
 * @property array|null $data_old
 * @property array|null $data_new
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 */
class Audit extends \Awyiss\Model\Entity {
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
		'type' => TRUE,
		'model' => TRUE,
		'parent_id' => TRUE,
		'data_old' => TRUE,
		'data_new' => TRUE,
		'created_by' => TRUE,
		'created_on' => TRUE,
	];


	/**
	 * @param $ax_value
	 *
	 * @return array
	 */
	public function _setDataOld ($ax_value): array {
		return is_array($ax_value) ? $ax_value : [$ax_value];
	}


	/**
	 * @param $ax_value
	 *
	 * @return array
	 */
	public function _setDataNew ($ax_value): array {
		return is_array($ax_value) ? $ax_value : [$ax_value];
	}
}
