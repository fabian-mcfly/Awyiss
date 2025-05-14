<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Event listeners for the PageRoles scope of the backend
 */
class PageRolesListener implements EventListenerInterface {
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
			'Model.PageRoles.beforeSave' => 'beforeSave',
			'Model.PageRoles.afterSave' => 'afterSave',
			'Model.PageRoles.afterSaveCommit' => 'afterSaveCommit',
			'Model.PageRoles.afterSoftDelete' => 'afterSoftDelete',
			'Model.PageRoles.afterSoftDeleteCommit' => 'afterSoftDeleteCommit',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\PageRole $entity
	 * @return void
	 */
	public function beforeSave(Event $event, PageRole $entity): void {
		//If the page role has an attributes table and there is a table change in progress, stop the event.
		$ls_attributesTable = 'attributes_' . Inflector::tableize($entity->identifier);
		$la_tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		if (in_array($ls_attributesTable, $la_tables)) {
			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
			if ($lo_queue->isQueued('attributes::table_changes')) {
				$event->stopPropagation();
				$entity->setError('_general', __d('attributes', 'table_changes_in_progress'));
			}
		}
	}


	/**
	 * @param Event $event
	 * @param PageRole $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $event, PageRole $entity): void {
		//Create backend menu entries for the new page role
		$this->createBackendMenuEntries($entity);
	}


	/**
	 * @param Event $event
	 * @param PageRole $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, PageRole $entity): void {
		if (
			$entity->isNew() ||
			(
				$entity->hasOriginal('identifier') &&
				$entity->get('identifier') !== $entity->getOriginal('identifier')
			)
		) {
			$this->bakePageRoleEnum();

			$this->bakePageRoleModel($entity);
		}
	}


	/**
	 * @param Event $event
	 * @param PageRole $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $event, PageRole $entity): void {
		$lo_tableLocator = FactoryLocator::get('Table');

		$lo_menuEntries = $lo_tableLocator->get('BackendMenuEntries');
		$lo_menuEntries->deleteAll([
			'OR' => [
				'link LIKE' => Inflector::tableize($entity->identifier) . '::%',
				'link' => 'Configuration::overview::scope:' . Inflector::pluralize($entity->identifier),
			],
		]);

		$lo_configuration = $lo_tableLocator->get('Configuration');
		$lo_configuration->deleteAll([
			'scope' => Inflector::pluralize($entity->identifier),
		]);

		$lo_configuration = $lo_tableLocator->get('I18n');
		$lo_configuration->deleteAll([
			'model' => Inflector::pluralize($entity->identifier),
		]);

		$lo_usergroupPermissions = $lo_tableLocator->get('UsergroupPermissions');
		$lo_usergroupPermissions->deleteAll([
			'scope' => Inflector::pluralize($entity->identifier),
		]);
	}


	/**
	 * @param Event $event
	 * @param PageRole $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDeleteCommit(Event $event, PageRole $entity): void {
		$lo_tableLocator = FactoryLocator::get('Table');

		$this->bakePageRoleEnum();

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = $lo_tableLocator->get('Queue.QueuedJobs');

		$ls_filePath = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Entity', Inflector::classify($entity->identifier) . '.php']);
		if (file_exists($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_filePath = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Table', Inflector::camelize(Inflector::tableize($entity->identifier)) . 'Table.php']);
		if (file_exists($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_attributesTable = 'attributes_' . Inflector::tableize($entity->identifier);
		$la_tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		if (in_array($ls_attributesTable, $la_tables)) {
			/** @var \Awyiss\Model\Table $lo_attributesTable */
			$lo_attributesTable = $lo_tableLocator->get('Attributes');

			/** @noinspection PhpUndefinedMethodInspection */
			$li_identityId = $lo_attributesTable->getBehavior('Audit')->getIdentity()?->id;

			$lo_queue->createJob('Attributes/Delete', [
				'identifier' => Inflector::tableize($entity->identifier),
				'identityId' => $li_identityId,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'attributes::table_changes',
			]);
		}

		$la_commands = [];

		$la_commands[] = 'bin' . DS . 'cake bake seed --data PageRoles --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate';

		//Queue the job.
		$lo_queue->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $la_commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'page_roles::bake_seed',
		]);
	}


	/**
	 * After saving or deleting a page role item, we delete the cached enum file.
	 * It's easier and doesn't affect performance that much to recreate the file once.
	 *
	 * @return void
	 */
	protected function bakePageRoleEnum(): void {
		$la_pageRoles = [];
		$lo_tableLocator = FactoryLocator::get('Table');

		/** @var \Awyiss\Model\Table\PageRolesTable $lo_pageRolesTable */
		$lo_pageRolesTable = $lo_tableLocator->get('PageRoles');

		/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
		foreach ($lo_pageRolesTable->find() as $lo_pageRole) {
			$la_pageRoles[] = $lo_pageRole->identifier . ':' . $lo_pageRole->id;
		}

		$la_commands[] = 'bin' . DS . 'cake bake enum PageRole ' . implode(',', $la_pageRoles) . ' -i --namespace ' . CUSTOM_NAMESPACE . ' --is-pagerole --force';

		if (!empty($la_commands)) {
			$la_data = [
				'command' => implode(' && ', array_map('escapeshellcmd', $la_commands)),
				'escape' => false,
				'log' => true,
			];

			/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
			$lo_queue = $lo_tableLocator->get('Queue.QueuedJobs');
			$lo_queue->createJob('Queue.Execute', $la_data, [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'page_roles::create_enum',
			]);
		}
	}


	/**
	 * @param PageRole $entity
	 * @return void
	 */
	private function bakePageRoleModel(PageRole $entity): void {
		if ($entity->identifier === 'page') {
			return;
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');

		if ($lo_queue->isQueued('system::create_page_role_model::' . $entity->identifier)) {
			return;
		}

		$la_commands = [];

		$ls_command = 'bin' . DS . 'cake bake model ' . Inflector::camelize(Inflector::pluralize($entity->identifier));
		$ls_command .= ' --namespace ' . CUSTOM_NAMESPACE;

		$ls_command .= ' --force';
		$ls_command .= ' --is-pagerole';
		$ls_command .= ' --no-associations';
		$ls_command .= ' --no-fixture';
		$ls_command .= ' --no-hidden';
		$ls_command .= ' --no-rules';
		$ls_command .= ' --no-test';
		$ls_command .= ' --no-validation';
		$ls_command .= ' --skip-relation-check';
		$ls_command .= ' --table pages';
		$ls_command .= ' --update';

		$la_commands[] = $ls_command;

		$la_commands[] = 'bin' . DS . 'cake bake seed --data PageRoles --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate';

		//Queue the job.
		$lo_queue->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $la_commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'system::create_page_role_model::' . $entity->identifier,
		]);
	}


	/**
	 * @param PageRole $entity
	 * @return void
	 */
	protected function createBackendMenuEntries(PageRole $entity): void {
		if (!Configure::read('Awyiss.PageRoles.Backend.autoCreateMenuEntries') || !$entity->isNew() || $entity->identifier === 'page') {
			return;
		}

		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $lo_menuEntriesTable */
		$lo_menuEntriesTable = $this->fetchTable('BackendMenuEntries');

		$ls_scope = Inflector::pluralize($entity->identifier);
		$ls_controller = Inflector::camelize($ls_scope);

		$lo_menuEntriesTable->createEntries($entity, $ls_controller, $ls_scope);
	}
}
