<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;


/**
 * System Controller
 *
 * @property \Awyiss\Model\Table\UrlHistoryTable $UrlHistory
 */
class SystemController extends Controller {
	protected ?string $defaultTable = '';


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return null;
	}


	/**
	 * Shows the system overview.
	 * Access check is handled in the view
	 *
	 * @return void
	 */
	public function overview(): void {
	}


	/**
	 * Analyze method checks if the system is set up correctly
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function analyze(): void {
		$this->Authorization->ensure('analyze');

		// Check if the cronjob is running
		$queuedProcessesTable = $this->fetchTable('Queue.QueueProcesses');
		$timeOffset = time() - Configure::read('Queue.workermaxruntime');
		$cronjobRunning = $queuedProcessesTable->find('all')->where(['QueueProcesses.modified >' => date('Y-m-d H:i:s', $timeOffset)])->count() > 0;

		// Check if webroot/media is writable
		$mediaPath = WWW_ROOT . 'media';
		$mediaWritable = is_writable($mediaPath);

		// Check if awyiss/assets/css is writable
		$awyissCssPath = ROOT . DS . 'awyiss' . DS . 'assets' . DS . 'css';
		$awyissCssWritable = is_writable($awyissCssPath);

		// Check if awyiss/assets/js is writable
		$awyissJsPath = ROOT . DS . 'awyiss' . DS . 'assets' . DS . 'js';
		$awyissJsWritable = is_writable($awyissJsPath);

		// Check if webroot/assets/css is writable
		$assetsCssPath = WWW_ROOT . 'assets' . DS . 'css';
		$assetsCssWritable = is_writable($assetsCssPath);

		// Check if webroot/assets/font is writable
		$assetsFontPath = WWW_ROOT . 'assets' . DS . 'font';
		$assetsFontWritable = is_writable($assetsFontPath);

		// Check if webroot/assets/js is writable
		$assetsJsPath = WWW_ROOT . 'assets' . DS . 'js';
		$assetsJsWritable = is_writable($assetsJsPath);

		// Check if tmp is writable
		$tmpPath = TMP;
		$tmpWritable = is_writable($tmpPath);

		// Check if the log path is writable
		$logPath = LOGS;
		$logWritable = is_writable($logPath);

		// Check if webroot is part of the URL
		$url = Router::url('/', true);
		$webrootNotInUrl = !str_contains($url, '/webroot/');

		$this->set([
			'currentUser' => get_current_user(),
			'rootPath' => ROOT . DS,
			'customPath' => ROOT . DS . CUSTOM_DIR . DS,
			'logPath' => LOGS,
			'tempPath' => TMP,
			'cronjobRunning' => $cronjobRunning,
			'mediaWritable' => $mediaWritable,
			'awyissCssWritable' => $awyissCssWritable,
			'awyissJsWritable' => $awyissJsWritable,
			'assetsCssWritable' => $assetsCssWritable,
			'assetsFontWritable' => $assetsFontWritable,
			'assetsJsWritable' => $assetsJsWritable,
			'logWritable' => $logWritable,
			'tmpWritable' => $tmpWritable,
			'webrootNotInUrl' => $webrootNotInUrl,
		]);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function clearCache(): void {
		$this->Authorization->ensure(['overview', 'analyze']);

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');

		$session = $this->request->getSession();
		$runningJobId = $session->read('Backend.System.clearCache.jobId');
		$runningJob = null;

		if ($runningJobId) {
			/** @noinspection PhpUndefinedMethodInspection */
			$runningJob = $queuedJobsTable->findById($runningJobId)->first();

			if (!$runningJob || $runningJob->completed) {
				$session->delete('Backend.System.clearCache.jobId');
			}
		}

		$type = $this->request->getParam('type');
		$commands = [];

		if (!$type || $type === 'full') {
			$commands[] = 'bin' . DS . 'cake cache clear_all';
		}

		if (in_array($type, ['media', 'full'], true)) {
			$commands[] = 'bin' . DS . 'cake media clear_cache';
		}

		if (in_array($type, ['twig', 'full'], true)) {
			$commands[] = 'bin' . DS . 'cake twig clear_cache';
		}

		if (!$runningJob) {
			$reference = 'System::clearCache';

			$runningJob = $queuedJobsTable->find()->where([
				'reference' => $reference,
				'completed IS' => null,
			])->first();

			if (!$runningJob) {
				$runningJob = $queuedJobsTable->createJob('Queue.Execute', [
					'command' => implode(' && ', $commands),
					'escape' => false,
					'log' => true,
				], [
					'group' => 'general',
					'priority' => 1,
					'reference' => $reference,
				]);
			}

			$session->write('Backend.System.clearCache.jobId', $runningJob->id);
		}

		if ($this->request->is('ajax')) {
			$this->viewBuilder()->setOption('serialize', ['runningJob'])->setClassName('Json');
		}

		$this->set([
			'runningJob' => $runningJob,
		]);
	}
}
