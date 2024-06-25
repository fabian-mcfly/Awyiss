<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * MediaAssignment Entity
 *
 * @property int $id
 * @property int $mediaElementId
 * @property string $mediaElementSelectorIdentifier
 * @property int $mediaId
 * @property string $scope
 * @property int $foreignKey
 * @property int $systemOrder
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class MediaAssignment extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_element_id' => 'mediaElementId',
		'media_element_selector_identifier' => 'mediaElementSelectorIdentifier',
		'media_id' => 'mediaId',
		'foreign_key' => 'foreignKey',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'created_by_user' => 'createdByUser',
		'changed_by_user' => 'changedByUser',
		'deleted_by_user' => 'deletedByUser',
		'media_element' => 'mediaElement',
	];
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'mediaElementId' => true,
		'mediaElementSelectorIdentifier' => true,
		'mediaId' => true,
		'scope' => true,
		'foreignKey' => true,
		'systemOrder' => true,
	];
}
