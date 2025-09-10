<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Customer\Model\Enum\PageRole;


/**
 * PageTemplate Entity Test Case
 *
 * @see \Awyiss\Model\Entity\PageTemplate
 */
class PageTemplateTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplate::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\PageTemplatesTable $table */
		$table = FactoryLocator::get('Table')->get('PageTemplates');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplate::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new PageTemplate();

		$this->assertSame([
			'pageRoleId' => true,
			'title' => true,
			'fileName' => true,
			'systemOrder' => true,
			'active' => true,
			'contentAreas' => true,
			'contentTemplateContentAreas' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplate::_setFileName()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testFileNameCleaningViaPropertyAssignment(): void {
		$entity = new PageTemplate();

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

		$entity->fileName = 'Test Template with Spaces';
		$this->assertEquals('test_template_with_spaces', $entity->fileName);

		$entity->fileName = null;
		$this->assertNull($entity->fileName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplate::_setFileName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFileNameCleaningViaSetMethod(): void {
		$entity = new PageTemplate();

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

		$entity->set('fileName', 'Test Template with Spaces');
		$this->assertEquals('test_template_with_spaces', $entity->fileName);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('fileName', null);
		$this->assertNull($entity->fileName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplate::_setPageRoleId()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testPageRoleIdCleaningViaPropertyAssignment(): void {
		$entity = new PageTemplate();

		$entity->pageRoleId = '123';
		$this->assertEquals(123, $entity->pageRoleId);

		$entity->pageRoleId = 456;
		$this->assertEquals(456, $entity->pageRoleId);

		$entity->pageRoleId = null;
		$this->assertNull($entity->pageRoleId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplate::_setPageRoleId()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPageRoleIdCleaningViaSetMethod(): void {
		$entity = new PageTemplate();

		$entity->set('pageRoleId', '123');
		$this->assertEquals(123, $entity->pageRoleId);

		$entity->set('pageRoleId', 456);
		$this->assertEquals(456, $entity->pageRoleId);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('pageRoleId', null);
		$this->assertNull($entity->pageRoleId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplate::_setPageRoleId()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPageRoleIdWithEnumInterface(): void {
		$entity = new PageTemplate();

		$entity->pageRoleId = PageRole::News;

		$this->assertSame(PageRole::News, $entity->pageRoleId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplate
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'page_role_id' => '123',
			'title' => 'Test Page Template',
			'file_name' => 'TestTemplate',
			'system_order' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new PageTemplate($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->pageRoleId);
		$this->assertEquals('Test Page Template', $entity->title);
		$this->assertEquals('testtemplate', $entity->fileName);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplate::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'page_role_id' => 456,
			'file_name' => 'test-file',
			'system_order' => 5,
			'content_areas' => [],
			'content_template_content_areas' => [],
		];

		$entity = new PageTemplate($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
