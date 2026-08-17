<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController;
use Awyiss\Utility\Inflector;
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

		$query = $this->getOverviewQuery();
		$queuedJobs = $this->paginate($query);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$queuedJobs
			->items()
			->each(function (QueuedJob $queuedJob) {
				// If the reference is not empty, check if '::' is in the reference
				// If it is, we try to translate it, using the first part as the domain name and the second part as the key
				$this->setJobProperties($queuedJob);
			})
		;

		$this->set([
			'queuedJobs' => $queuedJobs,
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

		/** @var \Queue\Model\Entity\QueuedJob $queuedJob */
		/** @noinspection PhpUndefinedMethodInspection */
		$queuedJob = $this->QueuedJobs->findById($id)->first();
		if (!$queuedJob) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		$this->setJobProperties($queuedJob);

		/** @noinspection PhpUndefinedFieldInspection */
		if (!$queuedJob->failed) {
			$this->Flash->error(__('restart_failed_not_failed'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->QueuedJobs->reset($queuedJob->id)) {
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

		/** @var \Queue\Model\Entity\QueuedJob $queuedJob */
		/** @noinspection PhpUndefinedMethodInspection */
		$queuedJob = $this->QueuedJobs->findById($id)->first();
		if (!$queuedJob) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		$this->setJobProperties($queuedJob);

		/** @noinspection PhpUndefinedFieldInspection */
		if (
			$queuedJob->fetched
			&& !$queuedJob->completed
			&& !$queuedJob->failed
		) {
			$this->Flash->error(__('delete_failed_in_progress'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->QueuedJobs->delete($queuedJob)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($queuedJob->getError('_general') as $error) {
					$this->Flash->error($error);
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
		$taskConfig = $this->taskConfig[ $queuedJob->job_task ] ?? null;

		if (!$queuedJob->failureMessage) {
			return false;
		}

		if (!$taskConfig) {
			return true;
		}

		return $queuedJob->attempts > $taskConfig['retries'];
	}


	/**
	 * @return void
	 */
	protected function loadTaskConfig(): void {
		$tasks = new TaskFinder()->all();
		$this->taskConfig = Config::taskConfig($tasks);
	}


	/**
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 * @return void
	 */
	protected function setJobProperties(QueuedJob $queuedJob): void {
		$queuedJob->setVirtual(['failed', 'scope'], true);
		/** @noinspection PhpUndefinedFieldInspection */
		$queuedJob->failed = $this->taskFailed($queuedJob);

		/** @noinspection PhpUndefinedFieldInspection */
		$queuedJob->scope = '';
		if (!$queuedJob->reference || !str_contains($queuedJob->reference, '::')) {
			return;
		}

		$referenceParts = explode('::', $queuedJob->reference);
		$queuedJob->scope = __d($referenceParts[0], 'headline_overview');
		if ($referenceParts[0] === 'system') {
			$queuedJob->scope = 'System';
		}

		$arguments = [];
		if (count($referenceParts) > 2) {
			$arguments = array_slice($referenceParts, 2);
		}

		$queuedJob->reference = __d($referenceParts[0], Inflector::underscore($referenceParts[1]), ...$arguments);
	}
}
