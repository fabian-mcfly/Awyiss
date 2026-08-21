<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography\Rule;


use Awyiss\Utility\Content\Typography\TypographyFixer;
use Awyiss\Utility\Content\Typography\TypographyRuleInterface;
use InvalidArgumentException;


/**
 * Forces a single, configurable separator between a number and any of a set of symbols that belong next to it
 * — currency signs, a percent sign, a degree sign, SI unit letters, and so on — regardless of whether the source contained no separator,
 * a regular space, or a non-breaking space.
 *
 * The position and separator are a property of the *language's* typography, not of any one symbol — a German text places `19,99 €` and
 * `19,99 $` the same way, so a single rule instance is given the whole set of symbols it should watch for, e.g.:
 * - German/French/Spanish/Italian: `new SymbolSpacingRule(['€', '$', '£', ...], 'after', "\u{202F}")`
 * - English: `new SymbolSpacingRule(['€', '$', '£', ...], 'before', '')`
 * - Percent sign for any language: `new SymbolSpacingRule(['%'], 'after', "\u{202F}")`
 *
 * A symbol whose own convention genuinely differs from the rest of the set (e.g. the cent sign `¢`, which follows the number even
 * in English) should be registered as its own rule instance with its own position, rather than folded into a shared set.
 *
 * SI and other letter-based unit symbols (`m`, `kg`, `km/h`, ...) use the same `after`/separator mechanism, but unlike a currency sign,
 * a bare letter also occurs as the start of an ordinary word right next to a number (`10 mögliche Fehler`).
 * Set `$requireWordBoundaryAfter` to `true` to only match where the symbol is *not* immediately followed by further word content, e.g.:
 * `new SymbolSpacingRule(['m', 'km', 'kg'], 'after', "\u{202F}", true)` matches `10 m Kabel` but leaves `10 mögliche Fehler` untouched.
 *
 * "Further word content" also covers an HTML entity that looks like it continues the word (`10 m&ouml;gliche`), even though the entity
 * itself isn't a plain letter — a `&` right after the symbol is deliberately not treated as an automatic pass.
 * The exception is the whitespace entities `&nbsp;`/`&#8239;` themselves (see
 * \Awyiss\Utility\Content\Typography\TypographyFixer::WHITESPACE_TOKEN_PATTERN): a literal, un-decoded one of those is recognized as a
 * genuine boundary, e.g. `10 m&nbsp;Kabel` still matches. Any other entity is treated conservatively as word content, which only means the
 * separator is left as-is rather than risking an incorrect one being forced in.
 */
class SymbolSpacingRule implements TypographyRuleInterface {
	/**
	 * Matches either a Unicode letter/number or a typographic apostrophe (`’`),
	 * or something that looks like an HTML entity
	 * reference (`&name;`, `&#123;`, `&#x1F;`) — both count as the word continuing.
	 * The whitespace entities recognized by \Awyiss\Utility\Content\Typography\TypographyFixer::WHITESPACE_TOKEN_PATTERN
	 * (`&nbsp;`, `&#8239;`) are deliberately excluded. A literal, un-decoded one of those right after the symbol is a legitimate boundary,
	 * not word content.
	 */
	protected const string WORD_CONTINUATION_PATTERN = '(?:[\p{L}\p{N}\x{2019}]|&(?!nbsp;|#8239;)(?:#x?[0-9A-Fa-f]+|'
		. '[A-Za-z][A-Za-z0-9]*);)'
	;


	/**
	 * @param array<int, string> $symbols The symbols to watch for, e.g. `['€', '$', '£']` or `['m', 'km', 'kg']`
	 * @param string $position Either `after` (number comes first, e.g. `19,99 €`) or `before` (symbol comes first, e.g. `$19.99`)
	 * @param string $separator The separator to force between number and symbol. An empty string removes any existing separator.
	 * @param bool $requireWordBoundaryAfter Only applies when `$position` is `after`. If true, a symbol immediately followed by
	 *   further word content (a letter, digit, typographic apostrophe `’`, or entity-like sequence)
	 *   is not matched — needed for letter-based unit symbols,
	 *   which would otherwise also match the start of an unrelated word right after the number (`10 mögliche`).
	 */
	public function __construct(
		protected array $symbols,
		protected string $position = 'after',
		protected string $separator = "\u{202F}",
		protected bool $requireWordBoundaryAfter = false,
	) {
		if ($this->symbols === []) {
			throw new InvalidArgumentException('At least one symbol is required.');
		}

		if (!in_array($this->position, ['before', 'after'], true)) {
			throw new InvalidArgumentException(
				sprintf('Invalid position. Expected one of `before`, `after`. `%s` given.', $this->position)
			);
		}
	}


	/**
	 * @param string $text
	 * @return string
	 */
	public function apply(string $text): string {
		$whitespace = TypographyFixer::WHITESPACE_TOKEN_PATTERN;

		$alternation = implode(
			'|',
			array_map(
				fn(string $symbol): string => preg_quote($symbol, '/'),
				$this->symbols
			)
		);

		if ($this->position === 'after') {
			$trailingBoundary = $this->requireWordBoundaryAfter
				? '(?!' . static::WORD_CONTINUATION_PATTERN . ')'
				: '';

			// Number, optional existing separator (regular or non-breaking space), then one of the symbols,
			// optionally required to not be followed by further word content
			return preg_replace(
				'/(?<=\d)' . $whitespace . '*(' . $alternation . ')' . $trailingBoundary . '/u',
				$this->separator . '$1',
				$text
			);
		}

		// One of the symbols, optional existing separator (regular or non-breaking space), then the number
		return preg_replace(
			'/(' . $alternation . ')' . $whitespace . '*(?=\d)/u',
			'$1' . $this->separator,
			$text
		);
	}
}
