<?php declare(strict_types=1);


namespace Awyiss\Authorization;


/**
 * Identity Group Permission interface
 *
 * Provides methods to manage and retrieve customer group permissions
 */
interface IdentityGroupPermissionInterface {
	/**
	 * Returns an array of CustomerGroup-entities
	 *
	 * @return array<\Awyiss\Model\Entity\CustomerGroup>
	 */
	public function getGroups(): array;


	/**
	 * Unsets the Groups assigned to this identity after changes have been made that could affect permissions.
	 *
	 * @return $this
	 */
	public function unsetGroups(): static;
}
