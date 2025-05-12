<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\WidgetTemplate;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\Utility\Hash;
use Cake\Utility\Text;


/**
 * Event listeners for the WidgetTemplates scope of the backend
 */
class WidgetTemplatesListener implements EventListenerInterface {
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
			'Model.WidgetTemplates.beforeMarshal' => 'beforeMarshal',
			'Model.WidgetTemplates.beforeSave' => 'beforeSave',
			'Model.WidgetTemplates.afterSaveCommit' => 'afterSaveCommit',
			'Model.WidgetTemplates.afterSoftDelete' => 'afterSoftDelete',
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
		if (empty($data['widget_template_elements'])) {
			return;
		}

		$la_elements = $data['widget_template_elements'];
		$lb_hasTitle = Hash::check($la_elements, '{n}[identifier=title]');
		$lb_hasSubtitle = Hash::check($la_elements, '{n}[identifier=subtitle]');

		//Filter out the title_tag and subtitle_tag elements when the title and subtitle are not present
		/** @noinspection PhpVariableNamingConventionInspection */
		$data['widget_template_elements'] = array_filter($la_elements, function ($element) use ($lb_hasTitle, $lb_hasSubtitle) {
			if ($element['identifier'] == 'title_tag' && !$lb_hasTitle) {
				return false;
			}

			if ($element['identifier'] == 'subtitle_tag' && !$lb_hasSubtitle) {
				return false;
			}

			return true;
		});
	}


	/**
	 * If the filename of a widget templates has changed,
	 * check the QueuedJobs table for jobs with the identifier 'widget_templates::file_changes'.
	 *
	 * If such an active job exists, stop the save event and return an error.
	 * This is neccesary since a second file rename job could interfere with the first one.
	 *
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\WidgetTemplate $entity
	 * @return void
	 */
	public function beforeSave(Event $event, WidgetTemplate $entity): void {
		if ($entity->hasOriginal('fileName') && $entity->fileName != $entity->getOriginal('fileName')) {
			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');

			if ($lo_queue->isQueued('widget_templates::file_changes')) {
				$event->stopPropagation();
				$entity->setError('_general', __d('widget_templates', 'file_changes_in_progress'));
			}
		}
	}


	/**
	 * After saving a widget template entity
	 * - create a template if it's new
	 * - rename the template if it already exists
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\WidgetTemplate $entity
	 * @param \ArrayObject $options
	 * @noinspection DuplicatedCode
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, WidgetTemplate $entity, ArrayObject $options): void {
		$ls_fileName = Text::slug($entity->fileName, ['replacement' => '_']);
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths['customer'] . 'Frontend' . DS . 'widget' . DS;


		$la_commands = [];

		if (!file_exists($ls_folderPath)) {
			$la_commands[] = 'mkdir -m 0750 -p ' . $ls_folderPath;
		}

		$ls_filePath = $ls_folderPath . $ls_fileName . $ls_extension;

		if (!($options['isCopy'] ?? false) && $entity->hasOriginal('fileName') && $entity->fileName != $entity->getOriginal('fileName')) {
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

		//If the file does not exist, we create one based on a twig-template for frontent widget templates
		if (!$lb_fileExists) {
			$la_commands[] = 'bin' . DS . 'cake bake template widget_templates widget_template ' . $ls_fileName . ' --prefix Frontend --controller widget';
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
				'reference' => 'widget_templates::file_changes',
			]);
		}
	}


	/**
	 * After deleting a widget template entity, rename the existing file:
	 *
	 * - prepend '_deleted-'
	 * - append '-' and the current timestamp
	 *
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\WidgetTemplate $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $event, WidgetTemplate $entity): void {
		$ls_fileName = Text::slug($entity->fileName, ['replacement' => '_']);
		$ls_fileName = trim($ls_fileName, '_');
		$ls_extension = '.twig';

		$la_templatePaths = Configure::read('App.paths.templates');
		$ls_folderPath = $la_templatePaths['customer'] . 'Frontend' . DS . 'widget' . DS;

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
				'reference' => 'widget_templates::file_changes',
			]);
		}
	}
}
