<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Event\Backend\GlobalContentTemplatesListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Queue\Model\Table\QueuedJobsTable;
use Symfony\Component\Process\Process;


/**
 * GlobalContentTemplatesListener Test Case
 *
 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener
 */
class GlobalContentTemplatesListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\GlobalContentTemplatesListener
	 */
	protected GlobalContentTemplatesListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new GlobalContentTemplatesListener();
	}


	/**
	 * @inheritDoc
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
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.GlobalContentTemplates.beforeMarshal' => 'beforeMarshal',
			'Model.GlobalContentTemplates.beforeSave' => 'beforeSave',
			'Model.GlobalContentTemplates.afterSaveCommit' => 'afterSaveCommit',
			'Model.GlobalContentTemplates.afterSoftDelete' => 'afterSoftDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::beforeMarshal()
	 */
	public function testBeforeMarshalWithEmptyElements(): void {
		$data = new ArrayObject([]);
		$options = new ArrayObject();
		$event = new Event('Model.GlobalContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		$this->assertArrayNotHasKey('global_content_template_elements', $data);

		$data = new ArrayObject(['global_content_template_elements' => null]);
		$options = new ArrayObject();
		$event = new Event('Model.GlobalContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		$this->assertNull($data['global_content_template_elements']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::beforeMarshal()
	 */
	public function testBeforeMarshalWithTitleAndSubtitlePresent(): void {
		$elements = [
			['identifier' => 'title'],
			['identifier' => 'subtitle'],
			['identifier' => 'title_tag'],
			['identifier' => 'subtitle_tag'],
			['identifier' => 'other_element'],
		];

		$data = new ArrayObject(['global_content_template_elements' => $elements]);
		$options = new ArrayObject();
		$event = new Event('Model.GlobalContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		// All elements should remain since title and subtitle are present
		$this->assertCount(5, $data['global_content_template_elements']);
		$identifiers = array_column($data['global_content_template_elements'], 'identifier');
		$this->assertContains('title', $identifiers);
		$this->assertContains('subtitle', $identifiers);
		$this->assertContains('title_tag', $identifiers);
		$this->assertContains('subtitle_tag', $identifiers);
		$this->assertContains('other_element', $identifiers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::beforeMarshal()
	 */
	public function testBeforeMarshalWithTitleButNoSubtitle(): void {
		$elements = [
			['identifier' => 'title'],
			['identifier' => 'title_tag'],
			['identifier' => 'subtitle_tag'],
			['identifier' => 'other_element'],
		];

		$data = new ArrayObject(['global_content_template_elements' => $elements]);
		$options = new ArrayObject();
		$event = new Event('Model.GlobalContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		// subtitle_tag should be filtered out since subtitle is not present
		$this->assertCount(3, $data['global_content_template_elements']);
		$identifiers = array_column($data['global_content_template_elements'], 'identifier');
		$this->assertContains('title', $identifiers);
		$this->assertContains('title_tag', $identifiers);
		$this->assertNotContains('subtitle_tag', $identifiers);
		$this->assertContains('other_element', $identifiers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::beforeMarshal()
	 */
	public function testBeforeMarshalWithSubtitleButNoTitle(): void {
		$elements = [
			['identifier' => 'subtitle'],
			['identifier' => 'title_tag'],
			['identifier' => 'subtitle_tag'],
			['identifier' => 'other_element'],
		];

		$data = new ArrayObject(['global_content_template_elements' => $elements]);
		$options = new ArrayObject();
		$event = new Event('Model.GlobalContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		// title_tag should be filtered out since title is not present
		$this->assertCount(3, $data['global_content_template_elements']);
		$identifiers = array_column($data['global_content_template_elements'], 'identifier');
		$this->assertContains('subtitle', $identifiers);
		$this->assertContains('subtitle_tag', $identifiers);
		$this->assertNotContains('title_tag', $identifiers);
		$this->assertContains('other_element', $identifiers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::beforeMarshal()
	 */
	public function testBeforeMarshalWithNeitherTitleNorSubtitle(): void {
		$elements = [
			['identifier' => 'title_tag'],
			['identifier' => 'subtitle_tag'],
			['identifier' => 'other_element'],
		];

		$data = new ArrayObject(['global_content_template_elements' => $elements]);
		$options = new ArrayObject();
		$event = new Event('Model.GlobalContentTemplates.beforeMarshal');

		$this->listener->beforeMarshal($event, $data, $options);

		// Both title_tag and subtitle_tag should be filtered out
		$this->assertCount(1, $data['global_content_template_elements']);
		$identifiers = array_column($data['global_content_template_elements'], 'identifier');
		$this->assertNotContains('title_tag', $identifiers);
		$this->assertNotContains('subtitle_tag', $identifiers);
		$this->assertContains('other_element', $identifiers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::beforeSave()
	 */
	public function testBeforeSaveWithNoFileNameChange(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$event = new Event('Model.GlobalContentTemplates.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::beforeSave()
	 */
	public function testBeforeSaveWithSameFileName(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';
		$entity->fileName = 'test_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$event = new Event('Model.GlobalContentTemplates.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::beforeSave()
	 */
	public function testBeforeSaveWithFileNameChangeAndNoQueuedJob(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$event = new Event('Model.GlobalContentTemplates.beforeSave');

		// Mock the queue table to return false for isQueued
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('global_content_templates::file_changes')->willReturn(false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::beforeSave()
	 */
	public function testBeforeSaveWithFileNameChangeAndQueuedJob(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$event = new Event('Model.GlobalContentTemplates.beforeSave');

		// Mock the queue table to return false for isQueued
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('global_content_templates::file_changes')->willReturn(true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertTrue($event->isStopped());
		$this->assertTrue($entity->hasErrors());

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertSame(['global_content_templates::file_changes_in_progress'], $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithNewEntity(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

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
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/global_content/',
						'bin/cake bake template global_content_templates global_content_template test_template --prefix Frontend --controller global_content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/global_content/test_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'global_content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithNewEntityAndExistingFile(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

		// Mock Configure to return template paths
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/global_content/test_template.twig';
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
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntity(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';
		$entity->fileName = 'test_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

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
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/global_content/',
						'bin/cake bake template global_content_templates global_content_template test_template --prefix Frontend --controller global_content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/global_content/test_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'global_content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntityAndExistingFile(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';
		$entity->fileName = 'test_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

		// Mock Configure to return template paths
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/global_content/test_template.twig';
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
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChange(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

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
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/global_content/',
						'bin/cake bake template global_content_templates global_content_template new_template --prefix Frontend --controller global_content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/global_content/new_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'global_content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChangeAndExistingOldFile(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/global_content/test_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'mv -f ' . TMP . 'test_templates/Frontend/global_content/test_template.twig ' . TMP . 'test_templates/Frontend/global_content/new_template.twig';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'global_content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChangeAndExistingNewFile(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/global_content/new_template.twig';
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
						'bin/cake bake template global_content_templates global_content_template new_template --prefix Frontend --controller global_content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/global_content/new_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'global_content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChangeAndExistingOldFileAndExistingNewFile(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';

		$this->assertTrue($entity->isDirty('fileName'));

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the files to simulate existing files
		$existingOldFilePath = TMP . 'test_templates/Frontend/global_content/test_template.twig';
		if (!is_dir(dirname($existingOldFilePath))) {
			mkdir(dirname($existingOldFilePath), 0755, true);
		}
		file_put_contents($existingOldFilePath, 'Existing old content');

		$existingNewFilePath = TMP . 'test_templates/Frontend/global_content/new_template.twig';
		if (!is_dir(dirname($existingNewFilePath))) {
			mkdir(dirname($existingNewFilePath), 0755, true);
		}
		file_put_contents($existingNewFilePath, 'Existing new content');

		// Mock the queue table
		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === 'mv -f ' . TMP . 'test_templates/Frontend/global_content/test_template.twig ' . TMP . 'test_templates/Frontend/global_content/new_template.twig';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'global_content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithCopyOption(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => true]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

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
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/global_content/',
						'bin/cake bake template global_content_templates global_content_template test_template --prefix Frontend --controller global_content',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/global_content/test_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'global_content_templates::file_changes',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithCopyOptionAndExistingFile(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => true]);
		$event = new Event('Model.GlobalContentTemplates.afterSaveCommit');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/global_content/test_template.twig';
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
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSoftDelete()
	 */
	public function testAfterSoftDeleteWithExistingFile(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$event = new Event('Model.GlobalContentTemplates.afterSoftDelete');

		// Mock Configure
		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		// Create the file to simulate existing file
		$existingFilePath = TMP . 'test_templates/Frontend/global_content/test_template.twig';
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
					str_starts_with($data['command'], 'mv -f ' . TMP . 'test_templates/Frontend/global_content/test_template.twig ' . TMP . 'test_templates/Frontend/global_content/_deleted-test_template-') &&
					str_ends_with($data['command'], '.twig');
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'global_content_templates::file_changes',
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
	 * @see \Awyiss\Event\Backend\GlobalContentTemplatesListener::afterSoftDelete()
	 */
	public function testAfterSoftDeleteWithoutExistingFile(): void {
		$entity = $this->fetchTable('GlobalContentTemplates')->newDefaultEntity([
			'fileName' => 'test_template',
		]);

		$event = new Event('Model.GlobalContentTemplates.afterSoftDelete');

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
