<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Attribute;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Hash;
use Queue\Model\Table\QueuedJobsTable;


/**
 * Event listeners for the Attributes scope of the backend
 */
class AttributesListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents (): array {
		return [
			'Model.Attributes.afterSaveCommit' => 'afterSaveCommit',
		];
	}


	/**
	 * After saving an attribute entity, create a job in the queue that handles the entity's new data
	 * and bakes migrations accordingly.
	 *
	 * @param Event     $ao_event
	 * @param Attribute $ao_entity
	 *
	 * @return void
	 *
	 * @see \Awyiss\Queue\Task\AttributesTask
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit (Event $ao_event, Entity $ao_entity): void {
		$la_relevantColumns = ['scope', 'identifier', 'type', 'hasIndex', 'required', 'defaultValue', 'deleted'];

		$la_oldData = $ao_entity->isNew() ? array_fill_keys($la_relevantColumns, NULL) : $ao_entity->extractOriginal($la_relevantColumns, FALSE);
		$la_newData = $ao_entity->extract($la_relevantColumns, FALSE, FALSE);
		$la_diff = Hash::diff($la_newData, $la_oldData);

		//No changes found in columns, relevant to the migrations?
		if (empty($la_diff)) {
			return;
		}

		/** @var QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		$lo_queue->createJob('Attributes', [
			'id' => $ao_entity->id,
			'old' => $la_oldData,
			'new' => $la_newData
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'attributes::table_changes',
		]);
	}
}