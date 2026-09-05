<?php declare(strict_types=1);


namespace Awyiss\Utility\Route;


/**
 * Interface RouteInterface
 * defines the structure for route objects.
 */
interface RouteInterface {
	/**
	 * @param \Awyiss\Utility\Route\AddressInterface $start
	 * @param \Awyiss\Utility\Route\AddressInterface $end
	 * @param array<string, mixed> $geoJson GeoJSON route data.
	 * @phpstan-param array{
	 *     type: string,
	 *     properties: array<string, mixed>,
	 *     bbox?: array{0: float, 1: float, 2: float, 3: float},
	 *     geometry?: array<string, mixed>,
	 * } $geoJson
	 */
	public function __construct(
		AddressInterface $start,
		AddressInterface $end,
		array $geoJson,
	);


	/**
	 * Get the start address of the route.
	 *
	 * @return AddressInterface
	 */
	public function getStart(): AddressInterface;


	/**
	 * Get the end address of the route.
	 *
	 * @return AddressInterface
	 */
	public function getEnd(): AddressInterface;


	/**
	 * Get the GeoJSON representation of the route.
	 *
	 * @return array
	 */
	public function getGeoJson(): array;


	/**
	 * Convert the route to an array representation.
	 *
	 * @return array{
	 *     start: \Awyiss\Utility\Route\AddressInterface,
	 *     end: \Awyiss\Utility\Route\AddressInterface,
	 *     geoJson: array<string, mixed>,
	 * }
	 */
	public function toArray(): array;
}
