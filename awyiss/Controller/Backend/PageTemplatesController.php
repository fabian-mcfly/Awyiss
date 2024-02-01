<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * PageTemplates Controller
 *
 * @property \Awyiss\Model\Table\PageTemplatesTable $PageTemplates
 */
class PageTemplatesController extends Controller {
	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_pageTemplateQuery = $this->PageTemplates->find('withUsages')->where($this->getOverviewWhere())->contain(['ContentAreas']);
		$this->Categories->filterQuery($lo_pageTemplateQuery);
		$this->Categories->groupResult($lo_pageTemplateQuery);
		$lo_pageTemplates = $lo_pageTemplateQuery->all();

		$this->set([
			'ao_pageTemplates' => $lo_pageTemplates,
			'aa_pageTemplates' => $lo_pageTemplates->toArray(),
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

		$lo_pageTemplate = $this->PageTemplates->newDefaultEntity([
			'pageRoleId' => $this->request->getParam('pageRoleId') ?? $this->Categories->getSelectedCategory(),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_pageTemplate);
		}

		$la_contentAreas = $this->PageTemplates->ContentAreas->find()->all()->toArray();

		$this->set([
			'ao_pageTemplate' => $lo_pageTemplate,
			'aa_contentAreas' => $la_contentAreas,
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

		/** @var PageTemplate $lo_pageTemplate */
		$lo_pageTemplate = $this->PageTemplates->findById($ai_id)->find('translations')->contain(['ContentAreas'])->first();
		if (!$lo_pageTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_pageTemplate, 'edit');
		}

		$la_contentAreas = $this->PageTemplates->ContentAreas->find()->all()->toArray();

		$this->set([
			'ao_pageTemplate' => $lo_pageTemplate,
			'aa_contentAreas' => $la_contentAreas,
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
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var PageTemplate $lo_pageTemplate */
		$lo_pageTemplate = $this->PageTemplates->findById($ai_id)->find('translations')->first();
		if (!$lo_pageTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->PageTemplates->delete($lo_pageTemplate)) {
			$this->Flash->success(__('delete_succeeded'));
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
	 * @param PageTemplate $ao_pageTemplate
	 * @param string $as_method
	 * @return void
	 */
	protected function save(PageTemplate $ao_pageTemplate, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->PageTemplates->hasAttributes()) {
			$la_associated[] = $this->PageTemplates->getAttributesTable(true);
			$ao_pageTemplate->setAccess('attributes', true);
		}

		$la_requestData = $this->request->getData();

		if (!empty($la_requestData['content_areas'])) {
			$lo_newContentArea = null;
			if (isset($la_requestData['content_areas']['new'])) {
				$lo_newContentArea = $this->createContentArea($la_requestData['content_areas']['new']);
			}

			$la_requestData['content_areas'] = array_values(array_filter($la_requestData['content_areas'], function (array $aa_element) {
				return !empty($aa_element['id']);
			}));

			array_walk($la_requestData['content_areas'], function (array &$aa_contentArea, int $ai_index): void {
				$aa_contentArea['_joinData']['system_order'] = $ai_index + 1;
			});

			if ($lo_newContentArea && !$lo_newContentArea->hasErrors()) {
				$la_requestData['content_areas'][] = [
					'id' => $lo_newContentArea->id,
					'_joinData' => [
						'system_order' => count($la_requestData['content_areas']) + 1,
					],
				];
			}
		}

		$this->PageTemplates->patchEntity($ao_pageTemplate, $la_requestData, [
			'associated' => array_merge($la_associated, [
				'ContentAreas' => [
					'fields' => ['_joinData'],
					'associated' => [
						'_joinData',
					],
				],
			]),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->PageTemplates->save($ao_pageTemplate)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'pageRoleId' => $ao_pageTemplate->pageRoleId], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_pageTemplate->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_pageTemplate->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			$ao_pageTemplate->systemOrder = null;
		}

		$this->Categories->ensurePossibleCategory($ao_pageTemplate);
	}


	/**
	 * @param array $aa_data
	 * @return \Awyiss\Model\Entity|null
	 */
	protected function createContentArea(array $aa_data): ?Entity {
		$ls_title = $aa_data['title'] ?? null;
		$ls_identifier = $aa_data['identifier'] ?? null;

		if (!$ls_title && !$ls_identifier) {
			return null;
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

		return $lo_contentArea;
	}
}
