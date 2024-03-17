<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


/**
 * Class AwyissColumn
 */
abstract class AbstractColumn implements ColumnInterface {
	protected string $cssClassPrefix = 'Column';
	protected int $denominator;
	protected string $fraction;
	protected int $numerator;
	protected string $label;


	/**
	 * Constructor
	 *
	 * @param string $fraction
	 * @param int $numerator
	 * @param int $denominator
	 * @param string $label
	 */
	public function __construct(string $fraction, int $numerator, int $denominator, ?string $label = null) {
		$this->setFraction($fraction);
		$this->setNumerator($numerator);
		$this->setDenominator($denominator);

		if ($label) {
			$this->setLabel($label);
		}
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
	public function setDenominator(int $denominator): static {
		$this->denominator = $denominator;


		return $this;
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
	public function setFraction(string $fraction): static {
		$this->fraction = $fraction;


		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function setLabel(string $label): static {
		$this->label = $label;


		return $this;
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
	public function setNumerator(int $numerator): static {
		$this->numerator = $numerator;


		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getPercentage(?int $decimalPoints = null): float {
		$lf_percentage = $this->numerator / $this->denominator;

		if ($decimalPoints !== null) {
			return round($lf_percentage, $decimalPoints);
		}


		return $lf_percentage;
	}
}
