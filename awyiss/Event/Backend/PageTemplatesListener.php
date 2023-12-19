<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;


class PageTemplatesListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
	 */
	public function implementedEvents (): array {
		return [
			'Model.PageTemplates.beforeSave' => 'beforeSave',
			'Model.PageTemplates.afterSaveCommit' => 'afterSaveCommit',
			'Model.PageTemplates.afterSoftDelete' => 'afterSoftDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 *
	 * @return void
	 */
	public function beforeSave (Event $ao_event, EntityInterface $ao_entity): void {
		if ($ao_entity->filename != $ao_entity->getOriginal('filename')) {
			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');

			if ($lo_queue->isQueued('page_templates::file_changes', 'Queue.Execute')) {
				$ao_event->stopPropagation();
				$ao_entity->setError('_general', __('page_templates::file_changes_in_progress'));
			}
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\PageTemplate $ao_entity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection DuplicatedCode
	 */
	public function afterSaveCommit (Event $ao_event, EntityInterface $ao_entity): void {
		$ls_fileName = \Cake\Utility\Text::slug($ao_entity->filename, ['replacement' => '_']);
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = \Cake\Core\Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths['customer'] . 'Frontend' . DS . 'pages' . DS;


		$la_commands = [];
		$lb_fileExists = FALSE;


		if ( ! file_exists($ls_folderPath)) {
			$la_commands[] = 'mkdir -m 777 -p ' . $ls_folderPath;
			//mkdir($ls_folderPath, 0777, TRUE);
		}

		$ls_filePath = $ls_folderPath . $ls_fileName . $ls_extension;

		if ($ao_entity->filename != $ao_entity->getOriginal('filename')) {
			//When renaming a file, we need to make sure that the new filename isn't already in use
			//I do no longer know why I would rename an exisitng file
			/*if (file_exists($ls_filePath)) {
				$ls_newFilePath = $ls_filePath;
				while (file_exists($ls_newFilePath)) {
					$ls_newFilePath = $ls_folderPath . $ls_fileName . '-' . (new \Cake\I18n\FrozenTime())->format('Ymd-His') . $ls_extension;
				}
				$la_commands[] = 'mv ' . $ls_filePath . ' ' . $ls_newFilePath;
				//rename($ls_filePath, $ls_newFilePath);
			}*/

			//After changing the filename in the database, we also need to move (read: rename) the existing file
			$ls_currentFilename = \Cake\Utility\Text::slug($ao_entity->getOriginal('filename'), ['replacement' => '_']);
			$ls_currentFilePath = $ls_folderPath . $ls_currentFilename . $ls_extension;
			if ($lb_fileExists = file_exists($ls_currentFilePath)) {
				$la_commands[] = 'mv ' . $ls_currentFilePath . ' ' . $ls_filePath;
				//rename($ls_currentFilePath, $ls_filePath);
			}
		}

		//If the file does not exist, we create one based on a twig-template for frontent page templates
		if (!$lb_fileExists) {
			$la_commands[] = 'bin/cake bake template pages page_template ' . $ls_fileName . ' --prefix Frontend --controller pages && chmod 0777 ' . $ls_filePath;
		}

		if (!empty($la_commands)) {
			$la_data = [
				'command' => implode(' && ', array_map('escapeshellcmd', $la_commands)),
				'escape' => FALSE,
			];

			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
			$lo_queue->createJob('Queue.Execute', $la_data, ['reference' => 'page_templates::file_changes', 'priority' => 1]);
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\PageTemplate $ao_entity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete (Event $ao_event, EntityInterface $ao_entity): void {
		$ls_fileName = \Cake\Utility\Text::slug($ao_entity->filename, ['replacement' => '_']);;
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = \Cake\Core\Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths['customer'] . 'Frontend' . DS . 'pages' . DS;

		$ls_filePath = $ls_folderPath . $ls_fileName . $ls_extension;

		if (file_exists($ls_filePath)) {
			$ls_newFilePath = $ls_filePath;
			while (file_exists($ls_newFilePath)) {
				$ls_newFilePath = $ls_folderPath . '_deleted-' . $ls_fileName . '-' . (new \Cake\I18n\FrozenTime())->getTimestamp() . $ls_extension;
			}
			//rename($ls_filePath, $ls_newFilePath);

			$la_data = [
				'command' => 'mv ' . $ls_filePath . ' ' . $ls_newFilePath,
			];

			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
			$lo_queue->createJob('Queue.Execute', $la_data, ['reference' => 'page_templates::file_changes', 'priority' => 1]);
		}
	}
}