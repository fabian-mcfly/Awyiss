<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * Comment Entity
 *
 * @property int $id
 * @property int $articleId
 * @property int|null $parentId
 * @property string $text
 * @property bool $active
 */
class Comment extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'articleId' => true,
		'parentId' => true,
		'text' => true,
		'active' => true,
	];

	/**
	* @inheritDoc
	*/
	protected static array $fieldMap = [
		'article_id' => 'articleId',
		'parent_id' => 'parentId',
	];
}
