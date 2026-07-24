<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Inflector;


/**
 * InflectorTest class
 *
 * @see \Awyiss\Utility\Inflector
 */
class InflectorTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Utility\Inflector::ucparts()
	 */
	public function testUcpartsCapitalizesEachPartWithDefaultDelimiter(): void {
		$this->assertSame('Foo_Bar', Inflector::ucparts('foo-bar'));
		$this->assertSame('Foo_Bar', Inflector::ucparts('foo_bar'));
		$this->assertSame('Foo_Bar', Inflector::ucparts('foo bar'));
		$this->assertSame('Foo_Bar', Inflector::ucparts('fooBar'));
		$this->assertSame('Foo_Bar', Inflector::ucparts('foo\'bar'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Inflector::ucparts()
	 */
	public function testUcpartsReplacesDelimiterWithCustomDelimiter(): void {
		$this->assertSame('Foo_Bar', Inflector::ucparts('foo-bar', '_'));
		$this->assertSame('Foo-Bar', Inflector::ucparts('foo_bar', '-'));
		$this->assertSame('Foo/Bar', Inflector::ucparts('foo_bar', '/'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Inflector::ucparts()
	 */
	public function testUcpartsRemovesDelimiterWhenFalse(): void {
		$this->assertSame('FooBar', Inflector::ucparts('foo-bar', false));
		$this->assertSame('FooBar', Inflector::ucparts('foo_bar', false));
		$this->assertSame('FooBar', Inflector::ucparts('foo bar', false));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Inflector::ucparts()
	 */
	public function testUcpartsHandlesEmptyString(): void {
		$this->assertSame('', Inflector::ucparts(''));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Inflector::ucparts()
	 */
	public function testUcpartsHandlesSingleWord(): void {
		$this->assertSame('Foo', Inflector::ucparts('foo'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Inflector::ucparts()
	 */
	public function testUcpartsHandlesMultipleDelimiters(): void {
		$this->assertSame('Foo_Bar_Baz', Inflector::ucparts('foo-bar_baz'));
		$this->assertSame('FooBarBaz', Inflector::ucparts('foo-bar_baz', false));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Inflector::underscore()
	 */
	public function testUnderscoreReplacesMultipleUnderscoresWithSingle(): void {
		$this->assertSame('foo_bar', Inflector::underscore('Foo__Bar'));
		$this->assertSame('foo_bar_baz', Inflector::underscore('Foo___Bar__Baz'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Inflector::underscore()
	 */
	public function testUnderscoreHandlesNoDoubleUnderscores(): void {
		$this->assertSame('foo_bar', Inflector::underscore('FooBar'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Inflector::underscore()
	 */
	public function testUnderscoreHandlesEmptyString(): void {
		$this->assertSame('', Inflector::underscore(''));
	}
}
