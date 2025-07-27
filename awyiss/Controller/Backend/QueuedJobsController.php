<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Queue\Model\Entity\QueuedJob;
use Queue\Queue\Config;
use Queue\Queue\TaskFinder;


/**
 * QueuedJobs controller
 * Lists all queued jobs and allows to restart failed ones.
 *
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 */
class QueuedJobsController extends BackendController {
	/**
	 * @inheritDoc
	 */
	protected ?string $defaultTable = 'Queue.QueuedJobs';
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
		'order' => [
			'created' => 'desc',
			'id' => 'desc',
		],
	];
	/**
	 * @inheritDoc
	 */
	protected array $search = [
		'autoload' => false,
	];
	/**
	 * A list of task configurations
	 *
	 * @var array
	 */
	protected array $taskConfig = [];


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadTaskConfig();
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->QueuedJobs->find()->where($this->getOverviewWhere());
	}


	/**
	 * Overview method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();
		$lo_queuedJobs = $this->paginate($lo_query);

		$lo_queuedJobs->items()->each(function (QueuedJob $queuedJob) {
			// If the reference is not empty, check if '::' is in the reference
			// If it is, we try to translate it, using the first part as the domain name and the second part as the key

			$this->setJobProperties($queuedJob);
		});

		$this->set([
			'queuedJobs' => $lo_queuedJobs,
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function restart(int $id): Response {
		$this->Authorization->ensure('restartFailed');

		$this->request->allowMethod(['get', 'post']);

		/** @var \Queue\Model\Entity\QueuedJob $lo_queuedJob */
		$lo_queuedJob = $this->QueuedJobs->findById($id)->first();
		if (!$lo_queuedJob) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		$this->setJobProperties($lo_queuedJob);

		if (!$lo_queuedJob->failed) {
			$this->Flash->error(__('restart_failed_not_failed'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->QueuedJobs->reset($lo_queuedJob->id)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('restart_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('restart_failed'));
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Queue\Model\Entity\QueuedJob $lo_queuedJob */
		$lo_queuedJob = $this->QueuedJobs->findById($id)->first();
		if (!$lo_queuedJob) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		$this->setJobProperties($lo_queuedJob);

		if (
			$lo_queuedJob->fetched &&
			!$lo_queuedJob->completed &&
			!$lo_queuedJob->failed
		) {
			$this->Flash->error(__('delete_failed_in_progress'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->QueuedJobs->delete($lo_queuedJob)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_queuedJob->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @return bool
	 */
	protected function taskFailed(QueuedJob $queuedJob): bool {
		$la_taskConfig = $this->taskConfig[ $queuedJob->job_task ] ?? null;

		if (!$queuedJob->failure_message) {
			return false;
		}

		if (!$la_taskConfig) {
			return true;
		}

		return $queuedJob->attempts > $la_taskConfig['retries'];
	}


	/**
	 * @return void
	 */
	protected function loadTaskConfig(): void {
		$lo_tasks = (new TaskFinder())->all();
		$this->taskConfig = Config::taskConfig($lo_tasks);
	}


	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @return void
	 */
	protected function setJobProperties(QueuedJob $queuedJob): void {
		$queuedJob->setVirtual(['failed', 'scope'], true);
		$queuedJob->failed = $this->taskFailed($queuedJob);

		$queuedJob->scope = '';
		if (!$queuedJob->reference || !str_contains($queuedJob->reference, '::')) {
			return;
		}

		$la_referenceParts = explode('::', $queuedJob->reference);
		$queuedJob->scope = __d($la_referenceParts[0], 'headline_overview');
		if ($la_referenceParts[0] === 'system') {
			$queuedJob->scope = 'System';
		}

		$la_arguments = [];
		if (count($la_referenceParts) > 2) {
			$la_arguments = array_slice($la_referenceParts, 2);
		}

		$queuedJob->reference = __d($la_referenceParts[0], $la_referenceParts[1], ...$la_arguments);
	}
}
