<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\Typography\Rule;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule;


/**
 * Tests for QuotationMarksRule
 *
 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule
 */
class QuotationMarksRuleTest extends TestCase {
	/**
	 * Tests that straight quotes are replaced with typographic quotes and inner spaces are trimmed.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyReplacesStraightQuotesAndTrimsInnerSpaces(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		$input = 'Er sagt: "  Hallo  " und "".';
		$expected = "Er sagt: \u{201E}Hallo\u{201C} und \u{201E}\u{201C}.";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that inner separator can be used for French style quotes with guillemets.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyCanUseInnerSeparatorForFrenchStyleQuotes(): void {
		$rule = new QuotationMarksRule("\u{00AB}", "\u{00BB}", "\u{202F}");

		$input = '"Bonjour"';
		$expected = "\u{00AB}\u{202F}Bonjour\u{202F}\u{00BB}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that unmatched quotes are left untouched.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyLeavesUnmatchedQuoteUntouched(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		$input = 'Er sagt: "Hallo.';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests handling of multiple quote pairs in the same string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesMultipleQuotePairs(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		$input = 'Sie sagte "Hallo" und er antwortete "Tschüss" zum Abschied.';
		$expected = "Sie sagte \u{201E}Hallo\u{201C} und er antwortete \u{201E}Tschüss\u{201C} zum Abschied.";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that quotes work across inline HTML tags.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyWorksAcrossInlineTags(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		$input = '"a <strong>very</strong> important" quote';
		$expected = "\u{201E}a <strong>very</strong> important\u{201C} quote";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of empty quotes.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesEmptyQuotes(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		$input = 'Text "" leer.';
		$expected = "Text \u{201E}\u{201C} leer.";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of empty quotes with inner separator configured.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesEmptyQuotesWithInnerSeparator(): void {
		$rule = new QuotationMarksRule("\u{00AB}", "\u{00BB}", "\u{202F}");

		$input = '""';
		$expected = "\u{00AB}\u{00BB}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that whitespace inside quotes is trimmed correctly.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyTrimsWhitespaceInsideQuotes(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		$input = '"  multiple   spaces  "';
		$expected = "\u{201E}multiple   spaces\u{201C}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests English style quotation marks.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyEnglishStyleQuotes(): void {
		$rule = new QuotationMarksRule("\u{201C}", "\u{201D}");

		$input = 'She said "hello" to me.';
		$expected = "She said \u{201C}hello\u{201D} to me.";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests French style quotes with multiple occurrences.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyFrenchStyleWithMultipleQuotes(): void {
		$rule = new QuotationMarksRule("\u{00AB}", "\u{00BB}", "\u{202F}");

		$input = '"Bonjour" et "au revoir"';
		$expected = "\u{00AB}\u{202F}Bonjour\u{202F}\u{00BB} et \u{00AB}\u{202F}au revoir\u{202F}\u{00BB}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that already typographic quotes are not modified (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyDoesNotModifyAlreadyTypographicQuotes(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		// False positive test: Should not touch already typographic quotes
		$input = "Er sagt: \u{201E}Hallo\u{201C}.";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that odd number of quotes leaves unmatched ones untouched.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyLeavesOddNumberOfQuotesUntouched(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		// False negative test: Three quotes should leave middle one unmatched
		$input = 'Text "eins" und " allein';

		// Only the first pair should be converted
		$expected = "Text \u{201E}eins\u{201C} und \" allein";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of quotes containing numbers.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesQuotesWithNumbers(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		$input = '"123" und "456"';
		$expected = "\u{201E}123\u{201C} und \u{201E}456\u{201C}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of quotes containing punctuation.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesQuotesWithPunctuation(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}");

		$input = '"Hallo!" und "Wie geht\'s?"';
		$expected = "\u{201E}Hallo!\u{201C} und \u{201E}Wie geht's?\u{201C}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests behavior with no inner separator configured.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyWithNoInnerSeparator(): void {
		$rule = new QuotationMarksRule("\u{201E}", "\u{201C}", '');

		$input = '"Text"';
		$expected = "\u{201E}Text\u{201C}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that named straight-quote entities are normalized like Unicode straight quotes.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesNamedEntityStraightQuotes(): void {
		$rule = new QuotationMarksRule('«', '»', "\u{202F}");

		$input = '&quot; Bonjour &quot;';
		$expected = "«\u{202F}Bonjour\u{202F}»";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that numeric straight-quote entities are normalized like Unicode straight quotes.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesNumericEntityStraightQuotes(): void {
		$rule = new QuotationMarksRule('«', '»', "\u{202F}");

		$input = '&#34; Bonjour &#34;';
		$expected = "«\u{202F}Bonjour\u{202F}»";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that mixed straight-quote variants still produce one deterministic output.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesMixedStraightQuoteVariants(): void {
		$rule = new QuotationMarksRule('«', '»', "\u{202F}");

		$input = '&quot;Bonjour&#34; und "Salut&quot;';
		$expected = "«\u{202F}Bonjour\u{202F}» und «\u{202F}Salut\u{202F}»";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests trimming of both Unicode and literal HTML-entity whitespace inside quotes.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyTrimsHtmlEntityWhitespaceInsideQuotes(): void {
		$rule = new QuotationMarksRule('«', '»', "\u{202F}");

		$input = '"&nbsp;Bonjour&#8239;"';
		$expected = "«\u{202F}Bonjour\u{202F}»";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that named-entity target marks also work when source quotes are Unicode straight quotes.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesNamedEntityTargetMarksWithUnicodeSourceQuotes(): void {
		$rule = new QuotationMarksRule('&laquo;', '&raquo;', "\u{202F}");

		$input = '"Bonjour"';
		$expected = "&laquo;\u{202F}Bonjour\u{202F}&raquo;";

		$this->assertSame($expected, $rule->apply($input));

		$input = '«Bonjour»';
		$expected = "«\u{202F}Bonjour\u{202F}»";

		$this->assertSame($expected, $rule->apply($input));

		$input = '&laquo;Bonjour»';
		$expected = "&laquo;\u{202F}Bonjour\u{202F}»";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that named-entity target marks also work when source quotes are numeric entities.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesNamedEntityTargetMarksWithNumericSourceQuotes(): void {
		$rule = new QuotationMarksRule('&laquo;', '&raquo;', "\u{202F}");

		$input = '&#34;Bonjour&#34; und &#x22;Salut&#x22;';
		$expected = "&laquo;\u{202F}Bonjour\u{202F}&raquo; und &laquo;\u{202F}Salut\u{202F}&raquo;";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that configured named guillemets also match Unicode guillemet input.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyMatchesUnicodeGuillemetsWhenConfiguredAsNamedEntities(): void {
		$rule = new QuotationMarksRule('&laquo;', '&raquo;', "\u{202F}");

		$input = '« Bonjour »';
		$expected = "«\u{202F}Bonjour\u{202F}»";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that configured Unicode guillemets also match named and numeric entity input.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyMatchesNamedAndNumericGuillemetsWhenConfiguredAsUnicode(): void {
		$rule = new QuotationMarksRule('«', '»', "\u{202F}");

		$input = '&laquo; Bonjour &raquo; und &#171;Salut&#187;';
		$expected = "&laquo;\u{202F}Bonjour\u{202F}&raquo; und &#171;\u{202F}Salut\u{202F}&#187;";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Test that `Und hier folgt ein Satz - mit einem Gedankenstrich - mitten im Text&quot; .<br>
	 * Und hier folgt ein Satz - mit einem Gedankenstrich - mitten im Text" .<br>` will result in one quote within the text
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\QuotationMarksRule::apply
	 */
	public function testApplyHandlesEdgeCaseWithQuotesOverMultipleLines(): void {
		$rule = new QuotationMarksRule('„', '“');

		$input = 'Und hier folgt ein Satz - mit einem Gedankenstrich - mitten im Text&quot; .<br>'
			. PHP_EOL . 'Und hier folgt ein Satz - mit einem Gedankenstrich - mitten im Text" .<br>';

		$expected = 'Und hier folgt ein Satz - mit einem Gedankenstrich - mitten im Text„.<br>'
			. PHP_EOL . 'Und hier folgt ein Satz - mit einem Gedankenstrich - mitten im Text“ .<br>';

		$this->assertSame($expected, $rule->apply($input));
	}

	/**
	 * Tests that quotes used for measurements (e.g., 5'10") or geographic coordinates (e.g., 40° 26' 46" N) are not replaced, as they
	 * are not typographic quotes. Multiple of those within a string should not create a false positive for a quote pair.
	 */
	public function testApplyDoesNotReplaceQuotesUsedForMeasurementsOrCoordinates(): void {
		$rule = new QuotationMarksRule('&laquo;', '&raquo;');

		$input = 'His height is 5\'10", hers is 5\'6". The average human height is around 5\'7".'
			. PHP_EOL . 'The average dog height is around 2\'6". The average cat height is around 1\'0".';

		$expected = 'His height is 5\'10", hers is 5\'6". The average human height is around 5\'7".'
			. PHP_EOL . 'The average dog height is around 2\'6". The average cat height is around 1\'0".';

		$this->assertSame($expected, $rule->apply($input));

		$input = 'The coordinates are 40° 26\' 46" N, 79° 58\' 56" W.';
		$expected = 'The coordinates are 40° 26\' 46" N, 79° 58\' 56" W.';

		$this->assertSame($expected, $rule->apply($input));
	}
}
