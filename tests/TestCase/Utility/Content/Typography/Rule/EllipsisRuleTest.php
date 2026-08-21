<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\Typography\Rule;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\Typography\Rule\EllipsisRule;


/**
 * Tests for EllipsisRule
 *
 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule
 */
class EllipsisRuleTest extends TestCase {
	/**
	 * Tests that original spacing is preserved when no override is configured.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyPreservesOriginalSpacingWhenNoOverrideIsConfigured(): void {
		$rule = new EllipsisRule();

		$input = "A ... B A&nbsp;...&nbsp;B A\u{00A0}...\u{00A0}B";
		$expected = "A \u{2026} B A&nbsp;\u{2026}&nbsp;B A\u{00A0}\u{2026}\u{00A0}B";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that existing ellipsis characters are not touched.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyDoesNotTouchExistingEllipsisCharacter(): void {
		$rule = new EllipsisRule('-', '-');

		$input = "A \u{2026} B";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests conversion of exactly three dots.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyConvertsExactlyThreeDots(): void {
		$rule = new EllipsisRule();

		$input = 'A...B';
		$expected = "A\u{2026}B";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests conversion of four dots.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyConvertsFourDots(): void {
		$rule = new EllipsisRule();

		$input = 'A....B';
		$expected = "A\u{2026}B";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests conversion of five dots.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyConvertsFiveDots(): void {
		$rule = new EllipsisRule();

		$input = 'A.....B';
		$expected = "A\u{2026}B";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that two dots are not converted (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyDoesNotConvertTwoDots(): void {
		$rule = new EllipsisRule();

		// False positive test: Two dots should not be converted
		$input = 'A..B';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that single dots are not converted (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyDoesNotConvertSingleDot(): void {
		$rule = new EllipsisRule();

		// False positive test: Single dot should not be converted
		$input = 'A.B';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests handling of dots at start of string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyHandlesDotsAtStartOfString(): void {
		$rule = new EllipsisRule();

		$input = '...Start';
		$expected = "\u{2026}Start";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of dots at end of string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyHandlesDotsAtEndOfString(): void {
		$rule = new EllipsisRule();

		$input = 'Ende...';
		$expected = "Ende\u{2026}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of multiple ellipses in the same string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyHandlesMultipleEllipsesInSameString(): void {
		$rule = new EllipsisRule();

		$input = 'A...B...C...';
		$expected = "A\u{2026}B\u{2026}C\u{2026}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests forcing space before on convert.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyForcesSpaceBeforeOnConvert(): void {
		$rule = new EllipsisRule(' ', null);

		$input = 'A...B';
		$expected = "A \u{2026}B";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests forcing space after on convert.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyForcesSpaceAfterOnConvert(): void {
		$rule = new EllipsisRule(null, ' ');

		$input = 'A...B';
		$expected = "A\u{2026} B";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests forcing no space before on convert.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyForcesNoSpaceBeforeOnConvert(): void {
		$rule = new EllipsisRule('', null);

		$input = 'A ... B';
		$expected = "A\u{2026} B";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests forcing no space after on convert.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyForcesNoSpaceAfterOnConvert(): void {
		$rule = new EllipsisRule(null, '');

		$input = 'A ... B';
		$expected = "A \u{2026}B";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that spacing is not forced at boundaries.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyDoesNotForceSpacingAtBoundaries(): void {
		$rule = new EllipsisRule(' ', ' ');

		// Should not add space when at start/end
		$input = '...A A... ...B...';
		$expected = "\u{2026}A A\u{2026}\u{2026}B\u{2026}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of dots with only space before.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyHandlesDotsWithOnlySpaceBefore(): void {
		$rule = new EllipsisRule();

		$input = 'Text ...';
		$expected = "Text \u{2026}";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of dots with only space after.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyHandlesDotsWithOnlySpaceAfter(): void {
		$rule = new EllipsisRule();

		$input = '... text';
		$expected = "\u{2026} text";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of dots with numbers.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\EllipsisRule::apply
	 */
	public function testApplyHandlesDotsWithNumbers(): void {
		$rule = new EllipsisRule();

		$input = '1...2';
		$expected = "1\u{2026}2";

		$this->assertSame($expected, $rule->apply($input));
	}
}
