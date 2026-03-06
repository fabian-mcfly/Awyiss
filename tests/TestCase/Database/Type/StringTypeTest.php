<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Database\Type;


use Awyiss\Database\Type\StringType;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Database\Driver;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use InvalidArgumentException;
use stdClass;
use Stringable;


/**
 * StringType Test Case
 *
 * @see \Awyiss\Database\Type\StringType
 */
class StringTypeTest extends TestCase {
	/**
	 * @var \Awyiss\Database\Type\StringType
	 */
	protected StringType $type;
	/**
	 * @var \Cake\Database\Driver
	 */
	protected Driver $driver;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->type = new StringType();
		$this->driver = $this->getMockBuilder(Driver::class)->disableOriginalConstructor()->getMock();
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithNullValue(): void {
		$result = $this->type->toDatabase(null, $this->driver);
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithStringValue(): void {
		// Test regular string
		$value = 'test string';
		$result = $this->type->toDatabase($value, $this->driver);
		$this->assertSame($value, $result);

		// Test empty string
		$result = $this->type->toDatabase('', $this->driver);
		$this->assertSame('', $result);

		// Test string with special characters
		$special = 'Special chars: !@#$%^&*()_+{}|:"<>?[];\',./';
		$result = $this->type->toDatabase($special, $this->driver);
		$this->assertSame($special, $result);

		// Test multi-line string
		$multiline = "Line 1\nLine 2\r\nLine 3";
		$result = $this->type->toDatabase($multiline, $this->driver);
		$this->assertSame($multiline, $result);

		// Test UTF-8 string
		$utf8 = 'UTF-8 string: 你好, こんにちは, 안녕하세요';
		$result = $this->type->toDatabase($utf8, $this->driver);
		$this->assertSame($utf8, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithDateObject(): void {
		// Test standard date
		$date = new Date('2023-08-15');
		$result = $this->type->toDatabase($date, $this->driver);
		$this->assertSame('2023-08-15', $result);

		// Test date at the beginning of Unix epoch
		$epochStart = new Date('1970-01-01');
		$result = $this->type->toDatabase($epochStart, $this->driver);
		$this->assertSame('1970-01-01', $result);

		// Test date in the future
		$futureDate = new Date('2050-12-31');
		$result = $this->type->toDatabase($futureDate, $this->driver);
		$this->assertSame('2050-12-31', $result);

		// Test date with different format
		$formattedDate = new Date('2023-08-15');
		$formattedDate = $formattedDate->i18nFormat('yyyy-MM-dd');
		$result = $this->type->toDatabase($formattedDate, $this->driver);
		$this->assertSame('2023-08-15', $result);

		// Test date at the limits
		$maxDate = new Date('9999-12-31');
		$result = $this->type->toDatabase($maxDate, $this->driver);
		$this->assertSame('9999-12-31', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithDateTimeObject(): void {
		// Test standard datetime
		$dateTime = new DateTime('2023-08-15 14:30:45');
		$result = $this->type->toDatabase($dateTime, $this->driver);
		$this->assertSame('2023-08-15 14:30:45', $result);

		// Test datetime with microseconds
		$microDateTime = new DateTime('2023-08-15 14:30:45.123456');
		$result = $this->type->toDatabase($microDateTime, $this->driver);
		$this->assertSame('2023-08-15 14:30:45', $result);

		// Test datetime at epoch start
		$epochStart = new DateTime('1970-01-01 00:00:00');
		$result = $this->type->toDatabase($epochStart, $this->driver);
		$this->assertSame('1970-01-01 00:00:00', $result);

		// Test datetime in the future
		$futureDateTime = new DateTime('2050-12-31 23:59:59');
		$result = $this->type->toDatabase($futureDateTime, $this->driver);
		$this->assertSame('2050-12-31 23:59:59', $result);

		// Test datetime with different format
		$formattedDateTime = new DateTime('2023-08-15 14:30:45');
		$formattedDateTime = $formattedDateTime->i18nFormat('yyyy-MM-dd HH:mm:ss');
		$result = $this->type->toDatabase($formattedDateTime, $this->driver);
		$this->assertSame('2023-08-15 14:30:45', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithTimeObject(): void {
		// Test standard time
		$time = new Time('14:30:45');
		$result = $this->type->toDatabase($time, $this->driver);
		$this->assertSame('14:30:45', $result);

		// Test time with microseconds
		$microTime = new Time('14:30:45.123456');
		$result = $this->type->toDatabase($microTime, $this->driver);
		$this->assertSame('14:30:45', $result);

		// Test time at midnight
		$midnight = new Time('00:00:00');
		$result = $this->type->toDatabase($midnight, $this->driver);
		$this->assertSame('00:00:00', $result);

		// Test time at end of day
		$endOfDay = new Time('23:59:59');
		$result = $this->type->toDatabase($endOfDay, $this->driver);
		$this->assertSame('23:59:59', $result);

		// Test time with different format
		$formattedTime = new Time('14:30:45');
		$formattedTime = $formattedTime->i18nFormat('HH:mm:ss');
		$result = $this->type->toDatabase($formattedTime, $this->driver);
		$this->assertSame('14:30:45', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithStringableObject(): void {
		// Create a Stringable object
		$stringable = new class implements Stringable {
			/**
			 * @return string
			 */
			public function __toString(): string {
				return 'stringable object';
			}
		};

		$result = $this->type->toDatabase($stringable, $this->driver);
		$this->assertSame('stringable object', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithScalarValues(): void {
		// Integer
		$result = $this->type->toDatabase(42, $this->driver);
		$this->assertSame('42', $result);

		// Float
		$result = $this->type->toDatabase(3.14, $this->driver);
		$this->assertSame('3.14', $result);

		// Boolean true
		$result = $this->type->toDatabase(true, $this->driver);
		$this->assertSame('1', $result);

		// Boolean false
		$result = $this->type->toDatabase(false, $this->driver);
		$this->assertSame('', $result);

		// Zero
		$result = $this->type->toDatabase(0, $this->driver);
		$this->assertSame('0', $result);

		// Negative number
		$result = $this->type->toDatabase(-123, $this->driver);
		$this->assertSame('-123', $result);

		// Float with trailing zeros
		$result = $this->type->toDatabase(3.10, $this->driver);
		$this->assertSame('3.1', $result);

		// Very small float
		$result = $this->type->toDatabase(0.0001, $this->driver);
		$this->assertSame('0.0001', $result);

		// Very large number
		$result = $this->type->toDatabase(1000000000, $this->driver);
		$this->assertSame('1000000000', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithBackedEnum(): void {
		$enum = ComparisonOperator::EndsWith;

		$result = $this->type->toDatabase($enum, $this->driver);
		$this->assertSame('endsWith', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithInvalidValue(): void {
		$this->expectException(InvalidArgumentException::class);

		// Test with object that can't be converted to string
		$object = new stdClass();
		$this->type->toDatabase($object, $this->driver);
	}


	/**
	 * @return void
	 * @see \Awyiss\Database\Type\StringType::toDatabase()
	 */
	public function testToDatabaseWithIntBackedEnum(): void {
		$this->expectException(InvalidArgumentException::class);

		$enum = ProcessStatus::InProgress;
		$this->type->toDatabase($enum, $this->driver);
	}
}
