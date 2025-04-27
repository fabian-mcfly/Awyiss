<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Routing\Router;
use Cake\Collection\CollectionInterface;
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
		$this->Search->filterQuery($lo_query);

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

		$this->setViewVars($lo_pageTemplate);
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
		$lo_pageTemplate = $this->PageTemplates->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->contain(['ContentAreas'])->first();
		if (!$lo_pageTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_pageTemplate, 'edit');
		}

		$this->setViewVars($lo_pageTemplate);
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
		$lo_pageTemplate = $this->PageTemplates->findById($id)->first();
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
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_pageTemplate->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
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

		if (!empty($la_requestData['content_template_content_areas'])) {
			if (empty($la_requestData['content_areas'])) {
				unset($la_requestData['content_template_content_areas']);
			}
			else {
				$la_contentAreaIds = array_column($la_requestData['content_areas'], 'id');
				$la_requestData['content_template_content_areas'] = array_merge(...$la_requestData['content_template_content_areas']);
				$la_requestData['content_template_content_areas'] = array_filter($la_requestData['content_template_content_areas'], function (array $element) use ($la_contentAreaIds) {
					return !empty($element['content_template_id']) && in_array($element['content_area_id'], $la_contentAreaIds);
				});
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
				'ContentTemplateContentAreas' => [
					'fields' => ['content_template_id', 'content_area_id'],
				],
			]),
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->PageTemplates->save($pageTemplate, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'pageRoleId' => $pageTemplate->pageRoleId,
						'page' => $this->Paginate->calculateEntityPagePosition($pageTemplate),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $pageTemplate->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($pageTemplate->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		else {
			if ($this->PageTemplates->getSystemOrderRelatedColumns($pageTemplate)) {
				$pageTemplate->systemOrder = null;
			}
			else {
				$pageTemplate->systemOrder = $pageTemplate->hasOriginal('systemOrder') ? $pageTemplate->getOriginal('systemOrder') : $pageTemplate->get('systemOrder');
			}

			// Update the request data. Otherwise, the SystemOrderHelper would use the outdated request data
			$lo_request = $this->request->withData('system_order', $pageTemplate->systemOrder);
			$this->setRequest($lo_request);
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


	/**
	 * @param \Awyiss\Model\Entity\PageTemplate $pageTemplate
	 * @return void
	 */
	protected function setViewVars(PageTemplate $pageTemplate): void {
		$lo_query = $this->PageTemplates->ContentAreas->find();

		if ($pageTemplate->contentAreas && !$pageTemplate->isNew()) {
			/** @noinspection PhpUndefinedMethodInspection */
			$lo_query->orderByDesc($lo_query->newExpr($lo_query->func()->FIELD([
				'id' => 'identifier',
				...array_reverse(collection($pageTemplate->contentAreas)->extract('id')->toArray()),
			])), true);

			$lo_query->contain([
				'ContentTemplates' => function (SelectQuery $query) use ($pageTemplate) {
					return $query->where(['ContentTemplateContentAreas.page_template_id' => $pageTemplate->id]);
				},
			])->formatResults(function (CollectionInterface $collection): CollectionInterface {
				return $collection->map(function ($row) {
					/** @var \Awyiss\Model\Entity\ContentArea $row */
					if (!is_array($row->contentTemplates)) {
						return $row;
					}

					$row->contentTemplates = collection($row->contentTemplates)->indexBy('id')->toArray();

					return $row;
				});
			});
		}

		$la_contentAreas = $lo_query->all()->toArray();

		$la_contentTemplates = $this->PageTemplates->ContentAreas->ContentTemplates->find('translations')->all()->toArray();

		$this->set([
			'pageTemplate' => $pageTemplate,
			'contentAreas' => $la_contentAreas,
			'contentTemplates' => $la_contentTemplates,
		]);
	}
}
