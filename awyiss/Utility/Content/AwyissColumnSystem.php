<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


/**
 * Class AwyissColumnSystem
 */
final class AwyissColumnSystem extends AbstractColumnSystem {
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

	/**
	 * @inheritDoc
	 */
	public static function getScssFilePaths(): array {
		$path = implode(DS, [ROOT, 'awyiss', 'assets', 'scss', 'Frontend', 'ColumnSystem', 'Awyiss']) . DS;

		return [
			'pre' => [
				$path . '_helpers.scss',
			],
			'post' => [
				$path . '_content_elements.scss',
			],
		];
	}
}
