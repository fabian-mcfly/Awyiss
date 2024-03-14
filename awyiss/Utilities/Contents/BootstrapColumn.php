<?php declare(strict_types=1);


namespace Awyiss\Utilities\Contents;


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
				? __dx('column_system', 'boostrap', 'column_width_full')
				: sprintf('%s/%s', $this->numerator, $this->denominator);
		}


		return $this->label;
	}
}
