<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Event\Backend\ContentTemplatesListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Queue\Model\Table\QueuedJobsTable;
use Symfony\Component\Process\Process;
use function str_ends_with;
use function str_starts_with;
use const DS;
use const TMP;


/**
 * ContentTemplatesListener Test Case
 *
 * @see \Awyiss\Event\Backend\ContentTemplatesListener
 */
class ContentTemplatesListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\ContentTemplatesListener
	 */
	protected ContentTemplatesListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new ContentTemplatesListener();
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

		new Process(['rm', '-rf', TMP . DS . 'test_templates'])->run();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.ContentTemplates.beforeMarshal' => 'beforeMarshal',
			'Model.ContentTemplates.beforeSave' => 'beforeSave',
			'Model.ContentTemplates.afterSaveCommit' => 'afterSaveCommit',
			'Model.ContentTemplates.afterSoftDelete' => 'afterSoftDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::beforeMarshal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalWithEmptyElements(): void {
		$data = new ArrayObject([]);
		$options = new ArrayObject();
		$event = new Event('Model.ContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		$this->assertArrayNotHasKey('content_template_elements', $data);

		$data = new ArrayObject(['content_template_elements' => null]);
		$options = new ArrayObject();
		$event = new Event('Model.ContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		$this->assertNull($data['content_template_elements']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::beforeMarshal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalWithTitleAndSubtitlePresent(): void {
		$elements = [
			['identifier' => 'title'],
			['identifier' => 'subtitle'],
			['identifier' => 'title_tag'],
			['identifier' => 'subtitle_tag'],
			['identifier' => 'other_element'],
		];

		$data = new ArrayObject(['content_template_elements' => $elements]);
		$options = new ArrayObject();
		$event = new Event('Model.ContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		// All elements should remain since title and subtitle are present
		$this->assertCount(5, $data['content_template_elements']);
		$identifiers = array_column($data['content_template_elements'], 'identifier');
		$this->assertContains('title', $identifiers);
		$this->assertContains('subtitle', $identifiers);
		$this->assertContains('title_tag', $identifiers);
		$this->assertContains('subtitle_tag', $identifiers);
		$this->assertContains('other_element', $identifiers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::beforeMarshal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalWithTitleButNoSubtitle(): void {
		$elements = [
			['identifier' => 'title'],
			['identifier' => 'title_tag'],
			['identifier' => 'subtitle_tag'],
			['identifier' => 'other_element'],
		];

		$data = new ArrayObject(['content_template_elements' => $elements]);
		$options = new ArrayObject();
		$event = new Event('Model.ContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		// subtitle_tag should be filtered out since subtitle is not present
		$this->assertCount(3, $data['content_template_elements']);
		$identifiers = array_column($data['content_template_elements'], 'identifier');
		$this->assertContains('title', $identifiers);
		$this->assertContains('title_tag', $identifiers);
		$this->assertNotContains('subtitle_tag', $identifiers);
		$this->assertContains('other_element', $identifiers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::beforeMarshal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalWithSubtitleButNoTitle(): void {
		$elements = [
			['identifier' => 'subtitle'],
			['identifier' => 'title_tag'],
			['identifier' => 'subtitle_tag'],
			['identifier' => 'other_element'],
		];

		$data = new ArrayObject(['content_template_elements' => $elements]);
		$options = new ArrayObject();
		$event = new Event('Model.ContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		// title_tag should be filtered out since title is not present
		$this->assertCount(3, $data['content_template_elements']);
		$identifiers = array_column($data['content_template_elements'], 'identifier');
		$this->assertContains('subtitle', $identifiers);
		$this->assertContains('subtitle_tag', $identifiers);
		$this->assertNotContains('title_tag', $identifiers);
		$this->assertContains('other_element', $identifiers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::beforeMarshal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalWithNeitherTitleNorSubtitle(): void {
		$elements = [
			['identifier' => 'title_tag'],
			['identifier' => 'subtitle_tag'],
			['identifier' => 'other_element'],
		];

		$data = new ArrayObject(['content_template_elements' => $elements]);
		$options = new ArrayObject();
		$event = new Event('Model.ContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		// Both title_tag and subtitle_tag should be filtered out
		$this->assertCount(1, $data['content_template_elements']);
		$identifiers = array_column($data['content_template_elements'], 'identifier');
		$this->assertNotContains('title_tag', $identifiers);
		$this->assertNotContains('subtitle_tag', $identifiers);
		$this->assertContains('other_element', $identifiers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithNoFileNameChange(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$event = new Event('Model.ContentTemplates.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithSameFileName(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';
		$entity->fileName = 'test_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$event = new Event('Model.ContentTemplates.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithFileNameChangeAndNoQueuedJob(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$event = new Event('Model.ContentTemplates.beforeSave');

		// Mock the queue table to return false for isQueued
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('content_templates::file_changes')->willReturn(false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithFileNameChangeAndQueuedJob(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$event = new Event('Model.ContentTemplates.beforeSave');

		// Mock the queue table to return false for isQueued
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('content_templates::file_changes')->willReturn(true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertTrue($event->isStopped());
		$this->assertTrue($entity->hasErrors());

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertSame(['content_templates::file_changes_in_progress'], $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithNewEntity(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure to return template paths
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/content/',
						'bin/cake bake template content_templates content_template test_template --prefix Frontend --controller content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/content/test_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithNewEntityAndExistingFile(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure to return template paths
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/content/test_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithExistingEntity(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';
		$entity->fileName = 'test_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure to return template paths
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/content/',
						'bin/cake bake template content_templates content_template test_template --prefix Frontend --controller content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/content/test_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithExistingEntityAndExistingFile(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';
		$entity->fileName = 'test_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure to return template paths
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/content/test_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChange(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/content/',
						'bin/cake bake template content_templates content_template new_template --prefix Frontend --controller content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/content/new_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChangeAndExistingOldFile(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/content/test_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'mv -f ' . TMP . 'test_templates/Frontend/content/test_template.twig ' . TMP . 'test_templates/Frontend/content/new_template.twig';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChangeAndExistingNewFile(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/content/new_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'bin/cake bake template content_templates content_template new_template --prefix Frontend --controller content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/content/new_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChangeAndExistingOldFileAndExistingNewFile(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the files to simulate existing files
		$existingOldFilePath = TMP . 'test_templates/Frontend/content/test_template.twig';
		if (!is_dir(dirname($existingOldFilePath))) {
			mkdir(dirname($existingOldFilePath), 0755, true);
		}
		file_put_contents($existingOldFilePath, 'Existing old content');

		$existingNewFilePath = TMP . 'test_templates/Frontend/content/new_template.twig';
		if (!is_dir(dirname($existingNewFilePath))) {
			mkdir(dirname($existingNewFilePath), 0755, true);
		}
		file_put_contents($existingNewFilePath, 'Existing new content');

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'mv -f ' . TMP . 'test_templates/Frontend/content/test_template.twig ' . TMP . 'test_templates/Frontend/content/new_template.twig';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithCopyOption(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => true]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/content/',
						'bin/cake bake template content_templates content_template test_template --prefix Frontend --controller content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/content/test_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSaveCommit()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveCommitWithCopyOptionAndExistingFile(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => true]);
		$event = new Event('Model.ContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/content/test_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSoftDelete()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSoftDeleteWithExistingFile(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$event = new Event('Model.ContentTemplates.afterSoftDelete');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/content/test_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) &&
					   str_starts_with(
						   $data['command'],
						   'mv -f ' . TMP . 'test_templates/Frontend/content/test_template.twig ' . TMP . 'test_templates/Frontend/content/_deleted-test_template-'
					   ) &&
					   str_ends_with($data['command'], '.twig');
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		// Mock file_exists to return true
		$this->listener->afterSoftDelete($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentTemplatesListener::afterSoftDelete()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSoftDeleteWithoutExistingFile(): void {
		$entity = $this->fetchTable('ContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$event = new Event('Model.ContentTemplates.afterSoftDelete');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		// Mock file_exists to return true
		$this->listener->afterSoftDelete($event, $entity);
	}
}
