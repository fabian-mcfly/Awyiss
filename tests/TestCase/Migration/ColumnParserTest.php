<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Migration;


use Awyiss\Migration\ColumnParser;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;


/**
 * ColumnParser Test Case
 *
 * @see \Awyiss\Migration\ColumnParser
 */
class ColumnParserTest extends TestCase {
	/**
	 * @var ColumnParser
	 */
	protected ColumnParser $columnParser;


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();
		$this->columnParser = new ColumnParser();
	}


	/**
	 * @return array<string, array{input: string, output: array}>
	 */
	public static function parseFieldsProvider(): array {
		return [
			[
				'input' => 'parentId:integer?[11]:index',
				'output' => [
					'parentId' => [
						'columnType' => 'integer',
						'options' => [
							'null' => true,
							'default' => null,
							'limit' => 11,
						],
					],
				],
			],
			[
				'input' => 'languageShortcode:char?[2]:index',
				'output' => [
					'languageShortcode' => [
						'columnType' => 'char',
						'options' => [
							'null' => true,
							'default' => null,
							'limit' => 2,
						],
					],
				],
			],
			[
				'input' => 'title:string?[255]',
				'output' => [
					'title' => [
						'columnType' => 'string',
						'options' => [
							'null' => true,
							'default' => null,
							'limit' => 255,
						],
					],
				],
			],
			[
				'input' => 'systemOrder:integer[11](0)',
				'output' => [
					'systemOrder' => [
						'columnType' => 'integer',
						'options' => [
							'null' => false,
							'default' => '0',
							'limit' => 11,
						],
					],
				],
			],
			[
				'input' => 'active:tinyinteger[1](1):index',
				'output' => [
					'active' => [
						'columnType' => 'tinyinteger',
						'options' => [
							'null' => false,
							'default' => '1',
							'limit' => 1,
						],
					],
				],
			],
			[
				'input' => 'deleted:tinyinteger[1](0):index',
				'output' => [
					'deleted' => [
						'columnType' => 'tinyinteger',
						'options' => [
							'null' => false,
							'default' => '0',
							'limit' => 1,
						],
					],
				],
			],
			[
				'input' => 'createdBy:integer?[11]',
				'output' => [
					'createdBy' => [
						'columnType' => 'integer',
						'options' => [
							'null' => true,
							'default' => null,
							'limit' => 11,
						],
					],
				],
			],
			[
				'input' => 'createdOn:datetime?',
				'output' => [
					'createdOn' => [
						'columnType' => 'datetime',
						'options' => [
							'null' => true,
							'default' => null,
						],
					],
				],
			],
			[
				'input' => 'changedBy:integer?[11]',
				'output' => [
					'changedBy' => [
						'columnType' => 'integer',
						'options' => [
							'null' => true,
							'default' => null,
							'limit' => 11,
						],
					],
				],
			],
			[
				'input' => 'changedOn:datetime?',
				'output' => [
					'changedOn' => [
						'columnType' => 'datetime',
						'options' => [
							'null' => true,
							'default' => null,
						],
					],
				],
			],
			[
				'input' => 'deletedBy:integer?[11]',
				'output' => [
					'deletedBy' => [
						'columnType' => 'integer',
						'options' => [
							'null' => true,
							'default' => null,
							'limit' => 11,
						],
					],
				],
			],
			[
				'input' => 'deletedOn:datetime?',
				'output' => [
					'deletedOn' => [
						'columnType' => 'datetime',
						'options' => [
							'null' => true,
							'default' => null,
						],
					],
				],
			],
			[
				'input' => 'email:string?',
				'output' => [
					'email' => [
						'columnType' => 'string',
						'options' => [
							'null' => true,
							'default' => null,
							'limit' => 255,
						],
					],
				],
			],
			[
				'input' => 'email:string:unique',
				'output' => [
					'email' => [
						'columnType' => 'string',
						'options' => [
							'null' => false,
							'default' => null,
							'limit' => 255,
						],
					],
				],
			],
			[
				'input' => 'email:string?[50]',
				'output' => [
					'email' => [
						'columnType' => 'string',
						'options' => [
							'null' => true,
							'default' => null,
							'limit' => 50,
						],
					],
				],
			],
			[
				'input' => 'email:string:unique:EMAIL_INDEX',
				'output' => [
					'email' => [
						'columnType' => 'string',
						'options' => [
							'null' => false,
							'default' => null,
							'limit' => 255,
						],
					],
				],
			],
			[
				'input' => 'email:string[120]:unique:EMAIL_INDEX',
				'output' => [
					'email' => [
						'columnType' => 'string',
						'options' => [
							'null' => false,
							'default' => null,
							'limit' => '120',
						],
					],
				],
			],
			[
				'input' => 'foobar2:string?[100](foo(bar)baz)',
				'output' => [
					'foobar2' => [
						'columnType' => 'string',
						'options' => [
							'null' => true,
							'default' => 'foo(bar)baz',
							'limit' => '100',
						],
					],
				],
			],
			[
				'input' => 'email:string?(foo(bar)baz)',
				'output' => [
					'email' => [
						'columnType' => 'string',
						'options' => [
							'null' => true,
							'default' => 'foo(bar)baz',
							'limit' => 255,
						],
					],
				],
			],
			[
				'input' => 'email:string(foo(bar)baz):unique',
				'output' => [
					'email' => [
						'columnType' => 'string',
						'options' => [
							'null' => false,
							'default' => 'foo(bar)baz',
							'limit' => 255,
						],
					],
				],
			],
		];
	}


	/**
	 * @param string $input
	 * @param array $output
	 * @return void
	 * @see \Awyiss\Migration\ColumnParser::parseFields()
	 */
	#[DataProvider('parseFieldsProvider')]
	public function testParseFields(string $input, array $output): void {
		$result = $this->columnParser->parseFields([$input]);
		$this->assertEquals($output, $result);
	}


	/**
	 * @return array<int, array{field: string, type: string, output: array}>
	 */
	public static function getTypeAndLengthAndDefaultProvider(): array {
		return [
			[
				'field' => 'price',
				'type' => 'decimal[10,2](0.00)',
				'output' => ['decimal', ['10', '2'], '0.00'],
			],
			[
				'field' => 'description',
				'type' => 'text',
				'output' => ['text', null, null],
			],
			[
				'field' => 'css',
				'type' => 'mediumtext',
				'output' => ['mediumtext', null, null],
			],
			[
				'field' => 'name',
				'type' => 'string[255](John Doe)',
				'output' => ['string', '255', 'John Doe'],
			],
			[
				'field' => 'is_active',
				'type' => 'boolean(1)',
				'output' => ['boolean', null, '1'],
			],
			[
				'field' => 'created_at',
				'type' => 'datetime',
				'output' => ['datetime', null, null],
			],
			[
				'field' => 'amount',
				'type' => 'float[8,2]',
				'output' => ['float', ['8', '2'], null],
			],
			[
				'field' => 'profile_picture',
				'type' => 'binary',
				'output' => ['binary', null, null],
			],
			[
				'field' => 'uuid',
				'type' => 'uuid',
				'output' => ['uuid', null, null],
			],
			[
				'field' => 'big_number',
				'type' => 'biginteger[20](12345678901234567890)',
				'output' => ['biginteger', '20', '12345678901234567890'],
			],
			[
				'field' => 'big_number',
				'type' => 'biginteger(12345678901234567890)',
				'output' => ['biginteger', '20', '12345678901234567890'],
			],
		];
	}


	/**
	 * @param string $field
	 * @param string $type
	 * @param array $output
	 * @return void
	 * @see \Awyiss\Migration\ColumnParser::getTypeAndLengthAndDefault()
	 */
	#[DataProvider('getTypeAndLengthAndDefaultProvider')]
	public function testGetTypeAndLengthAndDefault(string $field, string $type, array $output): void {
		$result = $this->columnParser->getTypeAndLengthAndDefault($field, $type);

		$this->assertEquals($output, $result);
	}
}
