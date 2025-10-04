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
			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');

			if ($lo_queue->isQueued('email_templates::file_changes')) {
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
		$ls_fileName = Text::slug($entity->get('fileName'), ['replacement' => '_']);
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths['customer'] . 'Frontend' . DS . 'email' . DS;


		$la_commands = [];

		if (!file_exists($ls_folderPath)) {
			$la_commands[] = 'mkdir -m 0755 -p ' . $ls_folderPath;
		}

		$ls_filePath = $ls_folderPath . $ls_fileName . $ls_extension;

		if (!($options['isCopy'] ?? false) && $entity->hasOriginal('fileName') && $entity->get('fileName') != $entity->getOriginal('fileName')) {
			//After changing the filename in the database, we also need to move (read: rename) the existing file
			$ls_currentFileName = Text::slug($entity->getOriginal('fileName'), ['replacement' => '_']);
			$ls_currentFilePath = $ls_folderPath . $ls_currentFileName . $ls_extension;
			$lb_fileExists = file_exists($ls_currentFilePath);
			if ($lb_fileExists) {
				$la_commands[] = 'mv -f ' . $ls_currentFilePath . ' ' . $ls_filePath;
			}
		}
		else {
			$lb_fileExists = file_exists($ls_filePath);
		}

		//If the file does not exist, we create one based on a twig-template for frontend email templates
		if (!$lb_fileExists) {
			$la_commands[] = 'bin' . DS . 'cake bake template email_templates email_template ' . $ls_fileName . ' --prefix Frontend --controller email';
			$la_commands[] = 'chmod 0755 ' . $ls_filePath;
		}

		if (!empty($la_commands)) {
			$la_data = [
				'command' => implode(' && ', array_map('escapeshellcmd', $la_commands)),
				'escape' => false,
				'log' => true,
			];

			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			$lo_queue->createJob('Queue.Execute', $la_data, [
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
		$ls_fileName = Text::slug($entity->get('filename'), ['replacement' => '_']);
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths['customer'] . 'Frontend' . DS . 'email' . DS;

		$ls_filePath = $ls_folderPath . $ls_fileName . $ls_extension;

		if (file_exists($ls_filePath)) {
			$ls_newFilePath = $ls_filePath;
			while (file_exists($ls_newFilePath)) {
				$ls_newFilePath = $ls_folderPath . '_deleted-' . $ls_fileName . '-' . new DateTime()->getTimestamp() . $ls_extension;
			}

			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			$lo_queue->createJob('Queue.Execute', [
				'command' => 'mv -f ' . $ls_filePath . ' ' . $ls_newFilePath,
				'log' => true,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'email_templates::file_changes',
			]);
		}
	}
}
