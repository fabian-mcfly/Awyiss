<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Audit Entity
 *
 * @property int $id
 * @property string|null $scope
 * @property int|null $foreignKey
 * @property string|null $subjectLeftTable
 * @property int|null $subjectLeftForeignKey
 * @property string|null $subjectRightTable
 * @property int|null $subjectRightForeignKey
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
		'subject_left_table' => 'subjectLeftTable',
		'subject_left_foreign_key' => 'subjectLeftForeignKey',
		'subject_right_table' => 'subjectRightTable',
		'subject_right_foreign_key' => 'subjectRightForeignKey',
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
		'subjectLeftTable' => true,
		'subjectLeftForeignKey' => true,
		'subjectRightTable' => true,
		'subjectRightForeignKey' => true,
		'transactionId' => true,
		'type' => true,
		'dataOld' => true,
		'dataNew' => true,
		'diff' => true,
		'createdBy' => true,
		'createdOn' => true,
	];
}
