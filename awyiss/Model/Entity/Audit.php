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
