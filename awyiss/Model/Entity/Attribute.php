<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * Attribute Entity
 *
 * @property int $id
 * @property string $name
 * @property string|NULL $default_value
 * @property string $scope
 * @property string|NULL $fieldset
 * @property string $input_type
 * @property string $type
 * @property bool $has_index
 * @property bool $required
 * @property int $system_order
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 * @property int|NULL $changed_by
 * @property \Cake\I18n\FrozenTime|NULL $changed_on
 * @property int|NULL $deleted_by
 * @property \Cake\I18n\FrozenTime|NULL $deleted_on
 */
class Attribute extends Entity {
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
	 * Make sure the name is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setName (string $as_name): string {
		return mb_strtolower(Text::slug($as_name, ['replacement' => '_']));
	}


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setScope (string $as_scope): string {
		return mb_strtolower(Text::slug($as_scope, ['replacement' => '_']));
	}
}
