<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\Utility\Text;


/**
 * ContentTemplate Entity
 *
 * @property int $id
 * @property string $title
 * @property string $filename
 * @property array|NULL $available_elements
 * @property array|NULL $assigned_template_positions
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
class ContentTemplate extends Entity {
	use TranslateTrait;


	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'title' => TRUE,
		'filename' => TRUE,
		'available_elements' => TRUE,
		'assigned_template_positions' => TRUE,
		'system_order' => TRUE,
		'active' => TRUE,
	];


	/**
	 * Make sure the available elements are an array and the 'required'-key is always a boolean value
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setAvailableElements (?array $aa_availableElements = NULL): ?array {
		if (empty($aa_availableElements)) {
			return [];
		}

		foreach ($aa_availableElements AS &$la_element) {
			$la_element['required'] = (bool)$la_element['required'];
		}
		unset($la_element);

		return $aa_availableElements;
	}


	/**
	 * Make sure the filename is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setFilename (string $as_filename): string {
		$ls_filename = Text::slug($as_filename, ['replacement' => '_']);

		return mb_strtolower($ls_filename);
	}
}
