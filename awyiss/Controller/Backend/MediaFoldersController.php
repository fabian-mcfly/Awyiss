<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Routing\Router;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * MediaFolder Controller
 *
 * @property \Awyiss\Model\Table\MediaFoldersTable $MediaFolders
 */
class MediaFoldersController extends Controller {
	/**
	 * @var \Cake\Collection\Iterator\TreeIterator
	 */
	protected CollectionInterface $threadedMediaFolders;


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_mediaFolders = $this->MediaFolders->find('forCurrentLanguage')->where($this->getOverviewWhere());
		$lo_mediaFolders->orderBy('language_shortcode');
		$lo_mediaFolders = $this->MediaFolders->listNested($lo_mediaFolders)->groupBy(function (MediaFolder $ao_mediaFolder) {
			return $ao_mediaFolder->languageShortcode ?? '_global';
		});

		$this->set([
			'ao_mediaFolders' => $lo_mediaFolders,
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

		$lo_mediaFolder = $this->MediaFolders->newDefaultEntity();

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
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var \Awyiss\Model\Entity\MediaFolder $lo_mediaFolder */
		$lo_mediaFolder = $this->MediaFolders->findById($ai_id)->find('translations')->first();

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
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\MediaFolder $lo_mediaFolder */
		$lo_mediaFolder = $this->MediaFolders->findById($ai_id)->find('translations')->first();
		if (!$lo_mediaFolder) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MediaFolders->delete($lo_mediaFolder)) {
			$this->Flash->success(__('delete_succeeded'));
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
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_mediaFolder
	 * @param string $as_method
	 * @return void
	 */
	protected function save(MediaFolder $ao_mediaFolder, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->MediaFolders->hasAttributes()) {
			$la_associated[] = $this->MediaFolders->getAttributesTableName(true);
			$ao_mediaFolder->setAccess('attributes', true);
		}

		$this->MediaFolders->patchEntity($ao_mediaFolder, $this->request->getData(), ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->MediaFolders->save($ao_mediaFolder, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $ao_mediaFolder->languageShortcode], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $ao_mediaFolder->languageShortcode, 'id' => $ao_mediaFolder->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_mediaFolder->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * Return a collection of media folders for the currently set languageShortcode,
	 * using `\Cake\Collection\CollectionTrait::listNested()` to be used in a form-select
	 *
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_mediaFolder
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Cake\Collection\CollectionTrait::listNested()
	 */
	public function getThreadedMediaFolders(MediaFolder $ao_mediaFolder): CollectionInterface {
		if (!isset($this->threadedMediaFolders)) {
			$lo_query = $this->MediaFolders->find('forCurrentLanguage', languageShortcode: $ao_mediaFolder->languageShortcode, includeGlobal: false)
			->where($this->getOverviewWhere());

			$this->threadedMediaFolders = $this->MediaFolders->listNested($lo_query);
		}

		return $this->threadedMediaFolders;
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_mediaFolder
	 * @param \Cake\Collection\CollectionInterface $ao_threadedMediaFolders
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function getParentMediaFolders(MediaFolder $ao_mediaFolder, CollectionInterface $ao_threadedMediaFolders): CollectionInterface {
		//We only want to find threaded media folders for an existing entity (id equals not null)
		$li_originalId = $ao_mediaFolder->get('id');
		if (!$li_originalId) {
			return $ao_threadedMediaFolders;
		}

		$li_foundAtLevel = null;
		$lo_threadedMediaFolders = new Collection($ao_threadedMediaFolders->toList());

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$lo_threadedMediaFolders = $lo_threadedMediaFolders->filter(function ($ao_mediaFolder) use ($li_originalId, &$li_foundAtLevel) {
			if ($ao_mediaFolder->get('id') === $li_originalId) {
				$li_foundAtLevel = $ao_mediaFolder->level;
			}
			elseif (is_null($li_foundAtLevel) || $ao_mediaFolder->level <= $li_foundAtLevel) {
				$li_foundAtLevel = null;


				return true;
			}


			return false;
		});


		return $lo_threadedMediaFolders;
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_mediaFolder
	 * @param \Cake\Collection\CollectionInterface $ao_threadedContents
	 * @return void
	 */
	protected function ensurePossibleParentId(MediaFolder $ao_mediaFolder, CollectionInterface $ao_threadedMediaFolders): void {
		$la_possibleParentIds = $ao_threadedMediaFolders->extract('id')->toList();

		if (!empty($ao_mediaFolder->parentId) && !in_array($ao_mediaFolder->parentId, $la_possibleParentIds)) {
			$la_errors = $ao_mediaFolder->getError('parentId');

			$ao_mediaFolder->parentId = reset($la_possibleParentIds);

			if ($la_errors) {
				$ao_mediaFolder->setError('parentId', $la_errors, true);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_mediaFolder
	 * @return void
	 */
	protected function setViewVars(MediaFolder $ao_mediaFolder): void {
		$lo_threadedMediaFolders = $this->getThreadedMediaFolders($ao_mediaFolder);

		$lo_parentMediaFolders = $this->getParentMediaFolders($ao_mediaFolder, $lo_threadedMediaFolders);
		$this->ensurePossibleParentId($ao_mediaFolder, $lo_parentMediaFolders);

		$this->set([
			'ao_mediaFolder' => $ao_mediaFolder,
			'ao_threadedMediaFolders' => $lo_threadedMediaFolders,
			'ao_parentMediaFolders' => $lo_parentMediaFolders,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}
}
