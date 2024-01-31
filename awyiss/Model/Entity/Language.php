<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Model\Entity;


/**
 * Language Entity
 *
 * @property int $id
 * @property string|null $shortcode
 * @property string|null $title
 * @property string|null $timezone
 * @property string|null $locale
 * @property string|null $realm
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Configuration[] $configuration
 */
class Language extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'shortcode' => true,
		'title' => true,
		'timezone' => true,
		'locale' => true,
		'realm' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'realm' => Awyiss::REALM_FRONTEND,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];
}
