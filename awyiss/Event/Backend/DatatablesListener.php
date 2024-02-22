<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Awyiss\Event\EventManager;
use Awyiss\Model\Entity\Datatable;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;


/**
 * Event listeners for the Datatables scope of the backend
 */
class DatatablesListener implements EventListenerInterface {
	use EventListenerTrait;
	use LocatorAwareTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Datatables.beforeSave' => 'afterSave',
			'Model.Datatables.afterSave' => 'afterSave',
			'Model.Datatables.afterSaveCommit' => 'afterSaveCommit',
			'Model.Datatables.afterSoftDelete' => 'afterSoftDelete',
			'Model.Datatables.afterSoftDeleteCommit' => 'afterSoftDeleteCommit',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Datatable $ao_entity
	 * @return void
	 */
	public function beforeSave(Event $ao_event, Datatable $ao_entity): void {
		//If the datatable has an attributes table and there is a table change in progress, stop the event.
		$ls_attributesTable = 'attributes_' . $ao_entity->identifier;
		$la_tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		if (in_array($ls_attributesTable, $la_tables)) {
			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			if ($lo_queue->isQueued('attributes::table_changes')) {
				$ao_event->stopPropagation();
				$ao_entity->setError('_general', __d('attributes', 'table_changes_in_progress'));
			}
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Datatable $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $ao_event, Datatable $ao_entity): void {
		//Create backend menu entries for the new datatables
		$this->createBackendMenuEntries($ao_entity);
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Datatable $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $ao_event, Datatable $ao_entity): void {
		//Create a task that bakes the migration and the model
		$this->bakeMigrationAndModel($ao_entity);

		if ($ao_entity->isNew()) {
			/**
			 * Trigger the creation of the custom configuriation
			 *
			 * @see \Awyiss\Event\Backend\ConfigurationListener::createCustomConfiguration()
			 */
			$lo_eventManager = EventManager::instance();
			$lo_eventManager->dispatch('Configuration.deleteCustomConfiguration');
		}
	}


	/**
	 * @param Event $ao_event
	 * @param Datatable $ao_entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $ao_event, Datatable $ao_entity): void {
		$lo_tableLocator = FactoryLocator::get('Table');

		$lo_menuEntries = $lo_tableLocator->get('BackendMenuEntries');
		$lo_menuEntries->deleteAll([
			'OR' => [
				'link LIKE' => Inflector::camelize($ao_entity->identifier) . '::%',
				'link' => 'Configuration::overview::scope:' . $ao_entity->identifier,
			],
		]);

		$lo_configuration = $lo_tableLocator->get('Configuration');
		$lo_configuration->deleteAll([
			'scope' => $ao_entity->identifier,
		]);

		$lo_configuration = $lo_tableLocator->get('I18n');
		$lo_configuration->deleteAll([
			'model' => $ao_entity->identifier,
		]);

		$lo_usergroupPermissions = $lo_tableLocator->get('UsergroupPermissions');
		$lo_usergroupPermissions->deleteAll([
			'scope' => $ao_entity->identifier,
		]);
	}


	/**
	 * @param Event $ao_event
	 * @param Datatable $ao_entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDeleteCommit(Event $ao_event, Datatable $ao_entity): void {
		$lo_tableLocator = FactoryLocator::get('Table');

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = $lo_tableLocator->get('Queue.QueuedJobs');

		$ls_filePath = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Entity', Inflector::classify($ao_entity->identifier) . '.php']);
		if (file_exists($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_filePath = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Table', Inflector::camelize($ao_entity->identifier) . 'Table.php']);
		if (file_exists($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_attributesTable = 'attributes_' . $ao_entity->identifier;
		$la_tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		if (in_array($ls_attributesTable, $la_tables)) {
			/** @var \Awyiss\Model\Table $lo_attributesTable */
			$lo_attributesTable = $lo_tableLocator->get('Attributes');

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$li_identityId = $lo_attributesTable->getBehavior('Audit')->getIdentity()?->id;

			$lo_queue->createJob(
				'AttributesDelete',
				[
					'identifier' => $ao_entity->identifier,
					'identityId' => $li_identityId,
				],
				[
					'group' => 'general',
					'priority' => 1,
					'reference' => 'attributes::table_changes',
				]
			);
		}

		$la_commands = [];

		$ls_folder = ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations';

		//Bake a `drop`-migration
		$la_commands[] = 'bin/cake bake migration drop_' . $ao_entity->identifier . $ls_folder;

		//Migrate all the newly baked migrations
		$la_commands[] = 'bin/cake migrations migrate' . $ls_folder . ' --no-lock';

		//Clear the database schema
		$la_commands[] = 'bin/cake schema_cache clear';

		//Bake the seed of the datatables table
		$la_commands[] = 'bin/cake bake seed --data Datatables --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate';

		//Queue the job.
		$lo_queue->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $la_commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'system::drop_datatables_table',
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Datatable $ao_entity
	 * @return void
	 */
	protected function bakeMigrationAndModel(Datatable $ao_entity): void {
		if (!$ao_entity->isNew()) {
			return;
		}

		$la_commands = [];

		//Force migrations for datatables to be stored in the custom directory, to not mess with the Awyiss migrations.
		$ls_migrationsPath = ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations';

		$la_columns = [
			'parent_id:integer?[11]',
			'language_shortcode:char?[2]',
			'title:string?[255]',
			'system_order:integer[11](0)',
			'active:tinyinteger[1](1)',
			'deleted:tinyinteger[1](0)',
			'created_by:integer?[11]',
			'created_on:datetime?',
			'changed_by:integer?[11]',
			'changed_on:datetime?',
			'deleted_by:integer?[11]',
			'deleted_on:datetime?',
		];

		//Bake a `create`-migration that also adds the parent id-column and the column for the attribute-entity
		$la_commands[] = 'bin/cake bake migration create_' . $ao_entity->identifier . ' ' . implode(' ', $la_columns) . $ls_migrationsPath;

		//Migrate all the newly baked migrations
		$la_commands[] = 'bin/cake migrations migrate --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Migrations --no-lock';

		//Clear the database schema
		$la_commands[] = 'bin/cake schema_cache clear';

		//Bake the model
		$la_commands[] = 'bin/cake bake model ' . $ao_entity->identifier . ' --namespace ' . CUSTOM_NAMESPACE . ' --no-fixture --no-test --update --force --is-datatable';

		//Bake the seed of the datatables table
		$la_commands[] = 'bin/cake bake seed --data Datatables --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate';

		$lo_tableLocator = FactoryLocator::get('Table');
		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = $lo_tableLocator->get('Queue.QueuedJobs');
		//Queue the job.
		$lo_queue->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $la_commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'datatables::table_changes',
		]);
	}


	/**
	 * @param Datatable $ao_entity
	 * @return void
	 */
	protected function createBackendMenuEntries(Datatable $ao_entity): void {
		if (!Configure::read('Awyiss.Datatables.Backend.autoCreateMenuEntries') || !$ao_entity->isNew()) {
			return;
		}

		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $lo_menuEntriesTable */
		$lo_menuEntriesTable = $this->fetchTable('BackendMenuEntries');

		$ls_controller = Inflector::camelize($ao_entity->identifier);

		$lo_menuEntriesTable->createEntries($ao_entity, $ls_controller, $ao_entity->identifier, 'media');
	}
}
