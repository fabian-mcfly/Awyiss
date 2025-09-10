<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Widget;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\AwyissColumn;
use Cake\Datasource\FactoryLocator;


/**
 * Widget Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Widget
 */
class WidgetTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\WidgetsTable $table */
		$table = FactoryLocator::get('Table')->get('Widgets');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new Widget();

		$this->assertSame([
			'identifier' => true,
			'parentId' => true,
			'title' => true,
			'titleTag' => true,
			'subtitle' => true,
			'subtitleTag' => true,
			'text' => true,
			'link' => true,
			'widgetTemplateId' => true,
			'columnWidth' => true,
			'columnIndent' => true,
			'columnLast' => true,
			'columnRtl' => true,
			'cssClass' => true,
			'data' => true,
			'formId' => true,
			'surveyId' => true,
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
	 * @see \Awyiss\Model\Entity\Widget::$_virtual
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVirtualFields(): void {
		$entity = new Widget();

		$this->assertSame(['column', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_setColumnLast()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testColumnLastCleaningViaPropertyAssignment(): void {
		$entity = new Widget();

		$entity->columnLast = true;
		$this->assertTrue($entity->columnLast);

		$entity->columnLast = false;
		$this->assertFalse($entity->columnLast);

		$entity->columnLast = null;
		$this->assertFalse($entity->columnLast);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_setColumnLast()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testColumnLastCleaningViaSetMethod(): void {
		$entity = new Widget();

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
	 * @see \Awyiss\Model\Entity\Widget::_setColumnRtl()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testColumnRtlCleaningViaPropertyAssignment(): void {
		$entity = new Widget();

		$entity->columnRtl = true;
		$this->assertTrue($entity->columnRtl);

		$entity->columnRtl = false;
		$this->assertFalse($entity->columnRtl);

		$entity->columnRtl = null;
		$this->assertFalse($entity->columnRtl);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_setColumnRtl()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testColumnRtlCleaningViaSetMethod(): void {
		$entity = new Widget();

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
	 * @see \Awyiss\Model\Entity\Widget::_setData()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testDataCleaningViaPropertyAssignment(): void {
		$entity = new Widget();

		$entity->data = ['key' => 'value'];
		$this->assertEquals(['key' => 'value'], $entity->data);

		$entity->data = [];
		$this->assertNull($entity->data);

		$entity->data = null;
		$this->assertNull($entity->data);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_setData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDataCleaningViaSetMethod(): void {
		$entity = new Widget();

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
	 * @see \Awyiss\Model\Entity\Widget::_getColumn()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testColumnVirtualProperty(): void {
		$entity = new Widget(['columnWidth' => '2/5', 'columnIndent' => '3/5']);

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
	 * @see \Awyiss\Model\Entity\Widget::_getColumn()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testColumnVirtualPropertyWithInvalidWidthAndIndent(): void {
		$entity = new Widget(['columnWidth' => 'invalid-width', 'columnIndent' => 'invalid-indent']);

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
	 * @see \Awyiss\Model\Entity\Widget::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyWithTitle(): void {
		$entity = new Widget(['title' => 'Test Widget Title']);

		$label = $entity->label;

		$this->assertEquals('Test Widget Title', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyWithSubtitle(): void {
		$entity = new Widget(['subtitle' => 'Test Widget Subtitle']);

		$label = $entity->label;

		$this->assertEquals('Test Widget Subtitle', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyWithText(): void {
		$entity = new Widget(['text' => '<p>Test Widget Text</p>']);

		$label = $entity->label;

		$this->assertEquals('Test Widget Text', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyWithCssClass(): void {
		$entity = new Widget(['cssClass' => 'test-class']);

		$label = $entity->label;

		$this->assertEquals('test-class', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyFallbackToDefault(): void {
		$entity = new Widget();
		$entity->setSource('Widgets');

		$label = $entity->label;

		$this->assertEquals('Widget', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyWithInactiveWidget(): void {
		$entity = new Widget(['title' => 'Test Widget', 'active' => false]);
		$entity->setSource('Widgets');

		$label = $entity->label;

		$this->assertSame('widgets::inactive Test Widget', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyPriorityOrder(): void {
		$entity = new Widget([
			'title' => 'Widget Title',
			'subtitle' => 'Widget Subtitle',
			'text' => 'Widget Text',
			'cssClass' => 'widget-class',
		]);

		$label = $entity->label;

		$this->assertEquals('Widget Title', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::getChildren()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetChildren(): void {
		/** @var \Awyiss\Model\Table\WidgetsTable $table */
		$table = FactoryLocator::get('Table')->get('Widgets');
		/** @var \Awyiss\Model\Entity\Widget $parent */
		$parent = $table->get(4);

		$children = $parent->getChildren();

		$this->assertNotNull($children);
		$this->assertCount(1, $children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::getNestedChildren()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNestedChildren(): void {
		/** @var \Awyiss\Model\Table\WidgetsTable $table */
		$table = FactoryLocator::get('Table')->get('Widgets');
		/** @var \Awyiss\Model\Entity\Widget $parent */
		$parent = $table->get(4);

		$nestedChildren = $parent->getNestedChildren();

		$this->assertNotNull($nestedChildren);
		$this->assertCount(2, $nestedChildren);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::getParent()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetParent(): void {
		/** @var \Awyiss\Model\Table\WidgetsTable $table */
		$table = FactoryLocator::get('Table')->get('Widgets');
		/** @var \Awyiss\Model\Entity\Widget $child */
		$child = $table->get(7);

		$parent = $child->getParent();

		$this->assertNotNull($parent);
		$this->assertEquals(4, $parent->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::getParent()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetParentWithNoParent(): void {
		/** @var \Awyiss\Model\Table\WidgetsTable $table */
		$table = FactoryLocator::get('Table')->get('Widgets');
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $table->get(1);

		$parent = $widget->getParent();

		$this->assertNull($parent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::getParents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetParents(): void {
		/** @var \Awyiss\Model\Table\WidgetsTable $table */
		$table = FactoryLocator::get('Table')->get('Widgets');
		/** @var \Awyiss\Model\Entity\Widget $deepChild */
		$deepChild = $table->get(15);

		$parents = $deepChild->getParents();

		$this->assertNotNull($parents);
		$this->assertCount(2, $parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::getParents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetParentsWithNoParents(): void {
		/** @var \Awyiss\Model\Table\WidgetsTable $table */
		$table = FactoryLocator::get('Table')->get('Widgets');
		/** @var \Awyiss\Model\Entity\Widget $widget */
		$widget = $table->get(1);

		$parents = $widget->getParents();

		$this->assertEmpty($parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'identifier' => 'test_widget',
			'widget_template_id' => 123,
			'parent_id' => 456,
			'title' => 'Test Widget',
			'title_tag' => 'h2',
			'subtitle' => 'Test Subtitle',
			'subtitle_tag' => 'h3',
			'text' => 'Test content',
			'link' => '/test-link',
			'column_width' => '6/12',
			'column_indent' => '1/12',
			'column_last' => true,
			'column_rtl' => false,
			'css_class' => 'test-class',
			'data' => ['key' => 'value'],
			'form_id' => 789,
			'survey_id' => 101,
			'system_order' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new Widget($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('test_widget', $entity->identifier);
		$this->assertEquals(123, $entity->widgetTemplateId);
		$this->assertEquals(456, $entity->parentId);
		$this->assertEquals('Test Widget', $entity->title);
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


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Widget::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'parent_id' => 456,
			'widget_template_id' => 123,
			'title_tag' => 'h2',
			'subtitle_tag' => 'h3',
			'css_class' => 'test-class',
			'column_width' => '6/12',
			'column_indent' => '1/12',
			'column_last' => true,
			'column_rtl' => false,
			'form_id' => 789,
			'survey_id' => 101,
			'system_order' => 10,
		];

		$entity = new Widget($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
