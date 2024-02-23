<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


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
			'Model.Attributes.beforeSave' => 'beforeSave',
			'Model.Attributes.afterSaveCommit' => 'afterSaveCommit',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Attribute $ao_entity
	 * @return void
	 * @throws \ReflectionException
	 */
	public function beforeSave(Event $ao_event, Attribute $ao_entity): void {
		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		if ($lo_queue->isQueued('attributes::table_changes')) {
			$ao_event->stopPropagation();
			$ao_entity->setError('_general', __d('attributes', 'table_changes_in_progress'));


			return;
		}

		/** @var \Awyiss\Model\Table\AttributesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		if ($ao_entity->scope === 'contents') {
			//For contents, the content template decides where an attribute will go
			$ao_entity->fieldset = '';
			//For contents, the content template decides whether an attribute is required
			$ao_entity->required = false;
		}

		$la_pageRoles = array_keys(array_filter($lo_table->getAvailableScopes(), function ($ax_table) {
			return !is_string($ax_table);
		}));

		//Contents, Menu Entries and all types of pages don't need to have translatable attributes since they all are translations themselves
		if (in_array($ao_entity->scope, array_merge($la_pageRoles, ['contents', 'menu_entries', 'pages']))) {
			$ao_entity->translatable = false;
		}
	}


	/**
	 * After saving an attribute entity, create a job in the queue that handles the entity's new data
	 * and bakes migrations accordingly.
	 *
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\Attribute $ao_entity
	 * @return void
	 * @see \Awyiss\Queue\Task\AttributesTask
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $ao_event, Attribute $ao_entity): void {
		$la_relevantColumns = ['scope', 'identifier', 'type', 'hasIndex', 'required', 'defaultValue', 'deleted'];

		$la_oldData = $ao_entity->isNew() ? array_fill_keys($la_relevantColumns, null) : $ao_entity->extractOriginal($la_relevantColumns, false);
		$la_newData = $ao_entity->extract($la_relevantColumns, false, false);
		$la_diff = Hash::diff($la_newData, $la_oldData);

		//No changes found in columns, relevant to the migrations?
		if (empty($la_diff)) {
			return;
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		$lo_queue->createJob('Attributes', [
			'id' => $ao_entity->id,
			'old' => $la_oldData,
			'new' => $la_newData,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'attributes::table_changes',
		]);
	}
}
