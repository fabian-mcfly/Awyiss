<?php

declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * PageRole Entity
 *
 * @property int $id
 * @property string $title
 * @property string $identifier
 * @property bool $include_in_linklist
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
class PageRole extends \Awyiss\Model\Entity {
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
        'title' => true,
        'identifier' => true,
        'include_in_linklist' => true,
        'system_order' => true,
        'active' => true,
        'deleted' => true,
        'created_by' => true,
        'created_on' => true,
        'changed_by' => true,
        'changed_on' => true,
        'deleted_by' => true,
        'deleted_on' => true,
    ];
}
