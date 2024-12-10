<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * UrlHistory Entity
 *
 * @property int $id
 * @property string $url
 * @property int $pageId
 * @property int|null $status
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class UrlHistory extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'page_id' => 'pageId',
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
		'pageId' => true,
		'status' => true,
	];
}
