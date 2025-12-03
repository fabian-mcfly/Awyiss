<?php


/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Model\Enum;


use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Test\TestSuite\TestCase;
use InvalidArgumentException;
use ValueError;


/**
 * ResizeStrategy Test Case
 *
 * @see \Awyiss\Model\Enum\ResizeStrategy
 */
class ResizeStrategyTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy
	 */
	public function testEnumCases(): void {
		$this->assertEquals(1, ResizeStrategy::Contain->value);
		$this->assertEquals(2, ResizeStrategy::Cover->value);
		$this->assertEquals(3, ResizeStrategy::Crop->value);
		$this->assertEquals(4, ResizeStrategy::Stretch->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy::normalize()
	 */
	public function testNormalizeWithEnumInstance(): void {
		$strategy = ResizeStrategy::Contain;
		$result = ResizeStrategy::normalize($strategy);

		$this->assertSame($strategy, $result);
		$this->assertEquals(ResizeStrategy::Contain, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy::normalize()
	 */
	public function testNormalizeWithInteger(): void {
		$this->assertEquals(ResizeStrategy::Contain, ResizeStrategy::normalize(1));
		$this->assertEquals(ResizeStrategy::Cover, ResizeStrategy::normalize(2));
		$this->assertEquals(ResizeStrategy::Crop, ResizeStrategy::normalize(3));
		$this->assertEquals(ResizeStrategy::Stretch, ResizeStrategy::normalize(4));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy::normalize()
	 */
	public function testNormalizeWithStringCaseNames(): void {
		$this->assertEquals(ResizeStrategy::Contain, ResizeStrategy::normalize('contain'));
		$this->assertEquals(ResizeStrategy::Cover, ResizeStrategy::normalize('cover'));
		$this->assertEquals(ResizeStrategy::Crop, ResizeStrategy::normalize('crop'));
		$this->assertEquals(ResizeStrategy::Stretch, ResizeStrategy::normalize('stretch'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy::normalize()
	 */
	public function testNormalizeWithStringExactCaseNames(): void {
		$this->assertEquals(ResizeStrategy::Contain, ResizeStrategy::normalize('Contain'));
		$this->assertEquals(ResizeStrategy::Cover, ResizeStrategy::normalize('Cover'));
		$this->assertEquals(ResizeStrategy::Crop, ResizeStrategy::normalize('Crop'));
		$this->assertEquals(ResizeStrategy::Stretch, ResizeStrategy::normalize('Stretch'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy::normalize()
	 * @throws \InvalidArgumentException
	 */
	public function testNormalizeThrowsExceptionForInvalidString(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid resize strategy: invalid');

		ResizeStrategy::normalize('invalid');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy::normalize()
	 * @throws \InvalidArgumentException
	 */
	public function testNormalizeThrowsExceptionForEmptyString(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid resize strategy: ');

		ResizeStrategy::normalize('');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy::normalize()
	 * @throws \InvalidArgumentException
	 */
	public function testNormalizeThrowsExceptionForInvalidInteger(): void {
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('999 is not a valid backing value for enum Awyiss\Model\Enum\ResizeStrategy');

		ResizeStrategy::normalize(999);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy
	 */
	public function testEnumFromMethod(): void {
		$this->assertEquals(ResizeStrategy::Contain, ResizeStrategy::from(1));
		$this->assertEquals(ResizeStrategy::Cover, ResizeStrategy::from(2));
		$this->assertEquals(ResizeStrategy::Crop, ResizeStrategy::from(3));
		$this->assertEquals(ResizeStrategy::Stretch, ResizeStrategy::from(4));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy
	 */
	public function testEnumTryFromMethodValid(): void {
		$this->assertEquals(ResizeStrategy::Contain, ResizeStrategy::tryFrom(1));
		$this->assertEquals(ResizeStrategy::Cover, ResizeStrategy::tryFrom(2));
		$this->assertEquals(ResizeStrategy::Crop, ResizeStrategy::tryFrom(3));
		$this->assertEquals(ResizeStrategy::Stretch, ResizeStrategy::tryFrom(4));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ResizeStrategy
	 * @noinspection PhpCaseWithValueNotFoundInEnumInspection
	 */
	public function testEnumTryFromMethodInvalid(): void {
		$this->assertNull(ResizeStrategy::tryFrom(0));
		$this->assertNull(ResizeStrategy::tryFrom(5));
		$this->assertNull(ResizeStrategy::tryFrom(-1));
		$this->assertNull(ResizeStrategy::tryFrom(999));
	}
}
