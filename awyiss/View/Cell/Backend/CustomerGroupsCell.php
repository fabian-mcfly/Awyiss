<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Awyiss;
use Awyiss\Model\Enum\CustomerGroupAccessType;
use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Cell;
use RuntimeException;


/**
 * Shows the access settings and customer groups assigned to an entity
 */
class CustomerGroupsCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * @var \Cake\Collection\Collection $groups
	 */
	protected static Collection $groups;


	/**
	 * Builds the access and group assignments view
	 * Shows access settings and all customer groups that are available for assignment,
	 * as well as all already assigned customer groups.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	public function display(EntityInterface $entity): void {
		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/CustomerGroups')->setTemplate('group_assignments');

		$availableGroups = $this->getGroups();
		$assignedGroups = $entity->customerGroupAssignments ?? false;

		$assignedGroupIds = array_column($assignedGroups ?: [], 'customerGroupId');

		$availableGroups = $availableGroups->sortBy(function ($element) use ($assignedGroupIds) {
			return array_search($element->id, $assignedGroupIds);
		}, SORT_ASC);

		$this->set([
			'entity' => $entity,
			'customerGroupAccessSettings' => $entity->customerGroupAccessSettings ?? null,
			'accessTypes' => CustomerGroupAccessType::class,
			'availableGroups' => $availableGroups,
			'assignedGroups' => $assignedGroups,
		]);
	}


	/**
	 * Fetches the CustomerGroups records
	 *
	 * @return \Cake\Collection\Collection
	 */
	protected function getGroups(): Collection {
		if (!isset(static::$groups)) {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @noinspection PhpFieldAssignmentTypeMismatchInspection
			 */
			static::$groups = $this
				->fetchTable('CustomerGroups')
				->find('active')
				->all()
				->compile();
		}

		return static::$groups;
	}


	/**
	 * Retrieve the identity attribute from the current request
	 *
	 * @noinspection PhpUnused
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User $identity */
		$identity = $this->request->getAttribute(Awyiss::REALM_BACKEND . 'Identity');

		if (!$identity) {
			throw new RuntimeException('No identity found in the request.');
		}

		if (!($identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($identity), IdentityPermissionsInterface::class));
		}


		return $identity;
	}
}
