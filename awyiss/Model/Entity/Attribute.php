<?php

declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Cake\ORM\Entity;


/**
 * Attribute Entity
 *
 * @property int $parent_id
 */
abstract class Attribute extends Entity {
	protected $_accessible = [
		'*' => TRUE,
	];
	/*public function getFields () {

	}*/
}
