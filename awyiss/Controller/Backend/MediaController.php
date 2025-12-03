<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Inflector;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;


/**
 * Media Controller
 *
 * @property \Awyiss\Model\Table\MediaTable $Media
 */
class MediaController extends Controller {
	/**
	 * Whether the view was requested for a hidden folder
	 *
	 * @var bool $activeHiddenFolder
	 */
	protected bool $activeHiddenFolder = false;
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'queryConditions' => [
			'active' => true,
			'parents_active' => true,
			'hidden' => false,
		],
		'startupMethods' => ['overview', 'batchEdit'],
		'uriParam' => 'media-folder-id',
	];
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'defaultSortableFields' => ['usage_count'],
		'enabled' => false,
		'order' => [
			'name' => 'asc',
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		if ($this->request->is('ajax')) {
			$this->Categories->setConfig('verifySelection', false);
		}

		$mediaFolderId = $this->request->getParam('mediaFolderId') ?? $this->request->getData('media_folder_id');
		if ($mediaFolderId) {
			/** @var \Awyiss\Model\Entity\MediaFolder $mediaFolder */
			$mediaFolder = $this->Media->MediaFolders->findById($mediaFolderId)->first();
			if ($mediaFolder && $mediaFolder->hidden) {
				$this->activeHiddenFolder = true;

				$categoriesBehavior = $this->Media->getBehavior('Categories');
				$queryConditions = $categoriesBehavior->getConfig('queryConditions');

				unset($queryConditions['hidden']);

				$queryConditions['OR'] = [
					'hidden' => false,
					'id' => $mediaFolder->id,
				];

				$categoriesBehavior->setConfig('queryConditions', $queryConditions, false);
			}
		}
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->Media->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();

		if ($this->request->getParam('paginate')) {
			$paginated = ($this->request->getParam('paginate', 'false') === 'true');

			if ($paginated) {
				$this->Paginate->enable();
			}
			else {
				$this->Paginate->disable();
			}
		}
		else {
			$paginated = $this->paginate['enabled'];
		}

		$query->enableAutoFields()->select([
			'usage_count' => $query->func()->count('MediaAssignments.id'),
		])->leftJoinWith('MediaAssignments')->groupBy('Media.id');

		if ($paginated) {
			$media = $this->paginate($query);
		}
		else {
			$media = $query->all();
		}

		/**
		 * @uses \Awyiss\Model\Table::findActive()
		 * @uses \Awyiss\Model\Table::findForCurrentLanguage()
		 */
		$mediaFoldersQuery = $this->Media->MediaFolders->find('active')->find('threaded')->find('forCurrentLanguage');

		$where = [];
		if (!$this->request->getParam('includeHidden', false)) {
			$where['OR'] = [
				'hidden' => false,
				'id' => $this->Categories->getSelectedCategory(),
			];
		}

		// Exclude hidden folders but include the selected one
		$mediaFoldersQuery->where($where);

		$mediaFolders = $mediaFoldersQuery->all()->groupBy(function (MediaFolder $element) {
			if ($element->hidden) {
				return 'hidden';
			}

			return $element->languageShortcode ?? '';
		})->toArray();

		$currentLanguageShortcode = $this->request->getParam('lang');
		// Sort the grouped media folders by the global and the current language first
		uksort($mediaFolders, function (string $a, string $b) use ($currentLanguageShortcode): int {
			if (
				($a === 'hidden' && $b === 'hidden') ||
				($a === '' && $b === '') ||
				($a === $currentLanguageShortcode && $b === $currentLanguageShortcode)
			) {
				return 0;
			}

			if ($a === 'hidden' || $a === '') {
				return -1;
			}

			if ($b === 'hidden' || $b === '') {
				return 1;
			}

			return 0;
		});

		$this->set([
			'media' => $media,
			'groupedMediaFolders' => $mediaFolders,
			'maxFileSize' => $this->Media->getMaxFileSize(),
			'isAjax' => $this->request->is('ajax'),
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'paginated' => $paginated,
			'attributes' => $this->Media->getAttributes(),
		]);

		if ($this->request->is('ajax')) {
			$this->viewBuilder()->disableAutoLayout();
		}
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->ensure('create');

		$media = $this->Media->newDefaultEntity();

		if ($this->request->is('post')) {
			try {
				$this->save($media, 'add', $this->request->is('ajax'));
			}
			catch (RedirectException $ex) {
				if (!$this->request->is('ajax')) {
					throw $ex;
				}
			}

			/**
			 * If the request is an AJAX request, the type is a form submit (not a reload)
			 * set the view class to JSON, disable the auto layout and render the response
			 */
			if ($this->request->is('ajax') && !$this->request->getData('reload_form')) {
				$errorMessage = $this->getErrorMessage($media);

				// Consume the flash messages to prevent them from being displayed the next time the page is loaded
				$this->request->getFlash()->consume('media');

				// Set the view class to JSON
				$this->viewBuilder()->disableAutoLayout();

				$elementView = $this->createView();
				$elementView->set([
					'mediaItem' => $media,
					'paginate' => ($this->request->getParam('paginate', 'false') === 'true'),
					'attributes' => $this->Media->getAttributes(),
				]);

				$this->set([
					'response' => [
						'html' => $media->hasErrors() ? null : $elementView->render('Backend/Media/overview_ajax_item'),
						'message' => $errorMessage,
						'success' => !$media->hasErrors(),
						'id' => 'Media-ListItem' . $media->id,
						'item' => $media->getErrors(),
					],
				]);

				$this->viewBuilder()->setClassName('Json');
				$this->viewBuilder()->setOption('serialize', 'response');
			}
		}

		$this->set([
			'media' => $media,
			'languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('create');

		/**
		 * Fetch, patch and save the media entity
		 *
		 * If the request is an AJAX request, catch the RedirectException so the
		 * rest of the method can handle the AJAX response
		 *
		 * If the request is not an AJAX request or another type of exception is thrown,
		 * the exception is rethrown so the exception handler can handle it
		 */
		try {
			/**
			 * @var \Awyiss\Model\Entity\Media $media
			 * @uses \Awyiss\Model\Table::findTranslations()
			 */
			$media = $this->Media->findById($id)->find('translations')->first();

			if (!$media) {
				$this->Flash->error(__('record_not_found'));

				return $this->redirect(['action' => 'overview']);
			}

			$media->usageCount = $this->Media->MediaAssignments->find()->where(['media_id' => $media->id, 'deleted' => 0])->count();

			if ($this->request->is(['patch', 'post', 'put'])) {
				$this->save($media, 'edit', $this->request->is('ajax'));
			}
		}
		catch (RedirectException $ex) {
			if (!$this->request->is('ajax') || $this->request->getParam('ajaxForm')) {
				throw $ex;
			}
		}

		/**
		 * If the request is an AJAX request, the type is a form submit (type patch, post or put and not a reload)
		 * set the view class to JSON, disable the auto layout and render the response
		 */
		if (
			$this->request->is(['patch', 'post', 'put']) &&
			$this->request->is('ajax') &&
			!$this->request->getData('reload_form') &&
			!$this->request->getParam('ajaxForm')
		) {
			$errorMessage = $this->getErrorMessage($media);

			// Consume the flash messages to prevent them from being displayed the next time the page is loaded
			$this->request->getFlash()->consume('media');

			// Set the view class to JSON
			$this->viewBuilder()->disableAutoLayout();

			$elementView = $this->createView();
			$elementView->set([
				'mediaItem' => $media,
				'paginate' => ($this->request->getParam('paginate', 'false') === 'true'),
			]);

			$this->set([
				'response' => [
					'html' => $media->hasErrors() ? null : $elementView->render('Backend/Media/overview_ajax_item'),
					'message' => $errorMessage,
					'success' => !$media->hasErrors(),
					'id' => 'Media-ListItem' . $media->id,
					'item' => $media->getErrors(),
				],
			]);

			$this->viewBuilder()->setClassName('Json');
			$this->viewBuilder()->setOption('serialize', 'response');
		}

		$this->set([
			'media' => $media,
			'languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}


	/**
	 * Batch Edit method
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function batchEdit(): void {
		$this->Authorization->ensure('create');

		$this->paginate['enabled'] = false;
		$query = $this->getOverviewQuery()->find('translations');
		$media = $query->all();
		$this->Paginate->disable();

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->batchSave($media);
		}

		$languages = [];
		foreach (LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND) as $language) {
			if (!$language->active) {
				continue;
			}

			$languages[ $language->shortcode ] = $language;
		}

		$this->set([
			'media' => $media,
			'languages' => $languages,
			'languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}



	/**
	 * Delete method
	 *
	 * @param ?int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(?int $id = null): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		if (!$id && $this->request->getMethod() === 'DELETE') {
			return $this->_deleteMultiple();
		}

		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->Media->findById($id)->first();
		if (!$media) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Media->delete($media)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($media->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Check the progress of the creation of preview images for the media files
	 * that do not have a preview image yet
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function checkPreviewProgress(): void {
		$this->checkProgress('preview');
	}


	/**
	 * Check the progress of the resizing of the images (or preview images) for the media files
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function checkResizeProgress(): void {
		$this->checkProgress('resize');
	}


	/**
	 * Check the progress and keep the connection alive by sending a JSON encoded array in a loop
	 * If the connection is aborted, send an "aborted" event.
	 * If the media files are processed, send a "done" event.
	 *
	 * @param string $type
	 * @return void
	 */
	protected function checkProgress(string $type): void {
		// Increase the maximum execution time to a bit more than 3 minutes
		set_time_limit(190);

		session_write_close();

		// Get the start time
		$startTime = time();

		// Set the necessary headers for JSON
		header('Content-Type: application/json');
		header('Cache-Control: no-cache');
		header('Connection: keep-alive');

		// Initialize an array to store the media files from the last iteration
		$lastRecords = [];

		// If there are initial elements, remember them for the first iteration
		// Due to race-conditions the initial elements might have been processed already by the time the loop starts
		if ($this->request->getData('elements')) {
			if ($type === 'preview') {
				// Get the media files that do not have a preview image yet
				$query = $this->Media->find()->where([
					'id IN' => $this->request->getData('elements'),
				]);
			}
			elseif ($type === 'resize') {
				// Get the media files that do not have resized images yet
				$query = $this->Media->MediaResizedImages->find()->where([
					'id IN' => $this->request->getData('elements'),
				]);
			}
			else {
				throw new InvalidArgumentException('Invalid type');
			}

			$lastRecords = $query->all()->indexBy('id')->toArray();
		}

		ignore_user_abort(true);

		// Loop for three minutes
		while (time() - $startTime < 180) {
			if (connection_aborted()) {
				// Send an "aborted" event
				$data = ['message' => 'aborted'];
				echo json_encode($data);
				echo "\n";

				// Flush the output buffer one last time
				ob_flush();
				flush();

				exit;
			}

			if ($type === 'preview') {
				// Get the media files that do not have a preview image yet
				$query = $this->Media->find()->where([
					'preview IN' => [
						ProcessStatus::Undefined,
						ProcessStatus::InProgress,
					],
				]);
			}
			elseif ($type === 'resize') {
				// Get the media files that do not have resized images yet
				$query = $this->Media->MediaResizedImages->find()->where([
					'status IN' => [
						ProcessStatus::Undefined,
						ProcessStatus::InProgress,
					],
				]);
			}
			else {
				throw new InvalidArgumentException('Invalid type');
			}

			$currentRecords = $query->all()->indexBy('id')->toArray();

			// Initialize arrays to store the completed and failed records
			$completed = [];
			$failed = [];

			// Check which media files from the last iteration are no longer in the current records
			/** @var \Awyiss\Model\Entity\Media|\Awyiss\Model\Entity\MediaResizedImage $lastRecord */
			foreach ($lastRecords as $id => $lastRecord) {
				if (!isset($currentRecords[ $id ])) {
					// The record is either completed or failed
					if (file_exists($lastRecord instanceof Media ? $lastRecord->previewPathAbsolute : $lastRecord->pathAbsolute)) {
						$completed[] = $id;
					}
					else {
						$failed[] = $id;
					}
				}
			}

			// Output a JSON encoded array with the message, the incomplete records, the completed records and the failed records
			$data = [
				'message' => !$currentRecords ? 'done' : 'waiting',
				'incomplete' => array_keys($currentRecords),
				'completed' => $completed,
				'failed' => $failed,
			];

			// Echo the JSON encoded data
			echo json_encode($data);
			echo "\n";

			// Flush the output buffer
			ob_flush();
			flush();

			// If there are no more media files to process, exit the loop
			if (!$currentRecords) {
				exit;
			}

			// Update the last records with the current records
			$lastRecords = $currentRecords;

			sleep(5);
		}

		exit;
	}


	/**
	 * Show a form to select a folder
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function folderSelect(): void {
		$this->Authorization->ensure('read');

		$where = [];

		if (!$this->request->getParam('includeHidden', false)) {
			$where['hidden'] = false;
		}

		/** @uses \Awyiss\Model\Table::findActive() */
		$mediaFolders = $this->Media->MediaFolders->find('active')->find('threaded')->where($where)->all();
		$mediaFolders = $mediaFolders->groupBy(function (MediaFolder $element) {
			return $element->languageShortcode ?? '';
		})->toArray();

		$currentLanguageShortcode = $this->request->getParam('lang');
		// Sort the grouped media folders by the global and the current language first
		uksort($mediaFolders, function ($a, $b) use ($currentLanguageShortcode) {
			if ($a === '' || $a === $currentLanguageShortcode) {
				return -1;
			}

			if ($b === '' || $b === $currentLanguageShortcode) {
				return 1;
			}

			return 0;
		});

		$this->set([
			'groupedMediaFolders' => $mediaFolders,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'mediaFolderId' => $this->request->getData('media_folder_id'),
		]);

		$this->viewBuilder()->setLayout('overlay_configuration');
	}


	/**
	 * Rebuild the system order to ensure that there are no gaps in the order
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	#[NoDirectAccess]
	public function rebuildSystemOrder(): void {
		$folderid = $this->request->getParam('mediaFolderId');

		if (!$folderid) {
			if ($this->request->is('ajax')) {
				$this->viewBuilder()->setOption('serialize', ['success', 'message']);

				$this->set('success', false);
				$this->set('message', __d('media_folders', 'record_not_found'));

				// Set the view class to JSON
				$this->viewBuilder()->setClassName('Json');

				// Setting the response status to 400 Bad Request
				$this->response = $this->response->withStatus(400, 'Bad Request');
			}
			else {
				$this->Flash->error(__d('media_folders', 'record_not_found'));
				$this->redirect(['action' => 'overview']);
			}
		}

		$affectedRows = $this->Media->getBehavior('SystemOrder')->rebuildSystemOrder('systemOrder', SORT_ASC, null, [
			'media_folder_id' => (int)$folderid,
		]);

		if ($this->request->is('ajax')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', $affectedRows !== false);
			$this->set('message', $affectedRows > 0 ? __d('system', 'system_order_saved') : __d('system', 'system_order_not_saved'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($affectedRows === false) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if ($affectedRows === false) {
				$this->Flash->error(__('rebuild_system_order_failed'));
			}
			else {
				$this->Flash->success(__('rebuild_system_order_succeeded'));
			}

			$this->redirect(['action' => 'overview']);
		}
	}


	/**
	 * Requests a lock for the specified method.
	 * For media elements, the required permission is 'create'.
	 *
	 * @param string $method The method for which the lock is requested. Default is 'create'.
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function requestLock(string $method = 'create'): void {
		parent::requestLock($method);
	}


	/**
	 * Releases a lock based on the provided method type.
	 * For media elements, the required permission is 'create'.
	 *
	 * @param string $method The method type to be used for releasing the lock. Defaults to 'create'.
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function releaseLock(string $method = 'create'): void {
		parent::releaseLock($method);
	}


	/**
	 * Overview method
	 *
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function usages(): Response {
		$this->Authorization->ensure('read');

		$this->request->allowMethod(['get']);

		$id = (int)$this->request->getParam('id');

		if (!$id) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->Media->findById($id)->contain([
			'MediaAssignments.MediaElements',
			'MediaAssignments.MediaElementSelectors',
		])->first();
		if (!$media) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		/** @var \Awyiss\Model\Table\DatatablesTable $datatablesTable */
		$datatablesTable = $this->fetchTable('Datatables');
		$datatables = $datatablesTable->findAllAndCache()->indexBy('identifier')->toArray();

		/** @var \Awyiss\Model\Table\PageRolesTable $pageRolesTable */
		$pageRolesTable = $this->fetchTable('PageRoles');
		$pageRoles = $pageRolesTable->findAllAndCache()->indexBy(function (PageRole $pageRole) {
			return Inflector::pluralize($pageRole->identifier);
		})->toArray();

		$inaccessibleAssignments = [];
		$usedScopes = $this->getUsedScopes($media, $pageRoles, $datatables);

		// Reorder the media assignments by their scope
		$mediaAssignments = collection($media->mediaAssignments ?? [])
			->groupBy('scope')
			->toArray();

		if (isset($mediaAssignments['contents'])) {
			$this->groupAssignmentsByPageRole($mediaAssignments, $usedScopes, $inaccessibleAssignments, $pageRoles);
		}

		Arrays::naturalSort($usedScopes);

		$inaccessibleAssignments = $this->setInaccessibleScopes($inaccessibleAssignments, $usedScopes, $mediaAssignments);

		$this->set([
			'mediaAssignments' => $mediaAssignments,
			'inaccessibleAssignments' => $inaccessibleAssignments,
			'media' => $media,
			'usedScopes' => $usedScopes,
		]);

		return $this->render();
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param string $method
	 * @param bool $isAjax
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Media $media, string $method = 'add', bool $isAjax = false): void {
		$this->patchEntity($media);

		/** @var \Laminas\Diactoros\UploadedFile $uploadedFile */
		$uploadedFile = $this->request->getData('file');

		$this->ensureValidFile($method, $media, $uploadedFile);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$options = [];

			if ($isAjax) {
				$options['systemOrder'] = ['skip' => true];
			}

			if ($this->Media->save($media, $options)) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'mediaFolderId' => $media->mediaFolderId,
						'page' => $this->Paginate->calculateEntityPagePosition($media),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $media->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__($method . '_failed'));
				foreach ($media->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		$this->Categories->ensurePossibleCategory($media);
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $media
	 * @return void
	 * @throws \Exception
	 */
	protected function batchSave(ResultSetInterface $media): void {
		$media = $media->indexBy('id')->toArray();
		$requestData = $this->request->getData('media');
		$requestData = array_map(function (array $data, int $id): array {
			$data['id'] = $id;

			return $data;
		}, $requestData, array_keys($requestData));
		$requestData = array_filter($requestData, function ($data) use ($media): bool {
			return !empty($data['name']) && isset($media[ $data['id'] ]);
		});

		$this->Media->patchEntities($media, $requestData, [
			'fields' => ['name', 'alt', '_translations'],
		]);

		if ($this->Media->saveMany($media)) {
			$this->Flash->success(__('batch_edit_succeeded'));

			throw new RedirectException(Router::url(['action' => 'batchEdit'], true), 302);
		}

		$this->Flash->error(__('batch_edit_failed'));
	}


	/**
	 * @inheritDoc
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		$mediaFolderId = $this->request->getData('media_folder_id');

		$orderData = [];
		foreach ($requestData as $index => $mediaId) {
			$orderData[] = [
				'id' => $mediaId,
				'mediaFolderId' => (int)$mediaFolderId,
				'systemOrder' => $index + 1,
			];
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$affectedRows = $table->updateAll(function (QueryExpression $expression) use ($orderData) {
			$folderCase = $expression->case();
			$systemOrderCase = $expression->case();

			foreach ($orderData as $data) {
				$folderCase->when(['id' => $data['id']])->then($data['mediaFolderId'], 'integer');
				$systemOrderCase->when(['id' => $data['id']])->then($data['systemOrder'], 'integer');
			}

			return [
				'media_folder_id' => $folderCase,
				'system_order' => $systemOrderCase,
			];
		}, [
			'id IN' => array_column($orderData, 'id'),
		]);


		return $affectedRows;
	}


	/**
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	protected function _deleteMultiple(): Response {
		$ids = $this->request->getData('ids');
		$mediaFolderId = $this->request->getData('media_folder_id');

		$error = null;
		if (!$ids) {
			$error = __('no_records_selected');
		}
		else {
			$query = $this->Media->find()->where([
				'id IN' => $ids,
				'media_folder_id' => (int)$mediaFolderId,
			]);
			$media = $query->all();

			if (
				!$media->count() ||
				!$this->Media->deleteMany($media, [
					'atomic' => false,
					'systemOrder' => ['skip' => $media->count() > 1],
				])
			) {
				$error = __('delete_failed');
			}
			elseif ($media->count() > 1) {
				// Rebuild the system order if multiple records were deleted
				$this->Media->getBehavior('SystemOrder')->rebuildSystemOrder('systemOrder', SORT_ASC, null, [
					'media_folder_id' => (int)$mediaFolderId,
				]);
			}
		}

		if ($this->request->is('ajax')) {
			$response = [
				'message' => $error ?: __('delete_succeeded'),
				'success' => $error === null,
			];

			return $this->response->withStatus($error ? 400 : 200, $error ? 'Bad Request' : 'OK')
				->withType('application/json')
				->withStringBody(json_encode($response));
		}

		if ($error) {
			$this->Flash->error(__('no_records_selected'));
		}
		else {
			$this->Flash->success(__('delete_succeeded'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param string $method
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param \Laminas\Diactoros\UploadedFile|null $uploadedFile
	 * @return void
	 * @throws \Exception
	 */
	protected function ensureValidFile(string $method, Media $media, ?UploadedFile $uploadedFile): void {
		if (
			$method === 'edit' &&
			(
				$this->request->is('ajax') || // When editing via AJAX, no file upload is allowed
				!$this->Authorization->isAccessible('create') // When in normal edit mode, no file upload is allowed if the user does not have the create permission
			)
		) {
			$media->file = null;

			if ($media->originalExtension && !str_ends_with($media->name, $media->originalExtension)) {
				$media->name .= '.' . $media->originalExtension;
			}
		}
		elseif (
			!$uploadedFile ||
			$uploadedFile->getError() === UPLOAD_ERR_INI_SIZE ||
			$uploadedFile->getError() === UPLOAD_ERR_FORM_SIZE
		) {
			$media->setError(
				'file',
				__df(
					strtolower($this->getName()),
					'validation',
					'error_media_file_size_within_limit'
				),
				true
			);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @return void
	 */
	protected function patchEntity(Media $media): void {
		$associated = [];
		if ($this->Media->hasAttributes()) {
			$associated[] = $this->Media->getAttributesTableName(true);
			$media->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();

		/** @var \Laminas\Diactoros\UploadedFile $uploadedFile */
		$uploadedFile = $this->request->getData('file');

		$extension = null;
		if ($uploadedFile && !$uploadedFile->getError()) {
			if (empty($requestData['name'])) {
				$requestData['name'] = $uploadedFile->getClientFilename();
			}

			$dotPos = strrpos($requestData['name'], '.');
			$extension = substr($requestData['name'], $dotPos + 1);

			$requestData['mimeType'] = $this->Media->detectMimeType($uploadedFile, $extension);
		}
		elseif (!empty($requestData['name'])) {
			$extension = $media->extension;
		}

		if ($extension && !str_ends_with($requestData['name'], $extension)) {
			$requestData['name'] .= '.' . $extension;
		}

		$requestData['crop'] = array_filter($requestData['crop'] ?? [], 'is_numeric');
		if (count($requestData['crop']) !== 6 || $this->request->getData('reload_form')) {
			$requestData['crop'] = null;
		}
		else {
			// If crop values and resize values are the same as the original values, set crop to null
			if (
				$media->width === (float)$requestData['crop']['width'] &&
				$media->width === (float)$requestData['crop']['resize_width'] &&
				$media->height === (float)$requestData['crop']['height'] &&
				$media->height === (float)$requestData['crop']['resize_height']
			) {
				$requestData['crop'] = null;
			}
		}

		$this->Media->patchEntity($media, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reload_form'),
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @return string|null
	 */
	protected function getErrorMessage(Media $media): ?string {
		if (!$media->hasErrors()) {
			return null;
		}

		$errors = $media->getErrors();
		//First key will be the field name
		$field = key($errors);

		$errors = $errors[ $field ];
		$errorMessage = __(Inflector::underscore($field)) . ': ';
		$errorMessage .= array_values($errors)[0];

		return $errorMessage;
	}


	/**
	 * @param array $mediaAssignments
	 * @param array $usedScopes
	 * @param array $inaccessibleAssignments
	 * @param array $pageRoles
	 * @return void
	 * @throws \Exception
	 */
	protected function groupAssignmentsByPageRole(array &$mediaAssignments, array &$usedScopes, array &$inaccessibleAssignments, array $pageRoles): void {
		// Contents need to be grouped by their page's role
		$contentIds = array_column($mediaAssignments['contents'], 'foreign_key');

		/** @var \Awyiss\Model\Table\ContentsTable $contentsTable */
		$contentsTable = $this->fetchTable('Contents');
		$contentsTable->forPageRole($pageRoles['pages']);
		$contents = $contentsTable->find('mediaAssignments', useMediaEntity: true)->where([
			'id IN' => $contentIds,
		])->contain([
			'Pages' => [
				'finder' => [
					'all' => [
						'skipPageRoleCheck' => true,
					],
				],
				'PageRoles',
			],
		])->all()->indexBy('id')->toArray();

		$groupedAssignments = [];
		/**
		 * For each content, group the media assignments by their page's role
		 *
		 * @var \Awyiss\Model\Entity\MediaAssignment $assignment
		 */
		foreach ($mediaAssignments['contents'] as $assignment) {
			/** @var \Awyiss\Model\Entity\Content $content */
			$content = $contents[ $assignment->foreignKey ];
			$pageRole = $content->page->pageRoleId;
			$pageRole = Inflector::underscore(Inflector::pluralize($pageRole->name));

			if (!isset($usedScopes[ $pageRole ])) {
				$translation = __d($pageRole, 'headline_overview');
				$usedScopes[ $pageRole ] = !str_contains($translation, '::') ? $translation : $pageRoles[ $pageRole ]->label;
			}

			/** @noinspection PhpUndefinedFieldInspection */
			$assignment->entity = $content;

			$groupedAssignments[ $pageRole ][] = $assignment;
		}

		foreach ($groupedAssignments as $scope => $assignments) {
			$isAccessible = $this->Authorization->scopeIsAccessible($scope, [], ['contents']);

			if ($isAccessible) {
				continue;
			}

			if (empty($mediaAssignments[ $scope ])) {
				unset($usedScopes[ $scope ], $mediaAssignments[ $scope ]);
			}

			$inaccessibleAssignments = array_merge($inaccessibleAssignments, $assignments);

			unset($groupedAssignments[ $scope ]);
		}

		if (!$groupedAssignments) {
			unset($usedScopes['contents'], $mediaAssignments['contents']);
			return;
		}

		$mediaAssignments['contents'] = $groupedAssignments;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param array $pageRoles
	 * @param array $datatables
	 * @return array
	 */
	protected function getUsedScopes(Media $media, array $pageRoles, array $datatables): array {
		$usedScopes = [];

		// Build the list of scopes that are used by the media assignments
		foreach (array_column($media->mediaAssignments ?? [], 'scope') as $scope) {
			if (isset($usedScopes[ $scope ])) {
				continue;
			}

			if (isset($pageRoles[ $scope ]) && $scope !== 'page') {
				$translation = __d($scope, 'headline_overview');
				$usedScopes[ $scope ] = !str_contains($translation, '::') ? $translation : $pageRoles[ $scope ]->label;

				continue;
			}

			if (isset($datatables[ $scope ])) {
				$translation = __d($scope, 'headline_overview');
				$usedScopes[ $scope ] = !str_contains($translation, '::') ? $translation : $datatables[ $scope ]->label;

				continue;
			}

			$usedScopes[ $scope ] = __d($scope, 'headline_overview');
		}

		return $usedScopes;
	}


	/**
	 * @param array $inaccessibleAssignments
	 * @param array $usedScopes
	 * @param array $mediaAssignments
	 * @return array
	 * @throws \Exception
	 */
	protected function setInaccessibleScopes(array &$inaccessibleAssignments, array &$usedScopes, array &$mediaAssignments): array {
		/** @var \Awyiss\Model\Table\MediaAssignmentsTable $mediaAssignmentsTable */
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');

		foreach ($usedScopes as $scope => $label) {
			if ($scope === 'contents') {
				continue;
			}

			$isAccessible = $this->Authorization->scopeIsAccessible($scope, [], ['read', 'update']);

			if (!$isAccessible) {
				$inaccessibleAssignments = array_merge($inaccessibleAssignments, $mediaAssignments[ $scope ]);
				unset($usedScopes[ $scope ], $mediaAssignments[ $scope ]);

				if (isset($mediaAssignments['contents'][ $scope ])) {
					$inaccessibleAssignments = array_merge($inaccessibleAssignments, $mediaAssignments['contents'][ $scope ]);
					unset($mediaAssignments['contents'][ $scope ]);
				}

				continue;
			}

			if (empty($mediaAssignments[ $scope ])) {
				continue;
			}

			$tableName = Inflector::camelize($scope);

			$mediaAssignmentsTable->belongsTo($tableName, [
				'foreignKey' => 'foreign_key',
				'conditions' => [
					'MediaAssignments.scope' => $scope,
				],
				'propertyName' => 'entity',
			]);

			$mediaAssignmentsTable->loadInto($mediaAssignments[ $scope ], [$tableName]);
		}

		return $inaccessibleAssignments;
	}
}
