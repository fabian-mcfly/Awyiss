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
	protected static array $fieldMap = [
		'date_time' => 'dateTime',
		'foreign_key' => 'foreignKey',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'scope' => true,
		'foreignKey' => true,
		'type' => true,
		'dateTime' => true,
	];
}
