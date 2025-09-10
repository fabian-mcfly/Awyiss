<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\SurveyEntry;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * SurveyEntry Entity Test Case
 *
 * @see \Awyiss\Model\Entity\SurveyEntry
 */
class SurveyEntryTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyEntry::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\SurveyEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('SurveyEntries');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyEntry::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new SurveyEntry();

		$this->assertSame([
			'surveyId' => true,
			'pageId' => true,
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
	 * @see \Awyiss\Model\Entity\SurveyEntry
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'survey_id' => 123,
			'page_id' => 456,
			'data' => '{"answer": "test"}',
			'ip_hash' => 'abc123hash',
			'post_hash' => 'def456hash',
			'identifier' => 'entry_001',
			'deleted' => false,
			'created_on' => '2025-01-06 12:00:00',
		];

		$entity = new SurveyEntry($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->surveyId);
		$this->assertEquals(456, $entity->pageId);
		$this->assertEquals('{"answer": "test"}', $entity->data);
		$this->assertEquals('abc123hash', $entity->ipHash);
		$this->assertEquals('def456hash', $entity->postHash);
		$this->assertEquals('entry_001', $entity->identifier);
		$this->assertFalse($entity->deleted);
		$this->assertNotNull($entity->createdOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyEntry::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'survey_id' => 789,
			'page_id' => 101,
			'ip_hash' => 'xyz789hash',
			'post_hash' => 'uvw012hash',
		];
		$entity = new SurveyEntry($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
