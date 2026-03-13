<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Event\Backend\EmailTemplatesListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Model\Entity\EmailTemplate;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Queue\Model\Table\QueuedJobsTable;
use Symfony\Component\Process\Process;


/**
 * EmailTemplatesListener Test Case
 *
 * @see \Awyiss\Event\Backend\EmailTemplatesListener
 */
class EmailTemplatesListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\EmailTemplatesListener
	 */
	protected EmailTemplatesListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new EmailTemplatesListener();
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
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.EmailTemplates.beforeSave' => 'beforeSave',
			'Model.EmailTemplates.afterSaveCommit' => 'afterSaveCommit',
			'Model.EmailTemplates.afterSoftDelete' => 'afterSoftDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::beforeSave()
	 */
	public function testBeforeSaveWithNewEntity(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);

		$event = new Event('Model.EmailTemplates.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::beforeSave()
	 */
	public function testBeforeSaveWithNoFileNameChange(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$event = new Event('Model.EmailTemplates.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::beforeSave()
	 */
	public function testBeforeSaveWithSameFileName(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->fileName = 'new_template';
		$entity->fileName = 'test_template';

		$event = new Event('Model.EmailTemplates.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::beforeSave()
	 */
	public function testBeforeSaveWithFileNameChangeAndNoQueuedJob(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$entity->fileName = 'new_template';

		$event = new Event('Model.EmailTemplates.beforeSave');

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('EmailTemplates::fileChanges')->willReturn(false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertFalse($event->isStopped());
		$this->assertFalse($entity->hasErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::beforeSave()
	 */
	public function testBeforeSaveWithFileNameChangeAndQueuedJob(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$entity->fileName = 'new_template';

		$event = new Event('Model.EmailTemplates.beforeSave');

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['isQueued'])->getMock();

		$queueTable->expects($this->once())->method('isQueued')->with('EmailTemplates::fileChanges')->willReturn(true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->beforeSave($event, $entity);

		$this->assertTrue($event->isStopped());
		$this->assertTrue($entity->hasErrors());

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertSame(['EmailTemplates::file_changes_in_progress'], $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithNewEntity(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.EmailTemplates.afterSaveCommit');

		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/email/',
						'bin/cake bake template email_templates email_template test_template --prefix Frontend --controller email',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/email/test_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'EmailTemplates::fileChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithNewEntityAndExistingFolder(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.EmailTemplates.afterSaveCommit');

		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		$folderPath = TMP . 'test_templates/Frontend/email/';
		if (!is_dir($folderPath)) {
			mkdir($folderPath, 0755, true);
		}

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'bin/cake bake template email_templates email_template test_template --prefix Frontend --controller email',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/email/test_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'EmailTemplates::fileChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithNewEntityAndExistingFile(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.EmailTemplates.afterSaveCommit');

		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		$existingFilePath = TMP . 'test_templates/Frontend/email/test_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChange(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$entity->fileName = 'new_template';

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.EmailTemplates.afterSaveCommit');

		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/email/',
						'bin/cake bake template email_templates email_template new_template --prefix Frontend --controller email',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/email/new_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'EmailTemplates::fileChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithExistingEntityAndFileNameChangeAndExistingOldFile(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$entity->fileName = 'new_template';

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.EmailTemplates.afterSaveCommit');

		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		$existingFilePath = TMP . 'test_templates/Frontend/email/test_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) &&
					   $data['command'] === 'mv -f ' . TMP . 'test_templates/Frontend/email/test_template.twig ' . TMP . 'test_templates/Frontend/email/new_template.twig';
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'EmailTemplates::fileChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithCopyOption(): void {
		$entity = new EmailTemplate([
			'fileName' => 'test_template',
		]);
		$entity->clean();

		$entity->fileName = 'new_template';

		$options = new ArrayObject(['isCopy' => true]);
		$event = new Event('Model.EmailTemplates.afterSaveCommit');

		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/email/',
						'bin/cake bake template email_templates email_template new_template --prefix Frontend --controller email',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/email/new_template.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'EmailTemplates::fileChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitWithSpecialCharactersInFileName(): void {
		$entity = new EmailTemplate([
			'fileName' => 'Test Template With Spaces & Special-Chars!',
		]);

		$options = new ArrayObject(['isCopy' => false]);
		$event = new Event('Model.EmailTemplates.afterSaveCommit');

		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) && $data['command'] === implode(' && ', [
						'mkdir -m 0755 -p ' . TMP . 'test_templates/Frontend/email/',
						'bin/cake bake template email_templates email_template test_template_with_spaces_special_chars --prefix Frontend --controller email',
						'chmod 0755 ' . TMP . 'test_templates/Frontend/email/test_template_with_spaces_special_chars.twig',
					]);
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'EmailTemplates::fileChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSaveCommit($event, $entity, $options);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::afterSoftDelete()
	 */
	public function testAfterSoftDeleteWithExistingFile(): void {
		$entity = new EmailTemplate([
			'filename' => 'test_template',
		]);

		$event = new Event('Model.EmailTemplates.afterSoftDelete');

		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		$existingFilePath = TMP . 'test_templates/Frontend/email/test_template.twig';
		if (!is_dir(dirname($existingFilePath))) {
			mkdir(dirname($existingFilePath), 0755, true);
		}
		file_put_contents($existingFilePath, 'Existing content');

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function ($data) {
				return isset($data['command']) &&
					str_starts_with($data['command'], 'mv -f ' . TMP . 'test_templates/Frontend/email/test_template.twig ' . TMP . 'test_templates/Frontend/email/_deleted-test_template-') &&
					str_ends_with($data['command'], '.twig');
			}),
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'EmailTemplates::fileChanges',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSoftDelete($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\EmailTemplatesListener::afterSoftDelete()
	 */
	public function testAfterSoftDeleteWithoutExistingFile(): void {
		$entity = new EmailTemplate([
			'filename' => 'test_template',
		]);

		$event = new Event('Model.EmailTemplates.afterSoftDelete');

		Configure::write('App.paths.templates', [
			'customer' => TMP . 'test_templates/',
		]);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->never())->method('createJob');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$this->listener->afterSoftDelete($event, $entity);
	}
}
