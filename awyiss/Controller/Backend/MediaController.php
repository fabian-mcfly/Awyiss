<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;


/**
 * Media Controller
 *
 * @property \Awyiss\Model\Table\MediaTable $Media
 */
class MediaController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'queryConditions' => [
			'active' => true,
			'parents_active' => true,
		],
		'uriParam' => 'media-folder-id',
	];
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => false,
		'order' => [
			'name' => 'asc',
		],
	];


	/**
	 * @inheritDoc
	 */
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->Media->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();

		if ($this->request->getParam('paginate')) {
			$lb_paginated = ($this->request->getParam('paginate', 'false') === 'true');

			if ($lb_paginated) {
				$this->Paginate->enable();
			}
			else {
				$this->Paginate->disable();
			}
		}
		else {
			$lb_paginated = $this->paginate['enabled'];
		}

		if ($lb_paginated) {
			$lo_media = $this->paginate($lo_query);
		}
		else {
			$lo_media = $lo_query->all();
		}

		$lo_mediaFolders = $this->Media->MediaFolders->find('active')->find('threaded')->all();
		$la_mediaFolders = $lo_mediaFolders->groupBy(function (MediaFolder $ao_element) {
			return $ao_element->languageShortcode ?? '';
		})->toArray();

		$ls_currentLanguageShortcode = $this->request->getParam('lang');
		// Sort the grouped media folders by the global and the current language first
		uksort($la_mediaFolders, function ($as_a, $as_b) use ($ls_currentLanguageShortcode) {
			if ($as_a === '' || $as_a === $ls_currentLanguageShortcode) {
				return -1;
			}

			if ($as_b === '' || $as_b === $ls_currentLanguageShortcode) {
				return 1;
			}

			return 0;
		});

		$this->set([
			'media' => $lo_media,
			'groupedMediaFolders' => $la_mediaFolders,
			'maxFileSize' => $this->Media->getMaxFileSize(),
			'isAjax' => $this->request->is('ajax'),
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'paginated' => $lb_paginated,
			'attributes' => $this->Media->getAttributes(),
			'ResizeStrategy' => ResizeStrategy::class,
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

		$lo_media = $this->Media->newDefaultEntity();

		if ($this->request->is('post')) {
			try {
				$this->save($lo_media, 'add', $this->request->is('ajax'));
			}
			catch (RedirectException $lo_exception) {
				if (!$this->request->is('ajax')) {
					throw $lo_exception;
				}
			}

			if ($this->request->is('ajax') && !$this->request->getData('reload_form')) {
				$ls_errorMessage = null;
				if ($lo_media->hasErrors()) {
					$la_errors = $lo_media->getErrors();
					//First key will be the field name
					$ls_field = key($la_errors);

					$la_errors = $la_errors[ $ls_field ];
					$ls_errorMessage = __(Inflector::underscore($ls_field)) . ': ';
					$ls_errorMessage .= array_values($la_errors)[0];
				}

				// Consume the flash messages to prevent them from being displayed the next time the page is loaded
				$this->request->getFlash()->consume('media');

				// Set the view class to JSON
				$this->viewBuilder()->disableAutoLayout();

				$lo_elementView = $this->createView();
				$lo_elementView->set([
					'mediaItem' => $lo_media,
					'paginate' => ($this->request->getParam('paginate', 'false') === 'true'),
					'attributes' => $this->Media->getAttributes(),
					'ResizeStrategy' => ResizeStrategy::class,
				]);

				$this->set([
					'response' => [
						'html' => $lo_media->hasErrors() ? null : $lo_elementView->render('Backend/Media/overview_ajax_item'),
						'message' => $ls_errorMessage,
						'success' => !$lo_media->hasErrors(),
						'id' => 'Media-ListItem' . $lo_media->id,
						'item' => $lo_media->getErrors(),
					],
				]);

				$this->viewBuilder()->setClassName('Json');
				$this->viewBuilder()->setOption('serialize', 'response');
			}
		}

		$this->set([
			'media' => $lo_media,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'ProcessStatus' => ProcessStatus::class,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		try {
			/** @var \Awyiss\Model\Entity\Media $lo_media */
			$lo_media = $this->Media->findById($ai_id)->find('translations')->first();

			if (!$lo_media) {
				$this->Flash->error(__('record_not_found'));

				return $this->redirect(['action' => 'overview']);
			}

			if ($this->request->is(['patch', 'post', 'put'])) {
				$this->save($lo_media, 'edit', $this->request->is('ajax'));
			}
		}
		catch (RedirectException $lo_exception) {
			if (!$this->request->is('ajax') || $this->request->getParam('ajaxForm')) {
				throw $lo_exception;
			}
		}

		if ($this->request->is(['patch', 'post', 'put']) && $this->request->is('ajax') && !$this->request->getData('reload_form')) {
			$ls_errorMessage = null;
			if ($lo_media->hasErrors()) {
				$la_errors = $lo_media->getErrors();
				//First key will be the field name
				$ls_field = key($la_errors);

				$la_errors = $la_errors[ $ls_field ];
				$ls_errorMessage = __(Inflector::underscore($ls_field)) . ': ';
				$ls_errorMessage .= array_values($la_errors)[0];
			}

			// Consume the flash messages to prevent them from being displayed the next time the page is loaded
			$this->request->getFlash()->consume('media');

			// Set the view class to JSON
			$this->viewBuilder()->disableAutoLayout();

			$lo_elementView = $this->createView();
			$lo_elementView->set([
				'mediaItem' => $lo_media,
				'paginate' => ($this->request->getParam('paginate', 'false') === 'true'),
				'ResizeStrategy' => ResizeStrategy::class,
			]);

			$this->set([
				'response' => [
					'html' => $lo_media->hasErrors() ? null : $lo_elementView->render('Backend/Media/overview_ajax_item'),
					'message' => $ls_errorMessage,
					'success' => !$lo_media->hasErrors(),
					'id' => 'Media-ListItem' . $lo_media->id,
					'item' => $lo_media->getErrors(),
				],
			]);

			$this->viewBuilder()->setClassName('Json');
			$this->viewBuilder()->setOption('serialize', 'response');
		}

		$this->set([
			'media' => $lo_media,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'ProcessStatus' => ProcessStatus::class,
			'ResizeStrategy' => ResizeStrategy::class,
		]);
	}


	/**
	 * Delete method
	 *
	 * @param ?int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(?int $ai_id = null): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		if (!$ai_id && $this->request->getMethod() === 'DELETE') {
			return $this->_deleteMultiple();
		}

		/** @var Media $lo_media */
		$lo_media = $this->Media->findById($ai_id)->find('translations')->first();
		if (!$lo_media) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Media->delete($lo_media)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_media->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Check the progress of the creation of preview images for the media files
	 * that do not have a preview image yet
	 *
	 * @return void
	 */
	public function checkPreviewProgress(): void {
		$this->checkProgress('preview');
	}


	/**
	 * Check the progress of the resizing of the images (or preview images) for the media files
	 *
	 * @return void
	 */
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
		$li_startTime = time();

		// Set the necessary headers for JSON
		header('Content-Type: application/json');
		header('Cache-Control: no-cache');
		header('Connection: keep-alive');

		// Initialize an array to store the media files from the last iteration
		$la_lastRecords = [];

		// If there are initial elements, remember them for the first iteration
		// Due to race conditions, the initial elements might have been processed already by the time the loop starts
		if ($this->request->getData('elements')) {
			if ($type === 'preview') {
				// Get the media files that do not have a preview image yet
				$lo_query = $this->Media->find()->where([
					'id IN' => $this->request->getData('elements'),
				]);
			}
			elseif ($type === 'resize') {
				// Get the media files that do not have resized images yet
				$lo_query = $this->Media->MediaResizedImages->find()->where([
					'id IN' => $this->request->getData('elements'),
				]);
			}
			else {
				throw new InvalidArgumentException('Invalid type');
			}

			$la_lastRecords = $lo_query->all()->indexBy('id')->toArray();
		}

		ignore_user_abort(true);

		// Loop for three minutes
		while (time() - $li_startTime < 180) {
			if (connection_aborted()) {
				// Send an "aborted" event
				$la_data = ['message' => 'aborted'];
				echo json_encode($la_data);

				// Flush the output buffer one last time
				ob_flush();
				flush();

				exit;
			}

			if ($type === 'preview') {
				// Get the media files that do not have a preview image yet
				$lo_query = $this->Media->find()->where([
					'preview IN' => [
						ProcessStatus::Undefined,
						ProcessStatus::InProgress,
					],
				]);
			}
			elseif ($type === 'resize') {
				// Get the media files that do not have resized images yet
				$lo_query = $this->Media->MediaResizedImages->find()->where([
					'status IN' => [
						ProcessStatus::Undefined,
						ProcessStatus::InProgress,
					],
				]);
			}
			else {
				throw new InvalidArgumentException('Invalid type');
			}

			$la_currentRecords = $lo_query->all()->indexBy('id')->toArray();

			// Initialize arrays to store the completed and failed records
			$la_completed = [];
			$la_failed = [];

			// Check which media files from the last iteration are no longer in the current records
			/** @var \Awyiss\Model\Entity\Media|\Awyiss\Model\Entity\MediaResizedImage $lo_lastRecord */
			foreach ($la_lastRecords as $li_id => $lo_lastRecord) {
				if (!isset($la_currentRecords[ $li_id ])) {
					// The record is either completed or failed
					if (file_exists($lo_lastRecord instanceof Media ? $lo_lastRecord->previewPathAbsolute : $lo_lastRecord->pathAbsolute)) {
						$la_completed[] = $li_id;
					}
					else {
						$la_failed[] = $li_id;
					}
				}
			}

			// Output a JSON encoded array with the message, the incomplete records, the completed records and the failed records
			$la_data = [
				'message' => !$la_currentRecords ? 'done' : 'waiting',
				'incomplete' => array_keys($la_currentRecords),
				'completed' => $la_completed,
				'failed' => $la_failed,
			];

			// Echo the JSON encoded data
			echo json_encode($la_data);

			// Flush the output buffer
			ob_flush();
			flush();

			// If there are no more media files to process, exit the loop
			if (!$la_currentRecords) {
				exit;
			}

			// Update the last records with the current records
			$la_lastRecords = $la_currentRecords;

			sleep(5);
		}

		exit;
	}


	/**
	 * Rebuild the system order to ensure that there are no gaps in the order
	 *
	 * @return void
	 */
	public function rebuildSystemOrder(): void {
		$li_folderid = $this->request->getParam('mediaFolderId');

		if (!$li_folderid) {
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

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$li_affectedRows = $this->Media->getBehavior('SystemOrder')->rebuildSystemOrder('systemOrder', SORT_ASC, null, [
			'media_folder_id' => (int)$li_folderid,
		]);

		if ($this->request->is('ajax')) {
			$this->viewBuilder()->setOption('serialize', ['success', 'message']);

			$this->set('success', $li_affectedRows !== false);
			$this->set('message', $li_affectedRows > 0 ? __d('system', 'system_order_saved') : __d('system', 'system_order_not_saved'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			if ($li_affectedRows === false) {
				// Setting the response status to 422 Unprocessable Entity
				$this->response = $this->response->withStatus(422, 'Unable to process entity');
			}
		}
		else {
			if ($li_affectedRows === false) {
				$this->Flash->error(__('rebuild_system_order_failed'));
			}
			else {
				$this->Flash->success(__('rebuild_system_order_succeeded'));
			}

			$this->redirect(['action' => 'overview']);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_mediaFolder
	 * @param string $as_method
	 * @param bool $ab_isAjax
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Media $ao_media, string $as_method = 'add', bool $ab_isAjax = false): void {
		$la_associated = [];
		if ($this->Media->hasAttributes()) {
			$la_associated[] = $this->Media->getAttributesTableName(true);
			$ao_media->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		/** @var \Laminas\Diactoros\UploadedFile $lo_uploadedFile */
		$lo_uploadedFile = $this->request->getData('file');

		$ls_extension = null;
		if ($lo_uploadedFile && !$lo_uploadedFile->getError()) {
			if (empty($la_data['name'])) {
				$la_data['name'] = $lo_uploadedFile->getClientFilename();
			}
			else {
				$li_dotPos = strrpos($lo_uploadedFile->getClientFilename(), '.');
				$ls_extension = substr($lo_uploadedFile->getClientFilename(), $li_dotPos + 1);
			}
		}
		elseif (!empty($la_data['name'])) {
			$ls_extension = $ao_media->extension;
		}

		if ($ls_extension && !str_ends_with($la_data['name'], $ls_extension)) {
			$la_data['name'] .= '.' . $ls_extension;
		}

		$la_data['crop'] = array_filter($la_data['crop'] ?? [], 'is_numeric');
		if (count($la_data['crop']) !== 6 || $this->request->getData('reload_form')) {
			$la_data['crop'] = null;
		}

		$this->Media->patchEntity($ao_media, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		$this->ensureValidFile($as_method, $ao_media, $lo_uploadedFile);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$la_options = [];

			if ($ab_isAjax) {
				$la_options = [
					'systemOrder' => ['skip' => true],
				];
			}

			if ($this->Media->save($ao_media, $la_options)) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($as_method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'mediaFolderId' => $ao_media->mediaFolderId,
						'page' => $this->Paginate->calculateEntityPagePosition($ao_media),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_media->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_media->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->Media->getSystemOrderRelatedColumns($ao_media)) {
				$ao_media->systemOrder = null;
			}
			else {
				$ao_media->systemOrder = $ao_media->hasOriginal('systemOrder') ? $ao_media->getOriginal('systemOrder') : $ao_media->get('systemOrder');
			}
		}

		$this->Categories->ensurePossibleCategory($ao_media);
	}


	/**
	 * @inheritDoc
	 */
	protected function _saveSystemOrder(array $aa_requestData, Table $ao_table): int {
		$li_mediaFolderId = $this->request->getData('media_folder_id');

		$la_orderData = [];
		foreach ($aa_requestData as $li_index => $li_mediaId) {
			$la_orderData[] = [
				'id' => $li_mediaId,
				'mediaFolderId' => (int)$li_mediaFolderId,
				'systemOrder' => $li_index + 1,
			];
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$li_affectedRows = $ao_table->updateAll(function (QueryExpression $ao_expression) use ($la_orderData) {
			$lo_folderCase = $ao_expression->case();
			$lo_systemOrderCase = $ao_expression->case();

			foreach ($la_orderData as $la_data) {
				$lo_folderCase->when(['id' => $la_data['id']])->then($la_data['mediaFolderId'], 'integer');
				$lo_systemOrderCase->when(['id' => $la_data['id']])->then($la_data['systemOrder'], 'integer');
			}

			return [
				'media_folder_id' => $lo_folderCase,
				'system_order' => $lo_systemOrderCase,
			];
		}, [
			'id IN' => array_column($la_orderData, 'id'),
		]);


		return $li_affectedRows;
	}


	/**
	 * @return \Cake\Http\Response
	 */
	protected function _deleteMultiple(): Response {
		$li_ids = $this->request->getData('ids');
		$li_mediaFolderId = $this->request->getData('media_folder_id');

		$ls_error = null;
		if (!$li_ids) {
			$ls_error = __('no_records_selected');
		}
		else {
			$lo_query = $this->Media->find()->where([
				'id IN' => $li_ids,
				'media_folder_id' => (int)$li_mediaFolderId,
			]);
			$lo_media = $lo_query->all();

			if (
				!$lo_media->count() ||
				!$this->Media->deleteMany($lo_media, [
					'atomic' => false,
					'systemOrder' => ['skip' => $lo_media->count() > 1],
				])
			) {
				$ls_error = __('delete_failed');
			}
			elseif ($lo_media->count() > 1) {
				// Rebuild the system order if multiple records were deleted
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$this->Media->getBehavior('SystemOrder')->rebuildSystemOrder('systemOrder', SORT_ASC, null, [
					'media_folder_id' => (int)$li_mediaFolderId,
				]);
			}
		}

		if ($this->request->is('ajax')) {
			$la_response = [
				'message' => $ls_error ?: __('delete_succeeded'),
				'success' => $ls_error === null,
			];

			return $this->response->withStatus($ls_error ? 400 : 200, $ls_error ? 'Bad Request' : 'OK')
				->withType('application/json')
				->withStringBody(json_encode($la_response));
		}

		if ($ls_error) {
			$this->Flash->error(__('no_records_selected'));
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param string $as_method
	 * @param \Awyiss\Model\Entity\Media $ao_media
	 * @param \Laminas\Diactoros\UploadedFile|null $ao_uploadedFile
	 * @return void
	 * @throws \Exception
	 */
	protected function ensureValidFile(string $as_method, Media $ao_media, ?UploadedFile $ao_uploadedFile): void {
		if (
			$as_method === 'edit' &&
			(
				$this->request->is('ajax') ||
				!$this->Authorization->isAccessible('create')
			)
		) {
			$ao_media->file = null;

			if ($ao_media->originalExtension && !str_ends_with($ao_media->name, $ao_media->originalExtension)) {
				$ao_media->name .= '.' . $ao_media->originalExtension;
			}
		}
		elseif (
			!$ao_uploadedFile ||
			$ao_uploadedFile->getError() === UPLOAD_ERR_INI_SIZE ||
			$ao_uploadedFile->getError() === UPLOAD_ERR_FORM_SIZE
		) {
			$ao_media->setError(
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
}
