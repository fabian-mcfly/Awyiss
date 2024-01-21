<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\Routing\Router;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


/**
 * Contents Controller
 *
 * @property \Awyiss\Model\Table\ContentsTable $Contents
 */
class ContentsController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'startupMethods' => null,
	];
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $contentTemplates;
	/**
	 * @var array<Page>
	 */
	protected array $pages;
	/**
	 * @var Page
	 */
	protected Page $page;
	/**
	 * @var CollectionInterface
	 */
	protected CollectionInterface $threadedContents;
	/**
	 * @var string
	 */
	protected string $pageRoleName;


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$lo_page = $this->forPage((int)$this->request->getParam('pageId'));

		$this->Authorization->ensure('read');

		/**
		 * Using `$this->Contents->find()` instead of
		 * `$this->Contents->Pages->loadInto($lo_page, ['Contents'])->contents, $lo_contents->count();`
		 * because `nestedByContentArea()` works with a Query, not an array.
		 * This could be changed, but I fail to see any benefits
		 */
		$lo_contents = $this->Contents->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_contents);
		$la_contents = $this->Contents->nestedByContentArea($lo_contents)->toArray();

		$la_contentAreas = array_combine(array_column($lo_page->pageTemplate->contentAreas, 'id'), array_column($lo_page->pageTemplate->contentAreas, 'label'));
		$la_unknownContentAreas = array_diff_key($la_contents, $la_contentAreas);
		foreach ($la_unknownContentAreas as $li_contentAreaId => $lo_contents) {
			$la_contentAreas[ $li_contentAreaId ] = null;
		}

		$this->set([
			'aa_contents' => $la_contents,
			'aa_contentAreas' => $la_contentAreas,
			'aa_unknownContentAreas' => $la_unknownContentAreas,
			'ao_page' => $lo_page,
			'as_forScope' => $this->Contents->getForScope(),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$li_pageId = (int)$this->request->getParam('pageId');
		$lo_page = $this->forPage($li_pageId);

		$this->Authorization->ensure('create');

		$lo_content = $this->Contents->newDefaultEntity([
			'pageId' => $li_pageId,
		]);

		if ($this->request->is('post')) {
			$this->save($lo_content);

			//Calling save() might change the page, so use this instead
			$lo_page = $this->page;
		}

		$lo_contentTemplates = $this->getContentTemplates();
		$this->ensurePossibleTemplate($lo_content, $lo_contentTemplates);

		$la_contentAreas = $this->getContentAreas($lo_content, $lo_page);
		$this->ensurePossibleContentArea($lo_content, $la_contentAreas);

		$lo_threadedContents = $this->getThreadedContents($lo_content);
		$this->ensurePossibleParentId($lo_content, $lo_threadedContents);

		$la_assignedAttributes = $this->getAssignedAttributes($lo_content);

		$ls_languageShortcode = $this->request->getData('languageShortcode') ?: $this->page->languageShortcode;

		/** @var \Awyiss\Model\Entity\ContentTemplate $lo_selectedContentTemplate */
		$lo_selectedContentTemplate = $lo_contentTemplates->firstMatch(['id' => $lo_content->contentTemplateId]);
		$la_contentElementsByFieldset = [];
		if (!empty($lo_selectedContentTemplate->contentTemplateElements)) {
			$la_contentElementsByFieldset = collection($lo_selectedContentTemplate->contentTemplateElements)->groupBy('fieldset')->toArray();
		}

		$this->set([
			'ao_content' => $lo_content,
			'ao_contentTemplates' => $lo_contentTemplates,
			'ao_threadedContents' => $lo_threadedContents,
			'ao_page' => $lo_page,
			'aa_assignedAttributes' => $la_assignedAttributes,
			'aa_contentAreas' => $la_contentAreas,
			'aa_contentElementsByFieldset' => $la_contentElementsByFieldset,
			'as_forScope' => $this->Contents->getForScope(),
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
			'as_languageShortcode' => $ls_languageShortcode,
		]);
	}


	/**
	 * Edit method
	 *
	 * @param int $ai_id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		/** @var Content $lo_content */
		$lo_content = $this->Contents->findById($ai_id)->find('translations')->first();
		if (!$lo_content) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		$lo_page = $this->forPage($lo_content->pageId);

		$this->Authorization->ensure('update');

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_content, 'edit');
		}

		$lo_contentTemplates = $this->getContentTemplates();
		$this->ensurePossibleTemplate($lo_content, $lo_contentTemplates);

		$la_contentAreas = $this->getContentAreas($lo_content, $lo_page);
		$this->ensurePossibleContentArea($lo_content, $la_contentAreas);

		$lo_threadedContents = $this->getThreadedContents($lo_content);
		$this->ensurePossibleParentId($lo_content, $lo_threadedContents);

		$la_assignedAttributes = $this->getAssignedAttributes($lo_content);

		$ls_languageShortcode = $this->request->getData('language_shortcode') ?: $this->page->languageShortcode;

		/** @var \Awyiss\Model\Entity\ContentTemplate $lo_selectedContentTemplate */
		$lo_selectedContentTemplate = $lo_contentTemplates->firstMatch(['id' => $lo_content->contentTemplateId]);
		$la_contentElementsByFieldset = [];
		if (!empty($lo_selectedContentTemplate->contentTemplateElements)) {
			$la_contentElementsByFieldset = collection($lo_selectedContentTemplate->contentTemplateElements)->groupBy('fieldset')->toArray();
		}

		$this->set([
			'ao_content' => $lo_content,
			'ao_contentTemplates' => $lo_contentTemplates,
			'ao_threadedContents' => $lo_threadedContents,
			'ao_page' => $lo_page,
			'aa_assignedAttributes' => $la_assignedAttributes,
			'aa_contentAreas' => $la_contentAreas,
			'aa_contentElementsByFieldset' => $la_contentElementsByFieldset,
			'as_forScope' => $this->Contents->getForScope(),
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
			'as_languageShortcode' => $ls_languageShortcode,
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->request->allowMethod(['get', 'delete']);

		/** @var Content $lo_content */
		$lo_content = $this->Contents->findById($ai_id)->find('translations')->first();
		if (!$lo_content) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		//Calling this ensures access to the pageId/it's scope resp. the page role.
		$this->forPage($lo_content->pageId);
		$this->Authorization->ensure('delete');

		if ($this->Contents->delete($lo_content)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_content->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview', 'pageId' => $lo_content->pageId]);
	}


	/**
	 * @param Content $ao_content
	 * @param string $as_method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Content $ao_content, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Contents->hasAttributes()) {
			$la_associated[] = $this->Contents->getAttributesTable(true);
			$ao_content->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		if (isset($la_data['data'])) {
			$la_dataSet = [];

			foreach ((array)$la_data['data'] as $lx_key => $lx_value) {
				if (!is_numeric($lx_key)) {
					if (empty($lx_value)) {
						continue;
					}

					$la_dataSet[ $lx_key ] = $lx_value;
					continue;
				}

				if (isset($lx_value['key']) && $lx_value['key'] !== '' && isset($lx_value['value']) && $lx_value['value'] !== '') {
					$la_dataSet[ $lx_value['key'] ] = $lx_value['value'];
				}
			}

			$la_data['data'] = $la_dataSet;
		}

		$this->Contents->patchEntity($ao_content, $la_data, ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($ao_content->isDirty('pageId')) {
				//Make sure the new page role of the new page id is accessible (could have changed)
				$this->page = $this->forPage($ao_content->pageId);
			}

			if ($this->Contents->save($ao_content)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $this->page->languageShortcode, 'pageId' => $ao_content->pageId], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $this->page->languageShortcode, 'id' => $ao_content->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_content->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->Contents->getSystemOrderRelatedColumns($ao_content)) {
				$ao_content->systemOrder = null;
			}
			else {
				$ao_content->systemOrder = $ao_content->hasOriginal('systemOrder') ? $ao_content->getOriginal('systemOrder') : $ao_content->get('systemOrder');
			}

			$this->Categories->ensurePossibleCategory($ao_content);

			if ($ao_content->isDirty('pageId')) {
				//Make sure the new page role of the new page id is accessible (could have changed)
				$this->page = $this->forPage($ao_content->pageId);
			}
		}
	}


	/**
	 * @param Content $ao_content
	 * @return array
	 */
	protected function getAssignedAttributes(Content $ao_content): array {
		if (empty($ao_content->contentTemplate)) {
			return [];
		}


		return $this->Contents->ContentTemplates->getAssignedContentAttributes($ao_content->contentTemplate);
	}


	/**
	 * Returns a collection of all available ContentTemplates
	 *
	 * @return CollectionInterface
	 */
	protected function getContentTemplates(): CollectionInterface {
		if (!isset($this->contentTemplates)) {
			$li_pageTemplateId = $this->page->pageTemplateId;

			$lo_query = $this->Contents->ContentTemplates->find(
				'active',
			)->select([
				'id',
				'title',
				'active',
			])->matching('ContentAreas', function (SelectQuery $ao_query) use ($li_pageTemplateId) {
				return $ao_query->where(['ContentTemplateContentAreas.page_template_id' => $li_pageTemplateId]);
			})->contain([
				'ContentAreas',
				'ContentTemplateElements',
			]);

			$this->contentTemplates = $lo_query->all()->indexBy('id');
		}


		return $this->contentTemplates;
	}


	/**
	 * Returns a Collection of all available contents that exist within the same page and the same `contentArea`
	 * as the entity `$ao_content`
	 *
	 * @param Content $ao_content
	 * @return CollectionInterface
	 */
	protected function getThreadedContents(Content $ao_content): CollectionInterface {
		if (!isset($this->threadedContents)) {
			if (empty($ao_content->contentAreaId)) {
				return new Collection([]);
			}

			$lo_query = $this->Contents->find()->where([
				'page_id' => $ao_content->pageId,
				'content_area_id' => $ao_content->contentAreaId,
			]);

			$this->threadedContents = $this->Contents->listNested($lo_query);
		}

		//We only want to find threaded contents for an existing entity (id equals not null)
		$li_originalId = $ao_content->get('id');
		if (!$li_originalId) {
			return $this->threadedContents;
		}

		$li_foundAtLevel = null;
		$lo_threadedContents = new Collection($this->threadedContents->toList());
		$lo_threadedContents = $lo_threadedContents->filter(function ($ao_content) use ($li_originalId, &$li_foundAtLevel) {
			if ($ao_content->get('id') === $li_originalId) {
				$li_foundAtLevel = $ao_content->level;
			}
			elseif (is_null($li_foundAtLevel) || $ao_content->level <= $li_foundAtLevel) {
				$li_foundAtLevel = null;


				return true;
			}


			return false;
		});

		$lo_threadedContents = $lo_threadedContents->nest('id', 'parentId');


		return $lo_threadedContents->listNested();
	}


	/**
	 * Returns and caches a Page object.
	 *
	 * @throws ForbiddenException
	 * @throws \Exception
	 * @throws \RuntimeException
	 * @see Page
	 */
	protected function getPage(int $ai_pageId): Page {
		if (!isset($this->pages[ $ai_pageId ])) {
			$this->pages[ $ai_pageId ] = $this->Contents->getPage($ai_pageId);
		}


		return $this->pages[ $ai_pageId ];
	}


	/**
	 * Sets the Contents table to use the page role of a page with the given id
	 * Requesting a page that does not exist or without having read access to the scope of the page (page role),
	 * a redirect exception is thrown.
	 *
	 * @param int $ai_pageId
	 * @return Page
	 * @throws \Exception
	 */
	protected function forPage(int $ai_pageId): Page {
		try {
			$lo_page = $this->getPage($ai_pageId);
		}
		catch (RecordNotFoundException | InvalidPrimaryKeyException) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['controller' => 'dashboard', 'action' => 'overview'], true), 404);
		}
		catch (ForbiddenException) {
			throw new ForbiddenException();
		}

		$this->page = $lo_page;

		$ls_languageShortcode = null;
		if ($this->request->is(['patch', 'post', 'put']) && in_array($this->request->getParam('action'), ['add', 'edit'])) {
			$ls_languageShortcode = $this->request->getData('language_shortcode');
		}

		$this->Categories->setConfig([
			'finder' => [
				'forCurrentLanguage' => [
					'languageShortcode' => $ls_languageShortcode,
				],
			],
			'selectedCategory' => $ai_pageId,
		]);
		$this->Contents->forPageRole($lo_page->pageRole->identifier);

		$this->pageRoleName = $this->Contents->getPageRoleName();

		if (!$this->request->is(['patch', 'post', 'put']) || $this->request->getParam('action') !== 'edit') {
			if ($lo_page->language_shortcode != LocaleMiddleware::getLanguage()->shortcode) {
				throw new RedirectException(Router::url([
					'lang' => $lo_page->languageShortcode,
					'pageId' => $ai_pageId,
				], true), 302);
			}
		}

		$this->Authorization->setScope($this->Contents->getForScope());

		if (!Configure::read('Awyiss.' . Inflector::camelize($this->Contents->getForScope()) . '.Backend.contents.enabled')) {
			$this->Flash->error(__d($this->Contents->getForScope(), 'contents_disabled'));
			throw new RedirectException(Router::url(['controller' => $this->Contents->getForScope(), 'action' => 'overview'], true), 404);
		}


		return $lo_page;
	}


	/**
	 * @param Content $ao_content
	 * @param CollectionInterface $ao_threadedContents
	 * @return void
	 */
	protected function ensurePossibleParentId(Content $ao_content, CollectionInterface $ao_threadedContents): void {
		$la_possibleParentIds = $ao_threadedContents->extract('id')->toList();
		$la_contentTemplates = $this->getContentTemplates()->toArray();

		if (!empty($ao_content->parentId) && !in_array($ao_content->parentId, $la_possibleParentIds)) {
			$la_errors = $ao_content->getError('parentId');

			/** @var \Awyiss\Model\Entity\ContentTemplate $lo_contentTemplate */
			$lo_contentTemplate = $la_contentTemplates[ $ao_content->contentTemplateId ] ?? null;
			if (!$lo_contentTemplate) {
				//macht diese if überhaupt sinn?
				dd(__FILE__, __LINE__);
				$ao_content->parentId = reset($la_possibleParentIds) ?: null;
			}
			else {
				$la_assignedContentElements = collection($lo_contentTemplate->contentTemplateElements)->indexBy('identifier')->toArray();

				if (($la_assignedContentElements['parent_id'] ?? null)?->required === true && $la_possibleParentIds) {
					$ao_content->parentId = reset($la_possibleParentIds);
				}
				else {
					$ao_content->parentId = null;
				}
			}

			if ($la_errors) {
				$ao_content->setError('parentId', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When parent_id is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('parent_id') !== null) {
				$lo_request = $lo_request->withData('parent_id', $ao_content->parentId);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param Content $ao_content
	 * @param CollectionInterface $ao_contentTemplates
	 * @return void
	 */
	protected function ensurePossibleTemplate(Content $ao_content, CollectionInterface $ao_contentTemplates): void {
		if (!$ao_content->contentTemplateId || !$ao_contentTemplates->firstMatch(['id' => $ao_content->contentTemplateId])) {
			$la_errors = $ao_content->getError('contentTemplateId');

			$ao_content->contentTemplate = $ao_contentTemplates->first();
			$ao_content->contentTemplateId = $ao_content->contentTemplate?->id;

			if ($la_errors) {
				$ao_content->setError('contentTemplateId', $la_errors);
			}
		}
		elseif (empty($ao_content->contentTemplate)) {
			$ao_content->contentTemplate = $ao_contentTemplates->firstMatch(['id' => $ao_content->contentTemplateId]);
			$ao_content->contentTemplateId = $ao_content->contentTemplate?->id;
		}

		$lo_request = $this->getRequest();
		//When content_template_id is part of the request data, overwrite it since it might be outdated
		if ($lo_request->getData('content_template_id') !== null) {
			$lo_request = $lo_request->withData('content_template_id', $ao_content->contentTemplateId);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @param Content $ao_content
	 * @param array $aa_availableContentAreas
	 * @return void
	 */
	protected function ensurePossibleContentArea(Content $ao_content, array $aa_availableContentAreas = []): void {
		if (empty($ao_content->contentAreaId) || !in_array($ao_content->contentAreaId, array_column($aa_availableContentAreas['all'], 'id'))) {
			$la_errors = $ao_content->getError('contentAreaId');

			if (!$aa_availableContentAreas['available']) {
				return;
			}

			$ao_content->contentArea = reset($aa_availableContentAreas['available']);
			$ao_content->contentAreaId = $ao_content->contentArea->id;

			if ($la_errors) {
				$ao_content->setError('contentAreaId', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When content_area_id is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('content_area_id') !== null) {
				$lo_request = $lo_request->withData('content_area_id', $ao_content->contentAreaId);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param Content $ao_content
	 * @param Page|null $ao_page
	 * @return array
	 * @throws ForbiddenException
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function getContentAreas(Content $ao_content, ?Page $ao_page = null): array {
		$lo_page = $ao_page ?? $this->getPage($ao_content->pageId);

		/** @var \Awyiss\Model\Entity\ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->contentTemplates->firstMatch(['id' => $ao_content->contentTemplateId]);
		if (!$lo_contentTemplate) {
			return [
				'available' => [],
				'unavailable' => [],
				'all' => [],
			];
		}

		$la_unavailableContentAreas = $la_contentAreas = collection($lo_page->pageTemplate->contentAreas)->indexBy('id')->toArray();

		$la_availableContentAreas = [];

		foreach ($lo_contentTemplate->contentAreas ?? [] as $lo_contentArea) {
			if ($lo_contentArea->_joinData->pageTemplateId != $lo_page->pageTemplateId) {
				continue;
			}

			if (isset($la_unavailableContentAreas[ $lo_contentArea->id ])) {
				$la_availableContentAreas[ $lo_contentArea->id ] = $la_unavailableContentAreas[ $lo_contentArea->id ];
				unset($la_unavailableContentAreas[ $lo_contentArea->id ]);
			}
		}

		return [
			'available' => $la_availableContentAreas,
			'unavailable' => $la_unavailableContentAreas,
			'all' => $la_contentAreas,
		];
	}
}
