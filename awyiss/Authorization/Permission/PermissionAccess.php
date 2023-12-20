<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


/**
 * Enum with all possible permission types.
 */
enum PermissionAccess: int {
	case OPTION_GRANTED = 1;
	case OPTION_DENIED = 0;
	//case OPTION_INDIFFERENT = NULL;
	/**
	 * Returns a value that can represent the enum case in the database.
	 * Since one is NULL, it cannot be a backed enum
	 *
	 * @return null|int
	 */
	/*public function databaseValue (): ?int {
		return match ($this) {
			self::OPTION_GRANTED => 1,
			self::OPTION_DENIED => 0,
			self::OPTION_INDIFFERENT => NULL,
		};
	}*/


	/**
	 * Returns a PermissionType case for a given value.
	 * It falls back to NULL for values that have no matching case.
	 *
	 * @param mixed $ax_value
	 *
	 * @return PermissionAccess
	 */
	/*public static function from (mixed $ax_value): PermissionAccess {
		return match ($ax_value) {
			1 => self::OPTION_GRANTED,
			0 => self::OPTION_DENIED,
			default => self::OPTION_INDIFFERENT,
		};
	}*/
}
