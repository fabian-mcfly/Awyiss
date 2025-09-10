<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Database\Type;


use Awyiss\Database\Type\IntegerType;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Database\Driver;
use InvalidArgumentException;
use stdClass;


/**
 * IntegerType Test Case
 *
 * @see \Awyiss\Database\Type\IntegerType
 */
class IntegerTypeTest extends TestCase {
	/**
	 * @var \Awyiss\Database\Type\IntegerType
	 */
	protected IntegerType $type;
	/**
	 * @var \Cake\Database\Driver
	 */
	protected Driver $driver;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->type = new IntegerType();
		$this->driver = $this->getMockBuilder(Driver::class)
			->disableOriginalConstructor()
			->getMock();
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\IntegerType::toDatabase()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToDatabaseWithNullValue(): void {
		$result = $this->type->toDatabase(null, $this->driver);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\IntegerType::toDatabase()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToDatabaseWithIntegerValue(): void {
		$value = 42;
		$result = $this->type->toDatabase($value, $this->driver);
		$this->assertSame($value, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\IntegerType::toDatabase()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToDatabaseWithNumericString(): void {
		$result = $this->type->toDatabase('42', $this->driver);
		$this->assertSame(42, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\IntegerType::toDatabase()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToDatabaseWithFloatValue(): void {
		$result = $this->type->toDatabase(42.75, $this->driver);
		$this->assertSame(42, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\IntegerType::toDatabase()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToDatabaseWithBooleanValues(): void {
		// True should convert to 1
		$result = $this->type->toDatabase(true, $this->driver);
		$this->assertSame(1, $result);

		// False should convert to 0
		$result = $this->type->toDatabase(false, $this->driver);
		$this->assertSame(0, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\IntegerType::toDatabase()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToDatabaseWithNonNumericString(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->type->toDatabase('not-a-number', $this->driver);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\IntegerType::toDatabase()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToDatabaseWithIntBackedEnum(): void {
		$enum = ProcessStatus::Fail;

		$result = $this->type->toDatabase($enum, $this->driver);
		$this->assertSame(3, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\IntegerType::toDatabase()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToDatabaseWithStringBackedEnum(): void {
		$this->expectException(InvalidArgumentException::class);

		$enum = ComparisonOperator::Between;
		$this->type->toDatabase($enum, $this->driver);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\IntegerType::toDatabase()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testToDatabaseWithInvalidObject(): void {
		$this->expectException(InvalidArgumentException::class);
		$object = new stdClass();
		$this->type->toDatabase($object, $this->driver);
	}
}
