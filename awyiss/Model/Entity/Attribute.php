<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Cake\Utility\Text;


/**
 * Attribute Entity
 *
 * @property int $id
 * @property string $name
 * @property string|null $default_value
 * @property string $scope
 * @property string|null $fieldset
 * @property string $input_type
 * @property string $type
 * @property bool $has_index
 * @property bool $required
 * @property int $system_order
 * @property bool $active
 * @property bool $deleted
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 * @property int|null $changed_by
 * @property \Cake\I18n\FrozenTime|null $changed_on
 * @property int|null $deleted_by
 * @property \Cake\I18n\FrozenTime|null $deleted_on
 */
class Attribute extends \Awyiss\Model\Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'name' => TRUE,
		'default_value' => TRUE,
		'scope' => TRUE,
		'fieldset' => TRUE,
		'input_type' => TRUE,
		'type' => TRUE,
		'has_index' => TRUE,
		'required' => TRUE,
		'system_order' => TRUE,
		'active' => TRUE,
	];


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setName (string $as_name): string {
		return mb_strtolower(Text::slug($as_name, ['replacement' => '_']));
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setScope (string $as_scope): string {
		return mb_strtolower(Text::slug($as_scope, ['replacement' => '_']));
	}
}
