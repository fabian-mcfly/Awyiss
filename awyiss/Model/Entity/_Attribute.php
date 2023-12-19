<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Cake\ORM\Entity;


/**
 * Attribute Entity
 *
 * @property int $parent_id
 */
class _Attribute extends Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'*' => TRUE,
		'id' => FALSE,
	];
	/*public function getFields () {

	}*/
}
