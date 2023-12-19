<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * PageTemplate Entity
 *
 * @property int $id
 * @property string $title
 * @property string $filename
 * @property array $template_positions
 * @property int $page_roles_id
 * @property int $system_order
 * @property bool $active
 * @property bool $deleted
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 * @property int|null $changed_by
 * @property \Cake\I18n\FrozenTime|null $changed_on
 * @property int|null $deleted_by
 * @property \Cake\I18n\FrozenTime|null $deleted_on
 * @property \Awyiss\Model\Entity\PageRole $page_role
 */
class PageTemplate extends \Awyiss\Model\Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'title' => TRUE,
		'filename' => TRUE,
		'template_positions' => TRUE,
		'page_roles_id' => TRUE,
		'system_order' => TRUE,
		'active' => TRUE,
	];


	/**
	 * @noinspection PhpUnused
	 */
	protected function _getTemplatePositions (?array $aa_templatePositions = NULL): ?array {
		return $aa_templatePositions ? array_combine($aa_templatePositions, $aa_templatePositions) : NULL;
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setTemplatePositions (mixed $ax_value): array {
		if (empty($ax_value)) {
			return [];
		}

		if (is_array($ax_value)) {
			return $ax_value;
		}

		return array_unique(array_map('trim', explode(',', trim($ax_value, ', '))));
	}



	/**
	 * @noinspection PhpUnused
	 */
	protected function _setFilename (string $as_filename): string {
		$ls_filename = \Cake\Utility\Text::slug($as_filename, ['replacement' => '_']);

		return mb_strtolower($ls_filename);
	}
}
