<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Audit Entity
 *
 * @property int $id
 * @property string|null $scope
 * @property int|null $parentId
 * @property string|null $transactionId
 * @property string|null $type
 * @property array|null $dataOld
 * @property array|null $dataNew
 * @property array|null $diff
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 */
class Audit extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'scope' => true,
		'parentId' => true,
		'transactionId' => true,
		'type' => true,
		'dataOld' => true,
		'dataNew' => true,
		'diff' => true,
		'createdBy' => true,
		'createdOn' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'transaction_id' => 'transactionId',
		'parent_id' => 'parentId',
		'data_old' => 'dataOld',
		'data_new' => 'dataNew',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
	];
}
