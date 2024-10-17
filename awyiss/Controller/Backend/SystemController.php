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
 * @property \Awyiss\Model\Table\SlugHistoryTable $SlugHistory
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
	 * Analyze method checks if the system is set up correctly
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function analyze(): void {
		$this->Authorization->ensure('analyze');

		// Check if the cronjob is running
		$lo_table = $this->fetchTable('Queue.QueueProcesses');
		$li_timeOffset = time() - Configure::read('Queue.workermaxruntime');
		$lb_cronjobRunning = $lo_table->find('all')->where(['QueueProcesses.modified >' => date('Y-m-d H:i:s', $li_timeOffset)])->count() > 0;

		// Check if webroot/media is writable
		$ls_mediaPath = WWW_ROOT . 'media';
		$lb_mediaWritable = is_writable($ls_mediaPath);

		// Check if awyiss/assets/css is writable
		$ls_awyissCssPath = ROOT . DS . 'awyiss' . DS . 'assets' . DS . 'css';
		$lb_awyissCssWritable = is_writable($ls_awyissCssPath);

		// Check if awyiss/assets/js is writable
		$ls_awyissJsPath = ROOT . DS . 'awyiss' . DS . 'assets' . DS . 'js';
		$lb_awyissJsWritable = is_writable($ls_awyissJsPath);

		// Check if webroot/assets/css is writable
		$ls_assetsCssPath = WWW_ROOT . 'assets' . DS . 'css';
		$lb_assetsCssWritable = is_writable($ls_assetsCssPath);

		// Check if webroot/assets/font is writable
		$ls_assetsFontPath = WWW_ROOT . 'assets' . DS . 'font';
		$lb_assetsFontWritable = is_writable($ls_assetsFontPath);

		// Check if webroot/assets/js is writable
		$ls_assetsJsPath = WWW_ROOT . 'assets' . DS . 'js';
		$lb_assetsJsWritable = is_writable($ls_assetsJsPath);

		// Check if tmp is writable
		$ls_tmpPath = TMP;
		$lb_tmpWritable = is_writable($ls_tmpPath);

		// Check if the log path is writable
		$ls_logPath = LOGS;
		$lb_logWritable = is_writable($ls_logPath);

		// Check if webroot is part of the URL
		$ls_url = Router::url('/', true);
		$lb_webrootNotInUrl = !str_contains($ls_url, '/webroot/');

		$this->set([
			'currentUser' => get_current_user(),
			'rootPath' => ROOT . DS,
			'customPath' => ROOT . DS . CUSTOM_DIR . DS,
			'logPath' => LOGS,
			'tempPath' => TMP,
			'cronjobRunning' => $lb_cronjobRunning,
			'mediaWritable' => $lb_mediaWritable,
			'awyissCssWritable' => $lb_awyissCssWritable,
			'awyissJsWritable' => $lb_awyissJsWritable,
			'assetsCssWritable' => $lb_assetsCssWritable,
			'assetsFontWritable' => $lb_assetsFontWritable,
			'assetsJsWritable' => $lb_assetsJsWritable,
			'logWritable' => $lb_logWritable,
			'tmpWritable' => $lb_tmpWritable,
			'webrootNotInUrl' => $lb_webrootNotInUrl,
		]);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function clearCache(): void {
		$this->Authorization->ensure('analyze');

		/** @var \Queue\Model\Table\QueuedJobsTable $lo_queue */
		$lo_queue = $this->fetchTable('Queue.QueuedJobs');

		$lo_session = $this->request->getSession();
		$li_runningJobId = $lo_session->read('Backend.System.clearCache.jobId');
		$lo_runningJob = null;

		if ($li_runningJobId) {
			$lo_runningJob = $lo_queue->findById($li_runningJobId)->first();

			if (!$lo_runningJob || $lo_runningJob->completed) {
				$lo_session->delete('Backend.System.clearCache.jobId');
			}
		}

		if (!$lo_runningJob) {
			$ls_reference = 'system::clear_cache';

			$lo_runningJob = $lo_queue->find()->where([
				'reference' => $ls_reference,
				'completed IS' => null,
			])->first();

			if (!$lo_runningJob) {
				$lo_runningJob = $lo_queue->createJob('Queue.Execute', [
					'command' => 'bin' . DS . 'cake cache clear_all && bin' . DS . 'cake media clear_cache',
					'escape' => false,
					'log' => true,
				], [
					'group' => 'general',
					'priority' => 1,
					'reference' => $ls_reference,
				]);
			}

			$lo_session->write('Backend.System.clearCache.jobId', $lo_runningJob->id);
		}

		if ($this->request->is('ajax')) {
			$this->viewBuilder()->setOption('serialize', ['runningJob'])->setClassName('Json');
		}

		$this->set([
			'runningJob' => $lo_runningJob,
		]);
	}
}
