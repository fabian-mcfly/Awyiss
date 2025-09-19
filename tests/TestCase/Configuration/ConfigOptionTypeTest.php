<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration;


use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * Tests for `ConfigOptionType`
 */
class ConfigOptionTypeTest extends TestCase {
	/**
	 * @return void
	 */
	public function testValidateBool(): void {
		$this->assertTrue(ConfigOptionType::Bool->validate(true));
		$this->assertTrue(ConfigOptionType::Bool->validate(false));
		$this->assertTrue(ConfigOptionType::Bool->validate(1));
		$this->assertTrue(ConfigOptionType::Bool->validate(0));
		$this->assertTrue(ConfigOptionType::Bool->validate('1'));
		$this->assertTrue(ConfigOptionType::Bool->validate('0'));
		$this->assertFalse(ConfigOptionType::Bool->validate('true'));
		$this->assertFalse(ConfigOptionType::Bool->validate('false'));
	}


	/**
	 * @return void
	 */
	public function testValidateFloat(): void {
		$this->assertTrue(ConfigOptionType::Float->validate(1.23));
		$this->assertFalse(ConfigOptionType::Float->validate(1));
		$this->assertFalse(ConfigOptionType::Float->validate('1.23'));
		$this->assertFalse(ConfigOptionType::Float->validate('abc'));
	}


	/**
	 * @return void
	 */
	public function testValidateInteger(): void {
		$this->assertTrue(ConfigOptionType::Integer->validate(123));
		$this->assertFalse(ConfigOptionType::Integer->validate(1.23));
		$this->assertFalse(ConfigOptionType::Integer->validate('123'));
		$this->assertFalse(ConfigOptionType::Integer->validate('abc'));
	}


	/**
	 * @return void
	 */
	public function testValidateJson(): void {
		$this->assertTrue(ConfigOptionType::Json->validate('["a", "b", "c"]'));
		$this->assertTrue(ConfigOptionType::Json->validate('{"a": "b"}'));
		$this->assertFalse(ConfigOptionType::Json->validate('abc'));
	}


	/**
	 * @return void
	 */
	public function testValidateString(): void {
		$this->assertTrue(ConfigOptionType::String->validate('abc'));
		$this->assertFalse(ConfigOptionType::String->validate(123));
	}


	/**
	 * @return void
	 */
	public function testCastBool(): void {
		$this->assertTrue(ConfigOptionType::Bool->cast('1'));
		$this->assertFalse(ConfigOptionType::Bool->cast('0'));
		$this->assertFalse(ConfigOptionType::Bool->cast('false'));
	}


	/**
	 * @return void
	 */
	public function testCastFloat(): void {
		$this->assertEquals(1.23, ConfigOptionType::Float->cast('1.23'));
	}


	/**
	 * @return void
	 */
	public function testCastInteger(): void {
		$this->assertEquals(123, ConfigOptionType::Integer->cast('123'));
	}


	/**
	 * @return void
	 */
	public function testCastJson(): void {
		$this->assertEquals(['a', 'b', 'c'], ConfigOptionType::Json->cast('["a", "b", "c"]'));
	}


	/**
	 * @return void
	 */
	public function testCastString(): void {
		$this->assertEquals('abc', ConfigOptionType::String->cast('abc'));
	}
}
