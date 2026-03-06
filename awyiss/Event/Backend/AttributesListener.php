<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Model\Entity\Attribute;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Hash;


/**
 * Event listeners for the Attributes scope of the backend
 */
class AttributesListener implements EventListenerInterface {
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
	 */
	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options): void {
		if (empty($data['inputType'])) {
			return;
		}

		if (in_array($data['inputType'], ['inputList', 'inputKeyValueList'], true)) {
			// Force the database type of inputList and inputKeyValueList to be json
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
		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		if ($queuedJobsTable->isQueued('Attributes::tableChanges')) {
			$event->stopPropagation();
			$entity->setError('_general', __d('Attributes', 'table_changes_in_progress'));

			return;
		}

		/** @var \Awyiss\Model\Table\AttributesTable $attributesTable */
		$attributesTable = $event->getSubject();

		if (in_array($entity->scope, ['Contents', 'GlobalContents'])) {
			//For contents & global contents, the content template decides where an attribute will go
			$entity->fieldset = '';
			//For contents & global contents, the content template decides whether an attribute is required
			$entity->required = false;
		}

		$pageRoles = array_keys(array_filter($attributesTable->getAvailableScopes(), function ($table) {
			return !is_string($table);
		}));

		//Contents, Menu Entries and all types of pages don't need to have translatable attributes since they all are translations themselves
		if (in_array($entity->scope, array_merge($pageRoles, ['Contents', 'MenuEntries', 'Pages']))) {
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
		$relevantColumns = ['scope', 'identifier', 'type', 'hasIndex', 'required', 'defaultValue', 'deleted'];

		$oldData = $entity->isNew() ? array_fill_keys($relevantColumns, null) : $entity->extractOriginal($relevantColumns);
		$newData = $entity->extract($relevantColumns);
		$diff = Hash::diff($newData, $oldData);

		//No changes found in columns, relevant to the migrations?
		if (empty($diff)) {
			return;
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		$queuedJobsTable->createJob('Attributes/Upsert', [
			'id' => $entity->id,
			'old' => $oldData,
			'new' => $newData,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'Attributes::tableChanges',
		]);
	}
}
