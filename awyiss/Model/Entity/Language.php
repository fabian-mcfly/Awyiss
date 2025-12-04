<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Model\Entity;


/**
 * Language Entity
 *
 * @property int $id
 * @property string|null $realm
 * @property string|null $shortcode
 * @property string|null $timezone
 * @property string|null $locale
 * @property string|null $dateFormat
 * @property string|null $timeFormat
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
 * @property \Awyiss\Model\Entity\Configuration[] $configuration
 */
class Language extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'date_format' => 'dateFormat',
		'time_format' => 'timeFormat',
		'system_order' => 'systemOrder',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'realm' => true,
		'shortcode' => true,
		'timezone' => true,
		'locale' => true,
		'dateFormat' => true,
		'timeFormat' => true,
		'title' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'realm' => Awyiss::REALM_FRONTEND,
	];
}
