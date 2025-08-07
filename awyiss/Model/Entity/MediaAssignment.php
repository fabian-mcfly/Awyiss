<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * MediaAssignment Entity
 *
 * @property int $id
 * @property int $mediaElementId
 * @property string $mediaElementSelectorIdentifier
 * @property int|null $mediaId
 * @property int|null $mediaFolderId
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
 * @property \Awyiss\Model\Entity\MediaElement $mediaElement
 * @property \Awyiss\Model\Entity\MediaElementAssignment[] $mediaElementAssignment
 * @property \Awyiss\Model\Entity\MediaElementSelector[] $mediaElementSelector
 * @property \Awyiss\Model\Entity\Media|null $media
 * @property \Awyiss\Model\Entity\MediaFolder|null $mediaFolder
 */
class MediaAssignment extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_element_id' => 'mediaElementId',
		'media_element_selector_identifier' => 'mediaElementSelectorIdentifier',
		'media_id' => 'mediaId',
		'media_folder_id' => 'mediaFolderId',
		'foreign_key' => 'foreignKey',
		'system_order' => 'systemOrder',
		'media_element' => 'mediaElement',
		'media_element_assignment' => 'mediaElementAssignment',
		'media_element_selector' => 'mediaElementSelector',
		'media_folder' => 'mediaFolder',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'mediaElementId' => true,
		'mediaElementSelectorIdentifier' => true,
		'mediaId' => true,
		'mediaFolderId' => true,
		'scope' => true,
		'foreignKey' => true,
		'systemOrder' => true,
	];
}
