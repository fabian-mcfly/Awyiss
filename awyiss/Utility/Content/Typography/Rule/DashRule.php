<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography\Rule;


use Awyiss\Utility\Content\Typography\TypographyFixer;
use Awyiss\Utility\Content\Typography\TypographyRuleInterface;


/**
 * Converts hyphens that are used as a dash — rather than as a word-internal hyphen — into the given dash character.
 * Two, deliberately narrow, unambiguous source patterns are recognized, so word-internal hyphens (`five-year-old`) or numeric ranges
 * without spaces (`10-20`) are never touched:
 *
 * - `$spaced = true`: one or two hyphens that are delimited by whitespace, text boundaries,
 *   or TypographyFixer's temporary node-boundary marker (`\u{E000}`),
 *   e.g. `word - word`, `word -- word`, `- intro`, or `outro -`. Common in
 *   German/French/Spanish/Italian running text: `new DashRule('–')`.
 * - `$spaced = false`: exactly two hyphens with no surrounding whitespace requirement,
 *   the classic typewriter shortcut for an em dash. Common in English: `new DashRule('—', false)`.
 *
 * Optionally, numeric ranges can be normalized as well (`10-20`, `10 - 20` -> `10–20`).
 */
class DashRule implements TypographyRuleInterface {
	/**
	 * @param string $dash The dash character to use, e.g. `–` (en dash) or `—` (em dash)
	 * @param bool $spaced Whether the source hyphen(s) are expected to be surrounded by whitespace
	 * @param bool $numericRanges Whether number-to-number ranges (`10-20`, `10 - 20`) should be converted too
	 */
	public function __construct(
		protected string $dash,
		protected bool $spaced = true,
		protected bool $numericRanges = false,
	) {
	}


	/**
	 * @param string $text
	 * @return string
	 */
	public function apply(string $text): string {
		$whitespace = TypographyFixer::WHITESPACE_TOKEN_PATTERN;
		$boundary = preg_quote(TypographyFixer::NODE_BOUNDARY, '/');
		$gap = '(?:' . $whitespace . '|' . $boundary . ')*';

		if ($this->numericRanges) {
			// Numeric ranges are unambiguous in German typography: `10-20` / `10 - 20` -> `10–20`, except
			// when part of a longer chain of hyphenated numbers (e.g. ISO date `2024-06-12` or rannge of ranges `10-20-30`),
			// which should be left unchanged.
			$text = preg_replace_callback(
				'/(\d+' . $gap . '-' . $gap . ')?(\d+\.?)' . $gap . '-' . $gap . '(\d+)(' . $gap . '-' . $gap . '\d+)?/u',
				function (array $matches) {
					// If there is another "-number" segment before or after, it is part of a chain (e.g. ISO date) -> leave unchanged.
					if (($matches[1] ?? '') !== '' || ($matches[4] ?? '') !== '') {
						return $matches[0];
					}

					return $matches[2] . $this->dash . $matches[3];
				},
				$text
			);
		}

		if ($this->spaced) {
			// Start-of-text variant: `- word`
			$text = preg_replace(
				'/\A-{1,2}(?=(' . $whitespace . '|' . $boundary . '|\z))/u',
				$this->dash,
				$text
			);

			// Delimited variant: whitespace/entity/boundary before and after, e.g. `word&nbsp;-&nbsp;word`
			return preg_replace(
				'/(' . $whitespace . '|' . $boundary . ')-{1,2}(?=(' . $whitespace . '|' . $boundary . '|\z))/u',
				'$1' . $this->dash,
				$text
			);
		}

		// Exactly two hyphens with no surrounding whitespace requirement, not part of a longer run of hyphens
		return preg_replace('/(?<!-)--(?!-)/u', $this->dash, $text);
	}
}
