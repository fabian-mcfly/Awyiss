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
		$params = [
			'api_key' => Configure::read('Awyiss.System.Frontend.route.orsApiKey'),
			'language' => $languageShortcode ?? Router::getRequest()->getParam('lang'),
			'text' => $search,
		];

		$url = 'https://api.openrouteservice.org/geocode/search';

		$client = $this->getClient();

		$response = $client->get(
			$url,
			$params,
			[
				'headers' => [
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
					'User-Agent' => 'Awyiss v' . Awyiss::VERSION,
				],
			]
		);

		if ($response->getStatusCode() !== 200) {
			return false;
		}

		$response = $response->getJson();

		if (empty($response['features'])) {
			return false;
		}

		$addresses = new AddressCollection();

		/** @var class-string<\Awyiss\Utility\Route\Address> $addressClass */
		$addressClass = App::className('Address', 'Utility/Route');

		foreach ($response['features'] as $feature) {
			$address = $addressClass::fromOrs($feature);
			if ($address) {
				$addresses->add($address);
			}
		}

		return $addresses;
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
		$params += [
			'language' => $languageShortcode ?? Router::getRequest()->getParam('lang'),
			'instructions_format' => 'html',
		];

		// The provided start and end addresses define the route.
		$params['coordinates'] = [
			[$start->getLng(), $start->getLat()],
			[$end->getLng(), $end->getLat()],
		];

		$url = 'https://api.openrouteservice.org/v2/directions/' . $transportationMode . '/geojson';

		$client = $this->getClient();

		$response = $client->post($url, json_encode($params), [
			'headers' => [
				'Accept' => 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
				'Authorization' => Configure::read('Awyiss.System.Frontend.route.orsApiKey'),
				'Content-Type' => 'application/json',
				'User-Agent' => 'Awyiss v' . Awyiss::VERSION,
			],
		]);

		if ($response->getStatusCode() !== 200) {
			return false;
		}

		$response = $response->getJson();

		if (!$response) {
			return false;
		}

		/** @var class-string<\Awyiss\Utility\Route\RouteInterface> $routeClass */
		$routeClass = App::className('Route', 'Utility/Route');

		return new $routeClass($start, $end, $response['features'][0]);
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
