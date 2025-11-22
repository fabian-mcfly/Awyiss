<?php declare(strict_types=1);


namespace Awyiss\Utility\Translation;


/**
 * Class TranslationUsageInfo
 * Represents usage/quota information for a translation service
 */
class TranslationUsageInfo {
	/**
	 * @param int $used Number of characters/units used
	 * @param int $limit Total limit of characters/units
	 * @param string $unit Unit of measurement (e.g., 'characters', 'requests')
	 * @param string|null $periodEnd End of the current billing/quota period (ISO 8601 format)
	 * @param array $metadata Additional service-specific metadata
	 */
	public function __construct(
		protected int $used,
		protected int $limit,
		protected string $unit = 'characters',
		protected ?string $periodEnd = null,
		protected array $metadata = []
	) {
	}


	/**
	 * Get the number of units used
	 *
	 * @return int
	 */
	public function getUsed(): int {
		return $this->used;
	}


	/**
	 * Get the total limit
	 *
	 * @return int
	 */
	public function getLimit(): int {
		return $this->limit;
	}


	/**
	 * Get the number of units remaining
	 *
	 * @return int
	 */
	public function getRemaining(): int {
		return max(0, $this->limit - $this->used);
	}


	/**
	 * Get the unit of measurement
	 *
	 * @return string
	 */
	public function getUnit(): string {
		return $this->unit;
	}


	/**
	 * Get the end of the current period
	 *
	 * @return string|null
	 */
	public function getPeriodEnd(): ?string {
		return $this->periodEnd;
	}


	/**
	 * Get the usage as a percentage
	 *
	 * @return float
	 */
	public function getUsagePercentage(): float {
		if ($this->limit === 0) {
			return 0.0;
		}

		return $this->used / $this->limit * 100;
	}


	/**
	 * Check if the quota is nearly exhausted (>90%)
	 *
	 * @return bool
	 */
	public function isNearlyExhausted(): bool {
		return $this->getUsagePercentage() > 90;
	}


	/**
	 * Check if the quota is exhausted
	 *
	 * @return bool
	 */
	public function isExhausted(): bool {
		return $this->used >= $this->limit;
	}


	/**
	 * Get additional metadata
	 *
	 * @return array
	 */
	public function getMetadata(): array {
		return $this->metadata;
	}


	/**
	 * Get a specific metadata value
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getMetadataValue(string $key, mixed $default = null): mixed {
		return $this->metadata[ $key ] ?? $default;
	}
}
