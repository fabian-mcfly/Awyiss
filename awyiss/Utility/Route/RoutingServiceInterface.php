<?php declare(strict_types=1);


namespace Awyiss\Utility\Route;


/**
 * Interface RoutingServiceInterface
 */
interface RoutingServiceInterface {
	/**
	 * Find coordinates for a search term,
	 * preferably returning the found address
	 * in the language specified by `languageShortcode`.
	 *
	 * Returns an AddressCollection with the found coordinates,
	 * or false if no coordinates were found.
	 *
	 * @param string $search
	 * @param string|null $languageShortcode
	 * @return \Awyiss\Utility\Route\AddressCollection|false
	 */
	public function findCoordinates(string $search, ?string $languageShortcode = null): AddressCollection|false;


	/**
	 * Get a route between two coordinates,
	 * optionally specifying the transportation mode
	 * and language.
	 *
	 * @param \Awyiss\Utility\Route\AddressInterface $start
	 * @param \Awyiss\Utility\Route\AddressInterface $end
	 * @param string $transportationMode
	 * @param string|null $languageShortcode
	 * @param array $params
	 * @return \Awyiss\Utility\Route\RouteInterface|false
	 */
	public function getRoute(AddressInterface $start, AddressInterface $end, string $transportationMode = 'driving-car', ?string $languageShortcode = null, array $params = []): RouteInterface|false;
}
