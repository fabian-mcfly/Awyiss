<?php


/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Model\Enum;


use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Test\TestSuite\TestCase;
use ValueError;


/**
 * ProcessStatus Test Case
 *
 * @see \Awyiss\Model\Enum\ProcessStatus
 */
class ProcessStatusTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ProcessStatus
	 */
	public function testEnumCases(): void {
		$this->assertEquals(0, ProcessStatus::Undefined->value);
		$this->assertEquals(1, ProcessStatus::Success->value);
		$this->assertEquals(2, ProcessStatus::InProgress->value);
		$this->assertEquals(3, ProcessStatus::Fail->value);
		$this->assertEquals(-1, ProcessStatus::NotRequired->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ProcessStatus
	 */
	public function testEnumFromMethod(): void {
		$this->assertEquals(ProcessStatus::Undefined, ProcessStatus::from(0));
		$this->assertEquals(ProcessStatus::Success, ProcessStatus::from(1));
		$this->assertEquals(ProcessStatus::InProgress, ProcessStatus::from(2));
		$this->assertEquals(ProcessStatus::Fail, ProcessStatus::from(3));
		$this->assertEquals(ProcessStatus::NotRequired, ProcessStatus::from(-1));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PublicationDataType
	 * @throws \ValueError
	 */
	public function testEnumFromMethodThrowsExceptionForInvalidValue(): void {
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('123 is not a valid backing value for enum Awyiss\Model\Enum\ProcessStatus');

		/** @noinspection PhpExpressionResultUnusedInspection */
		ProcessStatus::from(123);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\ProcessStatus
	 */
	public function testEnumTryFromMethodInvalid(): void {
		$this->assertNull(ProcessStatus::tryFrom(999));
		$this->assertNull(ProcessStatus::tryFrom(-999));
	}
}
