<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\GlobalContentTemplateElement;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\BootstrapColumn;
use Cake\Datasource\FactoryLocator;


/**
 * GlobalContentTemplateElement Entity Test Case
 *
 * @see \Awyiss\Model\Entity\GlobalContentTemplateElement
 */
class GlobalContentTemplateElementTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContentTemplateElement::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\GlobalContentTemplateElementsTable $table */
		$table = FactoryLocator::get('Table')->get('GlobalContentTemplateElements');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContentTemplateElement::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new GlobalContentTemplateElement();

		$this->assertSame([
			'globalContentTemplateId' => true,
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
	 * @see \Awyiss\Model\Entity\GlobalContentTemplateElement::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new GlobalContentTemplateElement();

		$this->assertSame(['column', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContentTemplateElement::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new GlobalContentTemplateElement();

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
	 * @see \Awyiss\Model\Entity\GlobalContentTemplateElement::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new GlobalContentTemplateElement();

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
	 * @see \Awyiss\Model\Entity\GlobalContentTemplateElement::_getColumn()
	 */
	public function testColumnVirtualProperty(): void {
		$entity = new GlobalContentTemplateElement(['columnSpan' => '4/12']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		$this->assertInstanceOf(BootstrapColumn::class, $column['span']);
		$this->assertEquals('4/12', $column['span']->getFraction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContentTemplateElement::_getColumn()
	 */
	public function testColumnVirtualPropertyWithInvalidSpan(): void {
		$entity = new GlobalContentTemplateElement(['columnSpan' => 'invalid-span']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		// Should return the first (reset) column span when invalid
		$this->assertInstanceOf(BootstrapColumn::class, $column['span']);
		$this->assertEquals('12/12', $column['span']->getFraction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContentTemplateElement::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'global_content_template_id' => 456,
			'column_span' => 'col-8',
			'system_order' => 15,
		];

		$entity = new GlobalContentTemplateElement($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
