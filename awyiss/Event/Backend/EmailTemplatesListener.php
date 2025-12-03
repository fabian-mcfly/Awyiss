<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Model\Entity\EmailTemplate;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\Utility\Text;


/**
 * Event listeners for the EmailTemplates scope of the backend
 */
class EmailTemplatesListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.EmailTemplates.beforeSave' => 'beforeSave',
			'Model.EmailTemplates.afterSaveCommit' => 'afterSaveCommit',
			'Model.EmailTemplates.afterSoftDelete' => 'afterSoftDelete',
		];
	}


	/**
	 * If the filename of an email templates has changed,
	 * check the QueuedJobs table for jobs with the identifier 'email_templates::file_changes'.
	 * If such an active job exists, stop the save event and return an error.
	 * This is necessary since a second file rename job could interfere with the first one.
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\EmailTemplate $entity
	 * @return void
	 */
	public function beforeSave(Event $event, EmailTemplate $entity): void {
		if ($entity->hasOriginal('fileName') && $entity->fileName != $entity->getOriginal('fileName')) {
			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');

			if ($queuedJobsTable->isQueued('email_templates::file_changes')) {
				$event->stopPropagation();
				$entity->setError('_general', __d('email_templates', 'file_changes_in_progress'));
			}
		}
	}


	/**
	 * After saving an email template entity
	 * - create a template if it's new
	 * - rename the template if it already exists
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\EmailTemplate $entity
	 * @param \ArrayObject $options
	 * @noinspection DuplicatedCode, PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, EmailTemplate $entity, ArrayObject $options): void {
		$fileName = Text::slug($entity->get('fileName'), ['replacement' => '_']);
		$fileName = trim($fileName, '_');
		$extension = '.twig';

		$templatePaths = Configure::read('App.paths.templates');
		$folderPath = $templatePaths['customer'] . 'Frontend' . DS . 'email' . DS;

		$commands = [];

		if (!file_exists($folderPath)) {
			$commands[] = 'mkdir -m 0755 -p ' . $folderPath;
		}

		$filePath = $folderPath . $fileName . $extension;

		if (!($options['isCopy'] ?? false) && $entity->hasOriginal('fileName') && $entity->get('fileName') != $entity->getOriginal('fileName')) {
			//After changing the filename in the database, we also need to move (read: rename) the existing file
			$currentFileName = Text::slug($entity->getOriginal('fileName'), ['replacement' => '_']);
			$currentFilePath = $folderPath . $currentFileName . $extension;
			$fileExists = file_exists($currentFilePath);
			if ($fileExists) {
				$commands[] = 'mv -f ' . $currentFilePath . ' ' . $filePath;
			}
		}
		else {
			$fileExists = file_exists($filePath);
		}

		//If the file does not exist, we create one based on a twig-template for frontend email templates
		if (!$fileExists) {
			$commands[] = 'bin' . DS . 'cake bake template email_templates email_template ' . $fileName . ' --prefix Frontend --controller email';
			$commands[] = 'chmod 0755 ' . $filePath;
		}

		if (!empty($commands)) {
			$data = [
				'command' => implode(' && ', array_map('escapeshellcmd', $commands)),
				'escape' => false,
				'log' => true,
			];

			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			$queuedJobsTable->createJob('Queue.Execute', $data, [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'email_templates::file_changes',
			]);
		}
	}


	/**
	 * After deleting an email template entity, rename the existing file:
	 * - prepend '_deleted-'
	 * - append '-' and the current timestamp
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\EmailTemplate $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $event, EmailTemplate $entity): void {
		$fileName = Text::slug($entity->get('filename'), ['replacement' => '_']);
		$fileName = trim($fileName, '_');
		$extension = '.twig';

		$templatePaths = Configure::read('App.paths.templates');
		$folderPath = $templatePaths['customer'] . 'Frontend' . DS . 'email' . DS;

		$filePath = $folderPath . $fileName . $extension;

		if (file_exists($filePath)) {
			$newFilePath = $filePath;
			while (file_exists($newFilePath)) {
				$newFilePath = $folderPath . '_deleted-' . $fileName . '-' . new DateTime()->getTimestamp() . $extension;
			}

			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			$queuedJobsTable->createJob('Queue.Execute', [
				'command' => 'mv -f ' . $filePath . ' ' . $newFilePath,
				'log' => true,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'email_templates::file_changes',
			]);
		}
	}
}
