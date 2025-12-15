<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\FormElement;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * FormElement Entity Test Case
 *
 * @see \Awyiss\Model\Entity\FormElement
 */
class FormElementTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new FormElement();

		$this->assertSame([
			'formId' => true,
			'parentId' => true,
			'type' => true,
			'identifier' => true,
			'title' => true,
			'titleEmail' => true,
			'placeholder' => true,
			'text' => true,
			'options' => true,
			'columnWidth' => true,
			'columnIndent' => true,
			'columnLast' => true,
			'columnRtl' => true,
			'cssClass' => true,
			'required' => true,
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
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'form_id' => 1,
			'parent_id' => null,
			'type' => 'fieldset',
			'identifier' => 'persoenliche_daten',
			'title' => 'Persönliche Daten',
			'title_email' => null,
			'placeholder' => null,
			'text' => null,
			'options' => null,
			'column_width' => '1/1',
			'column_indent' => null,
			'column_last' => false,
			'column_rtl' => false,
			'css_class' => null,
			'required' => false,
			'system_order' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = new FormElement($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(1, $entity->formId);
		$this->assertNull($entity->parentId);
		$this->assertEquals('fieldset', $entity->type);
		$this->assertEquals('persoenliche_daten', $entity->identifier);
		$this->assertEquals('Persönliche Daten', $entity->title);
		$this->assertNull($entity->titleEmail);
		$this->assertNull($entity->placeholder);
		$this->assertNull($entity->text);
		$this->assertNull($entity->options);
		$this->assertEquals('1/1', $entity->columnWidth);
		$this->assertNull($entity->columnIndent);
		$this->assertFalse($entity->columnLast);
		$this->assertFalse($entity->columnRtl);
		$this->assertNull($entity->cssClass);
		$this->assertFalse($entity->required);
		$this->assertEquals(1, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testEntityConstructionWithTextFormElement(): void {
		$properties = [
			'id' => 2,
			'form_id' => 1,
			'parent_id' => 1,
			'type' => 'text',
			'identifier' => 'vorname',
			'title' => 'Vorname',
			'title_email' => null,
			'placeholder' => null,
			'text' => null,
			'options' => null,
			'column_width' => '1/2',
			'column_indent' => null,
			'column_last' => false,
			'column_rtl' => false,
			'css_class' => null,
			'required' => true,
			'system_order' => 2,
			'active' => true,
			'deleted' => false,
		];

		$entity = new FormElement($properties);

		$this->assertEquals(2, $entity->id);
		$this->assertEquals(1, $entity->formId);
		$this->assertEquals(1, $entity->parentId);
		$this->assertEquals('text', $entity->type);
		$this->assertEquals('vorname', $entity->identifier);
		$this->assertEquals('Vorname', $entity->title);
		$this->assertEquals('1/2', $entity->columnWidth);
		$this->assertTrue($entity->required);
		$this->assertEquals(2, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testEntityConstructionWithSelectFormElement(): void {
		$properties = [
			'id' => 4,
			'form_id' => 1,
			'parent_id' => 1,
			'type' => 'select',
			'identifier' => 'anrede',
			'title' => 'Anrede',
			'options' => [
				['key' => null, 'value' => null, '_translations' => ['de' => ['key' => '', 'value' => ''], 'es' => ['key' => '', 'value' => '']]],
				['key' => null, 'value' => null, '_translations' => ['de' => ['key' => '', 'value' => 'Frau'], 'es' => ['key' => '', 'value' => '']]],
				['key' => null, 'value' => null, '_translations' => ['de' => ['key' => '', 'value' => 'Herr'], 'es' => ['key' => '', 'value' => 'Senor']]],
			],
			'column_width' => '1/2',
			'column_last' => true,
			'required' => false,
			'system_order' => 1,
			'active' => true,
		];

		$entity = new FormElement($properties);

		$this->assertEquals(4, $entity->id);
		$this->assertEquals('select', $entity->type);
		$this->assertEquals('anrede', $entity->identifier);
		$this->assertEquals('Anrede', $entity->title);
		$this->assertIsArray($entity->options);
		$this->assertCount(3, $entity->options);
		$this->assertEquals('Frau', $entity->options[1]['_translations']['de']['value']);
		$this->assertEquals('Herr', $entity->options[2]['_translations']['de']['value']);
		$this->assertEquals('Senor', $entity->options[2]['_translations']['es']['value']);
		$this->assertTrue($entity->columnLast);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testEntityConstructionWithTextareaFormElement(): void {
		$properties = [
			'id' => 7,
			'form_id' => 1,
			'parent_id' => null,
			'type' => 'textarea',
			'identifier' => 'nachricht',
			'title' => 'Nachricht',
			'text' => '<p>Test</p>',
			'column_width' => '1/1',
			'required' => true,
			'system_order' => 2,
			'active' => true,
		];

		$entity = new FormElement($properties);

		$this->assertEquals(7, $entity->id);
		$this->assertEquals('textarea', $entity->type);
		$this->assertEquals('nachricht', $entity->identifier);
		$this->assertEquals('Nachricht', $entity->title);
		$this->assertEquals('<p>Test</p>', $entity->text);
		$this->assertTrue($entity->required);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testEntityConstructionWithFileFormElement(): void {
		$properties = [
			'id' => 54,
			'form_id' => 1,
			'parent_id' => 1,
			'type' => 'file',
			'identifier' => 'dateiupload',
			'title' => 'Dateiupload',
			'column_width' => '1/1',
			'required' => false,
			'system_order' => 13,
			'active' => true,
		];

		$entity = new FormElement($properties);

		$this->assertEquals(54, $entity->id);
		$this->assertEquals('file', $entity->type);
		$this->assertEquals('dateiupload', $entity->identifier);
		$this->assertEquals('Dateiupload', $entity->title);
		$this->assertEquals(13, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testEntityConstructionWithFreeTextFormElement(): void {
		$properties = [
			'id' => 56,
			'form_id' => 2,
			'parent_id' => null,
			'type' => 'free_text',
			'identifier' => null,
			'title' => null,
			'text' => '<p>Form element with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
			'column_width' => '4/5',
			'required' => false,
			'system_order' => 1,
			'active' => true,
		];

		$entity = new FormElement($properties);

		$this->assertEquals(56, $entity->id);
		$this->assertEquals('free_text', $entity->type);
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->title);
		$this->assertStringContainsString('Form element with inline img tag', $entity->text);
		$this->assertStringContainsString('awyiss-responsive-image', $entity->text);
		$this->assertEquals('4/5', $entity->columnWidth);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testEntityConstructionWithInactiveFormElement(): void {
		$properties = [
			'id' => 57,
			'form_id' => 1,
			'parent_id' => null,
			'type' => 'free_text',
			'active' => false,
			'system_order' => 1,
		];

		$entity = new FormElement($properties);

		$this->assertEquals(57, $entity->id);
		$this->assertEquals('free_text', $entity->type);
		$this->assertFalse($entity->active);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'form_id' => 1,
			'parent_id' => 1,
			'title_email' => 'Email Title',
			'column_width' => '1/2',
			'column_indent' => '1/12',
			'column_last' => true,
			'column_rtl' => false,
			'css_class' => 'test-class',
			'system_order' => 5,
		];

		$entity = new FormElement($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testGetChildrenWithTestData(): void {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');
		/** @var \Awyiss\Model\Entity\FormElement $parent */
		$parent = $table->get(1); // fieldset with children

		$children = $parent->getChildren();

		$this->assertNotNull($children);
		$this->assertGreaterThan(0, $children->count());

		// Should contain the expected child elements from seed data
		$childIds = $children->extract('id')->toArray();
		$this->assertSame([
			4,
			2,
			3,
			5,
			6,
			47,
			48,
			49,
			50,
			51,
			52,
			53,
			54,
			55,
		], $childIds);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testGetChildrenWithNoChildren(): void {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');
		/** @var \Awyiss\Model\Entity\FormElement $element */
		$element = $table->get(2); // text element (vorname) - should have no children

		$children = $element->getChildren();

		$this->assertNotNull($children);
		$this->assertEquals(0, $children->count());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testGetParentWithTestData(): void {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');
		/** @var \Awyiss\Model\Entity\FormElement $child */
		$child = $table->get(2); // vorname element

		$parent = $child->getParent();

		$this->assertNotNull($parent);
		$this->assertEquals(1, $parent->id); // should be the fieldset
		$this->assertEquals('fieldset', $parent->type);
		$this->assertEquals('persoenliche_daten', $parent->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement
	 */
	public function testGetParentWithNoParent(): void {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');
		/** @var \Awyiss\Model\Entity\FormElement $element */
		$element = $table->get(1); // fieldset - should have no parent

		$parent = $element->getParent();

		$this->assertNull($parent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithFreeTextBasic(): void {
		$entity = new FormElement([
			'type' => 'free_text',
			'text' => '<p>Simple text content</p>',
		]);

		$label = $entity->label;

		$this->assertEquals('Simple text content', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithFreeTextWidgetTags(): void {
		$entity = new FormElement([
			'type' => 'free_text',
			'text' => '<p>Content with <widget data-identifier="test-widget">Widget Content</widget> inside</p>',
		]);

		$label = $entity->label;

		$this->assertEquals('Content with Widget: test-widget inside', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithFreeTextHtmlEntities(): void {
		$entity = new FormElement([
			'type' => 'free_text',
			'text' => '<p>Text with&nbsp;non-breaking spaces<br>and line breaks</p>',
		]);

		$label = $entity->label;

		$this->assertEquals('Text with non-breaking spaces and line breaks', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithFreeTextMultiline(): void {
		$entity = new FormElement([
			'type' => 'free_text',
			'text' => "<p>First line of text\nSecond line of text\nThird line of text</p>",
		]);

		$label = $entity->label;

		$this->assertEquals('First line of text', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithFreeTextLongContent(): void {
		$longText = str_repeat('This is a very long text that should be truncated. ', 10);
		$entity = new FormElement([
			'type' => 'free_text',
			'text' => "<p>$longText</p>",
		]);

		$label = $entity->label;

		$this->assertStringEndsWith('...', $label);
		$this->assertLessThanOrEqual(103, strlen($label)); // 100 chars + "..."
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithFreeTextComplexHtml(): void {
		$entity = new FormElement([
			'type' => 'free_text',
			'text' => '<div><h2>Title</h2><p>Content with <strong>bold</strong> and <em>italic</em> text</p></div>',
		]);

		$label = $entity->label;

		$this->assertEquals('TitleContent with bold and italic text', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithFreeTextEmptyContent(): void {
		$entity = new FormElement([
			'type' => 'free_text',
			'text' => '',
		]);
		$entity->setSource('FormElements');

		$label = $entity->label;

		$this->assertEquals('FormElement', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithFreeTextNullContent(): void {
		$entity = new FormElement([
			'type' => 'free_text',
			'text' => null,
		]);
		$entity->setSource('FormElements');

		$label = $entity->label;

		$this->assertEquals('FormElement', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithFreeTextResponsiveImage(): void {
		$entity = new FormElement([
			'type' => 'free_text',
			'text' => '<p>Form element with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
		]);
		$entity->setSource('FormElements');

		$label = $entity->label;

		$this->assertEquals('Form element with inline img tagbetween two paragraphs', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_getLabel()
	 */
	public function testLabelVirtualPropertyWithNonFreeTextFallsBackToParent(): void {
		$entity = new FormElement([
			'type' => 'text',
			'title' => 'Text Input Field',
		]);
		$entity->setSource('FormElements');

		$label = $entity->label;

		$this->assertEquals('Text Input Field', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_setColumnLast()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testColumnLastCleaningViaPropertyAssignment(): void {
		$entity = new FormElement();

		$entity->columnLast = true;
		$this->assertTrue($entity->columnLast);

		$entity->columnLast = false;
		$this->assertFalse($entity->columnLast);

		$entity->columnLast = null;
		$this->assertFalse($entity->columnLast);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_setColumnLast()
	 */
	public function testColumnLastCleaningViaSetMethod(): void {
		$entity = new FormElement();

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
	 * @see \Awyiss\Model\Entity\FormElement::_setColumnRtl()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testColumnRtlCleaningViaPropertyAssignment(): void {
		$entity = new FormElement();

		$entity->columnRtl = true;
		$this->assertTrue($entity->columnRtl);

		$entity->columnRtl = false;
		$this->assertFalse($entity->columnRtl);

		$entity->columnRtl = null;
		$this->assertFalse($entity->columnRtl);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_setColumnRtl()
	 */
	public function testColumnRtlCleaningViaSetMethod(): void {
		$entity = new FormElement();

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
	 * @see \Awyiss\Model\Entity\FormElement::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new FormElement();

		$entity->identifier = 'TestIdentifier';
		$this->assertEquals('testidentifier', $entity->identifier);

		$entity->identifier = 'Test Identifier';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'Test-Identifier';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'Test Identifier!@#$%';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'UPPERCASE IDENTIFIER';
		$this->assertEquals('uppercase_identifier', $entity->identifier);

		$entity->identifier = 'testHTMLElement';
		$this->assertEquals('testhtmlelement', $entity->identifier);

		$entity->identifier = 'already_underscored';
		$this->assertEquals('already_underscored', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new FormElement();

		$entity->set('identifier', 'TestIdentifier');
		$this->assertEquals('testidentifier', $entity->identifier);

		$entity->set('identifier', 'Test Identifier');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'Test-Identifier');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'Test Identifier!@#$%');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'UPPERCASE IDENTIFIER');
		$this->assertEquals('uppercase_identifier', $entity->identifier);

		$entity->set('identifier', 'testHTMLElement');
		$this->assertEquals('testhtmlelement', $entity->identifier);

		$entity->set('identifier', 'already_underscored');
		$this->assertEquals('already_underscored', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_setOptions()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testOptionsCleaningViaPropertyAssignment(): void {
		$entity = new FormElement();

		$entity->options = ['key' => 'value'];
		$this->assertEquals(['key' => 'value'], $entity->options);

		$entity->options = [];
		$this->assertNull($entity->options);

		$entity->options = null;
		$this->assertNull($entity->options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::_setOptions()
	 */
	public function testOptionsCleaningViaSetMethod(): void {
		$entity = new FormElement();

		$entity->set('options', ['key' => 'value']);
		$this->assertEquals(['key' => 'value'], $entity->options);

		$entity->set('options', []);
		$this->assertNull($entity->options);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('options', null);
		$this->assertNull($entity->options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithNullOptions(): void {
		$entity = new FormElement();

		$result = $entity->parseOptions(null, 'select');

		$this->assertEquals([], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithEmptyOptions(): void {
		$entity = new FormElement();

		$result = $entity->parseOptions([], 'select');

		$this->assertEquals([], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithBasicOptions(): void {
		$entity = new FormElement();
		$options = [
			['key' => 'option1', 'value' => 'Option 1'],
			['key' => 'option2', 'value' => 'Option 2'],
		];

		$result = $entity->parseOptions($options, 'select');

		$this->assertEquals([
			'option1' => 'Option 1',
			'option2' => 'Option 2',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithTranslations(): void {
		$entity = new FormElement();
		$options = [
			['key' => null, 'value' => null, '_translations' => ['de' => ['key' => 'ja', 'value' => 'Ja'], 'en' => ['key' => 'yes', 'value' => 'Yes']]],
			['key' => null, 'value' => null, '_translations' => ['de' => ['key' => 'nein', 'value' => 'Nein'], 'en' => ['key' => 'no', 'value' => 'No']]],
		];

		$result = $entity->parseOptions($options, 'select', 'de');

		$this->assertEquals([
			'ja' => 'Ja',
			'nein' => 'Nein',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithMissingKeys(): void {
		$entity = new FormElement();
		$options = [
			['key' => '', 'value' => 'Option 1'],
			['key' => 'option2', 'value' => ''],
		];

		$result = $entity->parseOptions($options, 'select');

		$this->assertEquals([
			'Option 1' => 'Option 1', // Key becomes value when key is empty
			'option2' => 'option2', // Value becomes key when value is empty
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithEmptyKeyValueForFirstOption(): void {
		$entity = new FormElement();
		$options = [
			['key' => '', 'value' => ''], // First option with empty key and value
			['key' => 'option1', 'value' => 'Option 1'],
		];

		$result = $entity->parseOptions($options, 'select');

		$this->assertEquals([
			'' => '', // First option is kept even if empty
			'option1' => 'Option 1',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithEmptyKeyValueSkippedForCheckboxRadio(): void {
		$entity = new FormElement();
		$options = [
			['key' => '', 'value' => ''], // Should be skipped for checkbox/radio even as first option
			['key' => 'option1', 'value' => 'Option 1'],
		];

		$resultCheckbox = $entity->parseOptions($options, 'checkbox');
		$resultRadio = $entity->parseOptions($options, 'radio');

		$this->assertEquals([
			'option1' => 'Option 1',
		], $resultCheckbox);

		$this->assertEquals([
			'option1' => 'Option 1',
		], $resultRadio);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithEmptyKeyValueSkippedForNonFirstOptions(): void {
		$entity = new FormElement();
		$options = [
			['key' => 'option1', 'value' => 'Option 1'],
			['key' => '', 'value' => ''], // Should be skipped as it's not the first option
			['key' => 'option2', 'value' => 'Option 2'],
		];

		$result = $entity->parseOptions($options, 'select');

		$this->assertEquals([
			'option1' => 'Option 1',
			'option2' => 'Option 2',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithRealSelectData(): void {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');
		/** @var \Awyiss\Model\Entity\FormElement $entity */
		$entity = $table->get(4); // Select element "anrede" from seed data

		$result = $entity->parseOptions($entity->options, 'select', 'de');

		$expected = [
			'' => '',
			'Frau' => 'Frau',
			'Herr' => 'Herr',
			'Divers' => 'Divers',
		];

		$this->assertEquals($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormElement::parseOptions()
	 */
	public function testParseOptionsWithRealSelectDataSpanishTranslation(): void {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');
		/** @var \Awyiss\Model\Entity\FormElement $entity */
		$entity = $table->get(4); // Select element "anrede" from seed data

		$result = $entity->parseOptions($entity->options, 'select', 'es');

		$expected = [
			'' => '',
			'Senor' => 'Senor',
			'Diversos' => 'Diversos',
		];

		$this->assertEquals($expected, $result);
	}
}
