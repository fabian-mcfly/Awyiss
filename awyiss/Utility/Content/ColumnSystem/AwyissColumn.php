<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\ColumnSystem;


use function __dx;


/**
 * Class AwyissColumn
 */
class AwyissColumn extends AbstractColumn {
	/**
	 * @inheritDoc
	 */
	public function getLabel(): string {
		if (!isset($this->label)) {
			return $this->numerator === $this->denominator
				? __dx('ColumnSystem', 'awyiss', 'column_width_full')
				: __dx(
					'ColumnSystem',
					'awyiss',
					'column_width',
					$this->numerator,
					$this->denominator
				) . ' (' . $this->getPercentage(2) * 100 . '%)';
		}


		return $this->label;
	}
}
