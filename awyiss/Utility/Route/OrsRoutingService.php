<?php declare(strict_types=1);


namespace Awyiss\Utility\Route;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Http\Client;


/**
 * Class OrsRoutingService provides utility functions for route planning
 * using the OpenRouteService API.
 */
class OrsRoutingService implements RoutingServiceInterface {
	/**
	 * @inheritDoc
	 */
	public function findCoordinates(string $search, ?string $languageShortcode = null): AddressCollection|false {
		$la_params = [
			'api_key' => Configure::read('Awyiss.System.Frontend.route.orsApiKey'),
			'language' => $languageShortcode ?? Router::getRequest()->getParam('lang'),
			'text' => $search,
		];

		$ls_url = 'https://api.openrouteservice.org/geocode/search';

		$lo_client = $this->getClient();

		$lo_response = $lo_client->get(
			$ls_url,
			$la_params,
			[
				'headers' => [
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
					'User-Agent' => 'Awyiss v' . Awyiss::VERSION,
				],
			]
		);

		if ($lo_response->getStatusCode() !== 200) {
			return false;
		}

		$la_response = $lo_response->getJson();

		if (empty($la_response['features'])) {
			return false;
		}

		$lo_addresses = new AddressCollection();

		/** @var class-string<\Awyiss\Utility\Route\Address> $ls_addressClass */
		$ls_addressClass = App::className('Address', 'Utility/Route');

		foreach ($la_response['features'] as $la_feature) {
			$lo_address = $ls_addressClass::fromOrs($la_feature);
			if ($lo_address) {
				$lo_addresses->add($lo_address);
			}
		}

		return $lo_addresses;
	}


	/**
	 * @inheritDoc
	 */
	public function getRoute(
		AddressInterface $start,
		AddressInterface $end,
		string $transportationMode = 'driving-car',
		?string $languageShortcode = null,
		array $params = []
	): RouteInterface|false {
		$la_params = $params + [
			'language' => $languageShortcode ?? Router::getRequest()->getParam('lang'),
			'instructions_format' => 'html',
		];

		// The provided start and end addresses define the route.
		$la_params['coordinates'] = [
			[$start->getLng(), $start->getLat()],
			[$end->getLng(), $end->getLat()],
		];

		$ls_url = 'https://api.openrouteservice.org/v2/directions/' . $transportationMode . '/geojson';

		$lo_client = $this->getClient();

		$lo_response = $lo_client->post($ls_url, json_encode($la_params), [
			'headers' => [
				'Accept' => 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
				'Authorization' => Configure::read('Awyiss.System.Frontend.route.orsApiKey'),
				'Content-Type' => 'application/json',
				'User-Agent' => 'Awyiss v' . Awyiss::VERSION,
			],
		]);

		if ($lo_response->getStatusCode() !== 200) {
			return false;
		}

		$la_response = $lo_response->getJson();

		if (!$la_response) {
			return false;
		}

		/** @var class-string<\Awyiss\Utility\Route\RouteInterface> $ls_routeClass */
		$ls_routeClass = App::className('Route', 'Utility/Route');

		return new $ls_routeClass($start, $end, $la_response['features'][0]);
	}


	/**
	 * @return \Cake\Http\Client
	 */
	protected function getClient(): Client {
		return new Client([
			'timeout' => 10,
			'http_errors' => false,
		]);
	}
}
