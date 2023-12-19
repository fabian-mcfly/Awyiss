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
 * @property \Cake\I18n\DateTime|null $value
 */
class Date extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'scope' => TRUE,
		'foreignId' => TRUE,
		'type' => TRUE,
		'value' => TRUE,
	];

	/**
	* @inheritDoc
	*/
	protected static array $fieldMap = [
		'foreign_id' => 'foreignId',
	];
}
