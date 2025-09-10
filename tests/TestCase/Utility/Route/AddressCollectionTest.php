<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Route;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Route\Address;
use Awyiss\Utility\Route\AddressCollection;
use Awyiss\Utility\Route\AddressInterface;
use InvalidArgumentException;


/**
 * Test case for the AddressCollection class.
 *
 * @see \Awyiss\Utility\Route\AddressCollection
 */
class AddressCollectionTest extends TestCase {
	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithEmptyArray(): void {
		$collection = new AddressCollection();
		$this->assertCount(0, $collection);
		$this->assertEquals([], $collection->all());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithAddressObjects(): void {
		$address1 = new Address(51.5074, -0.1278, 'London');
		$address2 = new Address(48.8566, 2.3522, 'Paris');

		$collection = new AddressCollection([$address1, $address2]);

		$this->assertCount(2, $collection);
		$this->assertSame($address1, $collection->get(0));
		$this->assertSame($address2, $collection->get(1));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithAddressArrays(): void {
		$addressArray1 = [
			'lat' => 51.5074,
			'lng' => -0.1278,
			'name' => 'London',
		];

		$addressArray2 = [
			'lat' => 48.8566,
			'lng' => 2.3522,
			'name' => 'Paris',
		];

		$collection = new AddressCollection([$addressArray1, $addressArray2]);

		$this->assertCount(2, $collection);
		$this->assertInstanceOf(AddressInterface::class, $collection->get(0));
		$this->assertInstanceOf(AddressInterface::class, $collection->get(1));
		$this->assertEquals(51.5074, $collection->get(0)->getLat());
		$this->assertEquals(48.8566, $collection->get(1)->getLat());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithAddressesMixed(): void {
		$address1 = new Address(51.5074, -0.1278, 'London');
		$addressArray2 = [
			'lat' => 48.8566,
			'lng' => 2.3522,
			'name' => 'Paris',
		];

		$collection = new AddressCollection([$address1, $addressArray2]);

		$this->assertCount(2, $collection);
		$this->assertSame($address1, $collection->get(0));
		$this->assertInstanceOf(AddressInterface::class, $collection->get(1));
		$this->assertEquals(48.8566, $collection->get(1)->getLat());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddSingleAddress(): void {
		$collection = new AddressCollection();
		$address = new Address(51.5074, -0.1278, 'London');

		$result = $collection->add($address);

		$this->assertSame($collection, $result);
		$this->assertCount(1, $collection);
		$this->assertSame($address, $collection->get(0));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddMultipleAddresses(): void {
		$collection = new AddressCollection();
		$address1 = new Address(51.5074, -0.1278, 'London');
		$address2 = new Address(48.8566, 2.3522, 'Paris');

		$collection->add($address1, $address2);

		$this->assertCount(2, $collection);
		$this->assertSame($address1, $collection->get(0));
		$this->assertSame($address2, $collection->get(1));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddAddressArray(): void {
		$collection = new AddressCollection();
		$addressArray = [
			'lat' => 51.5074,
			'lng' => -0.1278,
			'name' => 'London',
		];

		$collection->add($addressArray);

		$this->assertCount(1, $collection);
		$this->assertEquals(51.5074, $collection->get(0)->getLat());
		$this->assertEquals(-0.1278, $collection->get(0)->getLng());
		$this->assertEquals('London', $collection->get(0)->getName());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddInvalidAddressArray(): void {
		$collection = new AddressCollection();
		$invalidArray = ['name' => 'London']; // Missing lat and lng

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid address array provided');

		$collection->add($invalidArray);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetValidIndex(): void {
		$address = new Address(51.5074, -0.1278, 'London');
		$collection = new AddressCollection([$address]);

		$this->assertSame($address, $collection->get(0));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetInvalidIndex(): void {
		$collection = new AddressCollection();

		$this->assertNull($collection->get(0));
		$this->assertNull($collection->get(999));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveValidIndex(): void {
		$address1 = new Address(51.5074, -0.1278, 'London');
		$address2 = new Address(48.8566, 2.3522, 'Paris');
		$collection = new AddressCollection([$address1, $address2]);

		$result = $collection->remove(0);

		$this->assertSame($collection, $result);
		$this->assertCount(1, $collection);
		$this->assertSame($address2, $collection->get(0));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveInvalidIndex(): void {
		$collection = new AddressCollection();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('No address found at index 0');

		$collection->remove(0);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCountable(): void {
		$collection = new AddressCollection();
		$this->assertCount(0, $collection);

		$address = new Address(51.5074, -0.1278, 'London');
		$collection->add($address);
		$this->assertCount(1, $collection);

		$collection->add(new Address(48.8566, 2.3522, 'Paris'));
		$this->assertCount(2, $collection);

		$collection->remove(0);
		$this->assertCount(1, $collection);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIteratorAggregate(): void {
		$address1 = new Address(51.5074, -0.1278, 'London');
		$address2 = new Address(48.8566, 2.3522, 'Paris');
		$collection = new AddressCollection([$address1, $address2]);

		$addresses = [];
		foreach ($collection as $address) {
			$addresses[] = $address;
		}

		$this->assertCount(2, $addresses);
		$this->assertSame($address1, $addresses[0]);
		$this->assertSame($address2, $addresses[1]);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToArrayShallow(): void {
		$address1 = new Address(51.5074, -0.1278, 'London');
		$address2 = new Address(48.8566, 2.3522, 'Paris');
		$collection = new AddressCollection([$address1, $address2]);

		$result = $collection->toArray(false);

		$this->assertCount(2, $result);
		$this->assertSame($address1, $result[0]);
		$this->assertSame($address2, $result[1]);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToArrayDeep(): void {
		$address1 = new Address(51.5074, -0.1278, 'London');
		$address2 = new Address(48.8566, 2.3522, 'Paris');
		$collection = new AddressCollection([$address1, $address2]);

		$result = $collection->toArray();

		$this->assertCount(2, $result);
		$this->assertIsArray($result[0]);
		$this->assertIsArray($result[1]);
		$this->assertEquals(51.5074, $result[0]['lat']);
		$this->assertEquals(48.8566, $result[1]['lat']);
	}
}
