<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\EmailTemplate;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * EmailTemplate Entity Test Case
 *
 * @see \Awyiss\Model\Entity\EmailTemplate
 */
class EmailTemplateTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\EmailTemplate::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\EmailTemplatesTable $table */
		$table = FactoryLocator::get('Table')->get('EmailTemplates');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\EmailTemplate::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new EmailTemplate();

		$this->assertSame([
			'title' => true,
			'textHtml' => true,
			'textPlain' => true,
			'fileName' => true,
			'layout' => true,
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
	 * @see \Awyiss\Model\Entity\EmailTemplate::_setFileName()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testFileNameCleaningViaPropertyAssignment(): void {
		$entity = new EmailTemplate();

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
	 * @see \Awyiss\Model\Entity\EmailTemplate::_setFileName()
	 */
	public function testFileNameCleaningViaSetMethod(): void {
		$entity = new EmailTemplate();

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
	 * @see \Awyiss\Model\Entity\EmailTemplate
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Email Template',
			'textHtml' => '<p>HTML content</p>',
			'textPlain' => 'Plain text content',
			'fileName' => 'TestTemplate',
			'layout' => 'default',
			'active' => true,
			'deleted' => false,
			'usedForEmails' => 5,
			'usedForConfirmationEmails' => 3,
		];

		$entity = new EmailTemplate($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Test Email Template', $entity->title);
		$this->assertEquals('<p>HTML content</p>', $entity->textHtml);
		$this->assertEquals('Plain text content', $entity->textPlain);
		$this->assertEquals('testtemplate', $entity->fileName);
		$this->assertEquals('default', $entity->layout);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
		$this->assertEquals(5, $entity->usedForEmails);
		$this->assertEquals(3, $entity->usedForConfirmationEmails);
	}
}
