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
		$ls_path = implode(DS, [ROOT, CUSTOM_DIR, 'assets', 'scss', 'columns', 'Awyiss']) . DS;

		return [
			'pre' => [
				$ls_path . '_helpers.scss',
			],
			'post' => [
				$ls_path . '_content_elements.scss',
			],
		];
	}
}
