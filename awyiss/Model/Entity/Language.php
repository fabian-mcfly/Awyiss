<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Language Entity
 *
 * @property int $id
 * @property string $shortcode
 * @property string $title
 * @property string $timezone
 * @property string $locale
 * @property string $type
 * @property int $system_order
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 * @property int|NULL $changed_by
 * @property \Cake\I18n\FrozenTime|NULL $changed_on
 * @property int|NULL $deleted_by
 * @property \Cake\I18n\FrozenTime|NULL $deleted_on
 * @property \Awyiss\Model\Entity\Configuration[] $configuration
 */
class Language extends Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'shortcode' => TRUE,
		'title' => TRUE,
		'timezone' => TRUE,
		'locale' => TRUE,
		'type' => TRUE,
		'system_order' => TRUE,
		'active' => TRUE,
	];
}
