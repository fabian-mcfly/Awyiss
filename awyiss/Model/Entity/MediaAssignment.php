<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * MediaAssignment Entity
 *
 * @property int $id
 * @property int $mediaCompositeId
 * @property string $mediaCompositeSelectorIdentifier
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
 * @property \Awyiss\Model\Entity\MediaComposite $mediaComposite
 * @property \Awyiss\Model\Entity\MediaCompositeAssignment[] $mediaCompositeAssignment
 * @property \Awyiss\Model\Entity\MediaCompositeSelector[] $mediaCompositeSelector
 */
class MediaAssignment extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_composite_id' => 'mediaCompositeId',
		'media_composite_selector_identifier' => 'mediaCompositeSelectorIdentifier',
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
		'media_composite' => 'mediaComposite',
		'media_composite_assignment' => 'mediaCompositeAssignment',
		'media_composite_selector' => 'mediaCompositeSelector',
	];
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'mediaCompositeId' => true,
		'mediaCompositeSelectorIdentifier' => true,
		'mediaId' => true,
		'scope' => true,
		'foreignKey' => true,
		'systemOrder' => true,
	];
}
