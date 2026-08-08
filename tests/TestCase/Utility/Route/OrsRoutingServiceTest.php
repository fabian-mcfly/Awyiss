<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Route;


use Awyiss\Routing\Router;
use Awyiss\Utility\Route\Address;
use Awyiss\Utility\Route\AddressCollection;
use Awyiss\Utility\Route\OrsRoutingService;
use Awyiss\Utility\Route\RouteInterface;
use Awyiss\Utility\Route\RoutingServiceInterface;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;


/**
 * Test case for the OrsRoutingService class.
 *
 * @see \Awyiss\Utility\Route\OrsRoutingService
 */
class OrsRoutingServiceTest extends TestCase {
	/**
	 * Routing service instance
	 *
	 * @var \Awyiss\Utility\Route\OrsRoutingService&\PHPUnit\Framework\MockObject\MockObject
	 */
	protected RoutingServiceInterface $service;
	/**
	 * Original API key
	 *
	 * @var string|null
	 */
	protected ?string $originalApiKey;
	/**
	 * Original request
	 *
	 * @var ServerRequest|null
	 */
	protected ?ServerRequest $originalRequest;


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();

		// Store original API key
		$this->originalApiKey = Configure::read('Awyiss.System.Frontend.route.orsApiKey');

		// Store original request
		$this->originalRequest = Router::getRequest();

		// Set a test API key
		Configure::write('Awyiss.System.Frontend.route.orsApiKey', 'test-api-key');

		// Set a test request
		$request = new ServerRequest();
		$request = $request->withParam('lang', 'en');
		Router::setRequest($request);

		// Create the service with a modified getClient method
		$this->service = $this->getStubBuilder(OrsRoutingService::class)->onlyMethods(['getClient'])->getStub();
	}


	/**
	 * @inheritDoc
	 */
	public function tearDown(): void {
		// Restore original API key
		Configure::write('Awyiss.System.Frontend.route.orsApiKey', $this->originalApiKey);

		// Restore original request
		if ($this->originalRequest !== null) {
			Router::setRequest($this->originalRequest);
		}

		parent::tearDown();
	}


	/**
	 * Test findCoordinates method with successful response
	 *
	 * @return void
	 * @see \Awyiss\Utility\Route\OrsRoutingService::findCoordinates()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testFindCoordinates(): void {
		// Create sample response data
		$responseData = [
			'features' => [
				[
					'geometry' => [
						'coordinates' => [13.4050, 52.5200],
					],
					'properties' => [
						'label' => 'Berlin, Germany',
						'country' => 'Germany',
						'region' => 'Berlin',
						'locality' => 'Berlin',
						'confidence' => 0.9,
					],
				],
			],
		];

		// Create mock response
		$response = $this->createConfiguredStub(Response::class, [
			'getStatusCode' => 200,
			'getJson' => $responseData,
		]);

		// Create mock client
		$clientMock = $this->createStub(Client::class);
		$clientMock->method('get')->willReturn($response);

		// Configure service to use mock client
		$this->service->method('getClient')->willReturn($clientMock);

		// Execute the method
		$result = $this->service->findCoordinates('Berlin');

		// Assert result
		$this->assertInstanceOf(AddressCollection::class, $result);
		$this->assertEquals(1, $result->count());

		$address = $result->get(0);
		$this->assertEquals(52.5200, $address->getLat());
		$this->assertEquals(13.4050, $address->getLng());
		$this->assertEquals('Berlin, Germany', $address->getName());
	}


	/**
	 * Test findCoordinates method with multiple results
	 *
	 * @return void
	 * @see \Awyiss\Utility\Route\OrsRoutingService::findCoordinates()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testFindCoordinatesMultipleResults(): void {
		// Create sample response data with multiple addresses
		$responseData = [
			'features' => [
				[
					'geometry' => [
						'coordinates' => [13.4050, 52.5200],
					],
					'properties' => [
						'label' => 'Berlin, Germany',
						'country' => 'Germany',
						'region' => 'Berlin',
						'locality' => 'Berlin',
						'confidence' => 0.9,
					],
				],
				[
					'geometry' => [
						'coordinates' => [13.3833, 52.5167],
					],
					'properties' => [
						'label' => 'Berlin Mitte, Berlin, Germany',
						'country' => 'Germany',
						'region' => 'Berlin',
						'locality' => 'Berlin Mitte',
						'confidence' => 0.8,
					],
				],
				[
					'geometry' => [
						'coordinates' => [10.0133, 53.5653],
					],
					'properties' => [
						'label' => 'Berlin Street, Hamburg, Germany',
						'country' => 'Germany',
						'region' => 'Hamburg',
						'locality' => 'Hamburg',
						'confidence' => 0.7,
					],
				],
			],
		];

		// Create mock response
		$response = $this->createConfiguredStub(Response::class, [
			'getStatusCode' => 200,
			'getJson' => $responseData,
		]);

		// Create mock client
		$clientMock = $this->createStub(Client::class);
		$clientMock->method('get')->willReturn($response);

		// Configure service to use mock client
		$this->service->method('getClient')->willReturn($clientMock);

		// Execute the method
		$result = $this->service->findCoordinates('Berlin');

		// Assert result collection properties
		$this->assertInstanceOf(AddressCollection::class, $result);
		$this->assertEquals(3, $result->count());

		// Test first address
		$this->assertEquals(52.5200, $result->get(0)->getLat());
		$this->assertEquals(13.4050, $result->get(0)->getLng());
		$this->assertEquals('Berlin, Germany', $result->get(0)->getName());

		// Test second address
		$this->assertEquals(52.5167, $result->get(1)->getLat());
		$this->assertEquals(13.3833, $result->get(1)->getLng());
		$this->assertEquals('Berlin Mitte, Berlin, Germany', $result->get(1)->getName());

		// Test third address
		$this->assertEquals(53.5653, $result->get(2)->getLat());
		$this->assertEquals(10.0133, $result->get(2)->getLng());
		$this->assertEquals('Berlin Street, Hamburg, Germany', $result->get(2)->getName());
	}


	/**
	 * Test findCoordinates method with error response
	 *
	 * @return void
	 * @see \Awyiss\Utility\Route\OrsRoutingService::findCoordinates()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testFindCoordinatesError(): void {
		// Create mock response with error status
		$response = $this->createConfiguredStub(Response::class, [
			'getStatusCode' => 400,
		]);

		// Create mock client
		$clientMock = $this->createStub(Client::class);
		$clientMock->method('get')->willReturn($response);

		// Configure service to use mock client
		$this->service->method('getClient')->willReturn($clientMock);

		// Execute the method
		$result = $this->service->findCoordinates('Berlin');

		// Assert result
		$this->assertFalse($result);
	}


	/**
	 * Test findCoordinates method with empty features
	 *
	 * @return void
	 * @see \Awyiss\Utility\Route\OrsRoutingService::findCoordinates()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testFindCoordinatesEmptyFeatures(): void {
		// Create response with empty features
		$response = $this->createConfiguredStub(Response::class, [
			'getStatusCode' => 200,
			'getJson' => ['features' => []],
		]);

		// Create mock client
		$clientMock = $this->createStub(Client::class);
		$clientMock->method('get')->willReturn($response);

		// Configure service to use mock client
		$this->service->method('getClient')->willReturn($clientMock);

		// Execute the method
		$result = $this->service->findCoordinates('Berlin');

		// Assert result
		$this->assertFalse($result);
	}


	/**
	 * Test getRoute method with successful response
	 *
	 * @return void
	 * @see \Awyiss\Utility\Route\OrsRoutingService::getRoute()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetRoute(): void {
		$start = new Address(52.5200, 13.4050, 'Berlin');
		$end = new Address(48.8566, 2.3522, 'Paris');

		// Create sample route data
		$responseData = [
			'features' => [
				[
					'properties' => [
						'segments' => [
							[
								'distance' => 1050.3,
								'duration' => 3600.5,
								'steps' => [],
							],
						],
						'summary' => [
							'distance' => 1050.3,
							'duration' => 3600.5,
						],
					],
					'geometry' => [
						'coordinates' => [
							[13.4050, 52.5200],
							[2.3522, 48.8566],
						],
					],
				],
			],
		];

		// Create mock response
		$response = $this->createConfiguredStub(Response::class, [
			'getStatusCode' => 200,
			'getJson' => $responseData,
		]);

		// Create mock client
		$clientMock = $this->createStub(Client::class);
		$clientMock->method('post')->willReturn($response);

		// Configure service to use mock client
		$this->service->method('getClient')->willReturn($clientMock);

		// Execute the method
		$result = $this->service->getRoute($start, $end);

		// Assert result
		$this->assertInstanceOf(RouteInterface::class, $result);
		$this->assertSame($start, $result->getStart());
		$this->assertSame($end, $result->getEnd());
	}


	/**
	 * Test getRoute method with error response
	 *
	 * @return void
	 * @see \Awyiss\Utility\Route\OrsRoutingService::getRoute()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetRouteError(): void {
		// Create start and end addresses with correct parameter order
		$start = new Address(52.5200, 13.4050, 'Berlin');
		$end = new Address(48.8566, 2.3522, 'Paris');

		// Create mock response with error
		$response = $this->createConfiguredStub(Response::class, [
			'getStatusCode' => 400,
		]);

		// Create mock client
		$clientMock = $this->createStub(Client::class);
		$clientMock->method('post')->willReturn($response);

		// Configure service to use mock client
		$this->service->method('getClient')->willReturn($clientMock);

		// Execute the method
		$result = $this->service->getRoute($start, $end);

		// Assert result
		$this->assertFalse($result);
	}


	/**
	 * Test getRoute method with null response
	 *
	 * @return void
	 * @see \Awyiss\Utility\Route\OrsRoutingService::getRoute()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetRouteNullResponse(): void {
		// Create start and end addresses with correct parameter order
		$start = new Address(52.5200, 13.4050, 'Berlin');
		$end = new Address(48.8566, 2.3522, 'Paris');

		// Create mock response with null data
		$response = $this->createConfiguredStub(Response::class, [
			'getStatusCode' => 200,
			'getJson' => null,
		]);

		// Create mock client
		$clientMock = $this->createStub(Client::class);
		$clientMock->method('post')->willReturn($response);

		// Configure service to use mock client
		$this->service->method('getClient')->willReturn($clientMock);

		// Execute the method
		$result = $this->service->getRoute($start, $end);

		// Assert result
		$this->assertFalse($result);
	}
}
