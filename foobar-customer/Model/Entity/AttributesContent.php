<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * AttributesContent Entity
 *
 * @property int $id
 * @property int $contentId
 * @property string|null $backgroundColor
 * @property string|null $alter2
 */
class AttributesContent extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'contentId' => true,
		'backgroundColor' => true,
		'alter2' => true,
		'content' => true,
	];

	/**
	* @inheritDoc
	*/
	protected static array $fieldMap = [
		'content_id' => 'contentId',
		'background_color' => 'backgroundColor',
	];
}
