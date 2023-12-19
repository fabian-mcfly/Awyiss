<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\PageRole;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Inflector;
use Queue\Model\Table\QueuedJobsTable;


/**
 * Event listeners for the PageRoles scope of the backend
 */
class PageRolesListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents (): array {
		return [
			'Model.PageRoles.afterSaveCommit' => 'afterSaveCommit',
			'Model.PageRoles.afterDelete' => 'afterDelete',
		];
	}


	/**
	 * @param Event    $ao_event
	 * @param PageRole $ao_entity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit (Event $ao_event, PageRole $ao_entity): void {
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
	 * @param Event    $ao_event
	 * @param PageRole $ao_entity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete (Event $ao_event, PageRole $ao_entity): void {
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
	protected function createCustomConstantsFile (): void {
		/** @var QueuedJobsTable $lo_queue */
		$lo_queue = FactoryLocator::get('Table')->get('Queue.QueuedJobs');
		if ( ! $lo_queue->isQueued('system::create_custom_constants')) {
			$lo_queue->createJob('CreateCustomConstants', [
				'environment' => CONFIG_ENV,
			], [
				'group' => 'general',
				'priority' => 1,
				'reference' => 'system::create_custom_constants',
			]);
		}

		Awyiss::loadConstants(FALSE);
	}


	/**
	 * @param PageRole $ao_entity
	 *
	 * @return void
	 */
	private function createPageRoleModel (PageRole $ao_entity): void {
		if ($ao_entity->identifier === 'page') {
			return;
		}
		/** @var QueuedJobsTable $lo_queue */
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
			'escape' => FALSE,
			'log' => TRUE,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'system::create_page_role_model_' . $ao_entity->identifier,
		]);
	}
}
