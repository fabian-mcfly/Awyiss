<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Model\Entity\ContentTemplate;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\Utility\Hash;
use Cake\Utility\Text;


/**
 * Event listeners for the ContentTemplates scope of the backend
 */
class ContentTemplatesListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.ContentTemplates.beforeMarshal' => 'beforeMarshal',
			'Model.ContentTemplates.beforeSave' => 'beforeSave',
			'Model.ContentTemplates.afterSaveCommit' => 'afterSaveCommit',
			'Model.ContentTemplates.afterSoftDelete' => 'afterSoftDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection DuplicatedCode, PhpUnusedParameterInspection
	 */
	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options): void {
		if (empty($data['contentTemplateElements'])) {
			return;
		}

		$elements = $data['contentTemplateElements'];
		$hasTitle = Hash::check($elements, '{n}[identifier=title]');
		$hasSubtitle = Hash::check($elements, '{n}[identifier=subtitle]');

		/**
		 * Filter out the titleTag and subtitleTag elements when the title and subtitle are not present
		 */
		$data['contentTemplateElements'] = array_filter($elements, function ($element) use ($hasTitle, $hasSubtitle) {
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
	 * check the QueuedJobs table for jobs with the identifier 'ContentTemplates::fileChanges'.
	 *
	 * If such an active job exists, stop the save event and return an error.
	 *
	 * This is necessary since a second file rename job could interfere with the first one.
	 *
	 * @param Event $event
	 * @param ContentTemplate $entity
	 * @return void
	 */
	public function beforeSave(Event $event, ContentTemplate $entity): void {
		if ($entity->hasOriginal('fileName') && $entity->get('fileName') != $entity->getOriginal('fileName')) {
			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');

			if ($queuedJobsTable->isQueued('ContentTemplates::fileChanges')) {
				$event->stopPropagation();
				$entity->setError('_general', __d('ContentTemplates', 'file_changes_in_progress'));
			}
		}
	}


	/**
	 * After saving a content template entity
	 * - create a template if it's new
	 * - rename the template if it already exists
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\ContentTemplate $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection DuplicatedCode, PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, ContentTemplate $entity, ArrayObject $options): void {
		$fileName = Text::slug($entity->get('fileName'), ['replacement' => '_']);
		$fileName = trim($fileName, '_');
		$extension = '.twig';

		$templatePaths = Configure::read('App.paths.templates');
		$folderPath = $templatePaths['customer'] . 'Frontend' . DS . 'content' . DS;

		$commands = [];

		if (!file_exists($folderPath)) {
			$commands[] = 'mkdir -m 0755 -p ' . escapeshellarg($folderPath);
		}

		$filePath = $folderPath . $fileName . $extension;

		if (
			!($options['isCopy'] ?? false)
			&& $entity->hasOriginal('fileName')
			&& $entity->get('fileName') !== $entity->getOriginal('fileName')
		) {
			//After changing the filename in the database, we also need to move (read: rename) the existing file
			$currentFileName = Text::slug($entity->getOriginal('fileName'), ['replacement' => '_']);
			$currentFilePath = $folderPath . $currentFileName . $extension;
			$fileExists = file_exists($currentFilePath);
			if ($fileExists) {
				$commands[] = 'mv -f ' . escapeshellarg($currentFilePath) . ' ' . escapeshellarg($filePath);
			}
		}
		else {
			$fileExists = file_exists($filePath);
		}

		//If the file does not exist, we create one based on a twig-template for frontend content templates
		if (!$fileExists) {
			$commands[] = 'bin'
				. DS
				. 'cake bake template content_templates content_template '
				. escapeshellarg($fileName)
				. ' --prefix Frontend --controller content'
			;
			$commands[] = 'chmod 0755 ' . escapeshellarg($filePath);
		}

		if (!empty($commands)) {
			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			$queuedJobsTable->createJob('Queue.Execute', [
				'command' => implode(' && ', $commands),
				'escape' => false,
				'log' => true,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'ContentTemplates::fileChanges',
			]);
		}
	}


	/**
	 * After deleting a content template entity, rename the existing file:
	 * - prepend '_deleted-'
	 * - append '-' and the current timestamp
	 *
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\ContentTemplate $entity
	 * @return void
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $event, ContentTemplate $entity): void {
		$fileName = Text::slug($entity->get('fileName'), ['replacement' => '_']);
		$fileName = trim($fileName, '_');
		$extension = '.twig';

		$templatePaths = Configure::read('App.paths.templates');
		$folderPath = $templatePaths['customer'] . 'Frontend' . DS . 'content' . DS;

		$filePath = $folderPath . $fileName . $extension;

		if (file_exists($filePath)) {
			$newFilePath = $filePath;
			while (file_exists($newFilePath)) {
				$newFilePath = $folderPath . '_deleted-' . $fileName . '-' . new DateTime()->getTimestamp() . $extension;
			}

			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			$queuedJobsTable->createJob('Queue.Execute', [
				'command' => 'mv',
				'params' => ['-f', $filePath, $newFilePath],
				'log' => true,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'ContentTemplates::fileChanges',
			]);
		}
	}
}
