<?php


/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Model\Enum;


use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Test\TestSuite\TestCase;
use ValueError;


/**
 * ComparisonOperator Test Case
 *
 * @see \Awyiss\Model\Enum\ComparisonOperator
 */
class ComparisonOperatorTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ComparisonOperator
	 */
	public function testEnumCases(): void {
		$this->assertEquals('=', ComparisonOperator::Equal->value);
		$this->assertEquals('!=', ComparisonOperator::NotEqual->value);
		$this->assertEquals('contains', ComparisonOperator::Contains->value);
		$this->assertEquals('not_contains', ComparisonOperator::NotContains->value);
		$this->assertEquals('starts_with', ComparisonOperator::StartsWith->value);
		$this->assertEquals('not_starts_with', ComparisonOperator::NotStartsWith->value);
		$this->assertEquals('ends_with', ComparisonOperator::EndsWith->value);
		$this->assertEquals('not_ends_with', ComparisonOperator::NotEndsWith->value);
		$this->assertEquals('in', ComparisonOperator::In->value);
		$this->assertEquals('not_in', ComparisonOperator::NotIn->value);
		$this->assertEquals('<', ComparisonOperator::LessThan->value);
		$this->assertEquals('<=', ComparisonOperator::LessThanOrEqual->value);
		$this->assertEquals('>', ComparisonOperator::GreaterThan->value);
		$this->assertEquals('>=', ComparisonOperator::GreaterThanOrEqual->value);
		$this->assertEquals('between', ComparisonOperator::Between->value);
		$this->assertEquals('not_between', ComparisonOperator::NotBetween->value);
		$this->assertEquals('length_equal', ComparisonOperator::LengthEqual->value);
		$this->assertEquals('length_not_equal', ComparisonOperator::LengthNotEqual->value);
		$this->assertEquals('shorter_than', ComparisonOperator::ShorterThan->value);
		$this->assertEquals('shorter_than_or_equal', ComparisonOperator::ShorterThanOrEqual->value);
		$this->assertEquals('longer_than', ComparisonOperator::LongerThan->value);
		$this->assertEquals('longer_than_or_equal', ComparisonOperator::LongerThanOrEqual->value);
		$this->assertEquals('regexp', ComparisonOperator::Regexp->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ComparisonOperator
	 */
	public function testEnumFromMethod(): void {
		$this->assertEquals(ComparisonOperator::Equal, ComparisonOperator::from('='));
		$this->assertEquals(ComparisonOperator::NotEqual, ComparisonOperator::from('!='));
		$this->assertEquals(ComparisonOperator::Contains, ComparisonOperator::from('contains'));
		$this->assertEquals(ComparisonOperator::LessThan, ComparisonOperator::from('<'));
		$this->assertEquals(ComparisonOperator::GreaterThan, ComparisonOperator::from('>'));
		$this->assertEquals(ComparisonOperator::Regexp, ComparisonOperator::from('regexp'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ComparisonOperator
	 * @throws \ValueError
	 */
	public function testEnumFromMethodThrowsExceptionForInvalidValue(): void {
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('"invalid" is not a valid backing value for enum Awyiss\Model\Enum\ComparisonOperator');

		/** @noinspection PhpCaseWithValueNotFoundInEnumInspection, PhpExpressionResultUnusedInspection */
		ComparisonOperator::from('invalid');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ComparisonOperator
	 */
	public function testEnumTryFromMethodValid(): void {
		$this->assertEquals(ComparisonOperator::Equal, ComparisonOperator::tryFrom('='));
		$this->assertEquals(ComparisonOperator::NotEqual, ComparisonOperator::tryFrom('!='));
		$this->assertEquals(ComparisonOperator::Contains, ComparisonOperator::tryFrom('contains'));
		$this->assertEquals(ComparisonOperator::Between, ComparisonOperator::tryFrom('between'));
		$this->assertEquals(ComparisonOperator::Regexp, ComparisonOperator::tryFrom('regexp'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ComparisonOperator
	 * @noinspection PhpCaseWithValueNotFoundInEnumInspection
	 */
	public function testEnumTryFromMethodInvalid(): void {
		$this->assertNull(ComparisonOperator::tryFrom('invalid'));
		$this->assertNull(ComparisonOperator::tryFrom(''));
		$this->assertNull(ComparisonOperator::tryFrom('equals'));
		$this->assertNull(ComparisonOperator::tryFrom('=='));
		$this->assertNull(ComparisonOperator::tryFrom('CONTAINS'));
	}
}
