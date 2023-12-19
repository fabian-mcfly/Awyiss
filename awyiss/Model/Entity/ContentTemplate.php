<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Cake\ORM\Behavior\Translate\TranslateTrait;


/**
 * ContentTemplate Entity
 *
 * @property int $id
 * @property string $title
 * @property string $filename
 * @property array $available_elements
 * @property array $assigned_template_positions
 * @property int $system_order
 * @property bool $active
 * @property bool $deleted
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 * @property int|null $changed_by
 * @property \Cake\I18n\FrozenTime|null $changed_on
 * @property int|null $deleted_by
 * @property \Cake\I18n\FrozenTime|null $deleted_on
 * @property \Awyiss\Model\Entity\Attribute|null $attributes
 */
class ContentTemplate extends \Awyiss\Model\Entity {
	use TranslateTrait;


	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'title' => TRUE,
		'filename' => TRUE,
		'available_elements' => TRUE,
		'assigned_template_positions' => TRUE,
		'active' => TRUE,
		'system_order' => TRUE,
	];


	/**
	 * @noinspection PhpUnused
	 */
	protected function _getAvailableElements (?array $aa_availableElements = NULL): ?array {
		if (empty($aa_availableElements)) {
			return NULL;
		}

		foreach ($aa_availableElements AS &$la_element) {
			$la_element['required'] = (bool)$la_element['required'];
		}

		return $aa_availableElements;
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setAvailableElements (mixed $ax_value): array {
		if (empty($ax_value)) {
			return [];
		}

		return is_array($ax_value) ? $ax_value : [$ax_value];
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setAssignedTemplatePositions (mixed $ax_value): array {
		if (empty($ax_value)) {
			return [];
		}

		return is_array($ax_value) ? $ax_value : [$ax_value];
	}



	/**
	 * @noinspection PhpUnused
	 */
	protected function _setFilename (string $as_filename): string {
		$ls_filename = \Cake\Utility\Text::slug($as_filename, ['replacement' => '_']);

		return mb_strtolower($ls_filename);
	}
}
