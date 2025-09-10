<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\WidgetTemplate;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * WidgetTemplate Entity Test Case
 *
 * @see \Awyiss\Model\Entity\WidgetTemplate
 */
class WidgetTemplateTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\WidgetTemplate::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\WidgetTemplatesTable $table */
		$table = FactoryLocator::get('Table')->get('WidgetTemplates');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach (array_keys($entityArray) as $key) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\WidgetTemplate::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new WidgetTemplate();
		$accessible = $entity->getAccessible();

		$this->assertSame([
			'title' => true,
			'fileName' => true,
			'inContentRow' => true,
			'systemOrder' => true,
			'active' => true,
			'widgetTemplateElements' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $accessible);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\WidgetTemplate::_setFileName()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testFileNameCleaningViaPropertyAssignment(): void {
		$entity = new WidgetTemplate();

		$entity->fileName = 'Test Template';
		$this->assertEquals('test_template', $entity->fileName);

		$entity->fileName = 'TestTemplate';
		$this->assertEquals('testtemplate', $entity->fileName);

		$entity->fileName = 'Test-Template';
		$this->assertEquals('test_template', $entity->fileName);

		$entity->fileName = 'Test Template!@#$%';
		$this->assertEquals('test_template', $entity->fileName);

		$entity->fileName = 'UPPERCASE TEMPLATE';
		$this->assertEquals('uppercase_template', $entity->fileName);

		$entity->fileName = null;
		$this->assertNull($entity->fileName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\WidgetTemplate::_setFileName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFileNameCleaningViaSetMethod(): void {
		$entity = new WidgetTemplate();

		$entity->set('fileName', 'Test Template');
		$this->assertEquals('test_template', $entity->fileName);

		$entity->set('fileName', 'TestTemplate');
		$this->assertEquals('testtemplate', $entity->fileName);

		$entity->set('fileName', 'Test-Template');
		$this->assertEquals('test_template', $entity->fileName);

		$entity->set('fileName', 'Test Template!@#$%');
		$this->assertEquals('test_template', $entity->fileName);

		$entity->set('fileName', 'UPPERCASE TEMPLATE');
		$this->assertEquals('uppercase_template', $entity->fileName);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('fileName', null);
		$this->assertNull($entity->fileName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\WidgetTemplate
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Widget Template',
			'file_name' => 'TestTemplate',
			'in_content_row' => true,
			'system_order' => 10,
			'active' => false,
			'deleted' => false,
		];

		$entity = new WidgetTemplate($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Test Widget Template', $entity->title);
		$this->assertEquals('testtemplate', $entity->fileName);
		$this->assertTrue($entity->inContentRow);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\WidgetTemplate::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'file_name' => 'test-file',
			'in_content_row' => true,
			'system_order' => 5,
		];

		$entity = new WidgetTemplate($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
