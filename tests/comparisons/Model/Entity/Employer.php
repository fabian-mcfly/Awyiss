<?php declare(strict_types=1);


namespace Customer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Employer Entity
 *
 * @property int $id
 * @property int|null $parentId
 * @property string|null $languageShortcode
 * @property string|null $title
 * @property int $systemOrder
 * @property int $active
 * @property int $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class Employer extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'parent_id' => 'parentId',
		'language_shortcode' => 'languageShortcode',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'created_by_user' => 'createdByUser',
		'changed_by_user' => 'changedByUser',
		'deleted_by_user' => 'deletedByUser',
		'media_assignments' => 'mediaAssignments',
		'child_employers' => 'childEmployers',
		'parent_employer' => 'parentEmployer',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'parentId' => true,
		'languageShortcode' => true,
		'title' => true,
		'systemOrder' => true,
		'active' => true,
		'deleted' => true,
		'createdBy' => true,
		'createdOn' => true,
		'changedBy' => true,
		'changedOn' => true,
		'deletedBy' => true,
		'deletedOn' => true,
		'language' => true,
		'createdByUser' => true,
		'changedByUser' => true,
		'deletedByUser' => true,
		'mediaAssignments' => true,
		'childEmployers' => true,
		'parentEmployer' => true,
	];
}
