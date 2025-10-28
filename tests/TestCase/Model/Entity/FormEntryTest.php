<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\FormEntry;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * FormEntry Entity Test Case
 *
 * @see \Awyiss\Model\Entity\FormEntry
 */
class FormEntryTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormEntry::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\FormEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('FormEntries');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormEntry::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new FormEntry();

		$this->assertSame([
			'formId' => true,
			'pageId' => true,
			'languageShortcode' => true,
			'subject' => true,
			'subjectConfirmation' => true,
			'body' => true,
			'bodyConfirmation' => true,
			'data' => true,
			'ipHash' => true,
			'postHash' => true,
			'identifier' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormEntry
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'form_id' => 123,
			'page_id' => 456,
			'language_shortcode' => 'de',
			'subject' => 'Contact Form Submission',
			'subject_confirmation' => 'Thank you for your message',
			'body' => 'This is the form submission content',
			'body_confirmation' => 'Your message has been received',
			'data' => '{"name":"John Doe","email":"john@example.com"}',
			'ip_hash' => 'a1b2c3d4e5f6',
			'post_hash' => 'f6e5d4c3b2a1',
			'identifier' => 'form-entry-12345',
			'deleted' => false,
			'created_on' => '2025-01-06 12:00:00',
		];

		$entity = new FormEntry($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->formId);
		$this->assertEquals(456, $entity->pageId);
		$this->assertEquals('de', $entity->languageShortcode);
		$this->assertEquals('Contact Form Submission', $entity->subject);
		$this->assertEquals('Thank you for your message', $entity->subjectConfirmation);
		$this->assertEquals('This is the form submission content', $entity->body);
		$this->assertEquals('Your message has been received', $entity->bodyConfirmation);
		$this->assertEquals('{"name":"John Doe","email":"john@example.com"}', $entity->data);
		$this->assertEquals('a1b2c3d4e5f6', $entity->ipHash);
		$this->assertEquals('f6e5d4c3b2a1', $entity->postHash);
		$this->assertEquals('form-entry-12345', $entity->identifier);
		$this->assertFalse($entity->deleted);
		$this->assertNotNull($entity->createdOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\FormEntry::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'form_id' => 789,
			'page_id' => 101,
			'language_shortcode' => 'en',
			'subject_confirmation' => 'Confirmation subject',
			'body_confirmation' => 'Confirmation body',
			'ip_hash' => '123abc',
			'post_hash' => 'abc123',
		];

		$entity = new FormEntry($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
