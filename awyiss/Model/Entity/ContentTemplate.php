<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * ContentTemplate Entity
 *
 * @property int $id
 * @property string $title
 * @property string $filename
 * @property array $available_elements
 * @property array $assigned_content_areas
 * @property bool $active
 * @property bool $deleted
 * @property int $system_order
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 * @property int|null $changed_by
 * @property \Cake\I18n\FrozenTime|null $changed_on
 * @property int|null $deleted_by
 * @property \Cake\I18n\FrozenTime|null $deleted_on
 * @property \Awyiss\Model\Entity\Attribute|null $attributes
 */
class ContentTemplate extends \Awyiss\Model\Entity {
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
		'available_elements' => TRUE,
		'assigned_content_areas' => TRUE,
		'active' => TRUE,
		'system_order' => TRUE,
	];


	/**
	 * @noinspection PhpUnused
	 */
	public function _setAvailableElements (mixed $ax_value): array {
		return is_array($ax_value) ? $ax_value : [$ax_value];
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function _setAssignedContentAreas (mixed $ax_value): array {
		return is_array($ax_value) ? $ax_value : [$ax_value];
	}
}
