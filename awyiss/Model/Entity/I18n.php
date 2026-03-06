<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * I18n Entity
 * Holds all translated contents for a specific entity field
 *
 * @property string|null $locale
 * @property string|null $model
 * @property int|null $foreignKey
 * @property string|null $field
 * @property string|null $content
 */
class I18n extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'locale' => true,
		'model' => true,
		'foreignKey' => true,
		'field' => true,
		'content' => true,
	];
}
