<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Model\Entity;
use Cake\I18n\FrozenTime;


/**
 * Language Entity
 *
 * @property int $id
 * @property string $shortcode
 * @property string $title
 * @property string $timezone
 * @property string $locale
 * @property string $realm
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $createdBy
 * @property FrozenTime|NULL $createdOn
 * @property int|NULL $changedBy
 * @property FrozenTime|NULL $changedOn
 * @property int|NULL $deletedBy
 * @property FrozenTime|NULL $deletedOn
 * @property Configuration[] $configuration
 */
class Language extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'shortcode' => TRUE,
		'title' => TRUE,
		'timezone' => TRUE,
		'locale' => TRUE,
		'realm' => TRUE,
		'systemOrder' => TRUE,
		'active' => TRUE,
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


	/**
	 * @inheritDoc
	 */
	public function defaultValues (): array {
		$la_realms = Awyiss::getRealms();

		return [
			'realm' => reset($la_realms),
		];
	}
}
