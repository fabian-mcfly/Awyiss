<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Menu;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\BackendMenu;
use Awyiss\Utility\Menu\BackendMenuItem;
use Awyiss\Utility\Menu\Exception\MenuDuplicateIdentifierException;
use Awyiss\Utility\Menu\Exception\MenuFileException;
use Awyiss\Utility\Menu\Exception\MenuValidationException;
use Awyiss\Utility\Menu\MenuLoader;
use Error;
use Symfony\Component\Process\Process;


/**
 * Test case for MenuLoader class.
 *
 * @see \Awyiss\Utility\Menu\MenuLoader()
 */
class MenuLoaderTest extends TestCase {
	/**
	 * @var object
	 */
	protected object $menuSchema;
	/**
	 * Test directory for temporary files
	 *
	 * @var string
	 */
	protected string $testDir;


	/**
	 * Setup method to create test directory
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a temporary directory for test files
		$this->testDir = TMP . 'menu_loader_tests' . DS;
		if (!is_dir($this->testDir)) {
			mkdir($this->testDir);
		}

		$this->menuSchema = json_decode(json_encode([
			'type' => 'object',
			'patternProperties' => [
				'^[a-z][a-z0-9_]{1,49}+$' => [
					'$ref' => '#/definitions/menuItem',
				],
			],
			'definitions' => [
				'menuItem' => [
					'type' => 'object',
					'properties' => [
						'title' => [
							'oneOf' => [
								['type' => 'string'],
								[
									'type' => 'object',
									'properties' => [
										'translate' => [
											'type' => 'array',
											'items' => ['type' => 'string'],
										],
									],
									'required' => ['translate'],
									'additionalProperties' => false,
								],
							],
						],
						'link' => [
							'oneOf' => [
								['type' => 'null'],
								['type' => 'string', 'format' => 'uri'],
								[
									'type' => 'object',
									'properties' => [
										'url' => [
											'oneOf' => [
												['type' => 'string', 'format' => 'uri'],
												[
													'type' => 'object',
													'properties' => [
														'controller' => [
															'type' => 'string',
															'pattern' => '^[A-Z][a-zA-Z0-9]+$',
														],
														'action' => [
															'type' => 'string',
															'pattern' => '^[a-z][a-z0-9-]+$',
														],
													],
													'patternProperties' => [
														'^((?!^(controller|action|_name)))[a-z][a-z0-9-]+$' => [
															'pattern' => '^[a-z0-9-]+$',
														],
													],
													'required' => ['controller', 'action'],
													'additionalProperties' => false,
												],
											],
										],
										'attributes' => [
											'oneOf' => [
												['type' => 'null'],
												[
													'type' => 'object',
													'properties' => [
														'target' => ['type' => 'string'],
														'rel' => ['type' => 'string'],
													],
													'patternProperties' => [
														'^[a-zA-Z0-9-_]*$' => ['type' => 'string'],
													],
													'additionalProperties' => false,
												],
											],
										],
									],
									'additionalProperties' => false,
								],
							],
						],
						'children' => [
							'oneOf' => [
								['type' => 'null'],
								[
									'type' => 'object',
									'patternProperties' => [
										'^[a-z][a-z0-9_]+$' => [
											'$ref' => '#/definitions/menuItem',
										],
									],
									'additionalProperties' => false,
								],
							],
						],
					],
					'required' => ['title', 'link'],
					'additionalProperties' => false,
				],
			],
		]));
	}


	/**
	 * Teardown method to clean up test files
	 */
	public function tearDown(): void {
		// Clean up test files
		if (is_dir($this->testDir)) {
			new Process(['rm', '-r', $this->testDir])->run();
		}

		parent::tearDown();
	}


	/**
	 * Test validateData with valid data
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithValidData(): void {
		$data = json_decode(json_encode([
			'items' => [
				'item1' => ['title' => 'Item 1'],
				'item2' => ['title' => 'Item 2'],
			],
		]));

		$schema = json_decode(json_encode([
			'type' => 'object',
			'required' => ['items'],
			'properties' => [
				'items' => [
					'type' => 'object',
				],
			],
		]));

		$config = ['schema' => $schema];

		$result = MenuLoader::validateData($data, $config);
		$this->assertTrue($result);
	}


	/**
	 * Test validateData with invalid data
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithInvalidData(): void {
		$data = json_decode(json_encode([
			'wrongProperty' => 'value',
		]));

		$schema = json_decode(json_encode([
			'type' => 'object',
			'required' => ['items'],
			'properties' => [
				'items' => [
					'type' => 'object',
				],
			],
		]));

		$config = ['schema' => $schema];

		$result = MenuLoader::validateData($data, $config);
		$this->assertFalse($result);
	}


	/**
	 * Test validateData with schema from file
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::validateData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidateDataWithSchemaPath(): void {
		$data = json_decode(json_encode([
			'items' => [
				'item1' => ['title' => 'Item 1'],
			],
		]));

		// Create a temporary schema file
		$schemaPath = $this->testDir . 'schema.json';
		$schema = [
			'type' => 'object',
			'required' => ['items'],
			'properties' => [
				'items' => [
					'type' => 'object',
				],
			],
		];
		file_put_contents($schemaPath, json_encode($schema));

		$config = ['schemaPath' => $schemaPath];

		$result = MenuLoader::validateData($data, $config);
		$this->assertTrue($result);
	}


	/**
	 * Test fromObject with valid data
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::fromObject()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromObjectWithoutClasses(): void {
		$data = json_decode(json_encode([
			'items' => [
				'item1' => ['title' => 'Item 1'],
				'item2' => ['title' => 'Item 2'],
			],
		]));

		// Should throw an error if classes are not provided
		// since abstract classes cannot be instantiated
		$this->expectException(Error::class);
		$this->expectExceptionMessage('Cannot instantiate abstract class Awyiss\Utility\Menu\Menu');
		MenuLoader::fromObject($data);
	}


	/**
	 * Test fromObject with valid data
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::fromObject()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromObjectWithClasses(): void {
		$data = json_decode(json_encode([
			'item1' => ['title' => 'Item 1'],
			'item2' => ['title' => 'Item 2'],
		]));

		$menu = MenuLoader::fromObject($data, [
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$this->assertInstanceOf(BackendMenu::class, $menu);
		$this->assertCount(2, $menu->getItems());

		foreach ($menu->items() as $item) {
			$this->assertInstanceOf(BackendMenuItem::class, $item);
		}
	}


	/**
	 * Test fromObject with schema validation
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::fromObject()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromObjectWithSchemaValidation(): void {
		$data = json_decode(json_encode([
			'item1' => [
				'title' => 'Item 1',
				'link' => 'https://example.com',
			],
			'item2' => [
				'title' => 'Item 2',
				'link' => [
					'url' => 'https://example.com/item2',
					'attributes' => [
						'target' => '_blank',
						'rel' => 'noopener',
					],
				],
			],
		]));

		$config = [
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
			'validate' => ['schema' => $this->menuSchema],
		];

		$menu = MenuLoader::fromObject($data, $config);
		$this->assertInstanceOf(BackendMenu::class, $menu);
		$this->assertCount(2, $menu->getItems());
	}


	/**
	 * Test fromObject with schema validation failure
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::fromObject()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromObjectWithSchemaValidationFailure(): void {
		$data = json_decode(json_encode([
			'item1' => [
				'title' => 'Item 1',
				'link' => 'invalid-url', // Invalid URL
			],
		]));

		$config = [
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
			'validate' => ['schema' => $this->menuSchema],
		];

		$this->expectException(MenuValidationException::class);
		MenuLoader::fromObject($data, $config);
	}


	/**
	 * Test fromObject with uniqueIdentifiers validation
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::fromObject()
	 * @see \Awyiss\Utility\Menu\MenuLoader::validateUniqueIdentifiers()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromObjectWithUniqueIdentifiersValidation(): void {
		$data = json_decode(json_encode([
			'item1' => [
				'title' => 'Item 1',
				'link' => 'https://example.com/item1',
				'children' => [
					'subitem1' => [
						'title' => 'Subitem 1',
						'link' => 'https://example.com/subitem1',
					],
					'subitem2' => [
						'title' => 'Subitem 2',
						'link' => 'https://example.com/subitem2',
					],
				],
			],
			'item2' => [
				'title' => 'Item 2',
				'link' => 'https://example.com/item2',
				'children' => [
					// Duplicate identifier
					'subitem1' => [
						'title' => 'Subitem 1',
						'link' => 'https://example.com/subitem1',
					],
				],
			],
		]));

		$config = [
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
			'validate' => [
				'schema' => $this->menuSchema,
				'uniqueIdentifiers' => true,
			],
		];

		$this->expectException(MenuDuplicateIdentifierException::class);
		$this->expectExceptionMessage('Cannot use identifier `subitem1` twice in `Awyiss\Utility\Menu\MenuLoader`');
		MenuLoader::fromObject($data, $config);
	}


	/**
	 * Test fromJsonFile with valid file
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::fromJsonFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromJsonFile(): void {
		// Create a temporary JSON file
		$filePath = $this->testDir . 'menu_test.json';
		$data = [
			'item1' => ['title' => 'Item 1'],
			'item2' => ['title' => 'Item 2'],
		];
		file_put_contents($filePath, json_encode($data));

		$menu = MenuLoader::fromJsonFile($filePath, [
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$this->assertInstanceOf(BackendMenu::class, $menu);
		$this->assertCount(2, $menu->getItems());
	}


	/**
	 * Test fromJsonFile with non-existent file
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::fromJsonFile()
	 */
	public function testFromJsonFileWithNonExistentFile(): void {
		$this->expectException(MenuFileException::class);
		MenuLoader::fromJsonFile($this->testDir . 'nonexistent.json');
	}


	/**
	 * Test fromJsonString with valid JSON
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::fromJsonString()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFromJsonString(): void {
		$jsonString = json_encode([
			'item1' => ['title' => 'Item 1'],
			'item2' => ['title' => 'Item 2'],
		]);

		$menu = MenuLoader::fromJsonString($jsonString, [
			'menuClass' => BackendMenu::class,
			'menuItemClass' => BackendMenuItem::class,
		]);

		$this->assertInstanceOf(BackendMenu::class, $menu);
		$this->assertCount(2, $menu->getItems());
	}


	/**
	 * Test fromJsonString with invalid JSON
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::fromJsonString()
	 */
	public function testFromJsonStringWithInvalidJson(): void {
		$this->expectException(MenuValidationException::class);
		MenuLoader::fromJsonString('{invalid:json}');
	}


	/**
	 * Test loadJsonFile with valid file
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::loadJsonFile()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadJsonFile(): void {
		// Create a temporary JSON file
		$filePath = $this->testDir . 'data.json';
		$data = ['property' => 'value'];
		file_put_contents($filePath, json_encode($data));

		$result = MenuLoader::loadJsonFile($filePath);

		$this->assertIsObject($result);
		$this->assertEquals('value', $result->property);
	}


	/**
	 * Test loadJsonFile with non-existent file
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::loadJsonFile()
	 */
	public function testLoadJsonFileWithNonExistentFile(): void {
		$this->expectException(MenuFileException::class);
		$this->expectExceptionMessage('File');
		MenuLoader::loadJsonFile($this->testDir . 'nonexistent.json');
	}


	/**
	 * Test loadJsonString with valid JSON
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::loadJsonString()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadJsonString(): void {
		$result = MenuLoader::loadJsonString('{"property":"value"}');

		$this->assertIsObject($result);
		$this->assertEquals('value', $result->property);
	}


	/**
	 * Test loadJsonString with invalid JSON
	 *
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuLoader::loadJsonString()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadJsonStringWithInvalidJson(): void {
		$this->expectException(MenuValidationException::class);
		$this->expectExceptionMessage('Invalid JSON string');
		MenuLoader::loadJsonString('{invalid:json}');
	}
}
