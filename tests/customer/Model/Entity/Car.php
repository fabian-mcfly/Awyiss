<?php declare(strict_types=1);


namespace Customer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Car Entity
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
class Car extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'parentId' => true,
		'languageShortcode' => true,
		'title' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * Entity to be passed to the validation of attributes
	 */
	protected ?Entity $entity = null;


	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'parent_id' => 'parentId',
		'language_shortcode' => 'languageShortcode',
		'system_order' => 'systemOrder',
	];
}
