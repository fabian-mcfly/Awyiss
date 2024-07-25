<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Cake\ORM\Query\SelectQuery;


/**
 * Handles the dashboard of the backend
 */
class DashboardController extends Controller {
	protected ?string $defaultTable = '';


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return null;
	}


	/**
	 * @return void
	 */
	public function overview(): void {
		$lo_session = $this->request->getSession();
		$lo_lastLogin = $lo_session->read('Backend.lastLogin');

		$this->set([
			'lastLogin' => $lo_lastLogin,
		]);
	}
}
