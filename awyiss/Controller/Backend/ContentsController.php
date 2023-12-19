<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Routing\Router;


/**
 * Contents Controller
 *
 * @property \Awyiss\Model\Table\ContentsTable $Contents
 */
class ContentsController extends Controller {
	/**
	 * @inheritDoc
	 */
	public array $categorize = [
		'allowAggregation' => FALSE,
		'associationName' => 'Pages',
		'enabled' => TRUE,
		'name' => 'page_id',
		'paginate' => FALSE,
		'queryOptions' => [
			'access' => ['skip' => FALSE],
		],
		'threaded' => TRUE,
	];
	/**
	 * @var \Cake\Datasource\ResultSetInterface
	 */
	protected ResultSetInterface $contentTemplates;
	/** @var Page[] */
	protected array $pages;
	/**
	 * @var \Cake\Collection\CollectionInterface
	 */
	protected CollectionInterface $threadedContents;


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview (): void {
		$this->Access->ensure('read');

		$lo_page = $this->getPage(intval($this->request->getParam('page-id')));

		/*$lo_contents = $this->Categories->filterQuery($this->Contents->find('withAttributes')->where($this->getOverviewWhere()));
		$ao_content = $lo_contents->where(['id' => 4360])->first();

		$ao_content->attributes->jason_test = 'foobar';
		dd($ao_content);*/

		/*$this->Categories->setConfig('queryConditions', [
			'page_role_id' => $lo_page->page_role_id,
		]);*/

		$lo_contents = $this->Categories->filterQuery($this->Contents->find('withAttributes')->where($this->getOverviewWhere()));
		$la_contents = $this->Contents->nestedByTemplatePosition($lo_contents)->toArray();

		$this->set([
			'aa_contents' => $la_contents,
			'ao_contentTemplates' => $this->getContentTemplates(),
			'ao_page' => $lo_page,
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
		$this->Access->ensure('create');

		$lo_content = $this->Contents->newDefaultEntity([
			'page_id' => $this->request->getParam('page-id'),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_content);
		}

		$lo_page = $this->getPage($lo_content->page_id);

		$la_availableTemplatePositions = $this->getAvailableTemplatePositions($lo_content, $lo_page);
		$this->ensurePossibleTemplatePosition($lo_content, $la_availableTemplatePositions);

		$this->set([
			'ao_content' => $lo_content,
			'ao_contentTemplates' => $this->getContentTemplates(),
			'ao_threadedContents' => $this->getThreadedContents($lo_content),
			'ao_page' => $lo_page,
			'aa_availableTemplatePositions' => $la_availableTemplatePositions,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function edit () {
		$this->Access->ensure('update');

		/** @var Content $lo_content */
		$lo_content = $this->Contents->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_content) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_content, 'edit');
		}

		$lo_page = $this->getPage($lo_content->page_id);

		$la_availableTemplatePositions = $this->getAvailableTemplatePositions($lo_content, $lo_page);
		$this->ensurePossibleTemplatePosition($lo_content, $la_availableTemplatePositions);

		$this->set([
			'ao_content' => $lo_content,
			'ao_contentTemplates' => $this->getContentTemplates(),
			'ao_threadedContents' => $this->getThreadedContents($lo_content),
			'ao_page' => $lo_page,
			'aa_availableTemplatePositions' => $la_availableTemplatePositions,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return \Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Content $lo_content */
		$lo_content = $this->Contents->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_content) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		//Calling this ensures access to the page_id/it's scope resp. the page role.
		$this->getPage($lo_content->page_id);

		if ($this->Contents->delete($lo_content)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Content $ao_content
	 * @param string $as_method
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function save (Content $ao_content, string $as_method = 'add'): void {
		$this->Contents->patchEntity($ao_content, $this->request->getData());

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$this->Contents->forPage($ao_content->page_id);

			if ($this->Contents->save($ao_content)) {
				$this->Flash->success(__('::' . $as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'page-id' => $ao_content->page_id], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_content->id], TRUE), 302);
			}

			$this->Flash->error(__('::' . $as_method . '_failed'));
			$this->Flash->error(implode('<br>' . PHP_EOL, $ao_content->getError('_general')));
		}
		else {
			if ($this->Contents->getSystemOrderRelatedColumns($ao_content)) {
				$ao_content->system_order = NULL;
			}
			else {
				$ao_content->system_order = $ao_content->getOriginal('system_order');
			}
		}
	}


	/**
	 * Returns a Resultset of all available ContentTemplates using the `findList()` finder
	 *
	 * @return \Cake\Datasource\ResultSetInterface
	 *
	 * @see \Cake\ORM\Table::findList()
	 */
	public function getContentTemplates (): ResultSetInterface {
		if (!isset($this->contentTemplates)) {
			$this->contentTemplates = $this->Contents->ContentTemplates->find('list')->find('active', [
				'access' => ['skip' => TRUE],
			])->all();
		}

		return $this->contentTemplates;
	}


	/**
	 * Returns a Collection of all available contents that exist within the same page and the same `template_position`
	 * as the entity, provided via `$ao_content`
	 *
	 * @param \Awyiss\Model\Entity\Content $ao_content
	 *
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function getThreadedContents (Content $ao_content): CollectionInterface {
		if (!isset($this->threadedContents)) {
			$lo_query = $this->Contents->find('withAttributes')->where([
				'page_id' => $ao_content->page_id,
				'template_position' => $ao_content->template_position,
			]);

			$this->threadedContents = $this->Contents->listNested($lo_query);
		}

		//Single "=". We only want to find threaded contents for an existing entity (id equals not NULL)
		if ($li_originalId = $ao_content->getOriginal('id')) {
			/*$li_foundAtLevel = NULL;
			$la_threadedContents = [];
			foreach ($this->threadedContents AS $ao_content) {
				dump($ao_content->getOriginal('id') . ' == ' . $li_originalId);
				if ($ao_content->getOriginal('id') === $li_originalId) {
					$li_foundAtLevel = $ao_content->level;
					$la_threadedContents[] = $ao_content;
					continue;
				}
				elseif ($ao_content->level > $li_foundAtLevel) {
					continue;
				}
				elseif ($ao_content->level > $li_foundAtLevel) {
					$li_foundAtLevel = NULL;
				}
			}*/

			$li_foundAtLevel = NULL;
			$lo_threadedContents = new Collection($this->threadedContents->toList());
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
	 * Returns and caches a Page object.
	 * Requesting a page that does not exist or without having read access to the scope of the page (page role),
	 * a redirect exception is thrown.
	 *
	 * @see \Awyiss\Model\Entity\Page
	 *
	 * @throws \Cake\Http\Exception\ForbiddenException
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function getPage (int $ai_page_id): Page {
		if (isset($this->pages[ $ai_page_id ])) {
			return $this->pages[ $ai_page_id ];
		}

		try {
			$lo_page = $this->Contents->forPage($ai_page_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException|ForbiddenException) {
			$this->Flash->error(__('::record_not_found'));
			throw new RedirectException(Router::url(['controller' => 'dashboard', 'action' => 'overview'], TRUE), 404);
		}

		return $this->pages[ $ai_page_id ] = $lo_page;
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $ao_content
	 * @param array $aa_availableTemplatePositions
	 *
	 * @return void
	 */
	protected function ensurePossibleTemplatePosition (Content $ao_content, array $aa_availableTemplatePositions = []): void {
		if (empty($ao_content->template_position) || !in_array($ao_content->template_position, $aa_availableTemplatePositions)) {
			$la_errors = $ao_content->getError('template_position');

			$ao_content->template_position = reset($aa_availableTemplatePositions);

			if ($la_errors) {
				$ao_content->setError('template_position', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When template_position is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('template_position') !== NULL) {
				$lo_request = $lo_request->withData('template_position', $ao_content->template_position);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $ao_content
	 * @param NULL|\Awyiss\Model\Entity\Page $ao_page
	 *
	 * @return array
	 *
	 * @throws \Cake\Http\Exception\ForbiddenException
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function getAvailableTemplatePositions (Content $ao_content, ?Page $ao_page = NULL): array {
		$lo_page = $ao_page ?? $this->getPage($ao_content->page_id);

		$la_errors = $ao_content->getError('content_template_id');
		try {
			$lo_content_template = $this->Contents->ContentTemplates->get($ao_content->content_template_id, ['access' => ['skip' => TRUE]]);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$lo_content_template = $this->Contents->ContentTemplates->find('all', ['access' => ['skip' => TRUE]])->first();
			$ao_content->content_template_id = $lo_content_template->id;

			if ($la_errors) {
				$ao_content->setError('content_template_id', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When content_template_id is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('content_template_id') !== NULL) {
				$lo_request = $lo_request->withData('content_template_id', $ao_content->content_template_id);
				$this->setRequest($lo_request);
			}
		}

		return $lo_content_template->assigned_template_positions[ $lo_page->page_template_id ] ?? [];
	}
}

