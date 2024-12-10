<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Cell;
use RuntimeException;


/**
 * Provides an overview of the urls that were not found since the last login
 */
class UrlsNotFoundStatusCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * Generate the list of new entries and load templates/Backend/cell/UrlsNotFound/status
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(): void {
		// Get the user's identity and session
		$lo_identity = $this->_getIdentity();

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/UrlsNotFound')->setTemplate('status');

		$lb_accessible = $lo_identity->scopeIsAccessible('urls_not_found', [], 'read');

		$this->set([
			'accessible' => $lb_accessible,
		]);

		if (!$lb_accessible) {
			return;
		}

		$lo_session = $this->request->getSession();
		$lo_lastLogin = $lo_session->read('Backend.lastLogin');

		$lo_pagesTable = $this->fetchTable('Pages');
		$lo_pagesQuery = $lo_pagesTable->find('active', skipPageRoleCheck: true)
		->disableAutoFields()
		->find('published')
		->select(function ($query) {
			return ['slug' => $query->func()->concat(['/', 'language_shortcode' => 'identifier', '/', 'slug' => 'identifier'])];
		});

		$lo_urlHistoryTable = $this->fetchTable('UrlHistory');
		$lo_urlHistoryQuery = $lo_urlHistoryTable->find()
		->disableAutoFields()
		->select(function ($query) {
			return ['url' => $query->func()->concat(['/', 'url' => 'identifier'])];
		});

		$lo_urlsNotFoundTable = $this->fetchTable('UrlsNotFound');
		$lo_urlsNotFoundQuery = $lo_urlsNotFoundTable->find();
		$lo_urlsNotFoundQuery->select([
			'occurrences' => $lo_urlsNotFoundQuery->func()->count('*'),
		])
		->enableAutoFields()
		->where([
			'created_on >' => $lo_lastLogin ?? $lo_identity->createdOn,
		])
		->where(function (QueryExpression $exp) use ($lo_pagesQuery, $lo_urlHistoryQuery) {
			return $exp->notIn('url', $lo_pagesQuery)->notIn('url', $lo_urlHistoryQuery);
		})
		->groupBy('url')
		->orderBy([
			'created_on' => 'desc',
			'id' => 'desc',
		]);

		$this->set([
			'urlsNotFound' => $lo_urlsNotFoundQuery->all(),
			'lastLogin' => $lo_lastLogin,
		]);
	}


	/**
	 * Retreive the identity attribute from the current request
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
