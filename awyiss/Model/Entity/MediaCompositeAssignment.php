<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * MediaCompositeAssignment Entity
 *
 * @property int $id
 * @property int $mediaCompositeId
 * @property string $scope
 * @property int|null $foreignKey
 * @property \Awyiss\Model\Entity\MediaComposite $mediaComposite
 */
class MediaCompositeAssignment extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_composite_id' => 'mediaCompositeId',
		'foreign_key' => 'foreignKey',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'mediaCompositeId' => true,
		'scope' => true,
		'foreignKey' => true,
	];
}
