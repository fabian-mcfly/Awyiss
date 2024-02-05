<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\PageRole;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;


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
			'Model.PageRoles.afterSave' => 'afterSave',
			'Model.PageRoles.afterSaveCommit' => 'afterSaveCommit',
			'Model.PageRoles.afterSoftDelete' => 'afterSoftDelete',
			'Model.PageRoles.afterSoftDeleteCommit' => 'afterSoftDeleteCommit',
		];
	}


	/**
	 * @param Event $ao_event
	 * @param PageRole $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $ao_event, PageRole $ao_entity): void {
		//Create backend menu entries for the new page role
		$this->createBackendMenuEntries($ao_entity);
	}


	/**
	 * @param Event $ao_event
	 * @param PageRole $ao_entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $ao_event, PageRole $ao_entity): void {
		$this->createPageRoleEnum();

		$this->createPageRoleModel($ao_entity);
	}


	/**
	 * @param Event $ao_event
	 * @param PageRole $ao_entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $ao_event, PageRole $ao_entity): void {
		$lo_tableLocator = FactoryLocator::get('Table');

		$lo_usergroupPermissions = $lo_tableLocator->get('UsergroupPermissions');
		$lo_usergroupPermissions->deleteAll([
			'scope' => Inflector::pluralize($ao_entity->identifier),
		]);
	}


	/**
	 * @param Event $ao_event
	 * @param PageRole $ao_entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDeleteCommit(Event $ao_event, PageRole $ao_entity): void {
		$lo_tableLocator = FactoryLocator::get('Table');

		$this->createPageRoleEnum();

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = $lo_tableLocator->get('Queue.QueuedJobs');

		$ls_filePath = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Entity', Inflector::classify($ao_entity->identifier) . '.php']);
		if (file_exists($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_filePath = implode(DS, [ROOT, CUSTOM_DIR, 'Model', 'Table', Inflector::camelize(Inflector::tableize($ao_entity->identifier)) . 'Table.php']);
		if (file_exists($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_attributesTable = 'attributes_' . Inflector::tableize($ao_entity->identifier);
		$la_tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		if (in_array($ls_attributesTable, $la_tables)) {
			/** @var \Awyiss\Model\Table $lo_attributesTable */
			$lo_attributesTable = $lo_tableLocator->get('Attributes');

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$li_identityId = $lo_attributesTable->getBehavior('Audit')->getIdentity()?->id;

			$lo_queue->createJob(
				'AttributesDelete',
				[
					'identifier' => Inflector::tableize($ao_entity->identifier),
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

		$la_commands[] = 'bin/cake bake seed --data PageRoles --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate';

		//Queue the job.
		$lo_queue->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $la_commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'system::bake_page_roles_seed',
		]);
	}


	/**
	 * @param PageRole $ao_entity
	 * @return void
	 */
	protected function createBackendMenuEntries(PageRole $ao_entity): void {
		if (!Configure::read('Awyiss.PageRoles.Backend.autoCreateMenuEntries') || !$ao_entity->isNew() || $ao_entity->identifier === 'page') {
			return;
		}

		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $lo_menuEntriesTable */
		$lo_menuEntriesTable = $this->fetchTable('BackendMenuEntries');

		$ls_plural = Inflector::pluralize($ao_entity->identifier);
		$ls_controller = Inflector::camelize($ls_plural);

		$la_data = [
			'title' => $ao_entity->title,
			'insert_after_id' => 'pages',
			'link' => $ls_controller . '::overview',
			'access' => [
				'scope' => $ls_plural,
				'identifier' => 'read',
			],
			'child_backend_menu_entries' => [
				[
					'title' => $ls_plural . '::menu_overview',
					'link' => $ls_controller . '::overview',
					'access' => [
						'scope' => $ls_plural,
						'identifier' => 'read',
					],
					'system_order' => 1,
				],
				[
					'title' => $ls_plural . '::menu_add',
					'link' => $ls_controller . '::add',
					'access' => [
						'scope' => $ls_plural,
						'identifier' => 'create',
					],
					'system_order' => 2,
				],
				[
					'title' => $ls_plural . '::menu_configure',
					'link' => 'Configuration::overview::scope:' . $ls_plural,
					'access' => [
						'scope' => $ls_plural,
						'identifier' => 'configure',
					],
					'system_order' => 3,
				],
			],
		];

		if (isset($ao_entity->_translations)) {
			/** @var \Awyiss\Model\Entity $lo_translation */
			foreach ($ao_entity->_translations as $ls_shortcode => $lo_translation) {
				$la_data['_translations'][ $ls_shortcode ] = $lo_translation->extract([], false, false);
			}
		}

		$lo_menuEntry = $lo_menuEntriesTable->patchEntity($lo_menuEntriesTable->newDefaultEntity(), $la_data, [
			'accessibleFields' => 'childBackendMenuEntries',
			'associated' => [
				'ChildBackendMenuEntries' => [
					'validate' => false,
				],
			],
			'validate' => false,
		]);

		$lo_menuEntriesTable->save($lo_menuEntry);
	}


	/**
	 * After saving or deleting a page role item, we delete the cached constants file.
	 * It's easier and doesn't affect performance that much to recreate the file once.
	 *
	 * @return void
	 */
	protected function createPageRoleEnum(): void {
		$la_pageRoles = [];
		$lo_tableLocator = FactoryLocator::get('Table');

		/** @var \Awyiss\Model\Table\PageRolesTable $lo_pageRolesTable */
		$lo_pageRolesTable = $lo_tableLocator->get('PageRoles');

		/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
		foreach ($lo_pageRolesTable->find() as $lo_pageRole) {
			$la_pageRoles[] = $lo_pageRole->identifier . ':' . $lo_pageRole->id;
		}

		$la_commands[] = 'bin/cake bake enum PageRole ' . implode(',', $la_pageRoles) . ' -i --namespace ' . CUSTOM_NAMESPACE . ' --is-pagerole --force';

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
	 * @param PageRole $ao_entity
	 * @return void
	 */
	private function createPageRoleModel(PageRole $ao_entity): void {
		if ($ao_entity->identifier === 'page' || !$ao_entity->isNew()) {
			return;
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');

		if ($lo_queue->isQueued('system::create_page_role_model_' . $ao_entity->identifier)) {
			return;
		}

		$la_commands = [];

		$ls_command = 'bin/cake bake model ' . Inflector::camelize(Inflector::pluralize($ao_entity->identifier));
		$ls_command .= ' --namespace ' . CUSTOM_DIR;

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

		$la_commands[] = 'bin/cake bake seed --data PageRoles --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate';

		//Queue the job.
		$lo_queue->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $la_commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'system::create_page_role_model_' . $ao_entity->identifier,
		]);
	}
}
