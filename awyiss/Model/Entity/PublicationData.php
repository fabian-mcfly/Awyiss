<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Publication Data Entity
 *
 * @property int $id
 * @property string $scope
 * @property int $foreignKey
 * @property \Awyiss\Model\Enum\PublicationDataType $type
 * @property \Cake\I18n\DateTime|null $dateTime
 */
class PublicationData extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'scope' => true,
		'foreignKey' => true,
		'type' => true,
		'dateTime' => true,
	];
}
