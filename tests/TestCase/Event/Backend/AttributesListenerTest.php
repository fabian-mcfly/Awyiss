<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Event\Backend\AttributesListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Queue\Model\Table\QueuedJobsTable;


/**
 * AttributesListener Test Case
 *
 * @see \Awyiss\Event\Backend\AttributesListener
 */
class AttributesListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\AttributesListener
	 */
	protected AttributesListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new AttributesListener();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Attributes.beforeMarshal' => 'beforeMarshal',
			'Model.Attributes.beforeSave' => 'beforeSave',
			'Model.Attributes.afterSaveCommit' => 'afterSaveCommit',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeMarshal()
	 */
	public function testBeforeMarshalWithInputList(): void {
		$data = new ArrayObject([
			'inputType' => 'inputList',
			'type' => 'string',
			'required' => true,
		]);

		$options = new ArrayObject();

		$event = new Event('Model.Attributes.beforeMarshal', $this->fetchTable('Attributes'));
		$this->listener->beforeMarshal($event, $data, $options);

		$this->assertSame('json', $data['type']);
		$this->assertFalse($data['required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeMarshal()
	 */
	public function testBeforeMarshalWithInputKeyValueList(): void {
		$data = new ArrayObject([
			'inputType' => 'inputKeyValueList',
			'type' => 'string',
			'required' => true,
		]);

		$options = new ArrayObject();

		$event = new Event('Model.Attributes.beforeMarshal', $this->fetchTable('Attributes'));
		$this->listener->beforeMarshal($event, $data, $options);

		$this->assertSame('json', $data['type']);
		$this->assertFalse($data['required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeMarshal()
	 */
	public function testBeforeMarshalWithOtherInputType(): void {
		$data = new ArrayObject([
			'inputType' => 'text',
			'type' => 'string',
			'required' => true,
		]);
		$options = new ArrayObject();

		$event = new Event('Model.Attributes.beforeMarshal', $this->fetchTable('Attributes'));
		$this->listener->beforeMarshal($event, $data, $options);

		// Should not modify data for other input types
		$this->assertSame('string', $data['type']);
		$this->assertTrue($data['required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeSave()
	 */
	public function testBeforeSaveWithContentsScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'Contents',
			'fieldset' => 'general',
			'required' => true,
			'translatable' => true,
		]);

		$event = new Event('Model.Attributes.beforeSave', $this->fetchTable('Attributes'));
		$this->listener->beforeSave($event, $entity);

		$this->assertSame('', $entity->fieldset);
		$this->assertFalse($entity->required);
		$this->assertFalse($entity->translatable);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeSave()
	 */
	public function testBeforeSaveWithGlobalContentsScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'GlobalContents',
			'fieldset' => 'general',
			'required' => true,
			'translatable' => true,
		]);

		$event = new Event('Model.Attributes.beforeSave', $this->fetchTable('Attributes'));
		$this->listener->beforeSave($event, $entity);

		$this->assertSame('', $entity->fieldset);
		$this->assertFalse($entity->required);
		$this->assertTrue($entity->translatable);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeSave()
	 */
	public function testBeforeSaveWithPagesScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'Pages',
			'fieldset' => 'general',
			'required' => true,
			'translatable' => true,
		]);

		$event = new Event('Model.Attributes.beforeSave', $this->fetchTable('Attributes'));
		$this->listener->beforeSave($event, $entity);

		$this->assertSame('general', $entity->fieldset);
		$this->assertTrue($entity->required);
		$this->assertFalse($entity->translatable);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeSave()
	 */
	public function testBeforeSaveWithMenuEntriesScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'MenuEntries',
			'fieldset' => 'general',
			'required' => true,
			'translatable' => true,
		]);

		$event = new Event('Model.Attributes.beforeSave', $this->fetchTable('Attributes'));
		$this->listener->beforeSave($event, $entity);

		$this->assertSame('general', $entity->fieldset);
		$this->assertTrue($entity->required);
		$this->assertFalse($entity->translatable);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeSave()
	 */
	public function testBeforeSaveWithNewsScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'News',
			'fieldset' => 'general',
			'required' => true,
			'translatable' => true,
		]);

		$event = new Event('Model.Attributes.beforeSave', $this->fetchTable('Attributes'));
		$this->listener->beforeSave($event, $entity);

		$this->assertSame('general', $entity->fieldset);
		$this->assertTrue($entity->required);
		$this->assertFalse($entity->translatable);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeSave()
	 */
	public function testBeforeSaveWithQueuedJob(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'Users',
			'identifier' => 'testField',
			'type' => 'string',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('Attributes::tableChanges')->willReturn(true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Attributes.beforeSave', $this->fetchTable('Attributes'));

		$this->listener->beforeSave($event, $entity);

		$this->assertTrue($event->isStopped());
		$this->assertTrue($entity->hasErrors());
		$this->assertArrayHasKey('_general', $entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::beforeSave()
	 */
	public function testBeforeSaveWithoutQueuedJob(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'Users',
			'identifier' => 'testField',
			'type' => 'string',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('Attributes::tableChanges')->willReturn(false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Attributes.beforeSave', $this->fetchTable('Attributes'));

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithNewEntityAndRelevantChanges(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'Users',
			'identifier' => 'testField',
			'type' => 'string',
			'hasIndex' => true,
			'required' => false,
			'defaultValue' => null,
			'deleted' => false,
		]);
		$entity->id = 123;
		$entity->setNew(true);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Attributes/Upsert',
			$this->callback(function (array $data): bool {
				return $data === [
						'id' => 123,
						'old' => [
							'scope' => null,
							'identifier' => null,
							'type' => null,
							'hasIndex' => null,
							'required' => null,
							'defaultValue' => null,
							'deleted' => null,
						],
						'new' => [
							'scope' => 'Users',
							'identifier' => 'testField',
							'type' => 'string',
							'hasIndex' => true,
							'required' => false,
							'defaultValue' => null,
							'deleted' => false,
						],
					];
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'Attributes::tableChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Attributes.afterSaveCommit', $this->fetchTable('Attributes'));
		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntityAndNoChanges(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'Users',
			'identifier' => 'testField',
			'type' => 'string',
			'hasIndex' => true,
			'required' => false,
			'defaultValue' => null,
			'deleted' => false,
		]);
		$entity->id = 123;
		$entity->setNew(false);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->identifier = 'test_field2';
		$entity->identifier = 'test_field';

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Attributes.afterSaveCommit', $this->fetchTable('Attributes'));
		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntityAndRelevantChanges(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'Users',
			'identifier' => 'testField',
			'type' => 'string',
			'hasIndex' => true,
			'required' => false,
			'defaultValue' => null,
			'deleted' => false,
		]);
		$entity->id = 123;
		$entity->setNew(false);
		$entity->clean();

		// Make a relevant change
		$entity->type = 'text';
		$entity->required = true;

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Attributes/Upsert',
			$this->callback(function (array $data) use ($entity): bool {
				return $data === [
						'id' => 123,
						'old' => [
							'scope' => 'Users',
							'identifier' => 'testField',
							'type' => 'string',
							'hasIndex' => true,
							'required' => false,
							'defaultValue' => null,
							'deleted' => false,
						],
						'new' => [
							'scope' => 'Users',
							'identifier' => 'testField',
							'type' => 'text',
							'hasIndex' => true,
							'required' => true,
							'defaultValue' => null,
							'deleted' => false,
						],
					];
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'Attributes::tableChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Attributes.afterSaveCommit', $this->fetchTable('Attributes'));
		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * Test that template element identifiers are updated when an attribute with Contents scope is renamed
	 *
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\AttributesListener::updateTemplateElementIdentifiers()
	 */
	public function testAfterSaveCommitUpdatesContentTemplateElementIdentifiersOnRename(): void {
		$attributesTable = $this->fetchTable('Attributes');
		$templateElementsTable = $this->fetchTable('ContentTemplateElements');

		// Create an existing attribute with Contents scope
		$entity = $attributesTable->newDefaultEntity([
			'scope' => 'Contents',
			'identifier' => 'oldIdentifier',
			'type' => 'string',
			'hasIndex' => false,
			'required' => false,
			'defaultValue' => null,
			'deleted' => false,
		]);
		$entity->id = 999;
		$entity->setNew(false);
		$entity->clean();

		// Rename the identifier
		$entity->identifier = 'newIdentifier';

		// Mock QueuedJobsTable
		$queueTable = $this
			->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->set('Attributes', $attributesTable);
		$tableLocator->set('ContentTemplateElements', $templateElementsTable);

		// Verify the template element exists with old identifier
		$beforeElement = $templateElementsTable
			->find()->where(['identifier' => 'attributes.oldIdentifier'])->first();
		$this->assertNotNull($beforeElement, 'Template element with old identifier should exist');

		$event = new Event('Model.Attributes.afterSaveCommit', $attributesTable);
		$this->listener->afterSaveCommit($event, $entity);

		// Verify the template element identifier was updated
		$afterElementOld = $templateElementsTable
			->find()->where(['identifier' => 'attributes.oldIdentifier'])->first();
		$this->assertNull($afterElementOld, 'Template element with old identifier should not exist');

		$afterElementNew = $templateElementsTable
			->find()->where(['identifier' => 'attributes.newIdentifier'])->first();
		$this->assertNotNull($afterElementNew, 'Template element with new identifier should exist');
		$this->assertSame(200, $afterElementNew->id);
	}


	/**
	 * Test that template element identifiers are updated when an attribute with GlobalContents scope is renamed
	 *
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\AttributesListener::updateTemplateElementIdentifiers()
	 */
	public function testAfterSaveCommitUpdatesGlobalContentTemplateElementIdentifiersOnRename(): void {
		$attributesTable = $this->fetchTable('Attributes');
		$templateElementsTable = $this->fetchTable('GlobalContentTemplateElements');

		// Create an existing attribute with GlobalContents scope
		$entity = $attributesTable->newDefaultEntity([
			'scope' => 'GlobalContents',
			'identifier' => 'oldGlobalIdentifier',
			'type' => 'string',
			'hasIndex' => false,
			'required' => false,
			'defaultValue' => null,
			'deleted' => false,
		]);
		$entity->id = 998;
		$entity->setNew(false);
		$entity->clean();

		// Rename the identifier
		$entity->identifier = 'newGlobalIdentifier';

		// Mock QueuedJobsTable
		$queueTable = $this
			->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->set('Attributes', $attributesTable);
		$tableLocator->set('GlobalContentTemplateElements', $templateElementsTable);

		// Verify the template element exists with old identifier
		$beforeElement = $templateElementsTable
			->find()->where(['identifier' => 'attributes.oldGlobalIdentifier'])->first();
		$this->assertNotNull($beforeElement, 'Template element with old identifier should exist');

		$event = new Event('Model.Attributes.afterSaveCommit', $attributesTable);
		$this->listener->afterSaveCommit($event, $entity);

		// Verify the template element identifier was updated
		$afterElementOld = $templateElementsTable
			->find()->where(['identifier' => 'attributes.oldGlobalIdentifier'])->first();
		$this->assertNull($afterElementOld, 'Template element with old identifier should not exist');

		$afterElementNew = $templateElementsTable
			->find()->where(['identifier' => 'attributes.newGlobalIdentifier'])->first();
		$this->assertNotNull($afterElementNew, 'Template element with new identifier should exist');
		$this->assertSame(200, $afterElementNew->id);
	}


	/**
	 * Test that template element identifiers are NOT updated when an attribute with other scope is renamed
	 *
	 * @return void
	 * @see \Awyiss\Event\Backend\AttributesListener::afterSaveCommit()
	 * @see \Awyiss\Event\Backend\AttributesListener::updateTemplateElementIdentifiers()
	 */
	public function testAfterSaveCommitDoesNotUpdateTemplateElementIdentifiersForOtherScopes(): void {
		$attributesTable = $this->fetchTable('Attributes');

		// Create an existing attribute with Users scope (not Contents or GlobalContents)
		$entity = $attributesTable->newDefaultEntity([
			'scope' => 'Users',
			'identifier' => 'someIdentifier',
			'type' => 'string',
			'hasIndex' => false,
			'required' => false,
			'defaultValue' => null,
			'deleted' => false,
		]);
		$entity->id = 997;
		$entity->setNew(false);
		$entity->clean();

		// Rename the identifier
		$entity->identifier = 'renamedIdentifier';

		// Mock QueuedJobsTable
		$queueTable = $this
			->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);
		$tableLocator->set('Attributes', $attributesTable);

		$event = new Event('Model.Attributes.afterSaveCommit', $attributesTable);
		$this->listener->afterSaveCommit($event, $entity);

		// The test passes if no exception is thrown and the method completes
		// Template element tables should not be accessed for non-Contents/GlobalContents scopes
		$this->assertTrue(true);
	}
}
