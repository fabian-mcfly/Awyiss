<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\Typography\Rule;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule;


/**
 * Tests for PunctuationSpacingRule
 *
 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule
 */
class PunctuationSpacingRuleTest extends TestCase {
	/**
	 * Tests that spacing before punctuation marks is normalized for various whitespace variants.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyNormalizesSpacingBeforeMarksForWhitespaceVariants(): void {
		$rule = new PunctuationSpacingRule([';', ':', '!', '?'], "\u{202F}");

		$input = "Salut ; oui&nbsp;: bien\u{00A0}! ok?";
		$expected = "Salut\u{202F}; oui\u{202F}: bien\u{202F}! ok\u{202F}?";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that URL scheme colons are skipped but regular colons are formatted.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplySkipsUrlSchemeColonButFormatsRegularColon(): void {
		$rule = new PunctuationSpacingRule([':'], "\u{202F}");

		$input = 'https://example.com Test: ja';
		$expected = "https://example.com Test\u{202F}: ja";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that input is returned unchanged when no punctuation marks are configured.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyReturnsInputWhenNoPunctuationMarksAreConfigured(): void {
		$rule = new PunctuationSpacingRule([]);

		$input = 'Text : bleibt gleich';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that spacing is removed when empty string is given as spacing parameter.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyRemovesSpacingWhenEmptyStringGiven(): void {
		$rule = new PunctuationSpacingRule([';', ':', '!', '?'], '');

		$input = "Salut ; oui : bien ! ok ?";
		$expected = 'Salut; oui: bien! ok?';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of multiple spaces before punctuation.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesMultipleSpacesBeforePunctuation(): void {
		$rule = new PunctuationSpacingRule(['!'], "\u{202F}");

		$input = 'Text  !';
		$expected = "Text\u{202F}!";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of narrow non-breaking spaces.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesNarrowNonBreakingSpace(): void {
		$rule = new PunctuationSpacingRule(['?'], "\u{202F}");

		$input = "Question\u{202F}?";
		$expected = "Question\u{202F}?";

		// Should normalize even if already correct
		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of thin spaces.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesThinSpace(): void {
		$rule = new PunctuationSpacingRule(['!'], "\u{202F}");

		$input = "Exclaim\u{2009}!";
		$expected = "Exclaim\u{202F}!";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that URL query parameters are not modified (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyDoesNotModifyUrlQueryParameters(): void {
		$rule = new PunctuationSpacingRule(['?', ':'], "\u{202F}");

		$input = 'edit.php?id=555 und normal: ja';
		$expected = "edit.php?id=555 und normal\u{202F}: ja";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that colons in time formats are not modified (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyDoesNotModifyColonInTimeFormat(): void {
		$rule = new PunctuationSpacingRule([':'], "\u{202F}");

		// Colons in time formats should not be touched (no following whitespace)
		$input = '12:30 Uhr und Zeit: jetzt';
		$expected = "12:30 Uhr und Zeit\u{202F}: jetzt";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of punctuation at end of string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesPunctuationAtEndOfString(): void {
		$rule = new PunctuationSpacingRule(['!'], "\u{202F}");

		$input = 'Ende !';
		$expected = "Ende\u{202F}!";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of punctuation at start of string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesPunctuationAtStartOfString(): void {
		$rule = new PunctuationSpacingRule(['!'], "\u{202F}");

		$input = ' ! Start';
		$expected = "\u{202F}! Start";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of consecutive punctuation marks.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesConsecutivePunctuation(): void {
		$rule = new PunctuationSpacingRule(['!', '?'], "\u{202F}");

		// Special case: !? and ?! should NOT have spacing inserted between them
		$input = 'Was !? Ja !';
		$expected = "Was\u{202F}!? Ja\u{202F}!";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of semicolon only.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesSemicolonOnly(): void {
		$rule = new PunctuationSpacingRule([';'], "\u{202F}");

		$input = 'Eins ; zwei ; drei';
		$expected = "Eins\u{202F}; zwei\u{202F}; drei";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of question mark only.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesQuestionMarkOnly(): void {
		$rule = new PunctuationSpacingRule(['?'], "\u{202F}");

		$input = 'Warum ? Wieso ?';
		$expected = "Warum\u{202F}? Wieso\u{202F}?";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of exclamation mark only.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesExclamationMarkOnly(): void {
		$rule = new PunctuationSpacingRule(['!'], "\u{202F}");

		$input = 'Stop ! Halt !';
		$expected = "Stop\u{202F}! Halt\u{202F}!";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of colon only.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesColonOnly(): void {
		$rule = new PunctuationSpacingRule([':'], "\u{202F}");

		$input = 'Beispiel : eins';
		$expected = "Beispiel\u{202F}: eins";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests using regular space as spacing.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyWithRegularSpace(): void {
		$rule = new PunctuationSpacingRule(['!'], ' ');

		$input = "Halt  ! Stop!";
		$expected = 'Halt ! Stop !';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of punctuation that already has no space before it.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesPunctuationWithoutSpaceAlready(): void {
		$rule = new PunctuationSpacingRule(['!'], "\u{202F}");

		$input = 'Text! Mehr!';
		$expected = "Text\u{202F}! Mehr\u{202F}!";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that URL schemes are preserved.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyPreservesUrlSchemes(): void {
		$rule = new PunctuationSpacingRule([':'], "\u{202F}");

		$input = 'http://example.com https://test.de ftp://files.com';

		// URLs should remain unchanged
		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that no spacing is inserted between ! and ?.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyDoesNotInsertSpacingBetweenExclamationAndQuestion(): void {
		$rule = new PunctuationSpacingRule(['!', '?'], "\u{202F}");

		$input = 'Was!? Wirklich !? Echt!?';
		$expected = "Was\u{202F}!? Wirklich\u{202F}!? Echt\u{202F}!?";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that no spacing is inserted between ? and !.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyDoesNotInsertSpacingBetweenQuestionAndExclamation(): void {
		$rule = new PunctuationSpacingRule(['!', '?'], "\u{202F}");

		$input = 'Wirklich?! Echt ?! Wahnsinn?!';
		$expected = "Wirklich\u{202F}?! Echt\u{202F}?! Wahnsinn\u{202F}?!";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that spacing before !? is still normalized.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyNormalizesSpacingBeforeExclamationQuestionCombo(): void {
		$rule = new PunctuationSpacingRule(['!', '?'], "\u{202F}");

		$input = 'Text !?';
		$expected = "Text\u{202F}!?";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that spacing before ?! is still normalized.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyNormalizesSpacingBeforeQuestionExclamationCombo(): void {
		$rule = new PunctuationSpacingRule(['!', '?'], "\u{202F}");

		$input = 'Text ?!';
		$expected = "Text\u{202F}?!";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that !? and ?! work correctly with other punctuation.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyHandlesMixedPunctuationWithExclamationQuestion(): void {
		$rule = new PunctuationSpacingRule(['!', '?', ':'], "\u{202F}");

		$input = 'Frage : Was !? Und ?! Ende.';
		$expected = "Frage\u{202F}: Was\u{202F}!? Und\u{202F}?! Ende.";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that only ! without ? configured still gets spacing.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyWithOnlyExclamationConfiguredHandlesNormally(): void {
		$rule = new PunctuationSpacingRule(['!'], "\u{202F}");

		// Since ? is not configured, the special handling doesn't apply
		$input = 'Text !? More !';
		$expected = "Text\u{202F}!? More\u{202F}!";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that only ? without ! configured still gets spacing.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyWithOnlyQuestionConfiguredHandlesNormally(): void {
		$rule = new PunctuationSpacingRule(['?'], "\u{202F}");

		// Since ! is not configured, the special handling doesn't apply
		$input = 'Text !? More ?';
		$expected = "Text !\u{202F}? More\u{202F}?";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that only ? configured also formats ?! combinations.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyWithOnlyQuestionConfiguredFormatsQuestionExclamationCombo(): void {
		$rule = new PunctuationSpacingRule(['?'], "\u{202F}");

		$input = 'Text ?! More ?';
		$expected = "Text\u{202F}?! More\u{202F}?";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that semicolons terminating HTML entities are not reformatted as punctuation.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\PunctuationSpacingRule::apply
	 */
	public function testApplyDoesNotRewriteSemicolonInsideHtmlEntity(): void {
		$rule = new PunctuationSpacingRule([';', ':', '!', '?'], "\u{202F}");

		$input = '&laquo; Texte &raquo; : oui ; non ?';
		$expected = "&laquo; Texte &raquo;\u{202F}: oui\u{202F}; non\u{202F}?";

		$this->assertSame($expected, $rule->apply($input));
	}
}
