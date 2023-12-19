<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * Audit Entity
 *
 * @property int $id
 * @property string $type
 * @property string $scope
 * @property array|null $data_old
 * @property array|null $data_new
 * @property array|null $diff
 * @property int $parent_id
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 */
class Audit extends \Awyiss\Model\Entity {
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


	/**
	 * @param mixed $ax_value
	 *
	 * @return null|array
	 *
	 * @noinspection PhpUnused
	 */
	/*protected function _setDataOld (mixed $ax_value): ?array {
		if (empty($ax_value)) {
			return NULL;
		}

		return is_array($ax_value) ? $ax_value : [$ax_value];
	}*/


	/**
	 * @param mixed $ax_value
	 *
	 * @return null|array
	 *
	 * @noinspection PhpUnused
	 */
	/*protected function _setDataNew (mixed $ax_value): ?array {
		if (empty($ax_value)) {
			return NULL;
		}

		return is_array($ax_value) ? $ax_value : [$ax_value];
	}*/


	/**
	 * @param mixed $ax_value
	 *
	 * @return null|array
	 *
	 * @noinspection PhpUnused
	 */
	/*protected function _setDiff (mixed $ax_value): ?array {
		if (empty($ax_value)) {
			return NULL;
		}

		return is_array($ax_value) ? $ax_value : [$ax_value];
	}*/
}
