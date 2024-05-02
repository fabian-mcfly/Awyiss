<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


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
	public function getOverviewQuery(): ?SelectQuery {
		return null;
	}


	/**
	 * @return void
	 */
	public function overview(): void {
	}
}
