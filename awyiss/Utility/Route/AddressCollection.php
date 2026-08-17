<?php declare(strict_types=1);


namespace Awyiss\Utility\Route;


use ArrayIterator;
use Awyiss\Core\App;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;


/**
 * AddressCollection Class
 * This class represents a collection of addresses.
 *
 * @see \Awyiss\Utility\Route\AddressInterface
 */
class AddressCollection implements Countable, IteratorAggregate {
	/**
	 * @var class-string<\Awyiss\Utility\Route\AddressInterface>
	 */
	protected string $addressClass;
	/**
	 * @var array<int, \Awyiss\Utility\Route\AddressInterface>
	 */
	protected array $addresses = [];


	/**
	 * @param array<int, \Awyiss\Utility\Route\AddressInterface|array{lat: float, lng: float, name: string, data: array}> $addresses
	 */
	public function __construct(array $addresses = []) {
		$this->addressClass = App::className('Address', 'Utility/Route');

		foreach ($addresses as $address) {
			$this->add($address);
		}
	}


	/**
	 * @param array<\Awyiss\Utility\Route\AddressInterface|array> ...$addresses
	 * @return static
	 */
	public function add(AddressInterface|array ...$addresses): static {
		foreach ($addresses as $address) {
			if (!$address instanceof AddressInterface) {
				$address = $this->addressClass::fromArray($address);

				if (!$address) {
					throw new InvalidArgumentException('Invalid address array provided');
				}
			}

			$this->addresses[] = $address;
		}

		return $this;
	}


	/**
	 * @return array<int, \Awyiss\Utility\Route\AddressInterface>
	 */
	public function all(): array {
		return $this->addresses;
	}


	/**
	 * @param int $index
	 * @return \Awyiss\Utility\Route\AddressInterface|null
	 */
	public function get(int $index): ?AddressInterface {
		if (!isset($this->addresses[ $index ])) {
			return null;
		}

		return $this->addresses[ $index ];
	}


	/**
	 * @param int $index
	 * @return static
	 */
	public function remove(int $index): static {
		if (!isset($this->addresses[ $index ])) {
			throw new InvalidArgumentException('No address found at index ' . $index);
		}

		unset($this->addresses[ $index ]);
		$this->addresses = array_values($this->addresses);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function count(): int {
		return count($this->addresses);
	}


	/**
	 * @inheritDoc
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator($this->addresses);
	}


	/**
	 * Convert the collection to an array of addresses.
	 *
	 * If `$deep` is true, it will return the addresses
	 * as arrays as well.
	 *
	 * @param bool $deep Whether to convert addresses to arrays
	 * @return array<int, \Awyiss\Utility\Route\AddressInterface>
	 */
	public function toArray(bool $deep = true): array {
		if (!$deep) {
			return $this->addresses;
		}

		$addresses = [];
		foreach ($this->addresses as $address) {
			if ($address instanceof AddressInterface) {
				$addresses[] = $address->toArray();
			}
		}

		return $addresses;
	}
}
