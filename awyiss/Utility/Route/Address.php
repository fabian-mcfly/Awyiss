<?php declare(strict_types=1);


namespace Awyiss\Utility\Route;


/**
 * Address class that implements AddressInterface.
 * This class represents a geographical address
 */
class Address implements AddressInterface {
	/**
	 * City of the address
	 *
	 * @var string|null
	 */
	protected ?string $city;
	/**
	 * Country of the address
	 *
	 * @var string|null
	 */
	protected ?string $country;
	/**
	 * Additional data related to the address
	 *
	 * @var array
	 */
	protected array $data;
	/**
	 * House number of the address
	 *
	 * @var string|null
	 */
	protected ?string $houseNumber;
	/**
	 * Latitude of the address
	 *
	 * @var float
	 */
	protected float $lat;
	/**
	 * Longitude of the address
	 *
	 * @var float
	 */
	protected float $lng;
	/**
	 * Name of the address
	 *
	 * @var string
	 */
	protected string $name;
	/**
	 * Postal code of the address
	 *
	 * @var string|null
	 */
	protected ?string $postalCode;
	/**
	 * Street of the address
	 *
	 * @var string|null
	 */
	protected ?string $street;


	/**
	 * @inheritDoc
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
	) {
		$this->lat = (float)$lat;
		$this->lng = (float)$lng;
		$this->name = $name ?? ($this->lat . ', ' . $this->lng);
		$this->street = $street;
		$this->houseNumber = $houseNumber;
		$this->postalCode = $postalCode;
		$this->city = $city;
		$this->country = $country;
		$this->data = $data;
	}


	/**
	 * @inheritDoc
	 */
	public static function fromArray(array $data): ?static {
		if (!isset($data['lat'], $data['lng'], $data['name'])) {
			return null;
		}

		return new static(
			$data['lat'],
			$data['lng'],
			$data['name'],
			$data['street'] ?? null,
			$data['housenumber'] ?? $data['houseNumber'] ?? null,
			$data['postalcode'] ?? $data['postalCode'] ?? null,
			$data['city'] ?? null,
			$data['country'] ?? null,
			$data['data'] ?? []
		);
	}


	/**
	 * Create an address from an ORS result
	 */
	public static function fromOrs(array $data): ?static {
		if (!isset($data['geometry']['coordinates'][0], $data['geometry']['coordinates'][1], $data['properties']['label'])) {
			return null;
		}

		return new static(
			lat: $data['geometry']['coordinates'][1],
			lng: $data['geometry']['coordinates'][0],
			name: $data['properties']['label'],
			street: $data['properties']['street'] ?? null,
			houseNumber: $data['properties']['housenumber'] ?? null,
			postalCode: $data['properties']['postalcode'] ?? null,
			city: $data['properties']['locality'] ?? null,
			country: $data['properties']['country'] ?? null,
		);
	}


	/**
	 * @inheritDoc
	 */
	public function getLat(): float {
		return $this->lat;
	}


	/**
	 * @inheritDoc
	 */
	public function setLat(float $lat): static {
		$this->lat = $lat;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getLng(): float {
		return $this->lng;
	}


	/**
	 * @inheritDoc
	 */
	public function setLng(float $lng): static {
		$this->lng = $lng;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->name;
	}


	/**
	 * @inheritDoc
	 */
	public function setName(string $name): static {
		$this->name = $name;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getStreet(): ?string {
		return $this->street;
	}


	/**
	 * @inheritDoc
	 */
	public function setStreet(?string $street): static {
		$this->street = $street;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getHouseNumber(): ?string {
		return $this->houseNumber;
	}


	/**
	 * @inheritDoc
	 */
	public function setHouseNumber(?string $houseNumber): static {
		$this->houseNumber = $houseNumber;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getPostalCode(): ?string {
		return $this->postalCode;
	}


	/**
	 * @inheritDoc
	 */
	public function setPostalCode(?string $postalCode): static {
		$this->postalCode = $postalCode;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getCity(): ?string {
		return $this->city;
	}


	/**
	 * @inheritDoc
	 */
	public function setCity(?string $city): static {
		$this->city = $city;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getCountry(): ?string {
		return $this->country;
	}


	/**
	 * @inheritDoc
	 */
	public function setCountry(?string $country): static {
		$this->country = $country;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getData(): array {
		return $this->data;
	}


	/**
	 * @inheritDoc
	 */
	public function setData(array $data): static {
		$this->data = $data;

		return $this;
	}


	/**
	 * Convert the address to an array representation.
	 *
	 * @return array
	 */
	public function toArray(): array {
		return [
			'lat' => $this->lat,
			'lng' => $this->lng,
			'name' => $this->name,
			'street' => $this->street,
			'housenumber' => $this->houseNumber,
			'postalcode' => $this->postalCode,
			'city' => $this->city,
			'country' => $this->country,
			'data' => $this->data,
		];
	}
}
