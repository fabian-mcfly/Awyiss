<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventManager;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Event listeners for the Datatables scope of the backend
 */
class DatatablesListener implements EventListenerInterface {
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Datatables.beforeSave' => 'beforeSave',
			'Model.Datatables.afterSave' => 'afterSave',
			'Model.Datatables.afterSaveCommit' => 'afterSaveCommit',
			'Model.Datatables.afterSoftDelete' => 'afterSoftDelete',
			'Model.Datatables.afterSoftDeleteCommit' => 'afterSoftDeleteCommit',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Datatable $entity
	 * @return void
	 */
	public function beforeSave(Event $event, Datatable $entity): void {
		//If the datatable has an attributes table and there is a table change in progress, stop the event.
		$attributesTableName = 'attributes_' . $entity->identifier;
		$tables = ConnectionManager::get('default')
			->getSchemaCollection()
			->listTables()
		;
		if (in_array($attributesTableName, $tables)) {
			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			if ($queuedJobsTable->isQueued('Attributes::tableChanges')) {
				$event->stopPropagation();
				$entity->setError('_general', __d('Attributes', 'table_changes_in_progress'));
			}
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Datatable $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $event, Datatable $entity): void {
		//Create backend menu entries for the new datatables
		$this->createBackendMenuEntries($entity);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Datatable $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, Datatable $entity): void {
		//Create a task that bakes the migration and the model
		$this->bakeMigrationAndModel($entity);

		if ($entity->isNew()) {
			/**
			 * Trigger the creation of the custom configuration
			 *
			 * @see \Awyiss\Event\Backend\ConfigurationListener::createCustomConfiguration()
			 */
			$eventManager = EventManager::instance();
			$eventManager->dispatch('Awyiss.Configuration.deleteCustomConfiguration');
		}
	}


	/**
	 * @param Event $event
	 * @param Datatable $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $event, Datatable $entity): void {
		$tableLocator = FactoryLocator::get('Table');

		$menuEntries = $tableLocator->get('BackendMenuEntries');
		$menuEntries->deleteAll([
			'OR' => [
				'link LIKE' => Inflector::camelize($entity->identifier) . '::%',
				'link' => 'Configuration::overview::scope:' . Inflector::camelize($entity->identifier),
			],
		]);

		$configuration = $tableLocator->get('Configuration');
		$configuration->deleteAll([
			'scope' => Inflector::camelize($entity->identifier),
		]);

		$i18n = $tableLocator->get('I18n');
		$i18n->deleteAll([
			'model' => Inflector::camelize($entity->identifier),
		]);

		$usergroupPermissions = $tableLocator->get('UsergroupPermissions');
		$usergroupPermissions->deleteAll([
			'scope' => Inflector::camelize($entity->identifier),
		]);
	}


	/**
	 * @param Event $event
	 * @param Datatable $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDeleteCommit(Event $event, Datatable $entity): void {
		$tableLocator = FactoryLocator::get('Table');

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $tableLocator->get('Queue.QueuedJobs');

		$attributesTableName = 'attributes_' . $entity->identifier;
		$tables = ConnectionManager::get('default')
			->getSchemaCollection()
			->listTables()
		;
		if (in_array($attributesTableName, $tables)) {
			/** @var \Awyiss\Model\Table $attributesTable */
			$attributesTable = $tableLocator->get('Attributes');

			/** @noinspection PhpUndefinedMethodInspection */
			$identityId = $attributesTable->getBehavior('Audit')->getIdentity()?->id;

			$queuedJobsTable->createJob('Attributes/Delete', [
				'identifier' => Inflector::camelize($entity->identifier),
				'identityId' => $identityId,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'Attributes::tableChanges',
			]);
		}

		$commands = [];

		$filePath = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Entity', Inflector::classify($entity->identifier) . '.php']);
		if (file_exists($filePath)) {
			$commands[] = 'unlink ' . escapeshellarg($filePath);
		}

		$filePath = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Table', Inflector::camelize($entity->identifier) . 'Table.php']);
		if (file_exists($filePath)) {
			$commands[] = 'unlink ' . escapeshellarg($filePath);
		}

		//Bake a `drop`-migration
		$commands[] = 'bin' . DS . 'cake bake migration'
			. ' ' . escapeshellarg('drop_' . $entity->identifier)
			. ' --folder ' . escapeshellarg(CUSTOM_DIR . DS . 'config' . DS . 'Migrations')
		;

		//Migrate all the newly baked migrations
		$commands[] = 'bin' . DS . 'cake migrations migrate'
			. ' --source ' . escapeshellarg(CUSTOM_DIR . DS . 'config' . DS . 'Migrations') . ' --no-lock'
		;

		//Clear the database schema
		$commands[] = 'bin' . DS . 'cake schema_cache clear';

		//Bake the seed of the datatables table
		$commands[] = 'bin' . DS . 'cake bake seed --data Datatables'
			. ' --folder ' . escapeshellarg(CUSTOM_DIR . DS . 'config' . DS . 'Seeds') . ' --force --truncate'
		;

		//Queue the job.
		$queuedJobsTable->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', $commands) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'Datatables::dropTable',
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Datatable $entity
	 * @return void
	 */
	protected function bakeMigrationAndModel(Datatable $entity): void {
		if (!$entity->isNew()) {
			return;
		}

		$commands = [];

		//Force migrations for datatables to be stored in the custom directory, to not mess with the Awyiss migrations.
		$migrationsPath = ' --folder ' . escapeshellarg(CUSTOM_DIR . DS . 'config' . DS . 'Migrations');

		$columns = [
			escapeshellarg('parentId:integer?[11]:index'),
			escapeshellarg('languageShortcode:char?[2]:index'),
			escapeshellarg('title:string?[255]'),
			escapeshellarg('systemOrder:integer[11](0)'),
			escapeshellarg('active:tinyinteger[1](1):index'),
			escapeshellarg('deleted:tinyinteger[1](0):index'),
			escapeshellarg('createdBy:integer?[11]'),
			escapeshellarg('createdOn:datetime?'),
			escapeshellarg('changedBy:integer?[11]'),
			escapeshellarg('changedOn:datetime?'),
			escapeshellarg('deletedBy:integer?[11]'),
			escapeshellarg('deletedOn:datetime?'),
		];

		//Bake a `create`-migration that also adds the parent id-column and the column for the attribute-entity
		$commands[] = 'bin' . DS . 'cake bake migration'
			. ' ' . escapeshellarg('create_' . $entity->identifier)
			. ' ' . implode(' ', $columns) . $migrationsPath
		;

		//Migrate all the newly baked migrations
		$commands[] = 'bin' . DS . 'cake migrations migrate'
			. ' --source ' . escapeshellarg(CUSTOM_DIR . DS . 'config' . DS . 'Migrations') . ' --no-lock'
		;

		//Clear the database schema
		$commands[] = 'bin' . DS . 'cake schema_cache clear';

		//Bake the model
		$commands[] = 'bin' . DS . 'cake bake model ' . escapeshellarg($entity->identifier)
			. ' --namespace ' . CUSTOM_NAMESPACE
			. ' --no-fixture --no-test --update --force --is-datatable'
		;

		//Bake the seed of the datatables table
		$commands[] = 'bin' . DS . 'cake bake seed --data Datatables'
			. ' --folder ' . escapeshellarg(CUSTOM_DIR . DS . 'config' . DS . 'Seeds') . ' --force --truncate'
		;

		$tableLocator = FactoryLocator::get('Table');
		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $tableLocator->get('Queue.QueuedJobs');
		//Queue the job.
		$queuedJobsTable->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', $commands) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'Datatables::tableChanges',
		]);
	}


	/**
	 * @param Datatable $entity
	 * @return void
	 */
	protected function createBackendMenuEntries(Datatable $entity): void {
		if (!Configure::read('Awyiss.Datatables.Backend.autoCreateMenuEntries') || !$entity->isNew()) {
			return;
		}

		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $menuEntriesTable */
		$menuEntriesTable = $this->fetchTable('BackendMenuEntries');

		$controller = Inflector::camelize($entity->identifier);

		$menuEntriesTable->createEntries($entity, $controller, $entity->identifier, 'media');
	}
}
