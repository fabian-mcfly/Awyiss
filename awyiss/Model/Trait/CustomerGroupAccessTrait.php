<?php declare(strict_types=1);


namespace Awyiss\Model\Trait;


use Awyiss\Authorization\IdentityGroupPermissionInterface;
use Awyiss\Model\Entity\Customer;
use Awyiss\Model\Entity\CustomerGroup;
use Awyiss\Model\Entity\CustomerGroupAccessSetting;
use Awyiss\Model\Entity\CustomerGroupAssignment;
use Awyiss\Model\Enum\CustomerGroupAccessType;


/**
 * Trait CustomerGroupAccessTrait
 * Provides functionality to check if an entity is accessible by a given identity
 * based on Customer Group Access Settings.
 */
trait CustomerGroupAccessTrait {
	/**
	 * Check if this entity is accessible by the given identity.
	 * Access logic:
	 * - No access settings: accessible to everyone (identity irrelevant)
	 * - AllGroups: accessible to logged-in customers with at least one customer group
	 * - HideOnLogin: accessible only to non-logged-in users
	 * - SpecificGroups: accessible only to logged-in customers who belong to at least one assigned customer group
	 *
	 * @param IdentityGroupPermissionInterface|null $identity The identity to check access for
	 * @return bool True if the entity is accessible by the identity, false otherwise
	 */
	public function isAccessibleBy(?IdentityGroupPermissionInterface $identity): bool {
		// If no access settings exist, entity is accessible to everyone
		if (
			!isset($this->customerGroupAccessSettings)
			|| !$this->customerGroupAccessSettings instanceof CustomerGroupAccessSetting
		) {
			return true;
		}

		$accessType = $this->customerGroupAccessSettings->accessType;

		/**
		 * If no identity is provided, access is only possible when access type
		 * is set to HideOnLogin.
		 */
		if ($identity === null) {
			return $accessType === CustomerGroupAccessType::HideOnLogin;
		}

		// Identity must be a Customer to have customer groups
		if (!$identity instanceof Customer) {
			return false;
		}

		$identityCustomerGroups = $identity->getGroups();

		if (empty($identityCustomerGroups)) {
			return false;
		}

		// AllGroups: accessible to logged-in customers with at least one customer group
		if ($accessType === CustomerGroupAccessType::AllGroups) {
			// Must have at least one customer group
			return true;
		}

		// Get assigned customer groups from entity
		$assignedGroups = $this->customerGroupAssignments ?? [];
		if (empty($assignedGroups)) {
			return false;
		}

		// Extract customer group IDs from identity
		$identityGroupIds = array_map(fn(CustomerGroup $group) => $group->id, $identityCustomerGroups);
		// And the assigned group IDs from entity
		$assignedGroupIds = array_map(fn(CustomerGroupAssignment $assignment) => $assignment->customerGroupId, $assignedGroups);

		return !empty(array_intersect($identityGroupIds, $assignedGroupIds));
	}
}
