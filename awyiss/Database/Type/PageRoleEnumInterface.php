<?php declare(strict_types=1);


namespace Awyiss\Database\Type;


use BackedEnum;


/**
 * An interface used to clarify that an enum relects page roles
 */
interface PageRoleEnumInterface extends BackedEnum {
	/**
	 * Try to get a case from the name. Returns the page role id if found
	 *
	 * @param string $as_name
	 * @return int|null
	 */
	public static function tryFromName(string $as_name): ?PageRoleEnumInterface;


	/**
	 * @return string
	 */
	public function tableAlias(): string;


	/**
	 * @return string
	 */
	public function tableName(): string;
}
