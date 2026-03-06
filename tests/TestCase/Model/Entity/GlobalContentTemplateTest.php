<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\GlobalContentTemplate;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * GlobalContentTemplate Entity Test Case
 *
 * @see \Awyiss\Model\Entity\GlobalContentTemplate
 */
class GlobalContentTemplateTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContentTemplate::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\GlobalContentTemplatesTable $table */
		$table = FactoryLocator::get('Table')->get('GlobalContentTemplates');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach (array_keys($entityArray) as $key) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContentTemplate::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new GlobalContentTemplate();
		$accessible = $entity->getAccessible();

		$this->assertSame([
			'title' => true,
			'fileName' => true,
			'inContentRow' => true,
			'systemOrder' => true,
			'active' => true,
			'globalContentTemplateElements' => true,
			'_translations' => true,
			'_publicationData' => true,
			'customerGroupAccessSettings' => true,
			'customerGroupAssignments' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $accessible);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\GlobalContentTemplate::_setFileName()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testFileNameCleaningViaPropertyAssignment(): void {
		$entity = new GlobalContentTemplate();

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
	 * @see \Awyiss\Model\Entity\GlobalContentTemplate::_setFileName()
	 */
	public function testFileNameCleaningViaSetMethod(): void {
		$entity = new GlobalContentTemplate();

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
	 * @see \Awyiss\Model\Entity\GlobalContentTemplate
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test GlobalContent Template',
			'fileName' => 'TestTemplate',
			'inContentRow' => true,
			'systemOrder' => 10,
			'active' => false,
			'deleted' => false,
		];

		$entity = new GlobalContentTemplate($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Test GlobalContent Template', $entity->title);
		$this->assertEquals('testtemplate', $entity->fileName);
		$this->assertTrue($entity->inContentRow);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
