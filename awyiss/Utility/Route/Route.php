<?php declare(strict_types=1);


namespace Awyiss\Utility\Route;


/**
 * Route class that implements RouteInterface.
 * This class represents a route between two addresses with GeoJSON data.
 */
class Route implements RouteInterface {
	/**
	 * @var \Awyiss\Utility\Route\AddressInterface
	 */
	protected AddressInterface $start;
	/**
	 * @var \Awyiss\Utility\Route\AddressInterface
	 */
	protected AddressInterface $end;
	/**
	 * @var array{
	 *     type: string,
	 *     properties: array<string, mixed>,
	 *     bbox?: array<float, float, float, float>,
	 *     geometry?: array<string, mixed>,
	 * }
	 */
	protected array $geoJson;


	/**
	 * @inheritDoc
	 */
	public function __construct(AddressInterface $start, AddressInterface $end, array $geoJson) {
		$this->start = $start;
		$this->end = $end;
		$this->geoJson = $geoJson;
	}


	/**
	 * @inheritDoc
	 */
	public function getStart(): AddressInterface {
		return $this->start;
	}


	/**
	 * @inheritDoc
	 */
	public function getEnd(): AddressInterface {
		return $this->end;
	}


	/**
	 * @inheritDoc
	 */
	public function getGeoJson(): array {
		return $this->geoJson;
	}


	/**
	 * @inheritDoc
	 */
	public function toArray(): array {
		return [
			'start' => $this->start->toArray(),
			'end' => $this->end->toArray(),
			'geoJson' => $this->geoJson,
		];
	}
}
