<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\Routing\Router;
use Cake\Core\Configure;


/**
 * The Route Controller handles route actions.
 */
class RouteController extends AppController {
	/**
	 * Tries to find the coordinates of a given address/search string
	 * using the OpenRouteService API.
	 *
	 * If multiple results are found, the controller will return
	 * a notice with the results and a 300 status code.
	 *
	 * @param string $search
	 */
	public function findCoordinates(string $search): void {
		$this->viewBuilder()->setClassName('Json')->setOption('serialize', ['status', 'data', 'title', 'message']);

		$ls_orsApiKey = $this->getOrsApiKey();

		if (!$ls_orsApiKey) {
			// Set the response data
			$this->set([
				'status' => 'error',
				'message' => __d('route', 'geocode_error_api_key_missing'),
				'data' => null,
			]);

			$this->response = $this->response->withStatus(500);

			return;
		}

		/** @var class-string<\Awyiss\Utility\Route> $ls_routeClass */
		$ls_routeClass = App::className('Route', 'Utility');

		$la_data = $ls_routeClass::findCoordinates($search, $this->request->getParam('lang'));

		if ($la_data === false) {
			$this->set([
				'status' => 'error',
				'message' => __d('route', 'geocode_error_address'),
				'data' => null,
			]);

			$this->response = $this->response->withStatus(400);

			return;
		}

		if (!isset($la_data['lat']) || !isset($la_data['lng'])) {
			$this->set([
				'status' => 'notice',
				'message' => __d('route', 'geocode_multiple_results_found'),
				'title' => __d('route', 'geocode_multiple_results_found_title'),
				'data' => $la_data,
			]);

			$this->response = $this->response->withStatus(300);

			return;
		}

		$this->set([
			'status' => 'success',
			'message' => __d('route', 'geocode_address_found'),
			'data' => $la_data,
		]);
	}

	/**
	 * Fetch the route between two coordinates.
	 *
	 * If the start coordinates are not given as lat/lng,
	 * the controller will try to find the coordinates
	 * using the OpenRouteService API.
	 *
	 * @param string $start
	 * @param string $end
	 * @return void
	 */
	public function route(string $start, string $end): void {
		$this->viewBuilder()->setClassName('Json')->setOption('serialize', ['status', 'data', 'message']);

		$ls_orsApiKey = $this->getOrsApiKey();

		if (!$ls_orsApiKey) {
			// Set the response data
			$this->set([
				'status' => 'error',
				'message' => __d('route', 'route_planner_error_api_key_missing'),
				'data' => null,
			]);

			$this->response = $this->response->withStatus(500);

			return;
		}

		/** @var class-string<\Awyiss\Utility\Route> $ls_routeClass */
		$ls_routeClass = App::className('Route', 'Utility');

		$la_end = explode(',', $end);
		$la_end = array_map('trim', $la_end);

		if (
			count($la_end) !== 2 ||
			!preg_match('/^-?(90(\.0{1,6})?|[1-8]?\d(\.\d{1,6})?)$/', $la_end[0]) ||
			!preg_match('/^-?(180(\.0{1,6})?|1[0-7]\d(\.\d{1,6})?|\d{1,2}(\.\d{1,6})?)$/', $la_end[1])
		) {
			$this->set([
				'status' => 'error',
				'message' => __d('route', 'route_planner_error_end_coordinates'),
				'data' => null,
			]);

			$this->response = $this->response->withStatus(400);

			return;
		}

		$la_end = [
			'lat' => $la_end[0],
			'lng' => $la_end[1],
		];

		$la_start = explode(',', $start);
		$la_start = array_map('trim', $la_start);

		if (
			count($la_start) !== 2 ||
			!preg_match('/^-?(90(\.0{1,6})?|[1-8]?\d(\.\d{1,6})?)$/', $la_start[0]) ||
			!preg_match('/^-?(180(\.0{1,6})?|1[0-7]\d(\.\d{1,6})?|\d{1,2}(\.\d{1,6})?)$/', $la_start[1])
		) {
			$la_start = $ls_routeClass::findCoordinates($start, $this->request->getParam('lang'));

			if ($la_start === false) {
				$this->set([
					'status' => 'error',
					'message' => __d('route', 'route_planner_error_start_coordinates'),
					'data' => null,
				]);

				$this->response = $this->response->withStatus(400);

				return;
			}

			if (!isset($la_start['lat']) || !isset($la_start['lng'])) {
				$this->set([
					'status' => 'notice',
					'message' => __d('route', 'route_planner_multiple_results_found'),
					'data' => $la_start,
				]);

				$this->response = $this->response->withStatus(300);

				return;
			}
		}
		else {
			$la_start = [
				'lat' => $la_start[0],
				'lng' => $la_start[1],
			];
		}

		$ls_transportationMode = match ($this->request->getParam('transportationMode')) {
			'bike' => 'cycling-regular',
			'foot' => 'foot-walking',
			default => 'driving-car',
		};

		$la_data = $ls_routeClass::getRoute($la_start, $la_end, $ls_transportationMode, $this->request->getParam('lang'));
		$ls_message = __d('route', is_array($la_data) ? 'route_planner_directions_found' : 'route_planner_no_directions_found');

		$this->set([
			'status' => is_array($la_data) ? 'success' : 'error',
			'message' => $ls_message,
			'data' => $la_data ?: null,
		]);

		$this->response = $this->response->withStatus(200);
	}


	/**
	 * @return mixed|null
	 */
	protected function getOrsApiKey(): mixed {
		$ls_orsApiKey = Configure::read('Awyiss.System.Frontend.orsApiKey');

		$ls_referer = $this->request->getHeaderLine('Referer');
		if (!str_starts_with($ls_referer, Router::url('/', true))) {
			$ls_orsApiKey = null;
		}

		return $ls_orsApiKey;
	}
}
