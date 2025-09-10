<?php


/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Model\Enum;


use Awyiss\Model\Enum\PublicationDataType;
use Awyiss\Test\TestSuite\TestCase;
use ValueError;


/**
 * PublicationDataType Test Case
 *
 * @see \Awyiss\Model\Enum\PublicationDataType
 */
class PublicationDataTypeTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PublicationDataType
	 */
	public function testEnumCases(): void {
		$this->assertEquals('start', PublicationDataType::Start->value);
		$this->assertEquals('end', PublicationDataType::End->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PublicationDataType
	 */
	public function testEnumFromMethod(): void {
		$this->assertEquals(PublicationDataType::Start, PublicationDataType::from('start'));
		$this->assertEquals(PublicationDataType::End, PublicationDataType::from('end'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PublicationDataType
	 * @throws \ValueError
	 */
	public function testEnumFromMethodThrowsExceptionForInvalidValue(): void {
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('"invalid" is not a valid backing value for enum Awyiss\Model\Enum\PublicationDataType');

		/** @noinspection PhpExpressionResultUnusedInspection, PhpCaseWithValueNotFoundInEnumInspection */
		PublicationDataType::from('invalid');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PublicationDataType
	 */
	public function testEnumTryFromMethodValid(): void {
		$this->assertEquals(PublicationDataType::Start, PublicationDataType::tryFrom('start'));
		$this->assertEquals(PublicationDataType::End, PublicationDataType::tryFrom('end'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PublicationDataType
	 * @noinspection PhpCaseWithValueNotFoundInEnumInspection
	 */
	public function testEnumTryFromMethodInvalid(): void {
		$this->assertNull(PublicationDataType::tryFrom('invalid'));
		$this->assertNull(PublicationDataType::tryFrom(''));
		$this->assertNull(PublicationDataType::tryFrom('middle'));
		$this->assertNull(PublicationDataType::tryFrom('Start'));
		$this->assertNull(PublicationDataType::tryFrom('End'));
	}
}
