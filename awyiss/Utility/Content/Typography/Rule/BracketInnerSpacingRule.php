<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography\Rule;


use Awyiss\Utility\Content\Typography\SymbolAlternatives;
use Awyiss\Utility\Content\Typography\TypographyFixer;
use Awyiss\Utility\Content\Typography\TypographyRuleInterface;


/**
 * Normalizes spacing directly inside configurable bracket pairs.
 *
 * For each configured opening/closing pair, spacing right after the opening bracket and right before the closing bracket
 * is replaced with one configurable string.
 */
class BracketInnerSpacingRule implements TypographyRuleInterface {
	/**
	 * Default bracket pairs covered by this rule.
	 *
	 * @var array<string, string>
	 */
	protected const array DEFAULT_BRACKET_PAIRS = [
		'(' => ')',
		'[' => ']',
		'<' => '>',
	];


	/**
	 * @param array<string, string> $bracketPairs Opening => closing brackets to normalize
	 * @param string $innerSpacing Spacing to enforce directly inside each bracket side
	 */
	public function __construct(
		protected array $bracketPairs = self::DEFAULT_BRACKET_PAIRS,
		protected string $innerSpacing = '',
	) {
	}


	/**
	 * @param string $text
	 * @return string
	 */
	public function apply(string $text): string {
		if ($this->bracketPairs === []) {
			return $text;
		}

		$whitespace = TypographyFixer::WHITESPACE_TOKEN_PATTERN;

		foreach ($this->bracketPairs as $opening => $closing) {
			$openingPattern = static::buildAlternativesPattern($opening);
			$closingPattern = static::buildAlternativesPattern($closing);

			$text = preg_replace(
				'/(' . $openingPattern . ')' . $whitespace . '*/u',
				'$1' . $this->innerSpacing,
				$text
			);
			$text = preg_replace(
				'/' . $whitespace . '*(' . $closingPattern . ')/u',
				$this->innerSpacing . '$1',
				$text
			);
		}

		return $text;
	}


	/**
	 * @param string $symbol
	 * @return string
	 */
	protected static function buildAlternativesPattern(string $symbol): string {
		$alternatives = SymbolAlternatives::getAlternatives($symbol);

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
