<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Cell;
use RuntimeException;


/**
 * Provides an overview of the pages that were not found since the last login
 */
class PagesNotFoundStatusCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * Generate the list of new entries and load templates/Backend/cell/PagesNotFound/status
	 *
	 * @return void
	 */
	public function display(): void {
		// Get the user's identity and session
		$lo_identity = $this->_getIdentity();

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/PagesNotFound')->setTemplate('status');

		$lb_accessible = $lo_identity->scopeIsAccessible('pages_not_found', [], 'read');

		$this->set([
			'accessible' => $lb_accessible,
		]);

		if (!$lb_accessible) {
			return;
		}

		$lo_session = $this->request->getSession();
		$lo_lastLogin = $lo_session->read('Backend.lastLogin');

		$lo_pagesNotFoundTable = $this->fetchTable('PagesNotFound');
		$lo_pagesNotFoundQuery = $lo_pagesNotFoundTable->find();
		$lo_pagesNotFoundQuery->select([
			'occurrences' => $lo_pagesNotFoundQuery->func()->count('*'),
		])
		->enableAutoFields()
		->where([
			'created_on >' => $lo_lastLogin,
		])
		->groupBy('slug')
		->orderBy([
			'created_on' => 'desc',
			'id' => 'desc',
		]);

		$this->set([
			'pagesNotFound' => $lo_pagesNotFoundQuery->all(),
			'lastLogin' => $lo_lastLogin,
		]);
	}


	/**
	 * Retreive the identity attribute from the current request
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User|\Awyiss\Model\Entity\UsersExternal $lo_identity */
		$lo_identity = $this->request->getAttribute('identity');
		if (!($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}


		return $lo_identity;
	}
}
