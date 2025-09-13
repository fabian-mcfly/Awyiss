<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


/**
 * Class BootstrapColumn
 */
class BootstrapColumn extends AbstractColumn {
	/**
	 * @var string
	 */
	protected string $cssClassPrefix = 'col-md';


	/**
	 * @inheritDoc
	 */
	public function getLabel(): string {
		if (!isset($this->label)) {
			return $this->numerator === $this->denominator
				? __dx('column_system', 'bootstrap', 'column_width_full')
				: __dx(
					'column_system',
					'awyiss',
					'column_width',
					$this->numerator,
					$this->denominator
				) . ' (' . $this->getPercentage(2) * 100 . '%)';
		}


		return $this->label;
	}


	/**
	 * @inheritDoc
	 */
	public function getCssClass(): string {
		return $this->cssClassPrefix . '-' . $this->numerator;
	}
}
