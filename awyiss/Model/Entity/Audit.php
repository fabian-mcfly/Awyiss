<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Audit Entity
 *
 * @property int $id
 * @property string|null $scope
 * @property int|null $foreignKey
 * @property string|null $transactionId
 * @property string|null $type
 * @property string|null $dataOld
 * @property string|null $dataNew
 * @property array|null $diff
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 */
class Audit extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'transaction_id' => 'transactionId',
		'foreign_key' => 'foreignKey',
		'data_old' => 'dataOld',
		'data_new' => 'dataNew',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
	];

	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'scope' => true,
		'foreignKey' => true,
		'transactionId' => true,
		'type' => true,
		'dataOld' => true,
		'dataNew' => true,
		'diff' => true,
		'createdBy' => true,
		'createdOn' => true,
	];
}
