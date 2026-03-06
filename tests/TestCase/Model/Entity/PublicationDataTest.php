<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\PublicationData;
use Awyiss\Model\Enum\PublicationDataType;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;


/**
 * PublicationData Entity Test Case
 *
 * @see \Awyiss\Model\Entity\PublicationData
 */
class PublicationDataTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PublicationData::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\PublicationDataTable $table */
		$table = FactoryLocator::get('Table')->get('PublicationData');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PublicationData::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new PublicationData();

		$this->assertSame([
			'scope' => true,
			'foreignKey' => true,
			'type' => true,
			'dateTime' => true,
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
	 * @see \Awyiss\Model\Entity\PublicationData
	 */
	public function testEntityConstruction(): void {
		$dateTime = new DateTime('2023-12-25 10:30:00');
		$properties = [
			'id' => 1,
			'scope' => 'Pages',
			'foreignKey' => 123,
			'type' => PublicationDataType::Start,
			'dateTime' => $dateTime,
		];

		$entity = new PublicationData($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Pages', $entity->scope);
		$this->assertEquals(123, $entity->foreignKey);
		$this->assertEquals(PublicationDataType::Start, $entity->type);
		$this->assertEquals($dateTime, $entity->dateTime);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PublicationData
	 */
	public function testEntityWithPublicationStartType(): void {
		$entity = new PublicationData([
			'scope' => 'GlobalContents',
			'foreignKey' => 789,
			'type' => PublicationDataType::Start,
			'dateTime' => new DateTime('2023-06-15 09:00:00'),
		]);

		$this->assertEquals('GlobalContents', $entity->scope);
		$this->assertEquals(789, $entity->foreignKey);
		$this->assertEquals(PublicationDataType::Start, $entity->type);
		$this->assertInstanceOf(DateTime::class, $entity->dateTime);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PublicationData
	 */
	public function testEntityWithPublicationEndType(): void {
		$entity = new PublicationData([
			'scope' => 'Articles',
			'foreignKey' => 101,
			'type' => PublicationDataType::End,
			'dateTime' => new DateTime('2023-12-31 23:59:59'),
		]);

		$this->assertEquals('Articles', $entity->scope);
		$this->assertEquals(101, $entity->foreignKey);
		$this->assertEquals(PublicationDataType::End, $entity->type);
		$this->assertInstanceOf(DateTime::class, $entity->dateTime);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PublicationData
	 */
	public function testEntityWithNullDateTime(): void {
		$entity = new PublicationData([
			'scope' => 'Media',
			'foreignKey' => 202,
			'type' => PublicationDataType::Start,
			'dateTime' => null,
		]);

		$this->assertEquals('Media', $entity->scope);
		$this->assertEquals(202, $entity->foreignKey);
		$this->assertEquals(PublicationDataType::Start, $entity->type);
		$this->assertNull($entity->dateTime);
	}
}
