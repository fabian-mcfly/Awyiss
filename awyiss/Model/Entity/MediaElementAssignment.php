<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * MediaElementAssignment Entity
 *
 * @property int $id
 * @property int $mediaElementId
 * @property string $scope
 * @property int|null $foreignKey
 * @property \Awyiss\Model\Entity\MediaElement $mediaElement
 */
class MediaElementAssignment extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_element_id' => 'mediaElementId',
		'media_element' => 'mediaElement',
		'foreign_key' => 'foreignKey',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'mediaElementId' => true,
		'mediaElement' => true,
		'scope' => true,
		'foreignKey' => true,
	];
}
