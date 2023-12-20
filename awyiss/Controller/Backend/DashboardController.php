<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * Handles the dashboard of the backend
 */
class DashboardController extends Controller {
	protected array $categorize = [
		'enabled' => FALSE,
	];
	protected ?string $defaultTable = '';


	/**
	 * @return void
	 */
	public function overview(): void {
	}
}
