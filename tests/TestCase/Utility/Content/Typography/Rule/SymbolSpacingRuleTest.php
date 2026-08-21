<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\Typography\Rule;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule;
use InvalidArgumentException;


/**
 * Tests for SymbolSpacingRule
 *
 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule
 */
class SymbolSpacingRuleTest extends TestCase {
	/**
	 * Tests that spacing is normalized when symbols come after numbers with various whitespace variants.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyNormalizesSpacingWhenSymbolComesAfterNumber(): void {
		$rule = new SymbolSpacingRule(["\u{20AC}", '$'], 'after', "\u{202F}");

		$input = "10\u{20AC} 20 \u{20AC} 30\u{00A0}\u{20AC} 40&nbsp;\u{20AC} 50$ 60 $ 70\u{00A0}$ 80&nbsp;$";
		$expected = "10\u{202F}\u{20AC} 20\u{202F}\u{20AC} 30\u{202F}\u{20AC} 40\u{202F}\u{20AC} "
			. "50\u{202F}$ 60\u{202F}$ 70\u{202F}$ 80\u{202F}$";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that spacing is normalized when symbols come before numbers.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyNormalizesSpacingWhenSymbolComesBeforeNumber(): void {
		$rule = new SymbolSpacingRule(['$'], 'before', '');

		$input = '$ 10 $' . "\u{00A0}" . '20 $&nbsp;30';
		$expected = '$10 $20 $30';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that symbols without adjacent numbers remain unchanged.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyDoesNotChangeSymbolsWithoutAdjacentNumber(): void {
		$rule = new SymbolSpacingRule(["\u{20AC}", '$'], 'after', "\u{202F}");

		$input = 'Preis in $ oder \u{20AC} ohne Zahl bleibt gleich.';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that multiple occurrences of the same symbol are all normalized.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyHandlesMultipleOccurrencesInSameString(): void {
		$rule = new SymbolSpacingRule(['€'], 'after', "\u{202F}");

		$input = '10€ und 20€ und 30€';
		$expected = "10\u{202F}€ und 20\u{202F}€ und 30\u{202F}€";

		$this->assertSame($expected, $rule->apply($input));

		$input = '10€ und 20&nbsp;€ und 30 €';
		$expected = "10\u{202F}€ und 20\u{202F}€ und 30\u{202F}€";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that symbols between letters are not modified (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyDoesNotModifySymbolBetweenLetters(): void {
		$rule = new SymbolSpacingRule(['$'], 'after', "\u{202F}");

		// False positive test: Should not modify $ between letters
		$input = 'A$B and C$D';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that symbols without adjacent numbers remain unchanged (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyDoesNotModifySymbolAtStartOrEndWithoutNumber(): void {
		$rule = new SymbolSpacingRule(['€'], 'after', "\u{202F}");

		// False positive test: Symbols without adjacent numbers
		$input = '€ am Anfang und am Ende €';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that decimal numbers are handled correctly.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyHandlesDecimalNumbers(): void {
		$rule = new SymbolSpacingRule(['€'], 'after', "\u{202F}");

		$input = '19,99€ und 10.50€';
		$expected = "19,99\u{202F}€ und 10.50\u{202F}€";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that numbers with thousands separators are handled correctly.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyHandlesNumbersWithThousandsSeparators(): void {
		$rule = new SymbolSpacingRule(['€'], 'after', "\u{202F}");

		$input = '1.000€ und 2,500€';
		$expected = "1.000\u{202F}€ und 2,500\u{202F}€";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that 'before' position normalizes spacing with various whitespace variants.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyBeforePositionWithVariousWhitespace(): void {
		$rule = new SymbolSpacingRule(['$'], 'before', '');

		$input = "$ 100 $\u{00A0}200 $&nbsp;300 $\u{202F}400";
		$expected = '$100 $200 $300 $400';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that 'before' position does not modify symbols after numbers (false negative prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyBeforePositionDoesNotModifySymbolAfterNumber(): void {
		$rule = new SymbolSpacingRule(['$'], 'before', '');

		// False negative test: When configured as "before", should not touch "number$"
		$input = '100$ text';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests handling of percent signs with various spacing.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyHandlesPercentSign(): void {
		$rule = new SymbolSpacingRule(['%'], 'after', "\u{202F}");

		$input = '50% und 75 % und 100&nbsp;%';
		$expected = "50\u{202F}% und 75\u{202F}% und 100\u{202F}%";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of degree signs with various spacing.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyHandlesDegreeSign(): void {
		$rule = new SymbolSpacingRule(['°'], 'after', "\u{202F}");

		$input = '25°C und 100 °C und 0&nbsp;°C';
		$expected = "25\u{202F}°C und 100\u{202F}°C und 0\u{202F}°C";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that a multi-character symbol like °C can be configured directly.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyHandlesMultiCharacterDegreeCelsiusSymbol(): void {
		$rule = new SymbolSpacingRule(['°C'], 'after', "\u{202F}");

		$input = '25°C und 100 °C und 0&nbsp;°C';
		$expected = "25\u{202F}°C und 100\u{202F}°C und 0\u{202F}°C";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that a configured °C symbol does not affect plain ° usage.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithDegreeCelsiusSymbolLeavesPlainDegreeUnchanged(): void {
		$rule = new SymbolSpacingRule(['°C'], 'after', "\u{202F}");

		$input = '25° und 30 °C';
		$expected = "25° und 30\u{202F}°C";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that an empty separator removes all spacing.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithEmptySeparatorRemovesAllSpacing(): void {
		$rule = new SymbolSpacingRule(['$'], 'before', '');

		$input = '$ 10 $  20 $   30';
		$expected = '$10 $20 $30';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests using a regular space as separator.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithRegularSpaceSeparator(): void {
		$rule = new SymbolSpacingRule(['€'], 'after', ' ');

		$input = "10€ 20\u{00A0}€ 30&nbsp;€";
		$expected = '10 € 20 € 30 €';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of multiple different symbols with various spacing.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyMultipleSymbolsWithDifferentSpacingInInput(): void {
		$rule = new SymbolSpacingRule(['€', '$', '£'], 'after', "\u{202F}");

		$input = "10€ 20$ 30£ 40 € 50&nbsp;$ 60\u{00A0}£";
		$expected = "10\u{202F}€ 20\u{202F}$ 30\u{202F}£ 40\u{202F}€ 50\u{202F}$ 60\u{202F}£";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that symbols in the middle of words are not modified incorrectly.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyDoesNotTouchSymbolInMiddleOfWord(): void {
		$rule = new SymbolSpacingRule(['$'], 'after', "\u{202F}");

		// False positive test: $ characters that are part of variable names or other constructs
		$input = 'Variable $var and price 10$';
		$expected = "Variable \$var and price 10\u{202F}$";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that a thin space or the literal narrow no-break space entity between number and
	 * symbol is recognized and normalized, like the previously covered separator variants.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyNormalizesThinSpaceAndNarrowNbspEntitySeparators(): void {
		$rule = new SymbolSpacingRule(['€'], 'after', "\u{202F}");

		$input = "10\u{2009}€ und 20&#8239;€";
		$expected = "10\u{202F}€ und 20\u{202F}€";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that requireWordBoundaryAfter matches a unit symbol followed by whitespace, punctuation,  or the end of the string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithRequireWordBoundaryAfterMatchesUnitFollowedByBoundary(): void {
		$rule = new SymbolSpacingRule(['m', 'm²'], 'after', "\u{202F}", true);

		$input = '10 m Kabel. Noch 20 m.';
		$expected = "10\u{202F}m Kabel. Noch 20\u{202F}m.";

		$this->assertSame($expected, $rule->apply($input));

		$input = '10m Kabel. Noch 20m.';
		$expected = "10\u{202F}m Kabel. Noch 20\u{202F}m.";

		$this->assertSame($expected, $rule->apply($input));

		$input = '80m² Trinkeranstalt';
		$expected = "80\u{202F}m² Trinkeranstalt";

		$this->assertSame($expected, $rule->apply($input));

		$input = '80m<sup>2</sup> Trinkeranstalt';
		$expected = "80\u{202F}m<sup>2</sup> Trinkeranstalt";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that requireWordBoundaryAfter matches a unit symbol followed by whitespace, punctuation, or the end of the string,
	 * but not when it's followed by a letter or accented character, which would indicate the start of an unrelated word.
	 *
	 * For example, the french word "mètre" should not be matched as a unit symbol "m" followed by "ètre",
	 * but the unit symbol "m" in "10 m" should be matched.
	 *
	 * English `I found 1 Pa’s old notebook.` should not match the unit symbol `Pa` because it is followed by an apostrophe and a letter,
	 * indicating it is part of a possessive form rather than a standalone unit.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithRequireWordBoundaryAfterMatchesUnitFollowedByWordinternalSpecialCharacters(): void {
		$rule = new SymbolSpacingRule(['m', 'Pa'], 'after', "\u{202F}", true);

		$input = '10 mètre';

		$this->assertSame($input, $rule->apply($input));

		$input = 'I found 1 Pa’s old notebook.';

		$this->assertSame($input, $rule->apply($input));

		$input = 'I found 1 Pa\'s old notebook.';
		$expected = "I found 1\u{202F}Pa's old notebook.";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that requireWordBoundaryAfter leaves a symbol untouched when it is directly followed
	 * by a letter, i.e. is the start of an unrelated word rather than a standalone unit.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithRequireWordBoundaryAfterDoesNotMatchStartOfWord(): void {
		$rule = new SymbolSpacingRule(['m'], 'after', "\u{202F}", true);

		// False positive test: "m" is the start of "mögliche", not a standalone unit
		$input = '10 mögliche Fehler';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that requireWordBoundaryAfter treats an HTML entity right after the symbol as word
	 * content, as can occur in double-encoded editor output where the entity was never fully decoded.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithRequireWordBoundaryAfterDoesNotMatchBeforeLetterEntity(): void {
		$rule = new SymbolSpacingRule(['m'], 'after', "\u{202F}", true);

		// False positive test: "&ouml;" continues the word "möglich" even though "&" isn't a letter
		$input = '10 m&ouml;gliche Fehler';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that requireWordBoundaryAfter still matches when the symbol is followed by a literal,
	 * un-decoded whitespace entity, since that is a legitimate boundary, not word content.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithRequireWordBoundaryAfterMatchesBeforeWhitespaceEntity(): void {
		$rule = new SymbolSpacingRule(['m'], 'after', "\u{202F}", true);

		$input = '10m&nbsp;Kabel';
		$expected = "10\u{202F}m&nbsp;Kabel";

		$this->assertSame($expected, $rule->apply($input));

		$input = '10 m&nbsp;Kabel';
		$expected = "10\u{202F}m&nbsp;Kabel";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that requireWordBoundaryAfter does not match when the symbol is directly followed
	 * by a digit.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithRequireWordBoundaryAfterDoesNotMatchBeforeDigit(): void {
		$rule = new SymbolSpacingRule(['m'], 'after', "\u{202F}", true);

		$input = '10 m2';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests requireWordBoundaryAfter with several unit symbols in the same string, including
	 * ones sharing a common prefix.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithRequireWordBoundaryAfterHandlesMultipleUnits(): void {
		$rule = new SymbolSpacingRule(['m', 'km', 'kg'], 'after', "\u{202F}", true);

		$input = '10 m Kabel, 5km entfernt, 2 kg schwer, aber 3 mögliche Fehler';
		$expected = "10\u{202F}m Kabel, 5\u{202F}km entfernt, 2\u{202F}kg schwer, aber 3 mögliche Fehler";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that requireWordBoundaryAfter defaults to false, preserving the previous behavior
	 * where a unit-like symbol directly followed by a letter is still matched.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyDefaultsToNotRequiringWordBoundaryAfter(): void {
		$rule = new SymbolSpacingRule(['m'], 'after', "\u{202F}");

		$input = '10 mögliche Fehler';
		$expected = "10\u{202F}mögliche Fehler";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that requireWordBoundaryAfter has no effect when the position is 'before', since the
	 * option only guards the trailing side of an 'after' match.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::apply
	 */
	public function testApplyWithRequireWordBoundaryAfterIsIgnoredForBeforePosition(): void {
		$rule = new SymbolSpacingRule(['$'], 'before', '', true);

		$input = '$ 10 text';
		$expected = '$10 text';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that constructor throws when no symbols are given.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::__construct
	 */
	public function testConstructorThrowsWhenNoSymbolsAreGiven(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageIsOrContains('At least one symbol is required.');

		new SymbolSpacingRule([]);
	}


	/**
	 * Tests that constructor throws when an invalid position is given.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\SymbolSpacingRule::__construct
	 */
	public function testConstructorThrowsWhenPositionIsInvalid(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageIsOrContains('Invalid position. Expected one of `before`, `after`. `middle` given.');

		new SymbolSpacingRule(['$'], 'middle');
	}
}
