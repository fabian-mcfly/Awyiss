<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


/**
 * Interface with method signatures that are required for a complete permission
 */
interface PermissionInterface {
	/**
	 * Return the access
	 *
	 * @return mixed
	 */
	public function getAccess(): mixed;


	/**
	 * Return the identifier
	 *
	 * @return string
	 */
	public function getIdentifier(): string;


	/**
	 * Return the scope
	 *
	 * @return string
	 */
	public function getScope(): string;


	/**
	 * Return the settings
	 *
	 * @return mixed
	 */
	public function getSettings(): mixed;
}
