<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\ContentTemplate;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\Utility\Text;


/**
 * Event listeners for the ContentTemplates scope of the backend
 */
class ContentTemplatesListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.ContentTemplates.beforeSave' => 'beforeSave',
			'Model.ContentTemplates.afterSaveCommit' => 'afterSaveCommit',
			'Model.ContentTemplates.afterSoftDelete' => 'afterSoftDelete',
		];
	}


	/**
	 * If the filename of a content templates has changed,
	 * check the QueuedJobs table for jobs with the identifier 'content_templates::file_changes'.
	 *
	 * If such an active job exists, stop the save event and return an error.
	 *
	 * This is neccesary since a second file rename job could interfere with the first one.
	 *
	 * @param Event $event
	 * @param ContentTemplate $entity
	 * @return void
	 */
	public function beforeSave(Event $event, ContentTemplate $entity): void {
		if ($entity->hasOriginal('fileName') && $entity->fileName != $entity->getOriginal('fileName')) {
			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');

			if ($lo_queue->isQueued('content_templates::file_changes')) {
				$event->stopPropagation();
				$entity->setError('_general', __d('content_templates', 'file_changes_in_progress'));
			}
		}
	}


	/**
	 * After saving a content template entity
	 *
	 * - create a template if it's new
	 * - rename the template if it already exists
	 *
	 * @param Event $event
	 * @param ContentTemplate $entity
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection DuplicatedCode
	 */
	public function afterSaveCommit(Event $event, ContentTemplate $entity): void {
		$ls_fileName = Text::slug($entity->fileName, ['replacement' => '_']);
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths['customer'] . 'Frontend' . DS . 'content' . DS;


		$la_commands = [];

		if (!file_exists($ls_folderPath)) {
			$la_commands[] = 'mkdir -m 0750 -p ' . $ls_folderPath;
		}

		$ls_filePath = $ls_folderPath . $ls_fileName . $ls_extension;

		if ($entity->hasOriginal('fileName') && $entity->fileName != $entity->getOriginal('fileName')) {
			//After changing the filename in the database, we also need to move (read: rename) the existing file
			$ls_currentFileName = Text::slug($entity->getOriginal('fileName'), ['replacement' => '_']);
			$ls_currentFilePath = $ls_folderPath . $ls_currentFileName . $ls_extension;
			$lb_fileExists = file_exists($ls_currentFilePath);
			if ($lb_fileExists) {
				$la_commands[] = 'mv ' . $ls_currentFilePath . ' ' . $ls_filePath;
			}
		}
		else {
			$lb_fileExists = file_exists($ls_filePath);
		}

		//If the file does not exist, we create one based on a twig-template for frontent content templates
		if (!$lb_fileExists) {
			$la_commands[] = 'bin/cake bake template content_templates content_template ' . $ls_fileName . ' --prefix Frontend --controller content';
			$la_commands[] = 'chmod 0750 ' . $ls_filePath;
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
				'reference' => 'content_templates::file_changes',
			]);
		}
	}


	/**
	 * After deleting a content template entity, rename the existing file:
	 * - prepend '_deleted-'
	 * - append '-' and the current timestamp
	 *
	 * @param Event $event
	 * @param ContentTemplate $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $event, ContentTemplate $entity): void {
		$ls_fileName = Text::slug($entity->fileName, ['replacement' => '_']);
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths['customer'] . 'Frontend' . DS . 'content' . DS;

		$ls_filePath = $ls_folderPath . $ls_fileName . $ls_extension;

		if (file_exists($ls_filePath)) {
			$ls_newFilePath = $ls_filePath;
			while (file_exists($ls_newFilePath)) {
				$ls_newFilePath = $ls_folderPath . '_deleted-' . $ls_fileName . '-' . (new DateTime())->getTimestamp() . $ls_extension;
			}

			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			$lo_queue->createJob('Queue.Execute', [
				'command' => 'mv ' . $ls_filePath . ' ' . $ls_newFilePath,
				'log' => true,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'content_templates::file_changes',
			]);
		}
	}
}
