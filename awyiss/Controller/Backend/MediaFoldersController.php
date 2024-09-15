<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


/**
 * MediaFolder Controller
 *
 * @property \Awyiss\Model\Table\MediaFoldersTable $MediaFolders
 */
class MediaFoldersController extends Controller {
	/**
	 * @var array List of available languages
	 */
	protected array $languages = [];
	/**
	 * @var string|null Session identifier for the selected language
	 */
	protected ?string $selectedLanguageSessionIdentifier = null;
	/**
	 * @var \Cake\Collection\Iterator\TreeIterator Collection of media folders for the currently set languageShortcode
	 */
	protected CollectionInterface $threadedMediaFolders;


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->languages = [
			'all' => __('all'),
			'global' => __('global'),
			LocaleMiddleware::getLanguage()->shortcode => LocaleMiddleware::getLanguage()->title,
		];

		$this->selectedLanguageSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.language';

		if ($this->request->getParam('action') !== 'overview') {
			return;
		}

		$lo_session = $this->request->getSession();

		//Is there a request parameter with the name 'language'?
		$ls_language = $this->request->getParam('language');

		if ($ls_language) {
			$lo_session->write($this->selectedLanguageSessionIdentifier, $ls_language);
		}
		else {
			$ls_language = $lo_session->read($this->selectedLanguageSessionIdentifier, 'all');
		}

		//If the selected scope is not inside the available configuration scopes, reset it to the first available one.
		if (!array_key_exists($ls_language, $this->languages) && $ls_language !== 'all') {
			$ls_language = array_key_first($this->languages);

			$lo_session->write($this->selectedLanguageSessionIdentifier, $ls_language);

			//Redirect to remove the invalid scope parameter from the URL
			$this->redirect(['action' => 'overview']);
		}

		$this->setOverviewWhere('language_shortcode', $ls_language);
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->MediaFolders->find('forCurrentLanguage');

		if ($this->getOverviewWhere('language_shortcode') !== 'all') {
			$la_overviewWhere = $this->getOverviewWhere();
			if (($la_overviewWhere['language_shortcode'] ?? null) === 'global') {
				$la_overviewWhere['language_shortcode IS'] = null;
				unset($la_overviewWhere['language_shortcode']);
			}

			$lo_query->where($la_overviewWhere);
		}

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

		$lb_paginated = $this->paginate['enabled'];
		if ($lb_paginated) {
			$lo_mediaFolders = $this->paginate($lo_query);
		}
		else {
			$lo_query->orderBy('language_shortcode');

			$lo_mediaFolders = $lo_query->find('threaded')->all()->groupBy(function (MediaFolder $mediaFolder) {
				return $mediaFolder->languageShortcode ?? '_global';
			});
		}

		$this->set([
			'mediaFolders' => $lo_mediaFolders,
			'languages' => $this->languages,
			'selectedLanguage' => $this->getOverviewWhere('language_shortcode'),
			'paginated' => $lb_paginated,
			'attributes' => $this->MediaFolders->getAttributes(),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->ensure('create');

		$lo_session = $this->request->getSession();
		$ls_languageShortcode = $lo_session->read($this->selectedLanguageSessionIdentifier, 'all');
		if (strlen($ls_languageShortcode) !== 2) {
			$ls_languageShortcode = null;
		}

		$lo_mediaFolder = $this->MediaFolders->newDefaultEntity([
			'languageShortcode' => $ls_languageShortcode,
		]);

		if ($this->request->is('post')) {
			$this->save($lo_mediaFolder);
		}

		$this->setViewVars($lo_mediaFolder);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var \Awyiss\Model\Entity\MediaFolder $lo_mediaFolder */
		$lo_mediaFolder = $this->MediaFolders->findById($id)->find('translations')->first();

		if (!$lo_mediaFolder) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_mediaFolder, 'edit');
		}
		elseif ($lo_mediaFolder->language_shortcode !== null && $lo_mediaFolder->language_shortcode != LocaleMiddleware::getLanguage()->shortcode) {
			//Don't allow modifying a media folder in another language
			throw new RedirectException(Router::url([
				'lang' => $lo_mediaFolder->languageShortcode,
				'id' => $lo_mediaFolder->id,
			], true), 302);
		}

		$this->setViewVars($lo_mediaFolder);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\MediaFolder $lo_mediaFolder */
		$lo_mediaFolder = $this->MediaFolders->findById($id)->first();
		if (!$lo_mediaFolder) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MediaFolders->delete($lo_mediaFolder)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_mediaFolder->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $mediaFolder
	 * @param string $method
	 * @return void
	 */
	protected function save(MediaFolder $mediaFolder, string $method = 'add'): void {
		$la_associated = [];
		if ($this->MediaFolders->hasAttributes()) {
			$la_associated[] = $this->MediaFolders->getAttributesTableName(true);
			$mediaFolder->setAccess('attributes', true);
		}

		$this->MediaFolders->patchEntity($mediaFolder, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->MediaFolders->save($mediaFolder, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				$lo_session = $this->request->getSession();
				$ls_languageShortcode = $mediaFolder->languageShortcode ?? 'global';
				if ($lo_session->read($this->selectedLanguageSessionIdentifier, 'all') !== 'all') {
					$lo_session->write($this->selectedLanguageSessionIdentifier, $ls_languageShortcode);
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'lang' => $mediaFolder->languageShortcode,
						'page' => $this->Paginate->calculateEntityPagePosition($mediaFolder),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $mediaFolder->languageShortcode, 'id' => $mediaFolder->id], true), 302);
			}

			$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
			foreach ($mediaFolder->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->MediaFolders->getSystemOrderRelatedColumns($mediaFolder)) {
				$mediaFolder->systemOrder = null;
			}
			else {
				$mediaFolder->systemOrder = $mediaFolder->hasOriginal('systemOrder') ? $mediaFolder->getOriginal('systemOrder') : $mediaFolder->get('systemOrder');
			}
		}
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		// Create a flat array of all order data
		$la_orderData = [];
		foreach ($requestData as $la_itemsByLanguageShortcode) {
			foreach ($la_itemsByLanguageShortcode as $la_items) {
				array_map(function (array $item) use (&$la_orderData) {
					$la_data = $item;

					if ($la_data['languageShortcode'] === '_global') {
						$la_data['languageShortcode'] = null;
					}

					$la_orderData[] = $la_data;
				}, $la_items);
			}
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$li_affectedRows = $table->updateAll(function (QueryExpression $expression) use ($la_orderData) {
			$lo_languageShortcodeCase = $expression->case();
			$lo_systemOrderCase = $expression->case();

			foreach ($la_orderData as $la_data) {
				$lo_languageShortcodeCase->when(['id' => $la_data['id']])->then($la_data['languageShortcode'], 'string');
				$lo_systemOrderCase->when(['id' => $la_data['id']])->then($la_data['systemOrder'], 'integer');
			}

			return [
				'language_shortcode' => $lo_languageShortcodeCase,
				'system_order' => $lo_systemOrderCase,
			];
		}, [
			'id IN' => array_column($la_orderData, 'id'),
		]);


		return $li_affectedRows;
	}


	/**
	 * Return a collection of media folders for the currently set languageShortcode,
	 * using `\Cake\Collection\CollectionTrait::listNested()` to be used in a form-select
	 *
	 * @param \Awyiss\Model\Entity\MediaFolder $mediaFolder
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Cake\Collection\CollectionTrait::listNested()
	 */
	protected function getThreadedMediaFolders(MediaFolder $mediaFolder): CollectionInterface {
		if (!isset($this->threadedMediaFolders)) {
			$lo_query = $this->MediaFolders->find('forCurrentLanguage', languageShortcode: $mediaFolder->languageShortcode ?? '_global', includeGlobal: false)
			->where($this->getOverviewWhere());

			$this->threadedMediaFolders = $this->MediaFolders->listNested($lo_query);
		}

		return $this->threadedMediaFolders;
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $mediaFolder
	 * @param \Cake\Collection\CollectionInterface $threadedMediaFolders
	 * @return void
	 */
	protected function ensurePossibleParentId(MediaFolder $mediaFolder, CollectionInterface $threadedMediaFolders): void {
		$la_possibleParentIds = $threadedMediaFolders->extract('id')->toList();

		if (!empty($mediaFolder->parentId) && !in_array($mediaFolder->parentId, $la_possibleParentIds)) {
			$la_errors = $mediaFolder->getError('parentId');

			$mediaFolder->parentId = reset($la_possibleParentIds);

			if ($mediaFolder->parentId === 1) {
				$mediaFolder->parentId = null;
			}

			if ($la_errors) {
				$mediaFolder->setError('parentId', $la_errors, true);
			}
		}

		$lo_request = $this->getRequest();
		//When the field is part of the request data, overwrite it since it might be outdated
		if ($lo_request->getData('parent_id') !== null) {
			$lo_request = $lo_request->withData('parent_id', $mediaFolder->parentId);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $mediaFolder
	 * @return void
	 */
	protected function setViewVars(MediaFolder $mediaFolder): void {
		$lo_threadedMediaFolders = $this->getThreadedMediaFolders($mediaFolder);

		$lo_possibleParentMediaFolders = $this->MediaFolders->getPossibleParents($mediaFolder, $lo_threadedMediaFolders);
		$this->ensurePossibleParentId($mediaFolder, $lo_possibleParentMediaFolders);

		// Get the parent media folder if it exists
		if ($mediaFolder->parentId) {
			$lo_parentFolder = $this->MediaFolders->findById($mediaFolder->parentId)->first();
		}

		$this->set([
			'mediaFolder' => $mediaFolder,
			'threadedMediaFolders' => $lo_threadedMediaFolders,
			'possibleParentMediaFolders' => $lo_possibleParentMediaFolders,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'parentFolder' => $lo_parentFolder ?? null,
		]);
	}
}
