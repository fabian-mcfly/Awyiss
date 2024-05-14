<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


/**
 * Class AwyissColumn
 */
class BootstrapColumn extends AbstractColumn {
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
}
