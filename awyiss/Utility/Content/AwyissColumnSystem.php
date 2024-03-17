<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


/**
 * Class AwyissColumnSystem
 */
class AwyissColumnSystem extends AbstractColumnSystem {
	/**
	 * @inheritDoc
	 */
	protected static int $maxDenominator = 5;


	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return 'Awyiss';
	}
}
