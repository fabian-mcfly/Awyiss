<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


/**
 * Class BootstrapColumnSystem
 */
class BackendColumnSystem extends BootstrapColumnSystem {
	/**
	 * @var int The maximum denominator
	 */
	final protected static int $maxDenominator = 12;
	/**
	 * @var array<array> The column widths, indexed by the max denominator fraction
	 */
	protected static array $columnWidths = [];


	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return 'Backend';
	}
}
