<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\GlobalContent;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\AwyissColumn;
use Cake\Datasource\FactoryLocator;


/**
 * GlobalContent Entity Test Case
 *
 * @see \Awyiss\Model\Entity\GlobalContent
 */
class GlobalContentTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get('GlobalContents');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new GlobalContent();

		$this->assertSame([
			'identifier' => true,
			'parentId' => true,
			'title' => true,
			'titleTag' => true,
			'subtitle' => true,
			'subtitleTag' => true,
			'text' => true,
			'link' => true,
			'globalContentTemplateId' => true,
			'columnWidth' => true,
			'columnIndent' => true,
			'columnLast' => true,
			'columnRtl' => true,
			'cssClass' => true,
			'css' => true,
			'data' => true,
			'formId' => true,
			'surveyId' => true,
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
	 * @see \Awyiss\Model\Entity\GlobalContent::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new GlobalContent();

		$this->assertSame(['column', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_setColumnLast()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testColumnLastCleaningViaPropertyAssignment(): void {
		$entity = new GlobalContent();

		$entity->columnLast = true;
		$this->assertTrue($entity->columnLast);

		$entity->columnLast = false;
		$this->assertFalse($entity->columnLast);

		$entity->columnLast = null;
		$this->assertFalse($entity->columnLast);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_setColumnLast()
	 */
	public function testColumnLastCleaningViaSetMethod(): void {
		$entity = new GlobalContent();

		$entity->set('columnLast', true);
		$this->assertTrue($entity->columnLast);

		$entity->set('columnLast', false);
		$this->assertFalse($entity->columnLast);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('columnLast', null);
		$this->assertFalse($entity->columnLast);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_setColumnRtl()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testColumnRtlCleaningViaPropertyAssignment(): void {
		$entity = new GlobalContent();

		$entity->columnRtl = true;
		$this->assertTrue($entity->columnRtl);

		$entity->columnRtl = false;
		$this->assertFalse($entity->columnRtl);

		$entity->columnRtl = null;
		$this->assertFalse($entity->columnRtl);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_setColumnRtl()
	 */
	public function testColumnRtlCleaningViaSetMethod(): void {
		$entity = new GlobalContent();

		$entity->set('columnRtl', true);
		$this->assertTrue($entity->columnRtl);

		$entity->set('columnRtl', false);
		$this->assertFalse($entity->columnRtl);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('columnRtl', null);
		$this->assertFalse($entity->columnRtl);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_setData()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testDataCleaningViaPropertyAssignment(): void {
		$entity = new GlobalContent();

		$entity->data = ['key' => 'value'];
		$this->assertEquals(['key' => 'value'], $entity->data);

		$entity->data = [];
		$this->assertNull($entity->data);

		$entity->data = null;
		$this->assertNull($entity->data);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_setData()
	 */
	public function testDataCleaningViaSetMethod(): void {
		$entity = new GlobalContent();

		$entity->set('data', ['key' => 'value']);
		$this->assertEquals(['key' => 'value'], $entity->data);

		$entity->set('data', []);
		$this->assertNull($entity->data);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('data', null);
		$this->assertNull($entity->data);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_getColumn()
	 */
	public function testColumnVirtualProperty(): void {
		$entity = new GlobalContent(['columnWidth' => '2/5', 'columnIndent' => '3/5']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('width', $column);
		$this->assertArrayHasKey('indent', $column);
		$this->assertInstanceOf(AwyissColumn::class, $column['width']);
		$this->assertSame('2/5', $column['width']->getFraction());
		$this->assertInstanceOf(AwyissColumn::class, $column['indent']);
		$this->assertSame('3/5', $column['indent']->getFraction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_getColumn()
	 */
	public function testColumnVirtualPropertyWithInvalidWidthAndIndent(): void {
		$entity = new GlobalContent(['columnWidth' => 'invalid-width', 'columnIndent' => 'invalid-indent']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('width', $column);
		$this->assertArrayHasKey('indent', $column);
		$this->assertInstanceOf(AwyissColumn::class, $column['width']);
		$this->assertSame('1/1', $column['width']->getFraction());
		$this->assertNull($column['indent']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_getLabel()
	 */
	public function testLabelVirtualPropertyWithTitle(): void {
		$entity = new GlobalContent(['title' => 'Test GlobalContent Title']);

		$label = $entity->label;

		$this->assertEquals('Test GlobalContent Title', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_getLabel()
	 */
	public function testLabelVirtualPropertyWithSubtitle(): void {
		$entity = new GlobalContent(['subtitle' => 'Test GlobalContent Subtitle']);

		$label = $entity->label;

		$this->assertEquals('Test GlobalContent Subtitle', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_getLabel()
	 */
	public function testLabelVirtualPropertyWithText(): void {
		$entity = new GlobalContent(['text' => '<p>Test GlobalContent Text</p>']);

		$label = $entity->label;

		$this->assertEquals('Test GlobalContent Text', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_getLabel()
	 */
	public function testLabelVirtualPropertyWithCssClass(): void {
		$entity = new GlobalContent(['cssClass' => 'test-class']);

		$label = $entity->label;

		$this->assertEquals('test-class', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_getLabel()
	 */
	public function testLabelVirtualPropertyFallbackToDefault(): void {
		$entity = new GlobalContent();
		$entity->setSource('GlobalContents');

		$label = $entity->label;

		$this->assertEquals('GlobalContent', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_getLabel()
	 */
	public function testLabelVirtualPropertyWithInactiveGlobalContent(): void {
		$entity = new GlobalContent(['title' => 'Test GlobalContent', 'active' => false]);
		$entity->setSource('GlobalContents');

		$label = $entity->label;

		$this->assertSame('GlobalContents::inactive Test GlobalContent', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::_getLabel()
	 */
	public function testLabelVirtualPropertyPriorityOrder(): void {
		$entity = new GlobalContent([
			'title' => 'GlobalContent Title',
			'subtitle' => 'GlobalContent Subtitle',
			'text' => 'GlobalContent Text',
			'cssClass' => 'globalContent-class',
		]);

		$label = $entity->label;

		$this->assertEquals('GlobalContent Title', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::getChildren()
	 */
	public function testGetChildren(): void {
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get('GlobalContents');
		/** @var \Awyiss\Model\Entity\GlobalContent $parent */
		$parent = $table->get(4);

		$children = $parent->getChildren();

		$this->assertNotNull($children);
		$this->assertCount(1, $children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::getNestedChildren()
	 */
	public function testGetNestedChildren(): void {
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get('GlobalContents');
		/** @var \Awyiss\Model\Entity\GlobalContent $parent */
		$parent = $table->get(4);

		$nestedChildren = $parent->getNestedChildren();

		$this->assertNotNull($nestedChildren);
		$this->assertCount(2, $nestedChildren);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::getParent()
	 */
	public function testGetParent(): void {
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get('GlobalContents');
		/** @var \Awyiss\Model\Entity\GlobalContent $child */
		$child = $table->get(7);

		$parent = $child->getParent();

		$this->assertNotNull($parent);
		$this->assertEquals(4, $parent->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::getParent()
	 */
	public function testGetParentWithNoParent(): void {
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get('GlobalContents');
		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $table->get(1);

		$parent = $globalContent->getParent();

		$this->assertNull($parent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::getParents()
	 */
	public function testGetParents(): void {
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get('GlobalContents');
		/** @var \Awyiss\Model\Entity\GlobalContent $deepChild */
		$deepChild = $table->get(15);

		$parents = $deepChild->getParents();

		$this->assertNotNull($parents);
		$this->assertCount(2, $parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent::getParents()
	 */
	public function testGetParentsWithNoParents(): void {
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get('GlobalContents');
		/** @var \Awyiss\Model\Entity\GlobalContent $globalContent */
		$globalContent = $table->get(1);

		$parents = $globalContent->getParents();

		$this->assertEmpty($parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContent
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 123,
			'parentId' => 456,
			'title' => 'Test GlobalContent',
			'titleTag' => 'h2',
			'subtitle' => 'Test Subtitle',
			'subtitleTag' => 'h3',
			'text' => 'Test content',
			'link' => '/test-link',
			'columnWidth' => '6/12',
			'columnIndent' => '1/12',
			'columnLast' => true,
			'columnRtl' => false,
			'cssClass' => 'test-class',
			'data' => ['key' => 'value'],
			'formId' => 789,
			'surveyId' => 101,
			'systemOrder' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new GlobalContent($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('testGlobalContent', $entity->identifier);
		$this->assertEquals(123, $entity->globalContentTemplateId);
		$this->assertEquals(456, $entity->parentId);
		$this->assertEquals('Test GlobalContent', $entity->title);
		$this->assertEquals('h2', $entity->titleTag);
		$this->assertEquals('Test Subtitle', $entity->subtitle);
		$this->assertEquals('h3', $entity->subtitleTag);
		$this->assertEquals('Test content', $entity->text);
		$this->assertEquals('/test-link', $entity->link);
		$this->assertEquals('6/12', $entity->columnWidth);
		$this->assertEquals('1/12', $entity->columnIndent);
		$this->assertTrue($entity->columnLast);
		$this->assertFalse($entity->columnRtl);
		$this->assertEquals('test-class', $entity->cssClass);
		$this->assertEquals(['key' => 'value'], $entity->data);
		$this->assertEquals(789, $entity->formId);
		$this->assertEquals(101, $entity->surveyId);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
