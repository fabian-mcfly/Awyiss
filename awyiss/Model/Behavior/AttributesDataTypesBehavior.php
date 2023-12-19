<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\ORM\Behavior;


class AttributesDataTypesBehavior extends Behavior {
	public function beforeMarshal () {
		dd(func_get_args());
	}
}