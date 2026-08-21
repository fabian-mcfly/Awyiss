<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography\Rule;


use Awyiss\Utility\Content\Typography\SymbolAlternatives;
use Awyiss\Utility\Content\Typography\TypographyFixer;
use Awyiss\Utility\Content\Typography\TypographyRuleInterface;


/**
 * Replaces pairs of straight double quotes (`"..."`) — the kind produced by most WYSIWYG
 * editors — with the given opening/closing quotation marks. An optional separator is inserted
 * between the marks and the enclosed text, e.g. a non-breaking narrow space for the French
 * convention `« citation »`; German/English typography uses no separator (`„Zitat“`, `“quote”`).
 *
 * Straight quote detection supports Unicode (`"`) as well as named (`&quot;`) and numeric
 * (`&#34;`, `&#x22;`) HTML-entity variants.
 *
 * Pairing works across inline tags (e.g. `"a <strong>very</strong> important" quote`) as long
 * as opening and closing marks fall within the same block-level element — see
 * \Awyiss\Utility\Content\Typography\TypographyFixer for how that boundary is joined.
 * Nested quotation marks (a quote within a quote) are out of scope for this rule.
 */
class QuotationMarksRule implements TypographyRuleInterface {
	/**
	 * @param string $openingMark
	 * @param string $closingMark
	 * @param string $innerSeparator Separator forced between marks and enclosed text,
	 *  e.g. `"\u{202F}"` for French. Defaults to none.
	 */
	public function __construct(
		protected string $openingMark,
		protected string $closingMark,
		protected string $innerSeparator = '',
	) {
	}


	/**
	 * @param string $text
	 * @return string
	 */
	public function apply(string $text): string {
		$openingAlternatives = SymbolAlternatives::getAlternatives($this->openingMark);
		$closingAlternatives = SymbolAlternatives::getAlternatives($this->closingMark);

		$quoteAlternatives = array_values(array_unique([
			...$openingAlternatives,
			...$closingAlternatives,
			...SymbolAlternatives::getAlternatives('"'),
		]));

		$quotePattern = static::buildAlternativesPattern($quoteAlternatives);

		$whitespace = TypographyFixer::WHITESPACE_TOKEN_PATTERN;
		$edgeWhitespacePattern = '/^(?:' . $whitespace . ')+|(?:' . $whitespace . ')+$/u';

		// Process specific pairs separately to prevent mismatched opening/closing marks
		$pairs = [
			// First handle the configured opening/closing pair
			[
				'opening' => static::buildAlternativesPattern([$this->openingMark]),
				'closing' => static::buildAlternativesPattern([$this->closingMark]),
				'replaceOpening' => $this->openingMark,
				'replaceClosing' => $this->closingMark,
			],
			// Then handle straight quotes
			[
				'opening' => static::buildAlternativesPattern(['"']),
				'closing' => static::buildAlternativesPattern(['"']),
				'replaceOpening' => $this->openingMark,
				'replaceClosing' => $this->closingMark,
			],
		];

		foreach ($pairs as $pair) {
			$text = preg_replace_callback(
				'/(?<!\d)(' . $pair['opening'] . ')((?:(?!' . $quotePattern . ')[\s\S])*?)(' . $pair['closing'] . ')/u',
				function (array $matches) use ($edgeWhitespacePattern, $pair, $openingAlternatives, $closingAlternatives): string {
					$content = preg_replace(
						$edgeWhitespacePattern,
						'',
						$matches[2]
					);

					$opening = in_array($matches[1], $openingAlternatives, true)
						? $matches[1]
						: $pair['replaceOpening'];

					$closing = in_array($matches[3], $closingAlternatives, true)
						? $matches[3]
						: $pair['replaceClosing'];

					if ($content === '') {
						return $opening . $closing;
					}

					return $opening . $this->innerSeparator . $content . $this->innerSeparator . $closing;
				},
				$text
			);
		}

		return $text;
	}


	/**
	 * @param array<int, string> $symbols
	 * @return string
	 */
	protected static function buildAlternativesPattern(array $symbols): string {
		$alternatives = [];
		foreach ($symbols as $symbol) {
			array_push($alternatives, ...SymbolAlternatives::getAlternatives($symbol));
		}

		$alternatives = array_values(array_unique($alternatives));

		usort(
			$alternatives,
			static fn(string $left, string $right): int => strlen($right) <=> strlen($left)
		);

		return implode(
			'|',
			array_map(
				static fn(string $alternative): string => preg_quote($alternative, '/'),
				$alternatives
			)
		);
	}
}
