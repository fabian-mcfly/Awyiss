<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Route;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Route\Address;


/**
 * Test case for the Address class.
 *
 * @see \Awyiss\Utility\Route\Address
 */
class AddressTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Address::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructor(): void {
		$address = new Address(
			lat: 51.5074,
			lng: -0.1278,
			name: 'London',
			street: 'Baker Street',
			houseNumber: '221B',
			postalCode: 'NW1 6XE',
			city: 'London',
			country: 'UK',
			data: ['type' => 'landmark']
		);

		$this->assertEquals(51.5074, $address->getLat());
		$this->assertEquals(-0.1278, $address->getLng());
		$this->assertEquals('London', $address->getName());
		$this->assertEquals('Baker Street', $address->getStreet());
		$this->assertEquals('221B', $address->getHouseNumber());
		$this->assertEquals('NW1 6XE', $address->getPostalCode());
		$this->assertEquals('London', $address->getCity());
		$this->assertEquals('UK', $address->getCountry());
		$this->assertEquals(['type' => 'landmark'], $address->getData());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Address::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithMinimalParameters(): void {
		$address = new Address(51.5074, -0.1278);

		$this->assertEquals(51.5074, $address->getLat());
		$this->assertEquals(-0.1278, $address->getLng());
		$this->assertEquals('51.5074, -0.1278', $address->getName());
		$this->assertNull($address->getStreet());
		$this->assertNull($address->getHouseNumber());
		$this->assertNull($address->getPostalCode());
		$this->assertNull($address->getCity());
		$this->assertNull($address->getCountry());
		$this->assertEquals([], $address->getData());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Address::fromArray()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromArrayWithValidData(): void {
		$data = [
			'lat' => 51.5074,
			'lng' => -0.1278,
			'name' => 'London',
			'street' => 'Baker Street',
			'houseNumber' => '221B',
			'postalCode' => 'NW1 6XE',
			'city' => 'London',
			'country' => 'UK',
			'data' => ['type' => 'landmark'],
		];

		$address = Address::fromArray($data);

		$this->assertNotNull($address);
		$this->assertEquals(51.5074, $address->getLat());
		$this->assertEquals(-0.1278, $address->getLng());
		$this->assertEquals('London', $address->getName());
		$this->assertEquals('Baker Street', $address->getStreet());
		$this->assertEquals('221B', $address->getHouseNumber());
		$this->assertEquals('NW1 6XE', $address->getPostalCode());
		$this->assertEquals('London', $address->getCity());
		$this->assertEquals('UK', $address->getCountry());
		$this->assertEquals(['type' => 'landmark'], $address->getData());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Address::fromArray()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromArrayWithAlternativeKeys(): void {
		$data = [
			'lat' => 51.5074,
			'lng' => -0.1278,
			'name' => 'London',
			'street' => 'Baker Street',
			'housenumber' => '221B', // Wrong key
			'postalcode' => 'NW1 6XE', // Wrong key
			'city' => 'London',
			'country' => 'UK',
		];

		$address = Address::fromArray($data);

		$this->assertNotNull($address);
		$this->assertEquals('221B', $address->getHouseNumber());
		$this->assertEquals('NW1 6XE', $address->getPostalCode());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Address::fromArray()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromArrayWithInvalidData(): void {
		$this->assertNull(Address::fromArray(['lng' => -0.1278, 'name' => 'London'])); // Missing lat
		$this->assertNull(Address::fromArray(['lat' => 51.5074, 'name' => 'London'])); // Missing lng
		$this->assertNull(Address::fromArray(['lat' => 51.5074, 'lng' => -0.1278])); // Missing name
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Address::fromOrs()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromOrs(): void {
		$orsData = [
			'geometry' => [
				'coordinates' => [-0.1278, 51.5074], // ORS uses [lng, lat] order
			],
			'properties' => [
				'label' => 'London',
				'street' => 'Baker Street',
				'housenumber' => '221B',
				'postalcode' => 'NW1 6XE',
				'locality' => 'London',
				'country' => 'UK',
			],
		];

		$address = Address::fromOrs($orsData);

		$this->assertNotNull($address);
		$this->assertEquals(51.5074, $address->getLat());
		$this->assertEquals(-0.1278, $address->getLng());
		$this->assertEquals('London', $address->getName());
		$this->assertEquals('Baker Street', $address->getStreet());
		$this->assertEquals('221B', $address->getHouseNumber());
		$this->assertEquals('NW1 6XE', $address->getPostalCode());
		$this->assertEquals('London', $address->getCity());
		$this->assertEquals('UK', $address->getCountry());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Address::fromOrs()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromOrsWithInvalidData(): void {
		$this->assertNull(Address::fromOrs([
			'properties' => ['label' => 'London'],
		])); // Missing coordinates

		$this->assertNull(Address::fromOrs([
			'geometry' => ['coordinates' => [-0.1278, 51.5074]],
		])); // Missing properties.label
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Address::setLat()
	 * @see \Awyiss\Utility\Route\Address::setLng()
	 * @see \Awyiss\Utility\Route\Address::setName()
	 * @see \Awyiss\Utility\Route\Address::setStreet()
	 * @see \Awyiss\Utility\Route\Address::setHouseNumber()
	 * @see \Awyiss\Utility\Route\Address::setPostalCode()
	 * @see \Awyiss\Utility\Route\Address::setCity()
	 * @see \Awyiss\Utility\Route\Address::setCountry()
	 * @see \Awyiss\Utility\Route\Address::setData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetters(): void {
		$address = new Address(0, 0);

		$address->setLat(51.5074);
		$this->assertEquals(51.5074, $address->getLat());

		$address->setLng(-0.1278);
		$this->assertEquals(-0.1278, $address->getLng());

		$address->setName('London');
		$this->assertEquals('London', $address->getName());

		$address->setStreet('Baker Street');
		$this->assertEquals('Baker Street', $address->getStreet());

		$address->setHouseNumber('221B');
		$this->assertEquals('221B', $address->getHouseNumber());

		$address->setPostalCode('NW1 6XE');
		$this->assertEquals('NW1 6XE', $address->getPostalCode());

		$address->setCity('London');
		$this->assertEquals('London', $address->getCity());

		$address->setCountry('UK');
		$this->assertEquals('UK', $address->getCountry());

		$address->setData(['type' => 'landmark']);
		$this->assertEquals(['type' => 'landmark'], $address->getData());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Route\Address::toArray()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToArray(): void {
		$address = new Address(
			51.5074,
			-0.1278,
			'London',
			'Baker Street',
			'221B',
			'NW1 6XE',
			'London',
			'UK',
			['type' => 'landmark']
		);

		$expected = [
			'lat' => 51.5074,
			'lng' => -0.1278,
			'name' => 'London',
			'street' => 'Baker Street',
			'housenumber' => '221B',
			'postalcode' => 'NW1 6XE',
			'city' => 'London',
			'country' => 'UK',
			'data' => ['type' => 'landmark'],
		];

		$this->assertEquals($expected, $address->toArray());
	}
}
