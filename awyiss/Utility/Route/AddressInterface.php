<?php declare(strict_types=1);


namespace Awyiss\Utility\Route;


/**
 * Interface AddressInterface defines the structure for address objects.
 */
interface AddressInterface {
	/**
	 * @param float|int $lat
	 * @param float|int $lng
	 * @param string|null $name
	 * @param string|null $street
	 * @param string|null $houseNumber
	 * @param string|null $postalCode
	 * @param string|null $city
	 * @param string|null $country
	 * @param array $data
	 */
	public function __construct(
		float|int $lat,
		float|int $lng,
		?string $name = null,
		?string $street = null,
		?string $houseNumber = null,
		?string $postalCode = null,
		?string $city = null,
		?string $country = null,
		array $data = []
	);


	/**
	 * Create an Address object from an associative array.
	 *
	 * @param array $data
	 */
	public static function fromArray(array $data): ?static;


	/**
	 * Get the latitude of the address.
	 *
	 * @return float
	 */
	public function getLat(): float;


	/**
	 * Set the latitude of the address.
	 *
	 * @param float $lat
	 * @return static
	 */
	public function setLat(float $lat): static;


	/**
	 * Get the longitude of the address.
	 *
	 * @return float
	 */
	public function getLng(): float;


	/**
	 * Set the longitude of the address.
	 *
	 * @param float $lng
	 * @return static
	 */
	public function setLng(float $lng): static;


	/**
	 * Get the name of the address.
	 *
	 * @return string
	 */
	public function getName(): string;


	/**
	 * Set the name of the address.
	 *
	 * @param string $name
	 * @return static
	 */
	public function setName(string $name): static;


	/**
	 * @return string|null
	 */
	public function getStreet(): ?string;


	/**
	 * @param string|null $street
	 * @return $this
	 */
	public function setStreet(?string $street): static;


	/**
	 * @return string|null
	 */
	public function getHouseNumber(): ?string;


	/**
	 * @param string|null $houseNumber
	 * @return $this
	 */
	public function setHouseNumber(?string $houseNumber): static;


	/**
	 * @return string|null
	 */
	public function getPostalCode(): ?string;


	/**
	 * @param string|null $postalCode
	 * @return $this
	 */
	public function setPostalCode(?string $postalCode): static;


	/**
	 * @return string|null
	 */
	public function getCity(): ?string;


	/**
	 * @param string|null $city
	 * @return $this
	 */
	public function setCity(?string $city): static;


	/**
	 * @return string|null
	 */
	public function getCountry(): ?string;


	/**
	 * @param string|null $country
	 * @return $this
	 */
	public function setCountry(?string $country): static;


	/**
	 * Get additional data associated with the address.
	 *
	 * @return array
	 */
	public function getData(): array;


	/**
	 * Set additional data associated with the address.
	 *
	 * @param array $data
	 * @return static
	 */
	public function setData(array $data): static;


	/**
	 * Convert the address object to an array representation.
	 *
	 * @return array{
	 *     lat: float,
	 *     lng: float,
	 *     name: string,
	 *     street?: string,
	 *     houseNumber?: string,
	 *     postalCode?: string,
	 *     city?: string,
	 *     country?: string,
	 *     data: array,
	 *  }
	 */
	public function toArray(): array;
}
