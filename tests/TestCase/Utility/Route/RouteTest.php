<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Route;


use Awyiss\Utility\Route\AddressInterface;
use Awyiss\Utility\Route\Route;
use PHPUnit\Framework\TestCase;


/**
 * Test case for the Route class.
 *
 * @see \Awyiss\Utility\Route\Route
 */
class RouteTest extends TestCase {
	/**
	 * @var \Awyiss\Utility\Route\AddressInterface&\PHPUnit\Framework\MockObject\MockObject
	 */
	private AddressInterface $start;
	/**
	 * @var \Awyiss\Utility\Route\AddressInterface&\PHPUnit\Framework\MockObject\MockObject
	 */
	private AddressInterface $end;
	/**
	 * @var array
	 */
	private array $geoJson;


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		// Create mock AddressInterface objects
		$this->start = $this->createMock(AddressInterface::class);
		$this->start->method('toArray')->willReturn([
			'lat' => 51.5074,
			'lng' => -0.1278,
			'name' => 'London',
		]);

		$this->end = $this->createMock(AddressInterface::class);
		$this->end->method('toArray')->willReturn([
			'lat' => 48.8566,
			'lng' => 2.3522,
			'name' => 'Paris',
		]);

		// Sample GeoJSON data
		$this->geoJson = [
			'type' => 'FeatureCollection',
			'properties' => [
				'distance' => 340000,
				'duration' => 12600,
			],
			'bbox' => [-0.1278, 48.8566, 2.3522, 51.5074],
			'geometry' => [
				'type' => 'LineString',
				'coordinates' => [
					[-0.1278, 51.5074],
					[2.3522, 48.8566],
				],
			],
		];
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Route::getStart()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetStart(): void {
		$route = new Route($this->start, $this->end, $this->geoJson);

		$this->assertSame($this->start, $route->getStart());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Route::getEnd()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetEnd(): void {
		$route = new Route($this->start, $this->end, $this->geoJson);

		$this->assertSame($this->end, $route->getEnd());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Route::getGeoJson()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetGeoJson(): void {
		$route = new Route($this->start, $this->end, $this->geoJson);

		$this->assertSame($this->geoJson, $route->getGeoJson());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Route::toArray()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToArray(): void {
		$route = new Route($this->start, $this->end, $this->geoJson);

		$expected = [
			'start' => [
				'lat' => 51.5074,
				'lng' => -0.1278,
				'name' => 'London',
			],
			'end' => [
				'lat' => 48.8566,
				'lng' => 2.3522,
				'name' => 'Paris',
			],
			'geoJson' => $this->geoJson,
		];

		$this->assertEquals($expected, $route->toArray());
	}
}
