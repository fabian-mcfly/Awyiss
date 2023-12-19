<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * PageTemplate Entity
 *
 * @property int $id
 * @property string $title
 * @property string $filename
 * @property array $content_areas
 * @property int $page_roles_id
 * @property bool $active
 * @property bool $deleted
 * @property int $system_order
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 * @property int|null $changed_by
 * @property \Cake\I18n\FrozenTime|null $changed_on
 * @property int|null $deleted_by
 * @property \Cake\I18n\FrozenTime|null $deleted_on
 *
 * @property \Awyiss\Model\Entity\PageRole $page_role
 */
class PageTemplate extends \Awyiss\Model\Entity {
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
		'title' => TRUE,
		'filename' => TRUE,
		'content_areas' => TRUE,
		'page_roles_id' => TRUE,
		'active' => TRUE,
		'system_order' => TRUE,
	];


	/**
	 * @noinspection PhpUnused
	 */
	public function _setContentAreas (mixed $ax_value): array {
		return is_array($ax_value) ? $ax_value : [$ax_value];
	}
}
