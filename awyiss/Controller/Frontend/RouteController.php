<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\Routing\Router;
use Awyiss\Utility\Route\Address;
use Awyiss\Utility\Route\RoutingServiceInterface;
use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Jaybizzle\CrawlerDetect\CrawlerDetect;


/**
 * The Route Controller handles route actions.
 */
class RouteController extends AppController {
	/**
	 * @var string The default route preference (shortest, fastest, recommended)
	 */
	final public const string ROUTE_PREFERENCE_DEFAULT = self::ROUTE_PREFERENCE_SHORTEST;
	/**
	 * @var string Route preference for the fastest route
	 */
	final public const string ROUTE_PREFERENCE_FASTEST = 'fastest';
	/**
	 * @var string Route preference for the shortest route
	 */
	final public const string ROUTE_PREFERENCE_SHORTEST = 'shortest';
	/**
	 * @var string Route preference for the recommended route
	 */
	final public const string ROUTE_PREFERENCE_RECOMMENDED = 'recommended';


	/**
	 * @var \Awyiss\Utility\Route\RoutingServiceInterface
	 */
	protected RoutingServiceInterface $routingService;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->routingService = new (Configure::read('Awyiss.System.Frontend.route.routingService') ?? App::className('Ors', 'Utility/Route', 'RoutingService'))();
	}


	/**
	 * Tries to find the coordinates of a given address/search
	 * If multiple results are found, the controller will return
	 * a notice with the results and a 300 status code.
	 *
	 * @param string $search
	 * @see \Awyiss\Utility\Route\OrsRoutingService::findCoordinates()
	 * @noinspection PhpUnused
	 */
	public function findCoordinates(string $search): void {
		$this->viewBuilder()->setClassName('Json')->setOption('serialize', ['status', 'addresses', 'title', 'message']);

		$this->accessCheck();

		$lo_addresses = $this->routingService->findCoordinates($search, $this->request->getParam('lang'));

		if ($lo_addresses === false) {
			$this->set([
				'status' => 'error',
				'message' => __d('route', 'geocode_error_address'),
				'title' => null,
				'addresses' => null,
			]);

			$this->response = $this->response->withStatus(400);

			return;
		}

		if (count($lo_addresses) > 1) {
			$this->set([
				'status' => 'notice',
				'message' => __d('route', 'geocode_multiple_results_found'),
				'title' => __d('route', 'geocode_multiple_results_found_title'),
				'addresses' => $lo_addresses->toArray(),
			]);

			$this->response = $this->response->withStatus(300);

			return;
		}

		$this->set([
			'status' => 'success',
			'message' => __d('route', 'geocode_address_found'),
			'title' => null,
			'addresses' => $lo_addresses->toArray(),
		]);
	}

	/**
	 * Fetch the route between two coordinates.
	 * If the start coordinates are not given as lat/lng,
	 * the controller will try to find the coordinates.
	 * If multiple results are found, the controller will return
	 * a notice with the results and a 300 status code.
	 *
	 * @param string $start
	 * @param string $end
	 * @return void
	 * @see \Awyiss\Utility\Route\OrsRoutingService::findCoordinates()
	 * @see \Awyiss\Utility\Route\OrsRoutingService::getRoute()
	 */
	public function route(string $start, string $end): void {
		$this->viewBuilder()->setClassName('Json')->setOption('serialize', ['status', 'addresses', 'route', 'message']);

		$this->accessCheck();

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
				'addresses' => null,
				'route' => null,
			]);

			$this->response = $this->response->withStatus(400);

			return;
		}

		$lo_end = new Address(
			lat: (float)$la_end[0],
			lng: (float)$la_end[1],
		);

		$la_start = explode(',', $start);
		$la_start = array_map('trim', $la_start);

		if (
			count($la_start) !== 2 ||
			!preg_match('/^-?(90(\.0{1,6})?|[1-8]?\d(\.\d{1,6})?)$/', $la_start[0]) ||
			!preg_match('/^-?(180(\.0{1,6})?|1[0-7]\d(\.\d{1,6})?|\d{1,2}(\.\d{1,6})?)$/', $la_start[1])
		) {
			$lo_addresses = $this->routingService->findCoordinates($start, $this->request->getParam('lang'));

			if ($lo_addresses === false) {
				$this->set([
					'status' => 'error',
					'message' => __d('route', 'route_planner_error_start_coordinates'),
					'addresses' => null,
					'route' => null,
				]);

				$this->response = $this->response->withStatus(400);

				return;
			}

			if (count($lo_addresses) > 1) {
				$this->set([
					'status' => 'notice',
					'message' => __d('route', 'route_planner_multiple_results_found'),
					'addresses' => $lo_addresses->toArray(),
					'route' => null,
				]);

				$this->response = $this->response->withStatus(300);

				return;
			}

			$lo_start = $lo_addresses->get(0);
		}
		else {
			$lo_start = new Address(
				lat: (float)$la_start[0],
				lng: (float)$la_start[1],
			);
		}

		$ls_transportationMode = match ($this->request->getParam('transportationMode')) {
			'bike' => 'cycling-regular',
			'foot' => 'foot-walking',
			default => 'driving-car',
		};

		$la_params = [
			'preference' => match ($this->request->getParam('routePreference')) {
				self::ROUTE_PREFERENCE_FASTEST => self::ROUTE_PREFERENCE_FASTEST,
				self::ROUTE_PREFERENCE_SHORTEST => self::ROUTE_PREFERENCE_SHORTEST,
				self::ROUTE_PREFERENCE_RECOMMENDED => self::ROUTE_PREFERENCE_RECOMMENDED,
				default => self::ROUTE_PREFERENCE_DEFAULT,
			},
		];

		$lo_route = $this->routingService->getRoute($lo_start, $lo_end, $ls_transportationMode, $this->request->getParam('lang'), $la_params);
		$ls_message = __d('route', $lo_route !== false ? 'route_planner_directions_found' : 'route_planner_no_directions_found');

		$this->set([
			'status' => $lo_route !== false ? 'success' : 'error',
			'message' => $ls_message,
			'addresses' => null,
			'route' => $lo_route !== false ? $lo_route->toArray() : null,
		]);

		$this->response = $this->response->withStatus(200);
	}


	/**
	 * @return void
	 */
	protected function accessCheck(): void {
		$ls_referer = $this->request->getHeaderLine('Referer');
		if (!str_starts_with($ls_referer, Router::fullBaseUrl())) {
			throw new ForbiddenException(
				__d('route', 'route_planner_error_access')
			);
		}

		$ls_userAgent = $this->getRequest()->getHeaderLine('User-Agent');
		$lo_crawlerDetect = new CrawlerDetect();

		if ($lo_crawlerDetect->isCrawler($ls_userAgent)) {
			throw new ForbiddenException(
				__d('route', 'route_planner_error_access')
			);
		}
	}
}
