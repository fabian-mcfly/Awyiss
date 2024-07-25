<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * PagesNotFound Entity
 *
 * @property int $id
 * @property string $slug
 * @property string|null $referrer
 * @property bool|null $isRobot
 * @property \Cake\I18n\DateTime|null $createdOn
 */
class PagesNotFound extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'is_robot' => 'isRobot',
		'created_on' => 'createdOn',
	];
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'slug' => true,
		'referrer' => true,
		'isRobot' => true,
	];
}
