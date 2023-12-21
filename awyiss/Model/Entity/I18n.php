<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * I18n Entity
 * Holds all translated contents for a specific entity field
 */
class I18n extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'locale' => true,
		'model' => true,
		'foreignKey' => true,
		'field' => true,
		'content' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'foreign_key' => 'foreignKey',
	];
}
