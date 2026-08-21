<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography\Rule;


use Awyiss\Utility\Content\Typography\TypographyFixer;
use Awyiss\Utility\Content\Typography\TypographyRuleInterface;


/**
 * Normalizes spacing immediately before configured punctuation marks by replacing any existing spacing (` `, NBSP, narrow NBSP, or none)
 * with one configurable spacing string.
 *
 * Examples:
 * - `new PunctuationSpacingRule([';', ':', '!', '?'], "\u{202F}")` enforces French-style narrow NBSP.
 * - `new PunctuationSpacingRule([';', ':', '!', '?'], '')` removes spacing before punctuation.
 *
 * Also covers punctuation that follows a closing parenthesis or an HTML entity (e.g. `&quot;`), and normalizes both Unicode spaces
 * and literal `&nbsp;` sequences, without rewriting the semicolon that terminates an entity itself (e.g. `&laquo;`).
 *
 * Guards against rewriting colons and question marks in URL-like segments (e.g. `edit.php?id=555` or `id:555`), and against inserting
 * spacing between `!` and `?` (or `?` and `!`) combinations.
 */
class PunctuationSpacingRule implements TypographyRuleInterface {
	/**
	 * @param array<int, string> $punctuationMarks The punctuation marks whose leading spacing should be normalized
	 * @param string $spacing Spacing to enforce before each configured punctuation mark
	 */
	public function __construct(
		protected array $punctuationMarks,
		protected string $spacing = '',
	) {
	}


	/**
	 * @param string $text
	 * @return string
	 */
	public function apply(string $text): string {
		if ($this->punctuationMarks === []) {
			return $text;
		}

		$whitespace = TypographyFixer::WHITESPACE_TOKEN_PATTERN;
		$boundary = preg_quote(TypographyFixer::NODE_BOUNDARY, '/');

		// Check if both ! and ? are in the configured marks (for special handling)
		$hasExclamation = in_array('!', $this->punctuationMarks, true);
		$hasQuestion = in_array('?', $this->punctuationMarks, true);

		// Special handling for !? and ?! combinations
		if ($hasExclamation && $hasQuestion) {
			// Match !? or ?! as complete combinations
			$comboPattern = '/(?:' . $whitespace . ')*(![?]|[?]!)(?=(' . $whitespace . '|\z|' . $boundary . '))/u';
			$text = preg_replace($comboPattern, $this->spacing . '$1', $text);
		}

		$marks = implode(
			'|',
			array_map(
				fn(string $mark): string => preg_quote($mark, '/'),
				$this->punctuationMarks
			)
		);

		// Build negative lookahead/lookbehind for !? and ?! combinations
		$negativeLookbehind = '';
		$negativeLookahead = '';
		if ($hasExclamation && $hasQuestion) {
			// Don't match ! or ? if they are part of !? or ?! combinations
			$negativeLookbehind = '(?<![!?])';
			$negativeLookahead = '(?![!?])';
		}

		$followPattern = '(' . $whitespace . '|\z|' . $boundary . ')';
		if ($hasExclamation && !$hasQuestion) {
			// Allow matching ! in !? when only ! is configured.
			$followPattern .= '|\?(?=(' . $whitespace . '|\z|' . $boundary . '))';
		}
		elseif ($hasQuestion && !$hasExclamation) {
			// Allow matching ? in ?! when only ? is configured.
			$followPattern .= '|!(?=(' . $whitespace . '|\z|' . $boundary . '))';
		}

		// Handle individual punctuation marks (but skip those already handled in combinations)
		// Only handle punctuation when followed by whitespace, end of string, or node boundary.
		// This avoids rewriting URL/query-like segments such as `edit.php?id=555` or `id:555`.
		$pattern = '/' . $negativeLookbehind . '(?:' . $whitespace . ')*(' . $marks . ')'
			. $negativeLookahead . '(?=(' . $followPattern . '))/u'
		;

		return preg_replace_callback(
			$pattern,
			function (array $matches) use ($text): string {
				$fullMatch = $matches[0][0];
				$mark = $matches[1][0];
				$markOffset = $matches[1][1];

				// Keep the semicolon untouched when it is the terminator of an HTML entity like `&laquo;` or `&#8239;`.
				if ($mark === ';') {
					$before = substr($text, 0, $markOffset);
					if (preg_match('/&(?:#\d+|#x[0-9A-Fa-f]+|[A-Za-z][A-Za-z0-9]+)$/', $before) === 1) {
						return $fullMatch;
					}
				}

				return $this->spacing . $mark;
			},
			$text,
			-1,
			$count,
			PREG_OFFSET_CAPTURE
		);
	}
}
