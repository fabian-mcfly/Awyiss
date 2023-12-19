<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


enum PermissionTypes {
	case OPTION_GRANTED;
	case OPTION_DENIED;
	case OPTION_INDIFFERENT;


	public function databaseValue () {
		return match ($this) {
			self::OPTION_GRANTED => 1,
			self::OPTION_DENIED => 0,
			self::OPTION_INDIFFERENT => NULL,
		};
	}


	public static function from (mixed $lx_value) {
		return match ($lx_value) {
			1 => self::OPTION_GRANTED,
			0 => self::OPTION_DENIED,
			default => self::OPTION_INDIFFERENT,
		};
	}
}