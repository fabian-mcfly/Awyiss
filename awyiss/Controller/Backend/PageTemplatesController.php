<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * PageTemplates Controller
 *
 * @property \Awyiss\Model\Table\PageTemplatesTable $PageTemplates
 */
class PageTemplatesController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
		'defaultSortableFields' => ['used_for_pages'],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->PageTemplates->find('withUsages')->where($this->getOverviewWhere())->contain(['ContentAreas', 'PageRoles']);
		$this->Categories->filterQuery($lo_query, null, false);

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
			$lo_pageTemplates = $this->paginate($lo_query);
		}
		else {
			$lo_pageTemplates = $this->Categories->groupResult($lo_query)->all();
			$la_pageTemplatesGroupdByPageRole = $lo_pageTemplates->toArray();
		}

		$this->set([
			'pageTemplates' => $lo_pageTemplates,
			'pageTemplatesGroupdByPageRole' => $la_pageTemplatesGroupdByPageRole ?? [],
			'attributes' => $this->PageTemplates->getAttributes(),
			'paginated' => $lb_paginated,
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

		$li_pageRoleId = $this->request->getParam('pageRoleId') ?? $this->Categories->getSelectedCategory();
		if (is_numeric($li_pageRoleId)) {
			$li_pageRoleId = (int)$li_pageRoleId;
		}
		else {
			$li_pageRoleId = key($this->Categories->getCategories());
		}

		$lo_pageTemplate = $this->PageTemplates->newDefaultEntity([
			'pageRoleId' => $li_pageRoleId,
		]);

		if ($this->request->is('post')) {
			$this->save($lo_pageTemplate);
		}

		$la_contentAreas = $this->PageTemplates->ContentAreas->find()->all()->toArray();

		$this->set([
			'pageTemplate' => $lo_pageTemplate,
			'contentAreas' => $la_contentAreas,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var PageTemplate $lo_pageTemplate */
		$lo_pageTemplate = $this->PageTemplates->findById($id)->find('translations')->contain(['ContentAreas'])->first();
		if (!$lo_pageTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_pageTemplate, 'edit');
		}

		$lo_query = $this->PageTemplates->ContentAreas->find();
		if ($lo_pageTemplate->contentAreas) {
			/** @noinspection PhpUndefinedMethodInspection */
			$lo_query->orderByDesc($lo_query->newExpr($lo_query->func()->FIELD([
				'id' => 'identifier',
				...array_reverse(collection($lo_pageTemplate->contentAreas)->extract('id')->toArray()),
			])), true);
		}

		$la_contentAreas = $lo_query->all()->toArray();

		$this->set([
			'pageTemplate' => $lo_pageTemplate,
			'contentAreas' => $la_contentAreas,
		]);
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

		/** @var PageTemplate $lo_pageTemplate */
		$lo_pageTemplate = $this->PageTemplates->findById($id)->find('translations')->first();
		if (!$lo_pageTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->PageTemplates->delete($lo_pageTemplate)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_pageTemplate->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param PageTemplate $pageTemplate
	 * @param string $method
	 * @return void
	 */
	protected function save(PageTemplate $pageTemplate, string $method = 'add'): void {
		$la_associated = [];
		if ($this->PageTemplates->hasAttributes()) {
			$la_associated[] = $this->PageTemplates->getAttributesTableName(true);
			$pageTemplate->setAccess('attributes', true);
		}

		$la_requestData = $this->request->getData();

		if (!empty($la_requestData['content_areas'])) {
			$la_newContentAreas = null;
			if (isset($la_requestData['content_areas']['new'])) {
				$la_newContentAreas = $this->createContentArea($la_requestData['content_areas']['new']);
			}

			$la_requestData['content_areas'] = array_values(array_filter($la_requestData['content_areas'], function (array $element) {
				return !empty($element['id']);
			}));

			$li_systemOrder = 1;
			array_walk($la_requestData['content_areas'], function (array &$contentArea) use (&$li_systemOrder): void {
				/** @noinspection PhpVariableNamingConventionInspection */
				$contentArea['_joinData']['system_order'] = $li_systemOrder;
				$li_systemOrder++;
			});

			foreach ($la_newContentAreas as $lo_newContentArea) {
				$la_requestData['content_areas'][] = [
					'id' => $lo_newContentArea->id,
					'_joinData' => [
						'system_order' => count($la_requestData['content_areas']) + 1,
					],
				];
			}
		}

		$this->PageTemplates->patchEntity($pageTemplate, $la_requestData, [
			'associated' => array_merge($la_associated, [
				'ContentAreas' => [
					'fields' => ['_joinData'],
					'associated' => [
						'_joinData',
					],
				],
			]),
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->PageTemplates->save($pageTemplate, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'pageRoleId' => $pageTemplate->pageRoleId,
						'page' => $this->Paginate->calculateEntityPagePosition($pageTemplate),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $pageTemplate->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($pageTemplate->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			$pageTemplate->systemOrder = null;
		}

		$this->Categories->ensurePossibleCategory($pageTemplate);
	}


	/**
	 * @param array $data
	 * @return array<\Awyiss\Model\Entity>
	 */
	protected function createContentArea(array $data): array {
		$la_newContentAreas = [];

		foreach ($data['title'] as $li_key => $ls_title) {
			$ls_identifier = $data['identifier'][ $li_key ] ?? null;

			if (!$ls_title && !$ls_identifier) {
				continue;
			}

			if (!$ls_title) {
				$ls_title = $ls_identifier;
			}
			elseif (!$ls_identifier) {
				$ls_identifier = $ls_title;
			}

			$lo_contentArea = $this->PageTemplates->ContentAreas->newDefaultEntity([
				'title' => $ls_title,
				'identifier' => $ls_identifier,
			]);

			$this->PageTemplates->ContentAreas->save($lo_contentArea);

			if (!$lo_contentArea->hasErrors()) {
				$la_newContentAreas[] = $lo_contentArea;
			}
		}

		return $la_newContentAreas;
	}
}
