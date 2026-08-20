<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Attribute;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\ColumnSystem\ColumnInterface;
use Cake\Datasource\FactoryLocator;


/**
 * Attribute Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Attribute
 */
class AttributeTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\AttributesTable $table */
		$table = FactoryLocator::get('Table')->get('Attributes');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Attribute();

		$this->assertSame([
			'scope' => true,
			'title' => true,
			'identifier' => true,
			'defaultValue' => true,
			'fieldset' => true,
			'inputType' => true,
			'type' => true,
			'hasIndex' => true,
			'required' => true,
			'translatable' => true,
			'columnSpan' => true,
			'systemOrder' => true,
			'active' => true,
			'_translations' => true,
			'_publicationData' => true,
			'customerGroupAccessSettings' => true,
			'customerGroupAssignments' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new Attribute();

		$this->assertSame(['column', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new Attribute();

		$entity->identifier = 'Test Identifier';
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->identifier = 'TestIdentifier';
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->identifier = 'Test-Identifier';
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->identifier = 'Test Identifier!@#$%';
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->identifier = 'UPPERCASE IDENTIFIER';
		$this->assertEquals('uPPERCASEIDENTIFIER', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new Attribute();

		$entity->set('identifier', 'Test Identifier');
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->set('identifier', 'TestIdentifier');
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->set('identifier', 'Test-Identifier');
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->set('identifier', 'Test Identifier!@#$%');
		$this->assertEquals('testIdentifier', $entity->identifier);

		$entity->set('identifier', 'UPPERCASE IDENTIFIER');
		$this->assertEquals('uPPERCASEIDENTIFIER', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_setScope()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testScopeCleaningViaPropertyAssignment(): void {
		$entity = new Attribute();

		$entity->scope = 'Test Scope';
		$this->assertEquals('TestScope', $entity->scope);

		$entity->scope = 'TestScope';
		$this->assertEquals('TestScope', $entity->scope);

		$entity->scope = 'Test-Scope';
		$this->assertEquals('TestScope', $entity->scope);

		$entity->scope = 'Test Scope!@#$%';
		$this->assertEquals('TestScope', $entity->scope);

		$entity->scope = 'UPPERCASE SCOPE';
		$this->assertEquals('UPPERCASESCOPE', $entity->scope);

		$entity->scope = null;
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_setScope()
	 */
	public function testScopeCleaningViaSetMethod(): void {
		$entity = new Attribute();

		$entity->set('scope', 'Test Scope');
		$this->assertEquals('TestScope', $entity->scope);

		$entity->set('scope', 'TestScope');
		$this->assertEquals('TestScope', $entity->scope);

		$entity->set('scope', 'Test-Scope');
		$this->assertEquals('TestScope', $entity->scope);

		$entity->set('scope', 'Test Scope!@#$%');
		$this->assertEquals('TestScope', $entity->scope);

		$entity->set('scope', 'UPPERCASE SCOPE');
		$this->assertEquals('UPPERCASESCOPE', $entity->scope);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('scope', null);
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_getColumn()
	 */
	public function testColumnVirtualProperty(): void {
		$entity = new Attribute(['columnSpan' => '4/12']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		$this->assertInstanceOf(ColumnInterface::class, $column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_getColumn()
	 */
	public function testColumnVirtualPropertyWithInvalidSpan(): void {
		$entity = new Attribute(['columnSpan' => 'invalid-span']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		// Should return the first (reset) column span when invalid
		$this->assertInstanceOf(ColumnInterface::class, $column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::defaultValues()
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\AttributesTable $table */
		$table = FactoryLocator::get('Table')->get('Attributes');
		$entity = $table->newDefaultEntity();

		// Test that the default fieldset is set
		$this->assertNotNull($entity->fieldset);
		$this->assertIsString($entity->fieldset);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'scope' => 'Test Scope',
			'title' => 'Test Attribute',
			'identifier' => 'Test Identifier',
			'defaultValue' => 'default_test_value',
			'fieldset' => 'basic',
			'inputType' => 'text',
			'type' => 'string',
			'hasIndex' => true,
			'required' => false,
			'translatable' => true,
			'columnSpan' => '6/12',
			'systemOrder' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new Attribute($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('TestScope', $entity->scope); // Should be cleaned by setter
		$this->assertEquals('Test Attribute', $entity->title);
		$this->assertEquals('testIdentifier', $entity->identifier); // Should be cleaned by setter
		$this->assertEquals('default_test_value', $entity->defaultValue);
		$this->assertEquals('basic', $entity->fieldset);
		$this->assertEquals('text', $entity->inputType);
		$this->assertEquals('string', $entity->type);
		$this->assertTrue($entity->hasIndex);
		$this->assertFalse($entity->required);
		$this->assertTrue($entity->translatable);
		$this->assertEquals('6/12', $entity->columnSpan);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
