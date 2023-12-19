<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * AttributesContent Entity
 *
 * @property int $id
 * @property int $content_id
 * @property string $background_color
 * @property array $jason_test
 *
 * @property \Awyiss\Model\Entity\Content $content
 */
class AttributesContent extends Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
        'content_id' => true,
        'background_color' => true,
        'jason_test' => true,
        'content' => true,
    ];
}
