<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Cell;
use RuntimeException;


/**
 * Provides an overview of the form entries that were created since the last login
 */
class FormEntriesStatusCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * Generate the list of new entries and load templates/Backend/cell/FormEntries/status
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection DuplicatedCode
	 */
	public function display(): void {
		// Get the user's identity and session
		$lo_identity = $this->_getIdentity();

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/FormEntries')->setTemplate('status');

		$lb_accessible = $lo_identity->scopeIsAccessible('form_entries', [], 'read');

		$this->set([
			'accessible' => $lb_accessible,
		]);

		if (!$lb_accessible) {
			return;
		}

		$lo_session = $this->request->getSession();
		$lo_lastLogin = $lo_session->read('Backend.lastLogin');

		$lo_formEntriesTable = $this->fetchTable('FormEntries');
		$lo_formEntriesQuery = $lo_formEntriesTable->find()
		->contain(['Forms'])
		->where([
			'FormEntries.created_on >' => $lo_lastLogin ?? $lo_identity->createdOn,
			'FormEntries.body IS NOT' => null,
		])
		->orderBy([
			'FormEntries.created_on' => 'desc',
			'FormEntries.id' => 'desc',
		]);

		$this->set([
			'formEntries' => $lo_formEntriesQuery->all(),
			'lastLogin' => $lo_lastLogin,
		]);
	}


	/**
	 * Retrieve the identity attribute from the current request
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User $lo_identity */
		$lo_identity = $this->request->getAttribute('identity');

		if (!$lo_identity) {
			throw new RuntimeException('No identity found in the request.');
		}

		if (!($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}


		return $lo_identity;
	}
}
