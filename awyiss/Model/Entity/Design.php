<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Design Entity
 *
 * @property int $id
 * @property string $identifier
 * @property string $title
 * @property string|null $description
 * @property array|null $settings
 * @property string|null $css
 * @property bool $inUse
 * @property bool $isPreview
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class Design extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'in_use' => 'inUse',
		'is_preview' => 'isPreview',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'identifier' => true,
		'title' => true,
		'description' => true,
		'settings' => true,
		'css' => true,
		'inUse' => true,
		'isPreview' => true,
	];
}
