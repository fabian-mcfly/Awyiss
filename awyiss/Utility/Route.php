<?php declare(strict_types=1);


namespace Awyiss\Utility;


use Awyiss\Routing\Router;
use Cake\Core\Configure;


/**
 * Class Route provides utility functions for route planning.
 */
class Route {
	/**
	 * Find coordinates for a search term
	 * using the OpenRouteService API.
	 *
	 * @param string $search
	 * @param string|null $languageShortcode
	 * @return array|false
	 */
	public static function findCoordinates(string $search, ?string $languageShortcode = null): array|false {
		$la_params = [
			'api_key' => Configure::read('Awyiss.System.Frontend.orsApiKey'),
			'language' => $languageShortcode ?? Router::getRequest()->getParam('lang'),
			'text' => $search,
		];

		$ls_url = 'https://api.openrouteservice.org/geocode/search?';
		$ls_url .= http_build_query($la_params);

		$lo_curl = curl_init($ls_url);
		curl_setopt($lo_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($lo_curl, CURLOPT_HTTPHEADER, [
			'Accept: application/json',
			'Content-Type: application/json',
		]);

		$ls_response = curl_exec($lo_curl);
		$li_httpStatus = curl_getinfo($lo_curl, CURLINFO_HTTP_CODE);

		if (curl_errno($lo_curl) || $li_httpStatus !== 200) {
			curl_close($lo_curl);

			return false;
		}

		curl_close($lo_curl);

		$la_response = json_decode($ls_response, true);

		if (count($la_response['features']) === 0) {
			return false;
		}

		if (count($la_response['features']) === 1) {
			return [
				'lat' => $la_response['features'][0]['geometry']['coordinates'][1],
				'lng' => $la_response['features'][0]['geometry']['coordinates'][0],
			];
		}

		$la_results = [];
		foreach ($la_response['features'] as $la_feature) {
			$la_results[] = [
				'coordinates' => [
					'lat' => $la_feature['geometry']['coordinates'][1],
					'lng' => $la_feature['geometry']['coordinates'][0],
				],
				'name' => $la_feature['properties']['label'],
			];
		}

		return $la_results;
	}


	/**
	 * Get a route between two coordinates
	 * using the OpenRouteService API.
	 *
	 * @param array $start
	 * @param array $end
	 * @param string $transportationMode
	 * @param string|null $languageShortcode
	 * @param array $params
	 * @return array|false
	 */
	public static function getRoute(array $start, array $end, string $transportationMode = 'driving-car', ?string $languageShortcode = null, array $params = []): array|false {
		$la_params = $params + [
			'language' => $languageShortcode ?? Router::getRequest()->getParam('lang'),
			'coordinates' => [
				[$start['lng'], $start['lat']],
				[$end['lng'], $end['lat']],
			],
			'instructions_format' => 'html',
		];

		$ls_url = 'https://api.openrouteservice.org/v2/directions/' . $transportationMode . '/geojson';

		$lo_curl = curl_init($ls_url);
		curl_setopt($lo_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($lo_curl, CURLOPT_HTTPHEADER, [
			'Accept: application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
			'Authorization: ' . Configure::read('Awyiss.System.Frontend.orsApiKey'),
			'Content-Type: application/json',
		]);
		curl_setopt($lo_curl, CURLOPT_POST, true);
		curl_setopt($lo_curl, CURLOPT_POSTFIELDS, json_encode($la_params));

		$ls_response = curl_exec($lo_curl);
		$li_httpStatus = curl_getinfo($lo_curl, CURLINFO_HTTP_CODE);

		if (curl_errno($lo_curl) || $li_httpStatus !== 200) {
			curl_close($lo_curl);

			return false;
		}

		curl_close($lo_curl);

		$la_response = json_decode($ls_response, true);

		if (!$la_response) {
			return false;
		}

		return $la_response + [
			'start' => [
				'lat' => (float)$start['lat'],
				'lng' => (float)$start['lng'],
			],
			'end' => [
				'lat' => (float)$end['lat'],
				'lng' => (float)$end['lng'],
			],
		];
	}
}
