<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Application;
use Awyiss\Event\EventListenerTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;


/**
 * Event listeners for the PageRoles scope of the backend
 */
class PageRolesListener implements EventListenerInterface {
	use EventListenerTrait;


	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents (): array {
		return [
			'Model.PageRoles.afterSaveCommit' => 'createCustomConstantsFile',
			'Model.PageRoles.afterDelete' => 'createCustomConstantsFile',
		];
	}


	/**
	 * After saving or deleting a page role item, we delete the cached constants file.
	 * It's easier and doesn't affect performance that much to recreate the file once.
	 *
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\PageRole $ao_entity
	 *
	 * @noinspection PhpUnused
	 *
	 * @todo check if we want to have this inside a queue task, so it can be run with www-user privileges
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function createCustomConstantsFile (Event $ao_event, EntityInterface $ao_entity): void {
		/*$ls_filePath = ENV_CUSTOM_CONFIG . 'constants.php';

		if (file_exists($ls_filePath)) {
			unlink($ls_filePath);
		}*/

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		if ( ! $lo_queue->isQueued('create_custom_constants')) {
			$lo_queue->createJob('CreateCustomConstants', [
				'environment' => CONFIG_ENV,
			], [
				'reference' => 'create_custom_constants',
				'priority' => 1,
			]);
		}

		Application::loadConstants(FALSE);
	}
}