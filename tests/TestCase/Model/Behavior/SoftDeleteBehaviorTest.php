<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Model\Behavior\SoftDeleteBehavior;
use Awyiss\Model\Table;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\RulesChecker;
use Cake\Event\Event;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Customer\Model\Table\EmployersTable;
use ReflectionClass;
use RuntimeException;


/**
 * SoftDeleteBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior
 */
class SoftDeleteBehaviorTest extends TestCase {
	/**
	 * @var \Customer\Model\Table\EmployersTable
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\SoftDeleteBehavior
	 */
	protected SoftDeleteBehavior $behavior;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::loadConfiguration('de', 'de');

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Employers');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('SoftDelete');

		$this->table->deleteAll([]);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		$this->table->deleteAll([]);

		$attributesCarsTable = TableRegistry::getTableLocator()->get('AttributesCars');
		$attributesCarsTable->deleteAll([]);

		$i18nTable = TableRegistry::getTableLocator()->get('I18n');
		$i18nTable->deleteAll([
			'model IN' => ['cars', 'employers'],
		]);

		parent::tearDown();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::initialize()
	 */
	public function testInitialization(): void {
		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame([
			'buildRules',
			'beforeFind',
			'beforeDelete',
		], $config['implementedEvents']);

		$this->assertSame([
			'deleted' => 'findDeleted',
			'withDeleted' => 'findWithDeleted',
		], $config['implementedFinders']);

		$this->assertSame([
			'softDelete' => 'softDelete',
		], $config['implementedMethods']);

		$this->assertFalse($config['includeDeleted']);
		$this->assertFalse($config['skip']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::initialize()
	 */
	public function testInitializationDisablesWhenNoDeletedColumn(): void {
		// Create a table without a deleted column
		$table = TableRegistry::getTableLocator()->get('i18n', [
			'className' => Table::class,
		]);

		TableRegistry::getTableLocator()->clear();

		// Mock the schema to not have a deleted column
		$schema = $table->getSchema();
		$schema->removeColumn('deleted');
		$table->setSchema($schema);

		$behavior = new SoftDeleteBehavior($table);
		$behavior->initialize([]);

		TableRegistry::getTableLocator()->clear();

		$this->assertFalse($behavior->getConfig('enabled'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$events = $this->behavior->implementedEvents();
		$this->assertSame([
			'Model.buildRules' => 'buildRules',
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeDelete' => 'beforeDelete',
		], $events);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::findDeleted()
	 * @throws \Exception
	 */
	public function testFindDeletedReturnsOnlyDeletedEntities(): void {
		$result = $this->table->saveMany([
			$entity1 = $this->table->newDefaultEntity(['title' => 'Active', 'languageShortcode' => 'de', 'deleted' => false], ['accessibleFields' => ['deleted']]),
			$entity2 = $this->table->newDefaultEntity(['title' => 'Deleted', 'languageShortcode' => 'de', 'deleted' => true], ['accessibleFields' => ['deleted']]),
			$entity3 = $this->table->newDefaultEntity(['title' => 'Another Active', 'languageShortcode' => 'de', 'deleted' => false], ['accessibleFields' => ['deleted']]),
		]);

		$this->assertFalse($entity1->deleted);
		$this->assertTrue($entity2->deleted);
		$this->assertFalse($entity3->deleted);

		$this->assertNotFalse($result);

		/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findDeleted() */
		$query = $this->table->find('deleted');
		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Deleted', $results[0]->title);
		$this->assertTrue($results[0]->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::findDeleted()
	 * @throws \Exception
	 */
	public function testFindDeletedWhenDisabled(): void {
		$result = $this->table->saveMany([
			$entity1 = $this->table->newDefaultEntity(['title' => 'Active', 'languageShortcode' => 'de', 'deleted' => false], ['accessibleFields' => ['deleted']]),
			$entity2 = $this->table->newDefaultEntity(['title' => 'Deleted', 'languageShortcode' => 'de', 'deleted' => true], ['accessibleFields' => ['deleted']]),
			$entity3 = $this->table->newDefaultEntity(['title' => 'Another Active', 'languageShortcode' => 'de', 'deleted' => false], ['accessibleFields' => ['deleted']]),
		]);

		$this->assertFalse($entity1->deleted);
		$this->assertTrue($entity2->deleted);
		$this->assertFalse($entity3->deleted);

		$this->assertNotFalse($result);

		$this->behavior->setConfig('enabled', false);

		/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findDeleted() */
		$query = $this->table->find('deleted');
		$results = $query->toArray();

		$this->assertCount(3, $results);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted()
	 * @throws \Exception
	 */
	public function testFindWithDeletedReturnsAllEntities(): void {
		$result = $this->table->saveMany([
			$entity1 = $this->table->newDefaultEntity(['title' => 'Active', 'languageShortcode' => 'de', 'deleted' => false], ['accessibleFields' => ['deleted']]),
			$entity2 = $this->table->newDefaultEntity(['title' => 'Deleted', 'languageShortcode' => 'de', 'deleted' => true], ['accessibleFields' => ['deleted']]),
			$entity3 = $this->table->newDefaultEntity(['title' => 'Another Active', 'languageShortcode' => 'de', 'deleted' => false], ['accessibleFields' => ['deleted']]),
		]);

		$this->assertFalse($entity1->deleted);
		$this->assertTrue($entity2->deleted);
		$this->assertFalse($entity3->deleted);

		$this->assertNotFalse($result);

		/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
		$query = $this->table->find('withDeleted');
		$results = $query->toArray();

		$this->assertCount(3, $results);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted()
	 * @throws \Exception
	 */
	public function testFindWithDeletedWhenDisabled(): void {
		$result = $this->table->saveMany([
			$entity1 = $this->table->newDefaultEntity(['title' => 'Active', 'languageShortcode' => 'de', 'deleted' => false], ['accessibleFields' => ['deleted']]),
			$entity2 = $this->table->newDefaultEntity(['title' => 'Deleted', 'languageShortcode' => 'de', 'deleted' => true], ['accessibleFields' => ['deleted']]),
			$entity3 = $this->table->newDefaultEntity(['title' => 'Another Active', 'languageShortcode' => 'de', 'deleted' => false], ['accessibleFields' => ['deleted']]),
		]);

		$this->assertFalse($entity1->deleted);
		$this->assertTrue($entity2->deleted);
		$this->assertFalse($entity3->deleted);

		$this->assertNotFalse($result);

		$this->behavior->setConfig('enabled', false);

		/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
		$query = $this->table->find('withDeleted');
		$results = $query->toArray();

		$this->assertCount(3, $results);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::buildRules()
	 */
	public function testBuildRulesAddsUpdateRuleWhenDeleted(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Active', 'languageShortcode' => 'de', 'deleted' => true], ['accessibleFields' => ['deleted']]);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertTrue($entity->deleted);

		$entity->title = 'Updated Title';

		$result = $this->table->checkRules($entity, RulesChecker::UPDATE);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('deletedNotModified', $errors['_general']);
		$this->assertSame('employers::error_deleted_not_modified', $errors['_general']['deletedNotModified']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::buildRules()
	 */
	public function testBuildRulesAddsUpdateRuleWhenOriginalDeleted(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Active', 'languageShortcode' => 'de', 'deleted' => true], ['accessibleFields' => ['deleted']]);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertTrue($entity->deleted);

		$entity->deleted = false;

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertFalse($entity->deleted);

		$result = $this->table->checkRules($entity, RulesChecker::UPDATE);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('deletedNotModified', $errors['_general']);
		$this->assertSame('employers::error_deleted_not_modified', $errors['_general']['deletedNotModified']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::buildRules()
	 */
	public function testBuildRulesWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$entity = $this->table->newDefaultEntity(['title' => 'Active', 'languageShortcode' => 'de', 'deleted' => true], ['accessibleFields' => ['deleted']]);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertTrue($entity->deleted);

		$entity->title = 'Updated Title';

		$result = $this->table->checkRules($entity, RulesChecker::UPDATE);

		$this->assertTrue($result);
		$this->assertEmpty($entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeFind()
	 */
	public function testBeforeFindExcludesDeletedByDefault(): void {
		$query = $this->table->find();
		$options = new ArrayObject();
		$event = new Event('Model.beforeFind');

		$this->behavior->beforeFind($event, $query, $options, true);

		$this->markBeforeFindFired($query);

		$sql = $query->sql();
		$this->assertStringContainsString('deleted = :c0', $sql);

		$bindings = $query->getValueBinder()->bindings();
		$this->assertArrayHasKey(':c0', $bindings);
		$this->assertFalse($bindings[':c0']['value']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeFind()
	 */
	public function testBeforeFindWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$query = $this->table->find();
		$options = new ArrayObject();
		$event = new Event('Model.beforeFind');

		$this->behavior->beforeFind($event, $query, $options, true);

		$this->markBeforeFindFired($query);

		$sql = $query->sql();
		$this->assertStringNotContainsString('deleted =', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeFind()
	 */
	public function testBeforeFindWithIncludeDeletedOption(): void {
		$query = $this->table->find();
		$options = new ArrayObject(['softDelete' => ['includeDeleted' => true, 'foo' => 'bar']]);
		$event = new Event('Model.beforeFind');

		$this->behavior->beforeFind($event, $query, $options, true);

		$this->markBeforeFindFired($query);

		$sql = $query->sql();
		$this->assertStringNotContainsString('deleted =', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeDelete()
	 */
	public function testBeforeDeleteInterceptsAndCallsSoftDelete(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertFalse($entity->deleted);

		$options = new ArrayObject(['audit' => ['skip' => true]]);
		$event = new Event('Model.beforeDelete');

		$this->behavior->beforeDelete($event, $entity, $options);

		// Event should be stopped
		$this->assertTrue($event->isStopped());
		$this->assertTrue($event->getResult());

		// Entity should be soft deleted
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeDelete()
	 */
	public function testBeforeDeleteWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de',]);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);

		$options = new ArrayObject();
		$event = new Event('Model.beforeDelete');

		$this->behavior->beforeDelete($event, $entity, $options);

		// Event should not be stopped
		$this->assertFalse($event->isStopped());

		// Entity should not be soft deleted
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeDelete()
	 */
	public function testBeforeDeleteWhenSkipped(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de',]);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);

		$options = new ArrayObject(['softDelete' => ['skip' => true]]);
		$event = new Event('Model.beforeDelete');

		$this->behavior->beforeDelete($event, $entity, $options);

		// Event should not be stopped
		$this->assertFalse($event->isStopped());

		// Entity should not be soft deleted
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeDelete()
	 */
	public function testBeforeDeleteThrowsExceptionOnMissingPrimaryKey(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity']);
		// Don't save the entity so it has no primary key and soft delete will fail

		$options = new ArrayObject();
		$event = new Event('Model.beforeDelete');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Missing property `id` in entity `Customer\Model\Entity\Employer`');

		$this->behavior->beforeDelete($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeDelete()
	 */
	public function testBeforeDeleteThrowsExceptionOnFailure(): void {
		$this->table = $this->getMockBuilder(EmployersTable::class)->setConstructorArgs([
			[
				'table' => 'employers',
				'registryAlias' => 'Employers',
				'alias' => 'Employers',
			],
		])->onlyMethods(['save'])->getMock();
		$this->table->method('save')->willReturn(false);

		$this->table->addBehavior('SoftDelete');
		$this->behavior = $this->table->getBehavior('SoftDelete');

		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'systemOrder' => 5]);
		$entity->id = 123;

		$options = new ArrayObject([
			'audit' => ['skip' => true],
		]);
		$event = new Event('Model.beforeDelete');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not soft-delete entity of type `Awyiss\Model\Entity`');

		$this->behavior->beforeDelete($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeDelete()
	 */
	public function testBeforeDeleteUnsetsCheckRules(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'systemOrder' => 5]);
		$entity->id = 123;

		$options = new ArrayObject([
			'audit' => ['skip' => true],
			'checkRules' => true,
		]);
		$event = new Event('Model.beforeDelete');

		$this->behavior = $this->getMockBuilder(SoftDeleteBehavior::class)->setConstructorArgs([
			$this->table,
		])->onlyMethods(['softDelete'])->getMock();
		$rules = true;
		$this->behavior->expects($this->once())->method('softDelete')->with(
			$this->equalTo($entity),
			$this->callback(function (ArrayObject $options) use (&$rules) {
				$rules = $options['checkRules'];

				return !$options['checkRules'];
			}),
		)->willReturn(true);

		$this->behavior->beforeDelete($event, $entity, $options);

		// Check that checkRules was unset
		$this->assertFalse($rules);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeDelete()
	 */
	public function testBeforeDeleteDispatchesAfterSoftDeleteCommit(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);

		$afterSoftDeleteCommitFired = false;

		$this->table->getEventManager()->on('Model.afterSoftDeleteCommit', function () use (&$afterSoftDeleteCommitFired) {
			$afterSoftDeleteCommitFired = true;
		});

		$options = new ArrayObject(['audit' => ['skip' => true]]);
		$event = new Event('Model.beforeDelete');

		$this->behavior->beforeDelete($event, $entity, $options);

		$this->assertTrue($afterSoftDeleteCommitFired);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::beforeDelete()
	 */
	public function testBeforeDeleteNotDispatchesAfterSoftDeleteCommitWhenSoftDeleteFails(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);

		$afterSoftDeleteCommitFired = false;

		$this->table->getEventManager()->on('Model.afterSoftDeleteCommit', function () use (&$afterSoftDeleteCommitFired) {
			$afterSoftDeleteCommitFired = true;
		});

		// Mock the softDelete method to fail
		$this->behavior = $this->getMockBuilder(SoftDeleteBehavior::class)->setConstructorArgs([
			$this->table,
		])->onlyMethods(['softDelete'])->getMock();
		$this->behavior->expects($this->once())->method('softDelete')->willReturn(false);

		$options = new ArrayObject(['audit' => ['skip' => true]]);
		$event = new Event('Model.beforeDelete');

		$this->expectException(RuntimeException::class);

		$this->behavior->beforeDelete($event, $entity, $options);

		$this->assertFalse($afterSoftDeleteCommitFired);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::softDelete()
	 */
	public function testSoftDeleteDispatchesEvents(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);

		$beforeSoftDeleteFired = false;
		$afterSoftDeleteFired = false;

		$this->table->getEventManager()->on('Model.beforeSoftDelete', function () use (&$beforeSoftDeleteFired) {
			$beforeSoftDeleteFired = true;
		});

		$this->table->getEventManager()->on('Model.afterSoftDelete', function () use (&$afterSoftDeleteFired) {
			$afterSoftDeleteFired = true;
		});

		$options = new ArrayObject(['audit' => ['skip' => true]]);
		$event = new Event('Model.beforeDelete');

		$result = $this->behavior->softDelete($entity, $options, $event);

		$this->assertTrue($result);

		$this->assertTrue($beforeSoftDeleteFired);
		$this->assertTrue($afterSoftDeleteFired);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::softDelete()
	 */
	public function testSoftDeleteCleansEntity(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']);
		$entity->title = 'Updated Title';
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertFalse($entity->isDirty());

		$options = new ArrayObject(['audit' => ['skip' => true]]);
		$event = new Event('Model.beforeDelete');

		$this->behavior->softDelete($entity, $options, $event);

		// The entity should be cleaned
		$this->assertEmpty(
			$entity->getDirty()
		);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::softDelete()
	 */
	public function testSoftDeleteStopsWhenBeforeEventStopped(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']);
		$this->table->save($entity);

		// Stop the before event
		$this->table->getEventManager()->on('Model.beforeSoftDelete', function ($event) {
			$event->stopPropagation();
			$event->setResult(false);
		});

		$options = new ArrayObject();
		$event = new Event('Model.beforeDelete');
		$result = $this->behavior->softDelete($entity, $options, $event);

		$this->assertFalse($result);
		$this->assertFalse($entity->deleted);
		$this->assertTrue($event->isStopped());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::softDelete()
	 */
	public function testSoftDeleteThrowsExceptionWhenMissingPrimaryKey(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity']);

		$options = new ArrayObject(['audit' => ['skip' => true]]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Missing property `id`');

		$this->behavior->softDelete($entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::softDelete()
	 */
	public function testSoftDeleteReturnsFalseOnSaveFailure(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']);
		$entity->id = 123; // Set a primary key

		// Mock the save method to return false
		$this->behavior = $this->getMockBuilder(SoftDeleteBehavior::class)->setConstructorArgs([
			$this->table,
		])->onlyMethods(['softDelete'])->getMock();
		$this->behavior->expects($this->once())->method('softDelete')->willReturn(false);

		$options = new ArrayObject(['audit' => ['skip' => true]]);
		$event = new Event('Model.beforeDelete');

		$result = $this->behavior->softDelete($entity, $options, $event);

		$this->assertFalse($result);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::softDelete()
	 * @noinspection PhpFieldAssignmentTypeMismatchInspection
	 */
	public function testSoftDeleteDeletesCascadeAssociations(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);

		$childEntity = $this->table->newDefaultEntity([
			'title' => 'Child Entity',
			'parentId' => $entity->id,
			'languageShortcode' => 'de',
		]);
		$childResult = $this->table->save($childEntity);

		$this->assertNotFalse($childResult);

		$options = new ArrayObject(['audit' => ['skip' => true]]);
		$event = new Event('Model.beforeDelete');

		$result = $this->behavior->softDelete($entity, $options, $event);

		$this->assertTrue($result);

		/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
		$query = $this->table->find('withDeleted');
		$result = $query->all()->toArray();

		$this->assertCount(2, $result);

		$this->assertSame('Test Entity', $result[0]->title);
		$this->assertTrue($result[0]->deleted);
		$this->assertSame('Child Entity', $result[1]->title);
		$this->assertTrue($result[1]->deleted);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return void
	 */
	protected function markBeforeFindFired(SelectQuery $query): void {
		$reflection = new ReflectionClass($query);
		$property = $reflection->getProperty('_beforeFindFired');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($query, true);
	}
}
