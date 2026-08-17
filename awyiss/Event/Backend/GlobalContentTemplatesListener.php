<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Model\Entity\GlobalContentTemplate;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\Utility\Hash;
use Cake\Utility\Text;


/**
 * Event listeners for the GlobalContentTemplates scope of the backend
 */
class GlobalContentTemplatesListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.GlobalContentTemplates.beforeMarshal' => 'beforeMarshal',
			'Model.GlobalContentTemplates.beforeSave' => 'beforeSave',
			'Model.GlobalContentTemplates.afterSaveCommit' => 'afterSaveCommit',
			'Model.GlobalContentTemplates.afterSoftDelete' => 'afterSoftDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options): void {
		if (empty($data['globalContentTemplateElements'])) {
			return;
		}

		$elements = $data['globalContentTemplateElements'];
		$hasTitle = Hash::check($elements, '{n}[identifier=title]');
		$hasSubtitle = Hash::check($elements, '{n}[identifier=subtitle]');

		/**
		 * Filter out the titleTag and subtitleTag elements when the title and subtitle are not present
		 */
		$data['globalContentTemplateElements'] = array_filter($elements, function ($element) use ($hasTitle, $hasSubtitle) {
			if ($element['identifier'] == 'titleTag' && !$hasTitle) {
				return false;
			}

			if ($element['identifier'] == 'subtitleTag' && !$hasSubtitle) {
				return false;
			}

			return true;
		});
	}


	/**
	 * If the filename of a content templates has changed,
	 * check the QueuedJobs table for jobs with the identifier 'GlobalContentTemplates::fileChanges'.
	 * If such an active job exists, stop the save event and return an error.
	 * This is necessary since a second file rename job could interfere with the first one.
	 *
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $entity
	 * @return void
	 */
	public function beforeSave(Event $event, GlobalContentTemplate $entity): void {
		if ($entity->hasOriginal('fileName') && $entity->get('fileName') != $entity->getOriginal('fileName')) {
			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');

			if ($queuedJobsTable->isQueued('GlobalContentTemplates::fileChanges')) {
				$event->stopPropagation();
				$entity->setError('_general', __d('GlobalContentTemplates', 'file_changes_in_progress'));
			}
		}
	}


	/**
	 * After saving a content template entity
	 * - create a template if it's new
	 * - rename the template if it already exists
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $entity
	 * @param \ArrayObject $options
	 * @noinspection DuplicatedCode, PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, GlobalContentTemplate $entity, ArrayObject $options): void {
		$fileName = Text::slug($entity->get('fileName'), ['replacement' => '_']);
		$fileName = trim($fileName, '_');
		$extension = '.twig';

		$templatePaths = Configure::read('App.paths.templates');
		$folderPath = $templatePaths['customer'] . 'Frontend' . DS . 'global_content' . DS;

		$commands = [];

		if (!file_exists($folderPath)) {
			$commands[] = 'mkdir -m 0755 -p ' . $folderPath;
		}

		$filePath = $folderPath . $fileName . $extension;

		if (
			!($options['isCopy'] ?? false)
			&& $entity->hasOriginal('fileName')
			&& $entity->get('fileName') != $entity->getOriginal('fileName')
		) {
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

		//If the file does not exist, we create one based on a twig-template for frontend global content templates
		if (!$fileExists) {
			$commands[] = 'bin' . DS . 'cake bake template global_content_templates global_content_template '
				. $fileName . ' --prefix Frontend --controller global_content'
			;
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
				'reference' => 'GlobalContentTemplates::fileChanges',
			]);
		}
	}


	/**
	 * After deleting a content template entity, rename the existing file:
	 * - prepend '_deleted-'
	 * - append '-' and the current timestamp
	 *
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $event, GlobalContentTemplate $entity): void {
		$fileName = Text::slug($entity->get('fileName'), ['replacement' => '_']);
		$fileName = trim($fileName, '_');
		$extension = '.twig';

		$templatePaths = Configure::read('App.paths.templates');
		$folderPath = $templatePaths['customer'] . 'Frontend' . DS . 'global_content' . DS;

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
				'reference' => 'GlobalContentTemplates::fileChanges',
			]);
		}
	}
}
