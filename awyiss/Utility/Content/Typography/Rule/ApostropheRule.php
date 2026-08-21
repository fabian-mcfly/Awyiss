<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\Typography\Rule;


use Awyiss\Utility\Content\Typography\TypographyRuleInterface;


/**
 * Replaces a straight apostrophe (`'`) with the typographic apostrophe (`’`), but only where it sits directly between two letters
 * — English contractions (`don't`) and French/Italian elisions (`l'arbre`, `dell'anno`) both match this shape.
 *
 * A closing quotation mark can never occur in that exact position, so this transformation is safe without knowing whether the language
 * uses straight or curly quotation marks, and without pairing quotes at all.
 *
 * Deliberately out of scope: a trailing possessive apostrophe after a plural (`dogs' toys`) is not covered, since
 * `letter-apostrophe-non-letter` is not unambiguous — it also matches a closing single quotation mark.
 */
class ApostropheRule implements TypographyRuleInterface {
	/**
	 * @param string $text
	 * @return string
	 */
	public function apply(string $text): string {
		return preg_replace('/(?<=\p{L})\'(?=\p{L})/u', "\u{2019}", $text);
	}
}
