<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;


/**
 * Event listeners for the Attributes scope of the backend
 */
class AttributesListener implements EventListenerInterface {
	use EventListenerTrait;


	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents (): array {
		return [
			//'Model.Attributes.beforeSave' => 'beforeSave',
			'Model.Attributes.afterSaveCommit' => 'afterSaveCommit',
		];
	}


	/**
	 * Check the QueuedJobs table for jobs with the identifier 'attributes::table_changes_in_progress'.
	 * If such an active job exists, stop the save event and return an error.
	 * This is neccesary since a second migration job could interfere with the first one.
	 *
	 * @param \Cake\Event\Event $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 *
	 * @return void
	 */
	public function beforeSave (Event $ao_event, EntityInterface $ao_entity): void {
		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');

		if ($lo_queue->isQueued('attributes::table_changes')) {
			$ao_event->stopPropagation();
			$ao_entity->setError('_general', __('attributes::table_changes_in_progress'));
		}
	}


	/**
	 * After saving an attribute entity, create a job in the queue that handles the entity's new data
	 * and bakes migrations accordingly.
	 *
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Attribute $ao_entity
	 *
	 * @return void
	 *
	 * @see \Awyiss\Queue\Task\AttributesTask
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit (Event $ao_event, Entity $ao_entity): void {
		$la_relevantColumns = ['scope', 'name', 'type', 'has_index', 'required'];

		$la_oldData = $ao_entity->isNew() ? array_fill_keys($la_relevantColumns, NULL) : $ao_entity->extractOriginal($la_relevantColumns);
		$la_newData = $ao_entity->extract($la_relevantColumns);
		$la_diff = Hash::diff($la_newData, $la_oldData);

		//No changes found in columns, relevant to the migrations?
		if (empty($la_diff)) {
			return;
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$lo_queue->createJob('Attributes', [
			'id' => $ao_entity->id,
			'old' => $la_oldData,
			'new' => $la_newData
		], [
			'reference' => 'attributes::table_changes',
			'priority' => 1,
		]);
	}
}