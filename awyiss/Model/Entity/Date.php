<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Date Entity
 *
 * @property int $id
 * @property string $scope
 * @property int $foreignId
 * @property string $type
 * @property \Cake\I18n\DateTime|null $dateTime
 * @property \Cake\I18n\Date|null $date
 * @property \Cake\I18n\Time|null $time
 */
class Date extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'scope' => true,
		'foreignId' => true,
		'type' => true,
		'dateTime' => true,
		'date' => true,
		'time' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'datetime' => 'dateTime',
		'foreign_id' => 'foreignId',
	];
}
