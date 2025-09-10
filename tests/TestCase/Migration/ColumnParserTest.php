<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Migration;


use Awyiss\Migration\ColumnParser;
use Cake\TestSuite\TestCase;


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
				'input' => 'parent_id:integer?[11]:index',
				'output' => [
					'parent_id' => [
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
				'input' => 'language_shortcode:char?[2]:index',
				'output' => [
					'language_shortcode' => [
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
				'input' => 'system_order:integer[11](0)',
				'output' => [
					'system_order' => [
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
				'input' => 'created_by:integer?[11]',
				'output' => [
					'created_by' => [
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
				'input' => 'created_on:datetime?',
				'output' => [
					'created_on' => [
						'columnType' => 'datetime',
						'options' => [
							'null' => true,
							'default' => null,
						],
					],
				],
			],
			[
				'input' => 'changed_by:integer?[11]',
				'output' => [
					'changed_by' => [
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
				'input' => 'changed_on:datetime?',
				'output' => [
					'changed_on' => [
						'columnType' => 'datetime',
						'options' => [
							'null' => true,
							'default' => null,
						],
					],
				],
			],
			[
				'input' => 'deleted_by:integer?[11]',
				'output' => [
					'deleted_by' => [
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
				'input' => 'deleted_on:datetime?',
				'output' => [
					'deleted_on' => [
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
	 * @dataProvider parseFieldsProvider
	 * @param string $input
	 * @param array $output
	 * @return void
	 * @see \Awyiss\Migration\ColumnParser::parseFields()
	 */
	public function testParseFields(string $input, array $output): void {
		/** @noinspection PhpVariableNamingConventionInspection */
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
				'field' => 'tags',
				'type' => 'set(tag1,tag2,tag3)',
				'output' => ['set', null, 'tag1,tag2,tag3'],
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
	 * @dataProvider getTypeAndLengthAndDefaultProvider
	 * @param string $field
	 * @param string $type
	 * @param array $output
	 * @return void
	 * @see \Awyiss\Migration\ColumnParser::getTypeAndLengthAndDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTypeAndLengthAndDefault(string $field, string $type, array $output): void {
		$result = $this->columnParser->getTypeAndLengthAndDefault($field, $type);

		$this->assertEquals($output, $result);
	}
}
