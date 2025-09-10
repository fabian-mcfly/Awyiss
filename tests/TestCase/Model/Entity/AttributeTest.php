<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Attribute;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\ColumnInterface;
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::$_virtual
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVirtualFields(): void {
		$entity = new Attribute();

		$this->assertSame(['column', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_setIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new Attribute();

		$entity->identifier = 'Test Identifier';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'TestIdentifier';
		$this->assertEquals('testidentifier', $entity->identifier);

		$entity->identifier = 'Test-Identifier';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'Test Identifier!@#$%';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'UPPERCASE IDENTIFIER';
		$this->assertEquals('uppercase_identifier', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_setIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new Attribute();

		$entity->set('identifier', 'Test Identifier');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'TestIdentifier');
		$this->assertEquals('testidentifier', $entity->identifier);

		$entity->set('identifier', 'Test-Identifier');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'Test Identifier!@#$%');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'UPPERCASE IDENTIFIER');
		$this->assertEquals('uppercase_identifier', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_setScope()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testScopeCleaningViaPropertyAssignment(): void {
		$entity = new Attribute();

		$entity->scope = 'Test Scope';
		$this->assertEquals('test_scope', $entity->scope);

		$entity->scope = 'TestScope';
		$this->assertEquals('testscope', $entity->scope);

		$entity->scope = 'Test-Scope';
		$this->assertEquals('test_scope', $entity->scope);

		$entity->scope = 'Test Scope!@#$%';
		$this->assertEquals('test_scope', $entity->scope);

		$entity->scope = 'UPPERCASE SCOPE';
		$this->assertEquals('uppercase_scope', $entity->scope);

		$entity->scope = null;
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_setScope()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testScopeCleaningViaSetMethod(): void {
		$entity = new Attribute();

		$entity->set('scope', 'Test Scope');
		$this->assertEquals('test_scope', $entity->scope);

		$entity->set('scope', 'TestScope');
		$this->assertEquals('testscope', $entity->scope);

		$entity->set('scope', 'Test-Scope');
		$this->assertEquals('test_scope', $entity->scope);

		$entity->set('scope', 'Test Scope!@#$%');
		$this->assertEquals('test_scope', $entity->scope);

		$entity->set('scope', 'UPPERCASE SCOPE');
		$this->assertEquals('uppercase_scope', $entity->scope);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('scope', null);
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::_getColumn()
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'scope' => 'Test Scope',
			'title' => 'Test Attribute',
			'identifier' => 'Test Identifier',
			'default_value' => 'default_test_value',
			'fieldset' => 'basic',
			'input_type' => 'text',
			'type' => 'string',
			'has_index' => true,
			'required' => false,
			'translatable' => true,
			'column_span' => '6/12',
			'system_order' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new Attribute($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('test_scope', $entity->scope); // Should be cleaned by setter
		$this->assertEquals('Test Attribute', $entity->title);
		$this->assertEquals('test_identifier', $entity->identifier); // Should be cleaned by setter
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


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Attribute::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'has_index' => true,
			'input_type' => 'text',
			'default_value' => 'test_value',
			'column_span' => '4/12',
			'system_order' => 5,
		];

		$entity = new Attribute($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
