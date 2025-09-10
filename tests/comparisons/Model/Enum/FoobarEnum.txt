<?php declare(strict_types=1);


namespace Customer\Model\Enum;


use Awyiss\Utility\Inflector;
use Cake\Database\Type\EnumLabelInterface;


/**
 * FoobarEnum Enum
 */
enum FoobarEnum: int implements EnumLabelInterface {
	case Case1 = 1;
	case Case2 = 2;
	case Case3 = 3;


	/**
	 * @return string
	 */
	public function label(): string {
		return Inflector::humanize(Inflector::underscore($this->name));
	}
}
