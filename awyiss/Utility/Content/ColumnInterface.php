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
	 * @return float
	 */
	public function getFactor(): float;


	/**
	 * @return string
	 */
	public function getFraction(): string;


	/**
	 * @return string
	 */
	public function getLabel(): string;


	/**
	 * @return int
	 */
	public function getNumerator(): int;


	/**
	 * @param int|null $decimalPoints
	 * @return mixed
	 */
	public function getPercentage(?int $decimalPoints = null): float;
}
