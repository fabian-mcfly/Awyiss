<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


class I18n extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'locale' => TRUE,
		'model' => TRUE,
		'foreignKey' => TRUE,
		'field' => TRUE,
		'content' => TRUE,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'foreign_key' => 'foreignKey',
	];
}
