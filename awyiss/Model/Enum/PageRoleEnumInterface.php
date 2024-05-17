<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


use BackedEnum;


/**
 * An interface used to clarify that an enum relects page roles
 */
interface PageRoleEnumInterface extends BackedEnum {
	/**
	 * Try to get a case from the name. Returns the page role id if found
	 *
	 * @param string $name
	 * @return int|null
	 */
	public static function tryFromName(string $name): ?PageRoleEnumInterface;


	/**
	 * @return string
	 */
	public function tableAlias(): string;


	/**
	 * @return string
	 */
	public function tableName(): string;
}
