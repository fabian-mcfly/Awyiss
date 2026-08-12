<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


use InvalidArgumentException;
use JsonSerializable;


/**
 * Class AwyissColumn
 */
abstract class AbstractColumn implements ColumnInterface,  JsonSerializable {
	/**
	 * @var string
	 */
	protected string $cssClassPrefix = 'Column';
	/**
	 * @var int
	 */
	protected int $denominator;
	/**
	 * @var string
	 */
	protected string $fraction;
	/**
	 * @var int
	 */
	protected int $numerator;
	/**
	 * @var string|null
	 */
	protected ?string $label = null;


	/**
	 * Constructor
	 *
	 * @param int $numerator
	 * @param int $denominator
	 * @param string|null $label
	 */
	public function __construct(int $numerator, int $denominator, ?string $label = null) {
		if ($numerator <= 0) {
			throw new InvalidArgumentException('Numerator must be greater than zero.');
		}

		if ($denominator <= 0) {
			throw new InvalidArgumentException('Denominator must be greater than zero.');
		}

		$this->fraction = $numerator . '/' . $denominator;
		$this->numerator = $numerator;
		$this->denominator = $denominator;
		$this->label = $label;
	}


	/**
	 * @inheritDoc
	 */
	public function getCssClass(): string {
		return $this->cssClassPrefix . '-' . ($this->getPercentage(2) * 100);
	}


	/**
	 * @inheritDoc
	 */
	public function setCssClassPrefix(string $prefix): static {
		$this->cssClassPrefix = $prefix;


		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getDenominator(): int {
		return $this->denominator;
	}


	/**
	 * @inheritDoc
	 */
	public function getFactor(): float {
		return $this->numerator / $this->denominator;
	}


	/**
	 * @inheritDoc
	 */
	public function getFraction(): string {
		return $this->fraction;
	}


	/**
	 * @inheritDoc
	 */
	public function getNumerator(): int {
		return $this->numerator;
	}


	/**
	 * @inheritDoc
	 */
	public function getPercentage(?int $decimalPoints = null): float {
		$percentage = $this->numerator / $this->denominator;

		if ($decimalPoints !== null) {
			return round($percentage, $decimalPoints);
		}


		return $percentage;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'cssClass' => $this->getCssClass(),
			'denominator' => $this->getDenominator(),
			'fraction' => $this->getFraction(),
			'label' => $this->getLabel(),
			'numerator' => $this->getNumerator(),
			'percentage' => $this->getPercentage(),
		];
	}
}
