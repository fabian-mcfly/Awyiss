<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;


class AttributesListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
	 */
	public function implementedEvents (): array {
		return [
			//'Model.Attributes.beforeSave' => 'beforeSave',
			'Model.Attributes.afterSave' => 'afterSave',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 *
	 * @return void
	 */
	public function beforeSave (Event $ao_event, EntityInterface $ao_entity): void {
		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');

		if ($lo_queue->isQueued('attributes::table_changes', 'Queue.Execute')) {
			$ao_event->stopPropagation();
			$ao_entity->setError('_general', __('attributes::table_changes_in_progress'));
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Attribute $ao_entity
	 *
	 * @return void
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave (Event $ao_event, \Awyiss\Model\Entity $ao_entity): void {
		$la_relevantColumns = ['scope', 'name', 'type', 'has_index', 'required'];

		$la_oldData = $ao_entity->isNew() ? array_fill_keys($la_relevantColumns, NULL) : $ao_entity->extractOriginal($la_relevantColumns);
		$la_newData = $ao_entity->extract($la_relevantColumns);
		$la_diff = \Cake\Utility\Hash::diff($la_newData, $la_oldData);

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

		/*The first approach used to clear the schema_ceach before doing the migration.
		But we just assume it's up to date now. Yay.
		$la_commands = [
			'bin/cake schema_cache clear',
			'bin/cake queue add Attributes ' . base64_encode(json_encode([
				'id' => $ao_entity->id,
				'old' => $la_oldData,
				'new' => $la_newData,
			])),
		];

		$la_data = [
			'command' => implode(' && ', $la_commands),
			'escape' => FALSE,
		];


		#** @var \Queue\Model\Table\QueuedJobsTable $lo_queue *#
		$lo_queue = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		//$lo_queue->createJob('Queue.Execute', $la_data, ['reference' => 'attributes::table_changes', 'priority' => 1]);
		$lo_queue->createJob('Queue.Execute', $la_data, ['priority' => 1]);*/
	}
}