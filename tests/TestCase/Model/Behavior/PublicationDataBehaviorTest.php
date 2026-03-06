<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use Awyiss\Model\Behavior\PublicationDataBehavior;
use Awyiss\Model\Entity\PublicationData;
use Awyiss\Model\Table;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Exception;
use LogicException;


/**
 * PublicationDataBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\PublicationDataBehavior
 */
class PublicationDataBehaviorTest extends TestCase {
	/**
	 * @var \Customer\Model\Table\EmployersTable
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\PublicationDataBehavior
	 */
	protected PublicationDataBehavior $behavior;
	/**
	 * @var \Awyiss\Model\Table\PublicationDataTable
	 */
	protected Table $publicationDataTable;


	/**
	 * @return array<\Cake\Datasource\EntityInterface>|false
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::beforeSave()
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::buildMarshalMap()
	 */
	protected function saveTestData(): iterable|false {
		try {
			$result = $this->table->saveMany([
				$entity1 = $this->table->newDefaultEntity([
					'title' => 'Entity 1 (published starting 2023)',
					'languageShortcode' => 'de',
					'_publicationData' => [
						'start' => [
							'dateTime' => new DateTime('2023-01-01 00:00:00'),
						],
					],
				]),
				$entity2 = $this->table->newDefaultEntity([
					'title' => 'Entity 2 (published ending 2024)',
					'languageShortcode' => 'de',
					'_publicationData' => [
						'end' => [
							'dateTime' => new DateTime('2024-12-31 23:59:59'),
						],
					],
				]),
				$entity3 = $this->table->newDefaultEntity([
					'title' => 'Entity 3 (published starting last hour and ending next hour)',
					'languageShortcode' => 'de',
					'_publicationData' => [
						'start' => [
							'dateTime' => new DateTime()->subHours(1),
						],
						'end' => [
							'dateTime' => new DateTime()->addHours(1),
						],
					],
				]),
				$entity4 = $this->table->newDefaultEntity([
					'title' => 'Entity 4 (published starting next hour)',
					'languageShortcode' => 'de',
					'_publicationData' => [
						'start' => [
							'dateTime' => new DateTime()->addHours(1),
						],
					],
				]),
				$entity5 = $this->table->newDefaultEntity([
					'title' => 'Entity 5 (published ending last hour)',
					'languageShortcode' => 'de',
					'_publicationData' => [
						'end' => [
							'dateTime' => new DateTime()->subHours(1),
						],
					],
				]),
			]);

			$this->assertNotEmpty($entity1->_publicationData);
			$this->assertNotEmpty($entity2->_publicationData);
			$this->assertNotEmpty($entity3->_publicationData);
			$this->assertNotEmpty($entity4->_publicationData);
			$this->assertNotEmpty($entity5->_publicationData);

			return $result;
		}
		catch (Exception $ex) {
			$this->fail('Failed to save test data: ' . $ex->getMessage());
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Configure::write('Awyiss.Employers.Backend.publicationData.enabled', true);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Employers');

		Configure::delete('Awyiss');

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('PublicationData');

		$this->publicationDataTable = TableRegistry::getTableLocator()->get('PublicationData');

		$this->table->deleteAll([]);
		$this->publicationDataTable->deleteAll(['id >' => 8]);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		$this->table->deleteAll([]);
		$this->publicationDataTable->deleteAll(['id >' => 8]);

		parent::tearDown();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::__construct()
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::initialize()
	 */
	public function testInitializationWhenEnabled(): void {
		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame([
			'published' => 'findPublished',
			'publishedStartingBefore' => 'findPublishedStartingBefore',
			'publishedStartingAfter' => 'findPublishedStartingAfter',
			'publishedEndingBefore' => 'findPublishedEndingBefore',
			'publishedEndingAfter' => 'findPublishedEndingAfter',
		], $config['implementedFinders']);
		$this->assertSame('Employers', $config['referenceName']);
		$this->assertSame('subquery', $config['strategy']);
		$this->assertNotNull($config['tableLocator']);

		// Check that associations are set up
		$this->assertTrue($this->table->hasAssociation('EmployersPublicationDataStart'));
		$this->assertTrue($this->table->hasAssociation('EmployersPublicationDataEnd'));
		$this->assertTrue($this->table->hasAssociation('PublicationData'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::initialize()
	 */
	public function testInitializationWhenDisabled(): void {
		Configure::write('Awyiss.Employers.Backend.publicationData.enabled', false);
		TableRegistry::getTableLocator()->clear();

		$table = TableRegistry::getTableLocator()->get('Employers');

		Configure::delete('Awyiss');
		TableRegistry::getTableLocator()->clear();

		$behavior = $table->getBehavior('PublicationData');

		$config = $behavior->getConfig();

		$this->assertFalse($config['enabled']);

		// Check that associations are not set up
		$this->assertFalse($table->hasAssociation('EmployersPublicationDataStart'));
		$this->assertFalse($table->hasAssociation('EmployersPublicationDataEnd'));
		$this->assertFalse($table->hasAssociation('PublicationData'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
	 */
	public function testFindPublishedWhenDisabled(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		$this->behavior->setConfig('enabled', false);

		// Should not throw an error, but return empty results
		$query = $this->table->find('published');
		$results = $query->toArray();

		$this->assertCount(5, $results);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
	 */
	public function testFindPublishedWithNoDateFilters(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		$query = $this->table->find('published');
		$results = $query->toArray();

		// Will find all currently published entities
		$this->assertCount(2, $results);

		$this->assertSame('Entity 1 (published starting 2023)', $results[0]->title);
		$this->assertSame('Entity 3 (published starting last hour and ending next hour)', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
	 */
	public function testFindPublishedWithDate(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		// Find entities published at a specific date
		$query = $this->table->find('published', new DateTime('2023-01-01 00:00:00'));
		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame('Entity 1 (published starting 2023)', $results[0]->title);
		$this->assertSame('Entity 2 (published ending 2024)', $results[1]->title);
		$this->assertSame('Entity 5 (published ending last hour)', $results[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedStartingBefore()
	 */
	public function testFindPublishedStartingBefore(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		// Find entities published starting before a specific date
		$query = $this->table->find('publishedStartingBefore', new DateTime('2023-01-01 00:00:00'));
		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame('Entity 1 (published starting 2023)', $results[0]->title);
		$this->assertSame('Entity 2 (published ending 2024)', $results[1]->title);
		$this->assertSame('Entity 5 (published ending last hour)', $results[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedStartingBefore()
	 */
	public function testFindPublishedStartingBeforeWithoutUndefined(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		// Find entities published starting before a specific date
		$query = $this->table->find('publishedStartingBefore', new DateTime('2023-01-01 00:00:00'), false);
		$results = $query->toArray();

		$this->assertCount(1, $results);

		$this->assertSame('Entity 1 (published starting 2023)', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedStartingAfter()
	 */
	public function testFindPublishedStartingAfter(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		// Find entities published starting after a specific date
		$query = $this->table->find('publishedStartingAfter', new DateTime('2023-01-01 00:00:01'));
		$results = $query->toArray();

		$this->assertCount(4, $results);
		$this->assertSame('Entity 2 (published ending 2024)', $results[0]->title);
		$this->assertSame('Entity 3 (published starting last hour and ending next hour)', $results[1]->title);
		$this->assertSame('Entity 4 (published starting next hour)', $results[2]->title);
		$this->assertSame('Entity 5 (published ending last hour)', $results[3]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingBefore()
	 */
	public function testFindPublishedStartingAfterWithoutUndefined(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		// Find entities published starting after a specific date
		$query = $this->table->find('publishedStartingAfter', new DateTime('2023-01-01 00:00:01'), false);
		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 3 (published starting last hour and ending next hour)', $results[0]->title);
		$this->assertSame('Entity 4 (published starting next hour)', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingBefore()
	 */
	public function testFindPublishedEndingBefore(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		// Find entities published ending before a specific date
		$query = $this->table->find('publishedEndingBefore', new DateTime('2024-12-31 23:59:59'));
		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame('Entity 1 (published starting 2023)', $results[0]->title);
		$this->assertSame('Entity 2 (published ending 2024)', $results[1]->title);
		$this->assertSame('Entity 4 (published starting next hour)', $results[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingBefore()
	 */
	public function testFindPublishedEndingBeforeWithoutUndefined(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		// Find entities published ending before a specific date
		$query = $this->table->find('publishedEndingBefore', new DateTime('2024-12-31 23:59:59'), false);
		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Entity 2 (published ending 2024)', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingAfter()
	 */
	public function testFindPublishedEndingAfter(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		// Find entities published ending after a specific date
		$query = $this->table->find('publishedEndingAfter', new DateTime('2025-01-01 00:00:00'));
		$results = $query->toArray();

		$this->assertCount(4, $results);
		$this->assertSame('Entity 1 (published starting 2023)', $results[0]->title);
		$this->assertSame('Entity 3 (published starting last hour and ending next hour)', $results[1]->title);
		$this->assertSame('Entity 4 (published starting next hour)', $results[2]->title);
		$this->assertSame('Entity 5 (published ending last hour)', $results[3]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingAfter()
	 */
	public function testFindPublishedEndingAfterWithoutUndefined(): void {
		$result = $this->saveTestData();

		$this->assertNotFalse($result);

		// Find entities published ending after a specific date
		$query = $this->table->find('publishedEndingAfter', new DateTime('2025-01-01 00:00:00'), false);
		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 3 (published starting last hour and ending next hour)', $results[0]->title);
		$this->assertSame('Entity 5 (published ending last hour)', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedStartingBefore()
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingAfter()
	 * @throws \Exception
	 */
	public function testCombinedFindersStartedBeforeAndEndedAfter(): void {
		$result = $this->saveTestData();
		$this->assertNotFalse($result);

		// Find entities that started before now AND end after now (currently active)
		$query = $this->table->find('publishedStartingBefore', new DateTime('now'))->find('publishedEndingAfter', new DateTime('now'));
		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 1 (published starting 2023)', $results[0]->title);
		$this->assertSame('Entity 3 (published starting last hour and ending next hour)', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedStartingBefore()
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingBefore()
	 * @throws \Exception
	 */
	public function testCombinedFindersStartedBeforeAndEndedBefore(): void {
		$result = $this->saveTestData();
		$this->assertNotFalse($result);

		// Find entities that started before now AND end after now (currently active)
		$query = $this->table->find('publishedStartingBefore', new DateTime('now'))->find('publishedEndingBefore', new DateTime('now'));
		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame('Entity 1 (published starting 2023)', $results[0]->title);
		$this->assertSame('Entity 2 (published ending 2024)', $results[1]->title);
		$this->assertSame('Entity 5 (published ending last hour)', $results[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedStartingAfter()
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingBefore()
	 * @throws \Exception
	 */
	public function testCombinedFindersStartedAfterAndEndedBefore(): void {
		$result = $this->saveTestData();
		$this->assertNotFalse($result);

		// Find entities that started after today a month ago AND end before today next year
		$now = new DateTime('now');
		$query = $this->table->find('publishedStartingAfter', $now->subMonths(1))->find('publishedEndingBefore', $now->addYears(1));
		$results = $query->toArray();

		$this->assertCount(4, $results);
		$this->assertSame('Entity 3 (published starting last hour and ending next hour)', $results[1]->title);
		$this->assertSame('Entity 4 (published starting next hour)', $results[2]->title);
		$this->assertSame('Entity 5 (published ending last hour)', $results[3]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedStartingAfter()
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingAfter()
	 * @throws \Exception
	 */
	public function testCombinedFindersStartedAfterAndEndedAfter(): void {
		$result = $this->saveTestData();
		$this->assertNotFalse($result);

		// Find entities that started after today a month ago AND end before today next year
		$now = new DateTime('now');
		$query = $this->table->find('publishedStartingAfter', $now->subMonths(1))->find('publishedEndingAfter', $now->addYears(1));
		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Entity 4 (published starting next hour)', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedStartingBefore()
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedStartingAfter()
	 * @throws \Exception
	 */
	public function testCombinedFindersStartedBeforeAndStartedAfter(): void {
		$result = $this->saveTestData();
		$this->assertNotFalse($result);

		$now = new DateTime('now');

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Cannot use the publish finder with type `Start` twice.');

		$this->table->find('publishedStartingBefore', $now)->find('publishedStartingAfter', $now->subMonths(1));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingBefore()
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::findPublishedEndingAfter()
	 * @throws \Exception
	 */
	public function testCombinedFindersEndedBeforeAndEndedAfter(): void {
		$result = $this->saveTestData();
		$this->assertNotFalse($result);

		// Find entities that started before now AND started after today a month ago
		$now = new DateTime('now');

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Cannot use the publish finder with type `End` twice.');

		$this->table->find('publishedEndingBefore', $now)->find('publishedEndingAfter', $now->subMonths(1));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior::beforeFind()
	 */
	public function testBeforeFindContainsAndFormatsPublicationData(): void {
		$result = $this->saveTestData();
		$this->assertNotFalse($result);

		// Check that the formatter is added to the query
		$query = $this->table->find();
		$result = $query->toArray();

		$this->assertCount(5, $result);

		$this->assertArrayHasKey('_publicationData', $result[0]->toArray());
		$this->assertArrayHasKey('start', $result[0]->_publicationData);
		$this->assertInstanceOf(PublicationData::class, $result[0]->_publicationData['start']);
		$this->assertArrayNotHasKey('end', $result[0]->_publicationData);

		$this->assertArrayHasKey('_publicationData', $result[1]->toArray());
		$this->assertArrayNotHasKey('start', $result[1]->_publicationData);
		$this->assertArrayHasKey('end', $result[1]->_publicationData);
		$this->assertInstanceOf(PublicationData::class, $result[1]->_publicationData['end']);

		$this->assertArrayHasKey('_publicationData', $result[2]->toArray());
		$this->assertArrayHasKey('start', $result[2]->_publicationData);
		$this->assertInstanceOf(PublicationData::class, $result[2]->_publicationData['start']);
		$this->assertArrayHasKey('end', $result[2]->_publicationData);
		$this->assertInstanceOf(PublicationData::class, $result[2]->_publicationData['end']);

		$this->assertArrayHasKey('_publicationData', $result[3]->toArray());
		$this->assertArrayHasKey('start', $result[3]->_publicationData);
		$this->assertInstanceOf(PublicationData::class, $result[3]->_publicationData['start']);
		$this->assertArrayNotHasKey('end', $result[3]->_publicationData);

		$this->assertArrayHasKey('_publicationData', $result[4]->toArray());
		$this->assertArrayNotHasKey('start', $result[4]->_publicationData);
		$this->assertArrayHasKey('end', $result[4]->_publicationData);
		$this->assertInstanceOf(PublicationData::class, $result[4]->_publicationData['end']);
	}
}
