<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\Typography\Rule;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\Typography\Rule\DashRule;


/**
 * Tests for DashRule
 *
 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule
 */
class DashRuleTest extends TestCase {
	/**
	 * Tests that spaced dashes are converted for various whitespace variants.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyConvertsSpacedDashesForWhitespaceVariants(): void {
		$rule = new DashRule("\u{2013}");

		$input = "A - B A&nbsp;-&nbsp;B A\u{00A0}-\u{00A0}B A\u{202F}-\u{202F}B";
		$expected = "A \u{2013} B A&nbsp;\u{2013}&nbsp;B A\u{00A0}\u{2013}\u{00A0}B A\u{202F}\u{2013}\u{202F}B";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests conversion of leading dash at start of string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyConvertsLeadingDashAtStartOfString(): void {
		$rule = new DashRule("\u{2013}");

		$input = '- Intro';
		$expected = "\u{2013} Intro";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests conversion of double hyphen when configured as unspaced mode.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyConvertsDoubleHyphenWhenConfiguredAsUnspacedMode(): void {
		$rule = new DashRule("\u{2014}", false);

		$input = 'Wait--what --- nope -- yes';
		$expected = "Wait\u{2014}what --- nope \u{2014} yes";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests conversion of numeric ranges when enabled.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyConvertsNumericRangesWhenEnabled(): void {
		$rule = new DashRule("\u{2013}", true, true);

		$input = "10-20 10 - 20 10&nbsp;-&nbsp;20 10\u{00A0}-\u{00A0}20";
		$expected = "10\u{2013}20 10\u{2013}20 10\u{2013}20 10\u{2013}20";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that hyphens in words are not converted (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyDoesNotConvertHyphensInWords(): void {
		$rule = new DashRule("\u{2013}");

		// False positive test: Should not touch word-internal hyphens
		$input = 'five-year-old and twenty-one';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that numeric ranges are not converted when disabled (false negative prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyDoesNotConvertNumericRangesWhenDisabled(): void {
		$rule = new DashRule("\u{2013}", true, false);

		// False negative test: Numeric ranges should not be touched
		$input = '10-20 and 30-40';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests conversion of double leading dash.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyConvertsDoubleLeadingDash(): void {
		$rule = new DashRule("\u{2013}");

		$input = '-- Start';
		$expected = "\u{2013} Start";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests conversion of trailing dash.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyConvertsTrailingDash(): void {
		$rule = new DashRule("\u{2013}");

		$input = 'End -';
		$expected = "End \u{2013}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests conversion of trailing double dash.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyConvertsTrailingDoubleDash(): void {
		$rule = new DashRule("\u{2013}");

		$input = 'End --';
		$expected = "End \u{2013}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of multiple dashes in the same string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyHandlesMultipleDashesInSameString(): void {
		$rule = new DashRule("\u{2013}");

		$input = 'A - B - C - D';
		$expected = "A \u{2013} B \u{2013} C \u{2013} D";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests en dash with spaced mode.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyWithEmDashSpaced(): void {
		$rule = new DashRule("\u{2014}", true);

		$input = 'Text - dash - here';
		$expected = "Text \u{2014} dash \u{2014} here";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests em dash with unspaced mode.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyWithEmDashUnspaced(): void {
		$rule = new DashRule("\u{2014}", false);

		$input = 'Text--dash--here';
		$expected = "Text\u{2014}dash\u{2014}here";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests numeric ranges with decimal numbers.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyNumericRangesWithDecimals(): void {
		$rule = new DashRule("\u{2013}", true, true);

		$input = '1.5-2.5 and 10.0-20.0';
		$expected = "1.5\u{2013}2.5 and 10.0\u{2013}20.0";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that numeric ranges remove spacing.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyNumericRangesRemovesSpacing(): void {
		$rule = new DashRule("\u{2013}", true, true);

		$input = '10 - 20';
		$expected = "10\u{2013}20";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that date ranges are not converted (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyDoesNotConvertDateRanges(): void {
		$rule = new DashRule("\u{2013}", true, true);

		// False positive test: Date-like patterns should not be touched without numeric range conversion
		$input = '2024-01-15';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that date ranges are converted when numeric ranges are enabled.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyConvertsDateRangesWhenNumericRangesEnabled(): void {
		$rule = new DashRule("\u{2013}", true, true);

		// With numeric ranges enabled, year parts would be converted
		$input = '2024-2025';
		$expected = "2024\u{2013}2025";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that spaced mode does not convert unspaced hyphens.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplySpacedModeWithNoDash(): void {
		$rule = new DashRule("\u{2013}", true);

		$input = 'word-word number-123';

		// Should not convert as these are not spaced
		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests handling of dash after punctuation.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyHandlesDashAfterPunctuation(): void {
		$rule = new DashRule("\u{2013}");

		$input = 'Text. - Dash';
		$expected = "Text. \u{2013} Dash";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that numeric range does not convert range of ranges.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\DashRule::apply
	 */
	public function testApplyNumericRangeDoesNotConvertRangeOfRanges(): void {
		$rule = new DashRule("\u{2013}", true, true);

		// Should not convert middle hyphen in "10-20-30"
		$input = '10-20-30';
		$expected = "10-20-30";

		$this->assertSame($expected, $rule->apply($input));
	}
}
