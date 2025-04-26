<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Attribute;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Hash;


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
	public function implementedEvents(): array {
		return [
			'Model.Attributes.beforeMarshal' => 'beforeMarshal',
			'Model.Attributes.beforeSave' => 'beforeSave',
			'Model.Attributes.afterSaveCommit' => 'afterSaveCommit',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options): void {
		if (empty($data['input_type'])) {
			return;
		}

		if (in_array($data['input_type'], ['input_list', 'input_key_value_list'])) {
			// Force the database type of input_list and input_key_value_list to be json
			$data['type'] = 'json';
			$data['required'] = false;
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Attribute $entity
	 * @return void
	 */
	public function beforeSave(Event $event, Attribute $entity): void {
		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		if ($lo_queue->isQueued('attributes::table_changes')) {
			$event->stopPropagation();
			$entity->setError('_general', __d('attributes', 'table_changes_in_progress'));


			return;
		}

		/** @var \Awyiss\Model\Table\AttributesTable $lo_table */
		$lo_table = $event->getSubject();

		if (in_array($entity->scope, ['contents', 'widgets'])) {
			//For contents & widgets, the content template decides where an attribute will go
			$entity->fieldset = '';
			//For contents & widgets, the content template decides whether an attribute is required
			$entity->required = false;
		}

		$la_pageRoles = array_keys(array_filter($lo_table->getAvailableScopes(), function ($table) {
			return !is_string($table);
		}));

		//Contents, Menu Entries and all types of pages don't need to have translatable attributes since they all are translations themselves
		if (in_array($entity->scope, array_merge($la_pageRoles, ['contents', 'menu_entries', 'pages']))) {
			$entity->translatable = false;
		}
	}


	/**
	 * After saving an attribute entity, create a job in the queue that handles the entity's new data
	 * and bakes migrations accordingly.
	 *
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\Attribute $entity
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, Attribute $entity): void {
		$la_relevantColumns = ['scope', 'identifier', 'type', 'hasIndex', 'required', 'defaultValue', 'deleted'];

		$la_oldData = $entity->isNew() ? array_fill_keys($la_relevantColumns, null) : $entity->extractOriginal($la_relevantColumns, false);
		$la_newData = $entity->extract($la_relevantColumns, false, false);
		$la_diff = Hash::diff($la_newData, $la_oldData);

		//No changes found in columns, relevant to the migrations?
		if (empty($la_diff)) {
			return;
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		$lo_queue->createJob('Attributes/Upsert', [
			'id' => $entity->id,
			'old' => $la_oldData,
			'new' => $la_newData,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'attributes::table_changes',
		]);
	}
}
