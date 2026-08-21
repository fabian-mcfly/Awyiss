<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\Typography\Rule;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\Typography\Rule\ApostropheRule;


/**
 * Tests for ApostropheRule
 *
 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule
 */
class ApostropheRuleTest extends TestCase {
	/**
	 * Tests that apostrophes between letters are replaced with typographic apostrophes.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyReplacesApostropheBetweenLetters(): void {
		$rule = new ApostropheRule();

		$input = "don't l'arbre dell'anno";
		$expected = "don\u{2019}t l\u{2019}arbre dell\u{2019}anno";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that quotation-like and trailing apostrophes are not replaced (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyDoesNotReplaceQuotationLikeOrTrailingApostrophes(): void {
		$rule = new ApostropheRule();

		$input = "'Start' dogs' toys rock 'n' roll";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests handling of English contractions.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyHandlesEnglishContractions(): void {
		$rule = new ApostropheRule();

		$input = "don't can't won't I'm you're he's she's it's we're they're";
		$expected = "don\u{2019}t can\u{2019}t won\u{2019}t I\u{2019}m you\u{2019}re he\u{2019}s "
			. "she\u{2019}s it\u{2019}s we\u{2019}re they\u{2019}re";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of French elisions.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyHandlesFrenchElisions(): void {
		$rule = new ApostropheRule();

		$input = "l'homme d'accord c'est qu'il s'il";
		$expected = "l\u{2019}homme d\u{2019}accord c\u{2019}est qu\u{2019}il s\u{2019}il";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of Italian elisions.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyHandlesItalianElisions(): void {
		$rule = new ApostropheRule();

		$input = "dell'anno l'arte un'amica";
		$expected = "dell\u{2019}anno l\u{2019}arte un\u{2019}amica";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that leading apostrophes are not replaced (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyDoesNotReplaceLeadingApostrophe(): void {
		$rule = new ApostropheRule();

		// False positive test: Leading apostrophes (quotation marks)
		$input = "'Twas the night";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that trailing apostrophes are not replaced (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyDoesNotReplaceTrailingApostrophe(): void {
		$rule = new ApostropheRule();

		// False positive test: Trailing apostrophes (possessive plural)
		$input = "dogs' toys cats' food";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that isolated apostrophes are not replaced (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyDoesNotReplaceIsolatedApostrophe(): void {
		$rule = new ApostropheRule();

		// False positive test: Isolated apostrophe
		$input = "word ' word";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests handling of multiple apostrophes in the same string.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyHandlesMultipleApostrophesInSameString(): void {
		$rule = new ApostropheRule();

		$input = "It's don't can't won't";
		$expected = "It\u{2019}s don\u{2019}t can\u{2019}t won\u{2019}t";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that apostrophes between non-letters are not replaced (false positive prevention).
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyDoesNotReplaceApostropheBetweenNonLetters(): void {
		$rule = new ApostropheRule();

		// False positive test: Apostrophe between numbers
		$input = "1'2 3'4";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that apostrophes after letters but before non-letters are not replaced.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyDoesNotReplaceApostropheAfterLetter(): void {
		$rule = new ApostropheRule();

		// False positive test: Apostrophe after letter but before non-letter
		$input = "word' 123";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests that apostrophes before letters but after non-letters are not replaced.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyDoesNotReplaceApostropheBeforeLetter(): void {
		$rule = new ApostropheRule();

		// False positive test: Apostrophe before letter but after non-letter
		$input = "123 'word";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests handling of uppercase letters.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyHandlesUppercaseLetters(): void {
		$rule = new ApostropheRule();

		$input = "DON'T CAN'T WON'T";
		$expected = "DON\u{2019}T CAN\u{2019}T WON\u{2019}T";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of mixed case letters.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyHandlesMixedCase(): void {
		$rule = new ApostropheRule();

		$input = "Don't Can't Won't";
		$expected = "Don\u{2019}t Can\u{2019}t Won\u{2019}t";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of accented characters.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyHandlesAccentedCharacters(): void {
		$rule = new ApostropheRule();

		$input = "l'été d'été c'était";
		$expected = "l\u{2019}été d\u{2019}été c\u{2019}était";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests that already typographic apostrophes are not modified.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyDoesNotModifyAlreadyTypographicApostrophes(): void {
		$rule = new ApostropheRule();

		// Should not re-process already typographic apostrophes
		$input = "don\u{2019}t l\u{2019}arbre";

		$this->assertSame($input, $rule->apply($input));
	}


	/**
	 * Tests distinguishing possessive plural from contractions.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyDistinguishesPossessivePluralFromContraction(): void {
		$rule = new ApostropheRule();

		// Possessive plural should NOT be converted (apostrophe after letter, before space/punctuation)
		// Contractions should be converted (apostrophe between two letters)
		$input = "The boys' toys and the boy's toy";
		$expected = "The boys' toys and the boy\u{2019}s toy";

		$this->assertSame($expected, $rule->apply($input));
	}


	/**
	 * Tests handling of German umlauts.
	 *
	 * @covers \Awyiss\Utility\Content\Typography\Rule\ApostropheRule::apply
	 */
	public function testApplyHandlesGermanUmlauts(): void {
		$rule = new ApostropheRule();

		$input = "über's Wochenende";
		$expected = "über\u{2019}s Wochenende";

		$this->assertSame($expected, $rule->apply($input));
	}
}
