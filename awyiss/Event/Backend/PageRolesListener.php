<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\PageRole;
use Cake\Core\Configure;
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
			'Model.PageRoles.afterDelete' => 'afterDelete',
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
		$this->createCustomConstantsFile();

		$this->createPageRoleModel($ao_entity);
		/*$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		if ( ! $lo_queue->isQueued('system::create_page_role_model_' . $ao_entity->identifier)) {
			$lo_queue->createJob('CreatePageRoleModel', [
				'name' => Inflector::camelize(Inflector::pluralize($ao_entity->identifier)),
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'system::create_page_role_model_' . $ao_entity->identifier,
			]);
		}*/
	}


	/**
	 * @param Event $ao_event
	 * @param PageRole $ao_entity
	 * @noinspection PhpUnused
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete(Event $ao_event, PageRole $ao_entity): void {
		$this->createCustomConstantsFile();

		//TODO: Delete table file
		dd(__LINE__, __FILE__);
	}


	/**
	 * After saving or deleting a page role item, we delete the cached constants file.
	 * It's easier and doesn't affect performance that much to recreate the file once.
	 *
	 * @return void
	 */
	protected function createCustomConstantsFile(): void {
		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		if (!$lo_queue->isQueued('system::create_custom_constants')) {
			$lo_queue->createJob('CreateCustomConstants', [
				'environment' => CONFIG_ENV,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'system::create_custom_constants',
			]);
		}

		Awyiss::loadConstants(false);
	}


	/**
	 * @param PageRole $ao_entity
	 * @return void
	 */
	protected function createBackendMenuEntries(PageRole $ao_entity): void {
		if (!Configure::read('Awyiss.PageRoles.Backend.autoCreateMenuEntries') || !$ao_entity->isNew()) {
			return;
		}

		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $lo_menuEntriesTable */
		$lo_menuEntriesTable = $this->fetchTable('BackendMenuEntries');

		$la_data = [
			'title' => $ao_entity->title,
			'insert_after_id' => 'pages',
			'link' => Inflector::camelize(Inflector::pluralize($ao_entity->identifier)) . '::overview',
			'access' => [
				'scope' => Inflector::pluralize($ao_entity->identifier),
				'identifier' => 'read',
			],
			'child_backend_menu_entries' => [
				[
					'title' => Inflector::pluralize($ao_entity->identifier) . '::menu_overview',
					'link' => Inflector::camelize(Inflector::pluralize($ao_entity->identifier)) . '::overview',
					'access' => [
						'scope' => Inflector::pluralize($ao_entity->identifier),
						'identifier' => 'read',
					],
					'system_order' => 1,
				],
				[
					'title' => Inflector::pluralize($ao_entity->identifier) . '::menu_add',
					'link' => Inflector::camelize(Inflector::pluralize($ao_entity->identifier)) . '::add',
					'access' => [
						'scope' => Inflector::pluralize($ao_entity->identifier),
						'identifier' => 'create',
					],
					'system_order' => 2,
				],
				[
					'title' => Inflector::pluralize($ao_entity->identifier) . '::menu_configure',
					'link' => 'Configuration::overview::scope:' . Inflector::pluralize($ao_entity->identifier),
					'access' => [
						'scope' => Inflector::pluralize($ao_entity->identifier),
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
	 * @param PageRole $ao_entity
	 * @return void
	 */
	private function createPageRoleModel(PageRole $ao_entity): void {
		if ($ao_entity->identifier === 'page') {
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

		$la_commands[] = 'bin/cake bake seed --data PageRoles --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --truncate';

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
