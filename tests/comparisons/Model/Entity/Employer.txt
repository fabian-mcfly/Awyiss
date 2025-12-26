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
 * @property bool $active
 * @property bool $deleted
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
		'language' => true,
		'customerGroupAccessSettings' => true,
		'customerGroupAssignments' => true,
		'mediaAssignments' => true,
		'mediaElementAssignments' => true,
		'childEmployers' => true,
		'parentEmployer' => true,
	];
}
