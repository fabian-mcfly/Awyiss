<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * UrlHistory Entity
 *
 * @property int $id
 * @property string $url
 * @property string $scope
 * @property int|null $foreignKey
 * @property string|null $target
 * @property int|null $status
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Media|null $media
 * @property \Awyiss\Model\Entity\Page|null $page
 */
class UrlHistory extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'foreign_key' => 'foreignKey',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'url' => true,
		'scope' => true,
		'foreignKey' => true,
		'target' => true,
		'status' => true,
	];
}
