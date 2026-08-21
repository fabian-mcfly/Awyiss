<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\Typography\Rule;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule;


/**
 * Tests for BracketInnerSpacingRule
 *
 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule
 */
class BracketInnerSpacingRuleTest extends TestCase {
	/**
	 * Tests that inner spacing is removed for space and NBSP variants.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyRemovesInnerSpacingForSpaceAndNbspVariants(): void {
		$rule = new BracketInnerSpacingRule();

		$input = "( foo ) [\u{00A0}bar\u{00A0}] <&nbsp;baz&nbsp;>";
		$expected = '(foo) [bar] <baz>';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that custom inner spacing can be forced.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyCanForceCustomInnerSpacing(): void {
		$rule = new BracketInnerSpacingRule(innerSpacing: "\u{00A0}");

		$input = '(foo)';
		$expected = "(\u{00A0}foo\u{00A0})";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests limiting to configured bracket pairs only.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyCanLimitToConfiguredBracketPairs(): void {
		$rule = new BracketInnerSpacingRule(['{' => '}'], ' ');

		$input = '{foo}[bar]';
		$expected = '{ foo }[bar]';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that input is returned unchanged when no bracket pairs are configured.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyReturnsInputWhenNoBracketPairsAreConfigured(): void {
		$rule = new BracketInnerSpacingRule([]);

		$input = '( foo )';

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests handling of empty brackets.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesEmptyBrackets(): void {
		$rule = new BracketInnerSpacingRule();

		$input = '( ) [ ] < >';
		$expected = '() [] <>';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of multiple spaces.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesMultipleSpaces(): void {
		$rule = new BracketInnerSpacingRule();

		$input = '(  foo  ) [  bar  ]';
		$expected = '(foo) [bar]';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of nested brackets.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesNestedBrackets(): void {
		$rule = new BracketInnerSpacingRule();

		$input = '( foo ( bar ) )';
		$expected = '(foo (bar))';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of mixed whitespace types.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesMixedWhitespace(): void {
		$rule = new BracketInnerSpacingRule();

		$input = "(\u{00A0}foo ) [ bar\u{00A0}] <\u{202F}baz >";
		$expected = '(foo) [bar] <baz>';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of curly braces.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesCurlyBraces(): void {
		$rule = new BracketInnerSpacingRule(['{' => '}']);

		$input = '{ foo }';
		$expected = '{foo}';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of parentheses only.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesParenthesesOnly(): void {
		$rule = new BracketInnerSpacingRule(['(' => ')']);

		$input = '( foo ) [ bar ]';
		$expected = '(foo) [ bar ]';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of square brackets only.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesSquareBracketsOnly(): void {
		$rule = new BracketInnerSpacingRule(['[' => ']']);

		$input = '( foo ) [ bar ]';
		$expected = '( foo ) [bar]';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of angle brackets only.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesAngleBracketsOnly(): void {
		$rule = new BracketInnerSpacingRule(['<' => '>']);

		$input = '( foo ) < bar >';
		$expected = '( foo ) <bar>';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests forcing spacing in all brackets.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyForcesSpacingInAllBrackets(): void {
		$rule = new BracketInnerSpacingRule(innerSpacing: ' ');

		$input = '(foo)[bar]<baz>';
		$expected = '( foo )[ bar ]< baz >';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that brackets with no spacing are not modified.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyDoesNotModifyBracketsWithNoSpacing(): void {
		$rule = new BracketInnerSpacingRule();

		$input = '(foo)[bar]<baz>';

		// Should remain unchanged (already correct)
		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests handling of brackets at start of string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesBracketsAtStartOfString(): void {
		$rule = new BracketInnerSpacingRule();

		$input = '( start';
		$expected = '(start';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of brackets at end of string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesBracketsAtEndOfString(): void {
		$rule = new BracketInnerSpacingRule();

		$input = 'end )';
		$expected = 'end)';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of asymmetric spacing.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesAsymmetricSpacing(): void {
		$rule = new BracketInnerSpacingRule();

		$input = '(  foo) [bar  ]';
		$expected = '(foo) [bar]';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling custom bracket pairs only.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyWithCustomBracketPairsOnly(): void {
		$rule = new BracketInnerSpacingRule(['`' => '´']);

		$input = '( foo) ` text ´ ( bar)';
		$expected = '( foo) `text´ ( bar)';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that named entities for angle brackets are normalized.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesNamedEntityAngleBrackets(): void {
		$rule = new BracketInnerSpacingRule(['<' => '>']);

		$input = '&lt; foo &gt;';
		$expected = '&lt;foo&gt;';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that numeric entities for angle brackets are normalized.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesNumericEntityAngleBrackets(): void {
		$rule = new BracketInnerSpacingRule(['<' => '>']);

		$input = '&#60; foo &#62;';
		$expected = '&#60;foo&#62;';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that hex numeric entities for angle brackets are normalized.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesHexNumericEntityAngleBrackets(): void {
		$rule = new BracketInnerSpacingRule(['<' => '>']);

		$input = '&#x3C; foo &#x3E;';
		$expected = '&#x3C;foo&#x3E;';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that mixed bracket representations are normalized consistently.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesMixedBracketRepresentations(): void {
		$rule = new BracketInnerSpacingRule(['<' => '>'], ' ');

		$input = '<foo> &lt;bar&gt; &#60;baz&#62;';
		$expected = '< foo > &lt; bar &gt; &#60; baz &#62;';

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that custom pair matching also works through entity alternatives.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\BracketInnerSpacingRule::apply
	 */
	public function testApplyHandlesCustomPairAsNamedAndNumericEntities(): void {
		$rule = new BracketInnerSpacingRule(['{' => '}']);

		$input = '&lcub; foo &rcub; und &#123; bar &#125;';
		$expected = '&lcub;foo&rcub; und &#123;bar&#125;';

		$this->assertSame($expected, $rule->apply($input));
	}
}
