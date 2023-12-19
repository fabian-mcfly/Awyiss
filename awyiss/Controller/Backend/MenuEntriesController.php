<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Model\Table\MenuEntriesTable;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Awyiss\Routing\Router;


/**
 * MenuEntries Controller
 *
 * @property MenuEntriesTable $MenuEntries
 */
class MenuEntriesController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $categorize = [
		'allowAggregation' => FALSE,
		'associationName' => 'Menus',
		'enabled' => TRUE,
		'identifier' => 'menuId',
	];
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $threadedMenuEntries;


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview (): void {
		$this->Authorization->ensure('read');

		$lo_menuEntries = $this->Categories->filterQuery($this->MenuEntries->find()->where($this->getOverviewWhere()));
		$lo_menuEntries = $this->MenuEntries->listNested($lo_menuEntries);

		$this->set([
			'ao_menuEntries' => $lo_menuEntries,
		]);
	}
	

	/**
	 * Add method
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function add (): void {
		$this->Authorization->ensure('create');

		$lo_menuEntry = $this->MenuEntries->newDefaultEntity([
			'language_shortcode' => $this->getOverviewWhere('language_shortcode'),
			'menu_id' => $this->Categories->getSelectedCategory(),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_menuEntry);
		}

		$lo_threadedMenuEntries = $this->getThreadedMenuEntries($lo_menuEntry);
		$la_possibleParentIds = $lo_threadedMenuEntries->extract('id')->toArray(FALSE);
		if ( ! empty($lo_menuEntry->parentId) && ! in_array($lo_menuEntry->parentId, $la_possibleParentIds)) {
			$la_errors = $lo_menuEntry->getError('parentId');
			$lo_menuEntry->set('parentId', NULL, ['setter' => FALSE]);
			$lo_menuEntry->setError('parentId', $la_errors, TRUE);
		}

		$this->set([
			'ao_menuEntry' => $lo_menuEntry,
			'ao_threadedMenuEntries' => $lo_threadedMenuEntries,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}
	

	/**
	 * Edit method
	 *
	 * @return void|?Response
	 *
	 * @throws \Exception
	 */
	public function edit () {
		$this->Authorization->ensure('update');

		/** @var MenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->MenuEntries->findById((int) $this->request->getParam('id'))->first();
		if (! $lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_menuEntry, 'edit');
		}

		$lo_threadedMenuEntries = $this->getThreadedMenuEntries($lo_menuEntry);
		$la_possibleParentIds = $lo_threadedMenuEntries->extract('id')->toArray(FALSE);
		if (!empty($lo_menuEntry->parentId) && ! in_array($lo_menuEntry->parentId, $la_possibleParentIds)) {
			$la_errors = $lo_menuEntry->getError('parentId');
			$lo_menuEntry->set('parentId', NULL, ['setter' => FALSE]);
			$lo_menuEntry->setError('parentId', $la_errors, TRUE);
		}

		$this->set([
			'ao_menuEntry' => $lo_menuEntry,
			'ao_threadedMenuEntries' => $lo_threadedMenuEntries,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}
	

	/**
	 * Delete method
	 *
	 * @return Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var MenuEntry $lo_menuEntry */
		$lo_menuEntry = $this->MenuEntries->findById((int) $this->request->getParam('id'))->first();
		if (! $lo_menuEntry) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MenuEntries->delete($lo_menuEntry)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
	

	/**
	* @param MenuEntry $ao_menuEntry
	* @param string $as_method
	*
	* @return void
	*
	* @throws RedirectException
	*/
	protected function save (MenuEntry $ao_menuEntry, string $as_method = 'add'): void {
		if ($this->MenuEntries->hasAttributes()) {
			$ao_menuEntry->setAccess('attributes', TRUE);
		}

		$this->MenuEntries->patchEntity($ao_menuEntry, $this->request->getData());

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->MenuEntries->save($ao_menuEntry)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $ao_menuEntry->languageShortcode, 'menuId' => $ao_menuEntry->menuId], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $ao_menuEntry->languageShortcode, 'id' => $ao_menuEntry->id], TRUE), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_menuEntry->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->MenuEntries->getSystemOrderRelatedColumns($ao_menuEntry)) {
				$ao_menuEntry->systemOrder = NULL;
			}
			else {
				$ao_menuEntry->systemOrder = $ao_menuEntry->hasOriginal('systemOrder') ? $ao_menuEntry->getOriginal('systemOrder') : $ao_menuEntry->get('systemOrder');
			}
		}
	}


	/**
	 * Returns a Collection of all available menuentries that exist within the same menu and the same `language_shortcode`
	 * as the entity, provided via `$ao_menuEntry`
	 *
	 * @param MenuEntry $ao_menuEntry
	 *
	 * @return CollectionInterface
	 */
	public function getThreadedMenuEntries (MenuEntry $ao_menuEntry): CollectionInterface {
		if (!isset($this->threadedMenuEntries)) {
			$lo_query = $this->MenuEntries->find()->where([
				'language_shortcode' => $ao_menuEntry->languageShortcode,
				'menu_id' => $ao_menuEntry->menuId,
			]);

			$this->threadedMenuEntries = $this->MenuEntries->listNested($lo_query);
		}

		//Single "=". We only want to find threaded menu entries at the same level for an existing entity (id equals not NULL)
		if ($li_originalId = $ao_menuEntry->get('id')) {
			$li_foundAtLevel = NULL;
			$lo_threadedMenuEntries = new Collection($this->threadedMenuEntries->toList());

			$lo_threadedMenuEntries = $lo_threadedMenuEntries->filter(function($ao_menuEntry) use ($li_originalId, &$li_foundAtLevel) {
				if ($ao_menuEntry->get('id') === $li_originalId) {
					$li_foundAtLevel = $ao_menuEntry->level;
				}
				elseif (is_null($li_foundAtLevel) || $ao_menuEntry->level <= $li_foundAtLevel) {
					$li_foundAtLevel = NULL;
					return TRUE;
				}

				return FALSE;
			});

			$lo_threadedMenuEntries = $lo_threadedMenuEntries->nest('id', 'parent_id');

			return $lo_threadedMenuEntries->listNested();
		}


		return $this->threadedMenuEntries;
	}


	/**
	 * @inheritDoc
	 *
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere (): void {
		$ls_languageShortcode = LocaleMiddleware::getLanguage()->shortcode;

		$this->overviewWhere = [
			'language_shortcode' => $ls_languageShortcode,
		];
	}
}

