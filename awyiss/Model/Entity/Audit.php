<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\I18n\FrozenTime;


/**
 * Audit Entity
 *
 * @property int $id
 * @property string $scope
 * @property int $parentId
 * @property string $transactionId
 * @property string $type
 * @property array|NULL $dataOld
 * @property array|NULL $dataNew
 * @property array|NULL $diff
 * @property int|NULL $createdBy
 * @property FrozenTime|NULL $createdOn
 */
class Audit extends Entity {
	/**
	 * @inheritDoc
	 */
	 protected array $_accessible = [
		'scope' => TRUE,
		'parentId' => TRUE,
	 	'transactionId' => TRUE,
		'type' => TRUE,
		'dataOld' => TRUE,
		'dataNew' => TRUE,
		'diff' => TRUE,
		'createdBy' => TRUE,
		'createdOn' => TRUE,
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
