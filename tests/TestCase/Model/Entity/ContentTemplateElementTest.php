<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\ContentTemplateElement;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\ColumnInterface;
use Cake\Datasource\FactoryLocator;


/**
 * ContentTemplateElement Entity Test Case
 *
 * @see \Awyiss\Model\Entity\ContentTemplateElement
 */
class ContentTemplateElementTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\ContentTemplateElementsTable $table */
		$table = FactoryLocator::get('Table')->get('ContentTemplateElements');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new ContentTemplateElement();

		$this->assertSame([
			'contentTemplateId' => true,
			'identifier' => true,
			'title' => true,
			'fieldset' => true,
			'columnSpan' => true,
			'required' => true,
			'systemOrder' => true,
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
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new ContentTemplateElement();

		$this->assertSame(['column', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new ContentTemplateElement();

		$entity->identifier = 'testIdentifier';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'TestIdentifier';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'testHTMLElement';
		$this->assertEquals('test_h_t_m_l_element', $entity->identifier);

		// Test already underscored string remains unchanged
		$entity->identifier = 'already_underscored';
		$this->assertEquals('already_underscored', $entity->identifier);

		// Test null value
		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new ContentTemplateElement();

		$entity->set('identifier', 'testIdentifier');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'TestIdentifier');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'testHTMLElement');
		$this->assertEquals('test_h_t_m_l_element', $entity->identifier);

		// Test already underscored string remains unchanged
		$entity->set('identifier', 'already_underscored');
		$this->assertEquals('already_underscored', $entity->identifier);

		// Test null value
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::_getColumn()
	 */
	public function testColumnVirtualProperty(): void {
		$entity = new ContentTemplateElement(['columnSpan' => '4/12']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		$this->assertInstanceOf(ColumnInterface::class, $column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::_getColumn()
	 */
	public function testColumnVirtualPropertyWithInvalidSpan(): void {
		$entity = new ContentTemplateElement(['columnSpan' => 'invalid-span']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		// Should return the first (reset) column span when invalid
		$this->assertInstanceOf(ColumnInterface::class, $column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateElement
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'content_template_id' => 456,
			'identifier' => 'testElement',
			'title' => 'Test Element',
			'fieldset' => 'Test Fieldset',
			'column_span' => '6/12',
			'required' => true,
			'system_order' => 15,
		];

		$entity = new ContentTemplateElement($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(456, $entity->contentTemplateId);
		$this->assertEquals('test_element', $entity->identifier); // Should be cleaned by setter
		$this->assertEquals('Test Element', $entity->title);
		$this->assertEquals('Test Fieldset', $entity->fieldset);
		$this->assertEquals('6/12', $entity->columnSpan);
		$this->assertTrue($entity->required);
		$this->assertEquals(15, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'content_template_id' => 456,
			'column_span' => 'col-8',
			'system_order' => 15,
		];

		$entity = new ContentTemplateElement($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
