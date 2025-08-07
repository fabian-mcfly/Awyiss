<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * UrlsNotFound Entity
 *
 * @property int $id
 * @property string $url
 * @property string|null $referrer
 * @property bool|null $isRobot
 * @property \Cake\I18n\DateTime|null $createdOn
 */
class UrlsNotFound extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'is_robot' => 'isRobot',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'url' => true,
		'referrer' => true,
		'isRobot' => true,
	];
}
