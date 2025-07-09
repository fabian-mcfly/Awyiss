<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use RuntimeException;


/**
 * Class representing menu item access control.
 */
class MenuItemAccess {
	/**
	 * @var string
	 */
	protected string $scope;
	/**
	 * @var array|string
	 */
	protected array|string $identifier;
	/**
	 * @var array|null
	 */
	protected ?array $additionalData = null;


	/**
	 * @param object $access
	 */
	public function __construct(object $access) {
		if (!isset($access->scope)) {
			throw new RuntimeException('Access scope is required');
		}

		if (!isset($access->identifier)) {
			throw new RuntimeException('Access identifier is required');
		}

		$this->scope = $access->scope;
		$this->identifier = $access->identifier;

		if (isset($access->additionalData)) {
			$this->additionalData = (array)$access->additionalData;
		}
	}


	/**
	 * Returns the scope of the access control.
	 *
	 * @return string
	 */
	public function getScope(): string {
		return $this->scope;
	}


	/**
	 * Returns the identifier of the access control.
	 *
	 * @return array|string
	 */
	public function getIdentifier(): array|string {
		return $this->identifier;
	}


	/**
	 * Returns the additional data of the access control.
	 *
	 * @return array|null
	 */
	public function getAdditionalData(): ?array {
		return $this->additionalData;
	}


	/**
	 * Sets the additional data of the access control.
	 *
	 * @param array|null $additionalData
	 * @return $this
	 */
	public function setAdditionalData(?array $additionalData): static {
		$this->additionalData = $additionalData;

		return $this;
	}
}
