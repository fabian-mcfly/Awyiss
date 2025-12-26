<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Content;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\ColumnInterface;
use Cake\Datasource\FactoryLocator;


/**
 * Content Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Content
 */
class ContentTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = FactoryLocator::get('Table')->get('Contents');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Content();

		$this->assertSame([
			'pageId' => true,
			'parentId' => true,
			'title' => true,
			'titleTag' => true,
			'subtitle' => true,
			'subtitleTag' => true,
			'text' => true,
			'link' => true,
			'contentAreaId' => true,
			'contentTemplateId' => true,
			'columnWidth' => true,
			'columnIndent' => true,
			'columnLast' => true,
			'columnRtl' => true,
			'cssClass' => true,
			'css' => true,
			'duplicateOf' => true,
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
	 * @see \Awyiss\Model\Entity\Content::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new Content();

		$this->assertSame(['column', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_setColumnLast()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testColumnLastCleaningViaPropertyAssignment(): void {
		$entity = new Content();

		$entity->columnLast = true;
		$this->assertTrue($entity->columnLast);

		$entity->columnLast = false;
		$this->assertFalse($entity->columnLast);

		$entity->columnLast = null;
		$this->assertFalse($entity->columnLast);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_setColumnLast()
	 */
	public function testColumnLastCleaningViaSetMethod(): void {
		$entity = new Content();

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
	 * @see \Awyiss\Model\Entity\Content::_setColumnRtl()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testColumnRtlCleaningViaPropertyAssignment(): void {
		$entity = new Content();

		$entity->columnRtl = true;
		$this->assertTrue($entity->columnRtl);

		$entity->columnRtl = false;
		$this->assertFalse($entity->columnRtl);

		$entity->columnRtl = null;
		$this->assertFalse($entity->columnRtl);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_setColumnRtl()
	 */
	public function testColumnRtlCleaningViaSetMethod(): void {
		$entity = new Content();

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
	 * @see \Awyiss\Model\Entity\Content::_setData()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testDataCleaningViaPropertyAssignment(): void {
		$entity = new Content();

		$entity->data = ['key' => 'value'];
		$this->assertEquals(['key' => 'value'], $entity->data);

		$entity->data = [];
		$this->assertNull($entity->data);

		$entity->data = null;
		$this->assertNull($entity->data);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_setData()
	 */
	public function testDataCleaningViaSetMethod(): void {
		$entity = new Content();

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
	 * @see \Awyiss\Model\Entity\Content::_getColumn()
	 */
	public function testColumnVirtualProperty(): void {
		$entity = new Content(['columnWidth' => '2/5', 'columnIndent' => '3/5']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('width', $column);
		$this->assertArrayHasKey('indent', $column);
		$this->assertInstanceOf(ColumnInterface::class, $column['width']);
		$this->assertInstanceOf(ColumnInterface::class, $column['indent']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_getColumn()
	 */
	public function testColumnVirtualPropertyWithInvalidWidthAndIndent(): void {
		$entity = new Content(['columnWidth' => 'invalid-width', 'columnIndent' => 'invalid-indent']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('width', $column);
		$this->assertArrayHasKey('indent', $column);
		$this->assertInstanceOf(ColumnInterface::class, $column['width']);
		$this->assertNull($column['indent']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_getLabel()
	 */
	public function testLabelVirtualPropertyWithTitle(): void {
		$entity = new Content(['title' => 'Test Content Title']);

		$label = $entity->label;

		$this->assertEquals('Test Content Title', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_getLabel()
	 */
	public function testLabelVirtualPropertyWithSubtitle(): void {
		$entity = new Content(['subtitle' => 'Test Content Subtitle']);

		$label = $entity->label;

		$this->assertEquals('Test Content Subtitle', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_getLabel()
	 */
	public function testLabelVirtualPropertyWithText(): void {
		$entity = new Content(['text' => '<p>Test Content Text</p>']);

		$label = $entity->label;

		$this->assertEquals('Test Content Text', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_getLabel()
	 */
	public function testLabelVirtualPropertyWithCssClass(): void {
		$entity = new Content(['cssClass' => 'test-class']);

		$label = $entity->label;

		$this->assertEquals('test-class', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_getLabel()
	 */
	public function testLabelVirtualPropertyFallbackToDefault(): void {
		$entity = new Content();
		$entity->setSource('Contents');

		$label = $entity->label;

		$this->assertEquals('Content', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::_getLabel()
	 */
	public function testLabelVirtualPropertyWithInactiveContent(): void {
		$entity = new Content(['title' => 'Test Content', 'active' => false]);
		$entity->setSource('Contents');

		$label = $entity->label;

		$this->assertSame('contents::inactive Test Content', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::getChildren()
	 */
	public function testGetChildren(): void {
		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = FactoryLocator::get('Table')->get('Contents');
		/** @var \Awyiss\Model\Entity\Content $parent */
		$parent = $table->get(1);

		$children = $parent->getChildren();

		$this->assertNotNull($children);
		$this->assertCount(4, $children); // ID 1 has children: 9, 10, 11, 14
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::getNestedChildren()
	 */
	public function testGetNestedChildren(): void {
		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = FactoryLocator::get('Table')->get('Contents');
		/** @var \Awyiss\Model\Entity\Content $parent */
		$parent = $table->get(1);

		$nestedChildren = $parent->getNestedChildren();

		$this->assertNotNull($nestedChildren);
		$this->assertCount(7, $nestedChildren);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::getParent()
	 */
	public function testGetParent(): void {
		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = FactoryLocator::get('Table')->get('Contents');
		/** @var \Awyiss\Model\Entity\Content $child */
		$child = $table->get(9); // Content ID 9 has parent_id = 1

		$parent = $child->getParent();

		$this->assertNotNull($parent);
		$this->assertEquals(1, $parent->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::getParent()
	 */
	public function testGetParentWithNoParent(): void {
		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = FactoryLocator::get('Table')->get('Contents');
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $table->get(1); // Content ID 1 has parent_id = null

		$parent = $content->getParent();

		$this->assertNull($parent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::getParents()
	 */
	public function testGetParents(): void {
		/** @var \Awyiss\Model\Table\ContentsTable $table */
		$table = FactoryLocator::get('Table')->get('Contents');
		/** @var \Awyiss\Model\Entity\Content $deepChild */
		$deepChild = $table->get(12); // Content ID 12 has parent_id = 10, and 10 has parent_id = 1

		$parents = $deepChild->getParents();

		$this->assertNotNull($parents);
		$this->assertCount(2, $parents); // Parents: 10 and 1
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'page_id' => 123,
			'content_area_id' => 456,
			'content_template_id' => 789,
			'parent_id' => 101,
			'title' => 'Test Content',
			'title_tag' => 'h2',
			'subtitle' => 'Test Subtitle',
			'subtitle_tag' => 'h3',
			'text' => 'Test content text',
			'link' => '/test-link',
			'column_width' => '6/12',
			'column_indent' => '1/12',
			'column_last' => true,
			'column_rtl' => false,
			'css_class' => 'test-class',
			'duplicate_of' => 112,
			'data' => ['key' => 'value'],
			'form_id' => 113,
			'survey_id' => 114,
			'system_order' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new Content($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->pageId);
		$this->assertEquals(456, $entity->contentAreaId);
		$this->assertEquals(789, $entity->contentTemplateId);
		$this->assertEquals(101, $entity->parentId);
		$this->assertEquals('Test Content', $entity->title);
		$this->assertEquals('h2', $entity->titleTag);
		$this->assertEquals('Test Subtitle', $entity->subtitle);
		$this->assertEquals('h3', $entity->subtitleTag);
		$this->assertEquals('Test content text', $entity->text);
		$this->assertEquals('/test-link', $entity->link);
		$this->assertEquals('6/12', $entity->columnWidth);
		$this->assertEquals('1/12', $entity->columnIndent);
		$this->assertTrue($entity->columnLast);
		$this->assertFalse($entity->columnRtl);
		$this->assertEquals('test-class', $entity->cssClass);
		$this->assertEquals(112, $entity->duplicateOf);
		$this->assertEquals(['key' => 'value'], $entity->data);
		$this->assertEquals(113, $entity->formId);
		$this->assertEquals(114, $entity->surveyId);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Content::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'page_id' => 123,
			'parent_id' => 456,
			'content_area_id' => 789,
			'content_template_id' => 101,
			'title_tag' => 'h2',
			'subtitle_tag' => 'h3',
			'css_class' => 'test-class',
			'column_width' => '6/12',
			'column_indent' => '1/12',
			'column_last' => true,
			'column_rtl' => false,
			'duplicate_of' => 112,
			'form_id' => 113,
			'survey_id' => 114,
			'system_order' => 10,
		];

		$entity = new Content($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
