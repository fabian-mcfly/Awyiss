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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new AttributesListener();
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalWithInputList(): void {
		$data = new ArrayObject([
			'input_type' => 'input_list',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalWithInputKeyValueList(): void {
		$data = new ArrayObject([
			'input_type' => 'input_key_value_list',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalWithOtherInputType(): void {
		$data = new ArrayObject([
			'input_type' => 'text',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithContentsScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'contents',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithWidgetsScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'widgets',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithPagesScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'pages',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithMenuEntriesScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'menu_entries',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithNewsScope(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'news',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithQueuedJob(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'users',
			'identifier' => 'test_field',
			'type' => 'string',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('attributes::table_changes')->willReturn(true);

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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithoutQueuedJob(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'users',
			'identifier' => 'test_field',
			'type' => 'string',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('attributes::table_changes')->willReturn(false);

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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithNewEntityAndRelevantChanges(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'users',
			'identifier' => 'test_field',
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
							'scope' => 'users',
							'identifier' => 'test_field',
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
				'reference' => 'attributes::table_changes',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithExistingEntityAndNoChanges(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'users',
			'identifier' => 'test_field',
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithExistingEntityAndRelevantChanges(): void {
		$entity = $this->fetchTable('Attributes')->newDefaultEntity([
			'scope' => 'users',
			'identifier' => 'test_field',
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
							'scope' => 'users',
							'identifier' => 'test_field',
							'type' => 'string',
							'hasIndex' => true,
							'required' => false,
							'defaultValue' => null,
							'deleted' => false,
						],
						'new' => [
							'scope' => 'users',
							'identifier' => 'test_field',
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
				'reference' => 'attributes::table_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Attributes.afterSaveCommit', $this->fetchTable('Attributes'));
		$this->listener->afterSaveCommit($event, $entity);
	}
}
