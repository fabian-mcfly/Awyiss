<?php


/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Model\Enum\Survey;


use Awyiss\Model\Enum\Survey\Type;
use Awyiss\Test\TestSuite\TestCase;
use ValueError;


/**
 * Type Test Case
 *
 * @see \Awyiss\Model\Enum\Survey\Type
 */
class TypeTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\Type
	 */
	public function testEnumCases(): void {
		$this->assertEquals('linear', Type::Linear->value);
		$this->assertEquals('configurator', Type::Configurator->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\Type
	 */
	public function testEnumFromMethod(): void {
		$this->assertEquals(Type::Linear, Type::from('linear'));
		$this->assertEquals(Type::Configurator, Type::from('configurator'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\Type
	 * @throws \ValueError
	 * @noinspection PhpExpressionResultUnusedInspection
	 */
	public function testEnumFromMethodThrowsExceptionForInvalidValue(): void {
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('"invalid" is not a valid backing value for enum Awyiss\Model\Enum\Survey\Type');

		/** @noinspection PhpCaseWithValueNotFoundInEnumInspection */
		Type::from('invalid');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\Type
	 */
	public function testEnumTryFromMethodValid(): void {
		$this->assertEquals(Type::Linear, Type::tryFrom('linear'));
		$this->assertEquals(Type::Configurator, Type::tryFrom('configurator'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\Type
	 * @noinspection PhpCaseWithValueNotFoundInEnumInspection
	 */
	public function testEnumTryFromMethodInvalid(): void {
		$this->assertNull(Type::tryFrom('invalid'));
		$this->assertNull(Type::tryFrom(''));
		$this->assertNull(Type::tryFrom('Linear'));
		$this->assertNull(Type::tryFrom('Configurator'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\Type::label()
	 */
	public function testLabel(): void {
		$label = Type::Linear->label();
		$this->assertIsString($label);
		$this->assertSame('surveys::survey_type_linear', $label);

		$label = Type::Configurator->label();
		$this->assertIsString($label);
		$this->assertSame('surveys::survey_type_configurator', $label);
	}
}
