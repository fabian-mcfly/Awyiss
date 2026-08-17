<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


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
	use LocatorAwareTrait;


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
		// If the page role has an attributes table and there is a table change in progress, stop the event.
		$attributesTableName = 'attributes_' . Inflector::tableize($entity->identifier);
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
			$entity->isNew()
			|| (
				$entity->hasOriginal('identifier')
				&& $entity->get('identifier') !== $entity->getOriginal('identifier')
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
		$tableLocator = FactoryLocator::get('Table');

		$menuEntries = $tableLocator->get('BackendMenuEntries');
		$menuEntries->deleteAll([
			'OR' => [
				'link LIKE' => Inflector::camelize(Inflector::pluralize($entity->identifier)) . '::%',
				'link' => 'Configuration::overview::scope:' . Inflector::camelize(Inflector::pluralize($entity->identifier)),
			],
		]);

		$configuration = $tableLocator->get('Configuration');
		$configuration->deleteAll([
			'scope' => Inflector::camelize(Inflector::pluralize($entity->identifier)),
		]);

		$i18n = $tableLocator->get('I18n');
		$i18n->deleteAll([
			'model' => Inflector::camelize(Inflector::pluralize($entity->identifier)),
		]);

		$usergroupPermissions = $tableLocator->get('UsergroupPermissions');
		$usergroupPermissions->deleteAll([
			'scope' => Inflector::camelize(Inflector::pluralize($entity->identifier)),
		]);
	}


	/**
	 * @param Event $event
	 * @param PageRole $entity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDeleteCommit(Event $event, PageRole $entity): void {
		$tableLocator = FactoryLocator::get('Table');

		$this->bakePageRoleEnum();

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $tableLocator->get('Queue.QueuedJobs');

		$attributesTableName = 'attributes_' . Inflector::tableize($entity->identifier);
		$tables = ConnectionManager::get('default')
			->getSchemaCollection()
			->listTables()
		;
		if (in_array($attributesTableName, $tables)) {
			/** @var \Awyiss\Model\Table $attributesTable */
			$attributesTable = $tableLocator->get('Attributes');

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
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
			$commands[] = 'unlink ' . $filePath;
		}

		$filePath = implode(
			DS,
			[ROOT, CUSTOM_DIR, 'Model', 'Table', Inflector::camelize(Inflector::tableize($entity->identifier)) . 'Table.php']
		);
		if (file_exists($filePath)) {
			$commands[] = 'unlink ' . $filePath;
		}

		$commands[] = 'bin' . DS . 'cake bake seed --data PageRoles'
			. ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate'
		;

		//Queue the job.
		$queuedJobsTable->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'PageRoles::bakeSeed',
		]);
	}


	/**
	 * After saving or deleting a page role item, we delete the cached enum file.
	 * It's easier and doesn't affect performance that much to recreate the file once.
	 *
	 * @return void
	 */
	protected function bakePageRoleEnum(): void {
		$pageRoles = [];
		$tableLocator = FactoryLocator::get('Table');

		/** @var \Awyiss\Model\Table\PageRolesTable $pageRolesTable */
		$pageRolesTable = $tableLocator->get('PageRoles');

		/** @var \Awyiss\Model\Entity\PageRole $pageRole */
		foreach ($pageRolesTable->find() as $pageRole) {
			$pageRoles[] = $pageRole->identifier . ':' . $pageRole->id;
		}

		$commands[] = 'bin' . DS . 'cake bake enum PageRole '
			. implode(',', $pageRoles)
			. ' -i --namespace ' . CUSTOM_NAMESPACE
			. ' --is-pagerole --force'
		;

		if (!empty($commands)) {
			$data = [
				'command' => implode(' && ', array_map('escapeshellcmd', $commands)),
				'escape' => false,
				'log' => true,
			];

			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = $tableLocator->get('Queue.QueuedJobs');
			$queuedJobsTable->createJob('Queue.Execute', $data, [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'PageRoles::createEnum',
			]);
		}
	}


	/**
	 * @param PageRole $entity
	 * @return void
	 */
	protected function bakePageRoleModel(PageRole $entity): void {
		if ($entity->identifier === 'page') {
			return;
		}

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = FactoryLocator::get('Table')->get('Queue.QueuedJobs');

		if ($queuedJobsTable->isQueued('System::createPageRoleModel::' . $entity->identifier)) {
			return;
		}

		$commands = [];

		$command = 'bin' . DS . 'cake bake model ' . Inflector::camelize(Inflector::pluralize($entity->identifier));
		$command .= ' --namespace ' . CUSTOM_NAMESPACE;

		$command .= ' --force';
		$command .= ' --is-pagerole';
		$command .= ' --no-associations';
		$command .= ' --no-fixture';
		$command .= ' --no-hidden';
		$command .= ' --no-rules';
		$command .= ' --no-test';
		$command .= ' --no-validation';
		$command .= ' --skip-relation-check';
		$command .= ' --table pages';
		$command .= ' --update';

		$commands[] = $command;

		$commands[] = 'bin' . DS . 'cake bake seed --data PageRoles'
			. ' --folder ' . CUSTOM_DIR . DS . 'config' . DS . 'Seeds --force --truncate'
		;

		//Queue the job.
		$queuedJobsTable->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $commands)) . ')',
			'escape' => false,
			'log' => true,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'System::createPageRoleModel::' . $entity->identifier,
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

		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $menuEntriesTable */
		$menuEntriesTable = $this->fetchTable('BackendMenuEntries');

		$scope = Inflector::pluralize($entity->identifier);
		$controller = Inflector::camelize($scope);

		$menuEntriesTable->createEntries($entity, $controller, $scope);
	}
}
