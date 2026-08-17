<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use InvalidArgumentException;


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

		$this->selectedLanguageSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.'
			. Inflector::variable($this->getName()) . '.language'
		;

		if ($this->request->getParam('action') !== 'overview') {
			return;
		}

		$session = $this->request->getSession();

		//Is there a request parameter with the name 'language'?
		$language = $this->request->getParam('language');

		if ($language) {
			$session->write($this->selectedLanguageSessionIdentifier, $language);
		}
		else {
			$language = $session->read($this->selectedLanguageSessionIdentifier, 'all');
		}

		//If the selected scope is not inside the available configuration scopes, reset it to the first available one.
		if (!array_key_exists($language, $this->languages) && $language !== 'all') {
			$language = array_key_first($this->languages);

			$session->write($this->selectedLanguageSessionIdentifier, $language);

			//Redirect to remove the invalid scope parameter from the URL
			$this->redirect(['action' => 'overview']);
		}

		$this->setOverviewWhere('languageShortcode', $language);
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		$query = $this->MediaFolders->find('forCurrentLanguage')->where(['hidden' => false]);

		if ($this->getOverviewWhere('languageShortcode') !== 'all') {
			$overviewWhere = $this->getOverviewWhere();
			if (($overviewWhere['languageShortcode'] ?? null) === 'global') {
				$overviewWhere['languageShortcode IS'] = null;
				unset($overviewWhere['languageShortcode']);
			}

			$query->where($overviewWhere);
		}

		$this->Search->filterQuery($query);

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

		$paginated = $this->paginate['enabled'];
		if ($paginated) {
			$mediaFolders = $this->paginate($query);
		}
		else {
			$query->orderBy('languageShortcode');

			$mediaFolders = $query
				->find('threaded')
				->all()
				->groupBy(fn(MediaFolder $mediaFolder) => $mediaFolder->languageShortcode ?? '_global')
			;
		}

		$this->set([
			'mediaFolders' => $mediaFolders,
			'languages' => $this->languages,
			'selectedLanguage' => $this->getOverviewWhere('languageShortcode'),
			'paginated' => $paginated,
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

		$session = $this->request->getSession();
		$languageShortcode = $session->read($this->selectedLanguageSessionIdentifier, 'all');
		if (strlen($languageShortcode) !== 2) {
			$languageShortcode = null;
		}

		$mediaFolder = $this->MediaFolders->newDefaultEntity([
			'languageShortcode' => $languageShortcode,
		]);

		if ($this->request->is('post')) {
			$this->save($mediaFolder);
		}

		$this->setViewVars($mediaFolder);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/**
		 * @var \Awyiss\Model\Entity\MediaFolder $mediaFolder
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$mediaFolder = $this->MediaFolders
			->findById($id)
			->find('translations')
			->first()
		;

		if (!$mediaFolder) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($mediaFolder, 'edit');
		}
		elseif ($mediaFolder->languageShortcode !== null && $mediaFolder->languageShortcode != LocaleMiddleware::getLanguage()->shortcode) {
			//Don't allow modifying a media folder in another language
			throw new RedirectException(Router::url([
				'lang' => $mediaFolder->languageShortcode,
				'id' => $mediaFolder->id,
			], true), 302);
		}

		$this->setViewVars($mediaFolder);
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

		/** @var \Awyiss\Model\Entity\MediaFolder $mediaFolder */
		$mediaFolder = $this->MediaFolders->findById($id)->first();
		if (!$mediaFolder) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		$children = $this->MediaFolders->getNestedChildren($mediaFolder);

		// Check if any of the subfolders is hidden
		$hiddenSubfolders = $children
			->filter(fn(MediaFolder $mediaFolder) => $mediaFolder->hidden)
			->extract('id')
			->toArray()
		;
		if ($mediaFolder->hidden || $hiddenSubfolders) {
			$this->Flash->error(__('error_subfolders_hidden'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MediaFolders->delete($mediaFolder)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($mediaFolder->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
		$associated = [];
		if ($this->MediaFolders->hasAttributes()) {
			$associated[] = $this->MediaFolders->getAttributesTableName(true);
			$mediaFolder->setAccess('attributes', true);
		}

		$this->MediaFolders->patchEntity($mediaFolder, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->MediaFolders->save($mediaFolder, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				$session = $this->request->getSession();
				$languageShortcode = $mediaFolder->languageShortcode ?? 'global';
				if ($session->read($this->selectedLanguageSessionIdentifier, 'all') !== 'all') {
					$session->write($this->selectedLanguageSessionIdentifier, $languageShortcode);
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'lang' => $mediaFolder->languageShortcode,
						'page' => $this->Paginate->calculateEntityPagePosition($mediaFolder),
					], true), 302);
				}

				throw new RedirectException(
					Router::url(['action' => 'edit', 'lang' => $mediaFolder->languageShortcode, 'id' => $mediaFolder->id], true),
					302
				);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($mediaFolder->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * Check if the media folder or one of its children,
	 * or items in them, are used in the system.
	 *
	 * @param \Awyiss\Model\Entity\MediaFolder|null $mediaFolder
	 * @return \Cake\Http\Response|bool
	 * @noinspection PhpUnused
	 */
	public function checkUsage(?MediaFolder $mediaFolder = null): bool|Response {
		if (!$mediaFolder) {
			// Check if the request contains a media folder ID
			$mediaFolderId = $this->request->getParam('id');

			if ($mediaFolderId) {
				$mediaFolder = $this->MediaFolders->findById($mediaFolderId)->first();
			}
		}

		// Still no media folder? Throw an error
		if (!$mediaFolder) {
			if (!$this->request->is('ajax')) {
				throw new InvalidArgumentException('No media folder was provided');
			}

			$this->viewBuilder()->setOption('serialize', ['success', 'message', 'inUse', 'hiddenChildren']);

			$this->set('success', false);
			$this->set('inUse');
			$this->set('hiddenChildren');
			$this->set('message', __('record_not_found'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			return $this->render()->withStatus(404);
		}

		$children = $this->MediaFolders->getNestedChildren($mediaFolder);

		// Check if any of the subfolders is hidden
		$hiddenSubfolders = $children
			->filter(fn(MediaFolder $mediaFolder) => $mediaFolder->hidden)
			->extract('id')
			->toArray()
		;

		if ($mediaFolder->hidden || $hiddenSubfolders) {
			if (!$this->request->is('ajax')) {
				return true;
			}

			$this->viewBuilder()->setOption('serialize', ['success', 'message', 'inUse', 'hiddenChildren']);

			$this->set('success', true);
			$this->set('inUse');
			$this->set('hiddenChildren', true);
			$this->set('message', __('error_subfolders_hidden'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			return $this->render()->withStatus(200);
		}


		$mediaFolderIds = [$mediaFolder->id, ...array_column($children->toArray(), 'id')];

		$mediaFolderAssignments = $this->MediaFolders->MediaAssignments
			->find()
			->where([
				'mediaFolderId IN' => $mediaFolderIds,
			])
			->count()
		;

		if ($mediaFolderAssignments) {
			if (!$this->request->is('ajax')) {
				return true;
			}

			$this->viewBuilder()->setOption('serialize', ['success', 'message', 'inUse', 'hiddenChildren']);

			$this->set('success', true);
			$this->set('inUse', true);
			$this->set('hiddenChildren', false);
			$this->set('message', __('error_in_use'));

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');

			return $this->render()->withStatus(200);
		}

		// Check if any of the files inside the media folders are used
		$files = $this->MediaFolders->Media
			->find()
			->where([
				'mediaFolderId IN' => $mediaFolderIds,
			])
			->contain(['MediaAssignments'])
			->matching('MediaAssignments')
			->count()
		;

		if (!$this->request->is('ajax')) {
			return $files > 0;
		}

		$this->viewBuilder()->setOption('serialize', ['success', 'message', 'inUse', 'hiddenChildren']);

		$this->set('success', true);
		$this->set('inUse', $files > 0);
		$this->set('hiddenChildren', false);
		$this->set('message', __('error_files_in_use'));

		// Set the view class to JSON
		$this->viewBuilder()->setClassName('Json');

		return $this->render()->withStatus(200);
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		// Create a flat array of all order data
		$orderData = [];
		foreach ($requestData as $itemsByLanguageShortcode) {
			foreach ($itemsByLanguageShortcode as $items) {
				array_map(function (array $item) use (&$orderData) {
					if ($item['languageShortcode'] === '_global') {
						$item['languageShortcode'] = null;
					}

					$orderData[] = $item;
				}, $items);
			}
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$affectedRows = $table->updateAll(function (QueryExpression $expression) use ($orderData) {
			$languageShortcodeCase = $expression->case();
			$systemOrderCase = $expression->case();

			foreach ($orderData as $data) {
				$languageShortcodeCase->when(['id' => $data['id']])->then($data['languageShortcode'], 'string');
				$systemOrderCase->when(['id' => $data['id']])->then($data['systemOrder'], 'integer');
			}

			return [
				'languageShortcode' => $languageShortcodeCase,
				'systemOrder' => $systemOrderCase,
			];
		}, [
			'id IN' => array_column($orderData, 'id'),
		]);


		return $affectedRows;
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
			/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
			$query = $this->MediaFolders
				->find('forCurrentLanguage', languageShortcode: $mediaFolder->languageShortcode ?? false, includeGlobal: false)
				->where($this->getOverviewWhere())
				->where(['hidden' => $mediaFolder->hidden])
			;

			$this->threadedMediaFolders = $this->MediaFolders->listNested($query);
		}

		return $this->threadedMediaFolders;
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $mediaFolder
	 * @param \Cake\Collection\CollectionInterface $threadedMediaFolders
	 * @return void
	 */
	protected function ensurePossibleParentId(MediaFolder $mediaFolder, CollectionInterface $threadedMediaFolders): void {
		$possibleParentIds = $threadedMediaFolders->extract('id')->toList();

		if (!empty($mediaFolder->parentId) && !in_array($mediaFolder->parentId, $possibleParentIds)) {
			$errors = $mediaFolder->getError('parentId');

			$mediaFolder->parentId = reset($possibleParentIds);

			if ($mediaFolder->parentId === 1) {
				$mediaFolder->parentId = null;
			}

			if ($errors) {
				$mediaFolder->setError('parentId', $errors, true);
			}
		}

		$request = $this->getRequest();
		//When the field is part of the request data, overwrite it since it might be outdated
		if ($request->getData('parentId') !== null) {
			$request = $request->withData('parentId', $mediaFolder->parentId);
			$this->setRequest($request);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $mediaFolder
	 * @return void
	 */
	protected function setViewVars(MediaFolder $mediaFolder): void {
		$threadedMediaFolders = $this->getThreadedMediaFolders($mediaFolder);

		$possibleParentMediaFolders = $this->MediaFolders->getPossibleParents($mediaFolder, $threadedMediaFolders);
		$this->ensurePossibleParentId($mediaFolder, $possibleParentMediaFolders);

		// Get the parent media folder if it exists
		if ($mediaFolder->parentId) {
			$parentFolder = $this->MediaFolders->findById($mediaFolder->parentId)->first();
		}

		$this->set([
			'mediaFolder' => $mediaFolder,
			'threadedMediaFolders' => $threadedMediaFolders,
			'possibleParentMediaFolders' => $possibleParentMediaFolders,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'parentFolder' => $parentFolder ?? null,
		]);
	}
}
