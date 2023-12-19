<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Routing\Router;


/**
 * Contents Controller
 *
 * @property \Awyiss\Model\Table\ContentsTable $Contents
 * @method Content[]|\Cake\Datasource\ResultSetInterface paginate($ao_object = NULL, array $aa_settings = [])
 */
class ContentsController extends Controller {
	public array $categorize = [
		'associationName' => 'Pages',
		'enabled' => TRUE,
		'name' => 'page_id',
		'paginate' => FALSE,
		'threaded' => TRUE,
	];
	protected ResultSetInterface $contentTemplates;
	protected Page $page;
	protected CollectionInterface $threadedContents;


	//public function initialize (): void {
		//parent::initialize();

		/*$ls_action = $this->getRequest()->getParam('action');
		if (in_array($ls_action, ['overview', 'add'])) {
			$li_id = $this->request->getParam('pages-id');
			$this->page = $this->Contents->Pages->get($li_id, [
				'access' => ['skip' => TRUE],
				'contain' => [
					'PageRoles' => [
						'finder' => ['all' => ['access' => ['skip' => TRUE]]],
					],
				]
			]);

			if ( ! $this->page) {
				$this->Flash->error(__('::page_not_found'));
				$this->redirect(['controller' => 'dashboard', 'action' => 'overview']);
			}
			else {
				$this->Categories->setConfig('queryConditions', [
					'page_role_id' => $this->page->page_role_id,
				]);
			}
		}
		elseif ($ls_action === 'edit') {
			$li_id = $this->request->getParam('id');
			$lo_content = $this->Contents->get($li_id, [
				'access' => ['skip' => TRUE],
				'contain' => [
					'Pages' => [
						'finder' => ['all' => ['access' => ['skip' => TRUE]]],
						'PageRoles' => [
							'finder' => ['all' => ['access' => ['skip' => TRUE]]],
						],
					],
				],
			]);

			$this->page = $lo_content->page;

			$this->Categories->setConfig('queryConditions', [
				'page_role_id' => $this->page->page_role_id,
			]);
		}*/
	//}


	/**
	 * Overview method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->ensurePageAccess($this->request->getParam('pages-id'));

		$this->Access->ensure('read');

		$lo_contents = $this->Categories->filterQuery($this->Contents->find('withAttributes')->where($this->getOverviewWhere()));
		$lo_content = $lo_contents->where(['id' => 4360])->first();

		$lo_content->attributes->jason_test = 'foobar';
		dd($lo_content);

		$la_contents = $this->Contents->nestedByTemplatePosition($lo_contents)->toArray();

		$this->set([
			'aa_contents' => $la_contents,
			'ao_contentTemplates' => $this->getContentTemplates(),
			'ao_page' => $this->page,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_content = $this->Contents->newDefaultEntity([
			'page_id' => $this->request->getParam('page-id'),
		]);

		if ($this->request->is('post')) {
			$this->Contents->patchEntity($lo_content, $this->request->getData());

			$this->ensurePageAccess($lo_content->page_id);
			$this->ensurePossibleTemplatePosition($lo_content);
			//$this->Categories->ensurePossibleCategorySelection($lo_content);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Contents->save($lo_content)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview', 'page_id' => $lo_content->page_id]);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_content->id]);
				}

				$this->Flash->error(__('::add_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_content->getError('_general')));
			}
		}
		else {
			$this->ensurePageAccess($lo_content->page_id);
			$this->ensurePossibleTemplatePosition($lo_content);
			//$this->Categories->ensurePossibleCategorySelection($lo_content);
		}

		$this->set([
			'ao_content' => $lo_content,
			'ao_contentTemplates' => $this->getContentTemplates(),
			'ao_threadedContents' => $this->getThreadedContents($lo_content),
			'ao_page' => $this->page,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		try {
			$li_id = $this->request->getParam('id');
			/** @var Content $lo_content */
			$lo_content = $this->Contents->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}


		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->Contents->patchEntity($lo_content, $this->request->getData());

			$this->ensurePageAccess($lo_content->page_id);
			$this->ensurePossibleTemplatePosition($lo_content);
			//$this->Categories->ensurePossibleCategorySelection($lo_content);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Contents->save($lo_content)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview', 'page_id' => $lo_content->page_id]);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_content->id]);
				}

				$this->Flash->error(__('::edit_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_content->getError('_general')));
			}
			else {
				if ($this->Contents->getSystemOrderRelatedColumns($lo_content)) {
					$lo_content->system_order = NULL;
				}
				else {
					$lo_content->system_order = $lo_content->getOriginal('system_order');
				}
			}
		}
		else {
			$this->ensurePageAccess($lo_content->page_id);
			$this->ensurePossibleTemplatePosition($lo_content);
			//$this->Categories->ensurePossibleCategorySelection($lo_content);
		}

		$this->set([
			'ao_content' => $lo_content,
			'ao_contentTemplates' => $this->getContentTemplates(),
			'ao_threadedContents' => $this->getThreadedContents($lo_content),
			'ao_page' => $this->page,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		try {
			$li_id = $this->request->getParam('id');
			/** @var Content $lo_content */
			$lo_content = $this->Contents->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Contents->delete($lo_content)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	public function getContentTemplates (): ResultSetInterface {
		if (!isset($this->contentTemplates)) {
			$this->contentTemplates = $this->Contents->ContentTemplates->find('list')->find('active')->all();
		}

		return $this->contentTemplates;
	}


	public function getThreadedContents (Content $ao_content): CollectionInterface {
		if (!isset($this->threadedContents)) {
			$lo_query = $this->Contents->find('withAttributes')->where([
				'page_id' => $this->page->id,
				'template_position' => $ao_content->template_position,
			]);

			$this->threadedContents = $this->Contents->listNested($lo_query);
		}

		if ($li_originalId = $ao_content->getOriginal('id')) {
			/*$li_foundAtLevel = NULL;
			$la_threadedContents = [];
			foreach ($this->threadedContents AS $lo_content) {
				dump($lo_content->getOriginal('id') . ' == ' . $li_originalId);
				if ($lo_content->getOriginal('id') === $li_originalId) {
					$li_foundAtLevel = $lo_content->level;
					$la_threadedContents[] = $lo_content;
					continue;
				}
				elseif ($lo_content->level > $li_foundAtLevel) {
					continue;
				}
				elseif ($lo_content->level > $li_foundAtLevel) {
					$li_foundAtLevel = NULL;
				}
			}*/

			$li_foundAtLevel = NULL;
			$lo_threadedContents = new \Cake\Collection\Collection($this->threadedContents->toList());
			$lo_threadedContents = $lo_threadedContents->filter(function($ao_content) use ($li_originalId, &$li_foundAtLevel) {
				if ($ao_content->getOriginal('id') === $li_originalId) {
					$li_foundAtLevel = $ao_content->level;
				}
				elseif (is_null($li_foundAtLevel) || $ao_content->level <= $li_foundAtLevel) {
					$li_foundAtLevel = NULL;
					return TRUE;
				}

				return FALSE;
			});

			$lo_threadedContents = $lo_threadedContents->nest('id', 'parent_id');

			return $lo_threadedContents->listNested();
		}

		return $this->threadedContents;
	}


	/**
	 * @throws RecordNotFoundException|InvalidPrimaryKeyException|\Exception
	 */
	protected function ensurePageAccess ($ai_page_id): void {
		try {
			/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
			$this->page = $this->Contents->Pages->get($ai_page_id, [
				'access' => ['skip' => TRUE],
				'contain' => [
					'PageRoles' => [
						'finder' => ['all' => ['access' => ['skip' => TRUE]]],
					],
					'PageTemplates' => [
						'finder' => ['all' => ['access' => ['skip' => TRUE]]],
					],
				]
			]);

			$this->Categories->setConfig('queryConditions', [
				'page_role_id' => $this->page->page_role_id,
			]);

			$ls_scope = \Cake\Utility\Inflector::pluralize($this->page->page_role->identifier);
			$this->Access->forScope($ls_scope)->ensureOne('read');
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$this->Flash->error(__('::page_not_found'));
			throw new RedirectException(Router::url(['controller' => 'dashboard', 'action' => 'overview'], TRUE), 404);
		}
	}


	protected function ensurePossibleTemplatePosition (Content $ao_content): void {
		$la_availableTemplatePositions = $this->page->page_template->template_positions;

		if (empty($ao_content->template_position) || !in_array($ao_content->template_position, $la_availableTemplatePositions)) {
			$ao_content->template_position = reset($la_availableTemplatePositions);
		}
	}
}

