<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography\Rule;


use Awyiss\Utility\Content\Typography\TypographyFixer;
use Awyiss\Utility\Content\Typography\TypographyRuleInterface;


/**
 * Replaces runs of three or more dots (`...`) — as typically typed by editors without autocorrect — with a single ellipsis character (`…`).
 *
 * Optional spacing flags can force whether a space should be present directly before and/or after converted dot-runs.
 * This is only applied during `...` -> `…` conversion; already existing ellipsis characters are intentionally not touched.
 */
class EllipsisRule implements TypographyRuleInterface {
	/**
	 * @param string|null $spaceBeforeOnConvert Character(s) to enforce before converted ellipses.
	 *  Set to any string (e.g. `' '`, `"\u{00A0}"`, `"\u{202F}"`, `''`) to force that spacing.
	 *  Set to `null` to preserve the original spacing from the source.
	 * @param string|null $spaceAfterOnConvert Character(s) to enforce after converted ellipses.
	 *  Set to any string (e.g. `' '`, `"\u{00A0}"`, `"\u{202F}"`, `''`) to force that spacing.
	 *  Set to `null` to preserve the original spacing from the source.
	 */
	public function __construct(
		protected ?string $spaceBeforeOnConvert = null,
		protected ?string $spaceAfterOnConvert = null,
	) {
	}


	/**
	 * @param string $text
	 * @return string
	 */
	public function apply(string $text): string {
		$whitespace = TypographyFixer::WHITESPACE_TOKEN_PATTERN;
		$pattern = '/(?<before>[\p{L}\p{N}]?)(?<spaceBefore>' . $whitespace . '*)\.{3,}'
			. '(?<spaceAfter>' . $whitespace . '*)(?<after>[\p{L}\p{N}]?)/u'
		;

		return preg_replace_callback(
			$pattern,
			function (array $match): string {
				$charBefore = $match['before'];
				$charAfter = $match['after'];
				$origSpaceBefore = $match['spaceBefore'];
				$origSpaceAfter = $match['spaceAfter'];

				$spaceBefore = $this->spaceBeforeOnConvert !== null
					? ($charBefore !== '' && $charAfter !== '' ? $this->spaceBeforeOnConvert : '')
					: $origSpaceBefore;

				$spaceAfter = $this->spaceAfterOnConvert !== null
					? ($charBefore !== '' && $charAfter !== '' ? $this->spaceAfterOnConvert : '')
					: $origSpaceAfter;

				return $charBefore . $spaceBefore . "\u{2026}" . $spaceAfter . $charAfter;
			},
			$text
		);
	}
}
