<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


/**
 * Interface ColumnInterface
 */
interface ColumnInterface {
	/**
	 * @return string The CSS class
	 */
	public function getCssClass(): string;


	/**
	 * @param string $prefix
	 * @return $this
	 */
	public function setCssClassPrefix(string $prefix): static;

	/**
	 * @return int The denominator
	 */
	public function getDenominator(): int;


	/**
	 * @param int $denominator The denominator
	 * @return $this
	 */
	public function setDenominator(int $denominator): static;


	/**
	 * @return string
	 */
	public function getFraction(): string;


	/**
	 * @param string $fraction
	 * @return $this
	 */
	public function setFraction(string $fraction): static;


	/**
	 * @return string
	 */
	public function getLabel(): string;


	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel(string $label): static;


	/**
	 * @return int
	 */
	public function getNumerator(): int;


	/**
	 * @param int $numerator
	 * @return $this
	 */
	public function setNumerator(int $numerator): static;


	/**
	 * @param int|null $decimalPoints
	 * @return mixed
	 */
	public function getPercentage(?int $decimalPoints = null): float;
}
