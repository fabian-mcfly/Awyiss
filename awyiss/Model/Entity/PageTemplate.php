<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * PageTemplate Entity
 *
 * @property int $id
 * @property string $title
 * @property string $filename
 * @property array $template_positions
 * @property int $page_role_id
 * @property int $system_order
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 * @property int|NULL $changed_by
 * @property \Cake\I18n\FrozenTime|NULL $changed_on
 * @property int|NULL $deleted_by
 * @property \Cake\I18n\FrozenTime|NULL $deleted_on
 * @property \Awyiss\Model\Entity\PageRole $page_role
 */
class PageTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'title' => TRUE,
		'filename' => TRUE,
		'template_positions' => TRUE,
		'page_role_id' => TRUE,
		'system_order' => TRUE,
		'active' => TRUE,
	];


	/**
	 * Make sure the template position property contains an array.
	 * If a string is provides, trim it, removing leading and trailing commas, explode it at ',', trim the values
	 * and remove duplicate values from the array.
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setTemplatePositions (mixed $ax_value): array {
		if (empty($ax_value)) {
			return [];
		}

		if (is_array($ax_value)) {
			return array_values($ax_value);
		}

		return array_unique(array_map('trim', explode(',', trim($ax_value, ', '))));
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
