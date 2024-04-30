<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * ContentTemplates Controller
 *
 * @property \Awyiss\Model\Table\ContentTemplatesTable $ContentTemplates
 */
class ContentTemplatesController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
		'defaultSortableFields' => ['used_for_contents'],
	];


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->ContentTemplates->find('withUsages')->where($this->getOverviewWhere());

		$lb_paginated = $this->paginate['enabled'];
		if ($lb_paginated) {
			$lo_contentTemplates = $this->paginate($lo_query);
		}
		else {
			$lo_contentTemplates = $lo_query->all();
		}

		$this->set([
			'contentTemplates' => $lo_contentTemplates,
			'attributes' => $this->ContentTemplates->getAttributes(),
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

		$lo_contentTemplate = $this->ContentTemplates->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_contentTemplate);
		}

		$this->setViewVars($lo_contentTemplate);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->ContentTemplates->findById($ai_id)->find('translations')->contain([
			'ContentAreas',
			'ContentTemplateElements',
		])->first();
		if (!$lo_contentTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_contentTemplate, 'edit');
		}

		$this->setViewVars($lo_contentTemplate);
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

		/** @var ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->ContentTemplates->findById($ai_id)->find('translations')->first();
		if (!$lo_contentTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->ContentTemplates->delete($lo_contentTemplate)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_contentTemplate->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param ContentTemplate $ao_contentTemplate
	 * @param string $as_method
	 * @return void
	 */
	protected function save(ContentTemplate $ao_contentTemplate, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->ContentTemplates->hasAttributes()) {
			$la_associated[] = $this->ContentTemplates->getAttributesTableName(true);
			$ao_contentTemplate->setAccess('attributes', true);
		}

		$la_requestData = $this->request->getData() + ['content_template_elements' => []];

		if (!empty($la_requestData['content_template_elements'])) {
			$la_requestData['content_template_elements'] = array_filter($la_requestData['content_template_elements'], function ($aa_element) {
				return !empty($aa_element['identifier']);
			});

			$la_associated[] = 'ContentTemplateElements';
		}

		$this->ContentTemplates->patchEntity($ao_contentTemplate, $la_requestData, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		$ao_contentTemplate->set('contentAreas', []);
		if (!empty($la_requestData['content_areas'])) {
			$la_contentAreas = collection(array_column($this->getPageTemplates(false), 'contentAreas'))->unfold();
			$la_contentAreas = $la_contentAreas->indexBy('id')->toArray();

			$ls_throughTable = $this->ContentTemplates->ContentAreas->getThrough();
			$lo_throughTable = $this->fetchTable($ls_throughTable);

			foreach ($la_requestData['content_areas'] as $la_contentAreaData) {
				if (empty($la_contentAreaData['content_area_id'])) {
					continue;
				}

				$lo_contentArea = clone $la_contentAreas[ $la_contentAreaData['content_area_id'] ];
				unset($lo_contentArea->_joinData);
				$lo_contentArea->_joinData = $lo_throughTable->newEntity([
					'page_template_id' => $la_contentAreaData['page_template_id'],
				]);

				$ao_contentTemplate->contentAreas[] = $lo_contentArea;
			}
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->ContentTemplates->save($ao_contentTemplate, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_contentTemplate->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_contentTemplate->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * @param bool $ab_returnGrouped
	 * @return array
	 */
	protected function getPageTemplates(bool $ab_returnGrouped = true): array {
		static $lo_pageTemplates;

		if (!isset($lo_pageTemplates)) {
			$lo_pageTemplates = $this->fetchTable('PageTemplates')->find()->contain(['ContentAreas', 'PageRoles'])->all()->sortBy('pageRole.systemOrder', SORT_ASC);
		}

		if ($ab_returnGrouped) {
			$lo_groupedPageTemplates = $lo_pageTemplates->filter(function (PageTemplate $ao_entity) {
				return !empty($ao_entity->contentAreas);
			})->groupBy(function (PageTemplate $ao_entity) {
				return $ao_entity->pageRole->label;
			});

			return $lo_groupedPageTemplates->toArray();
		}


		return $lo_pageTemplates->toArray();
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $ao_contentTemplate
	 * @return void
	 */
	protected function setViewVars(ContentTemplate $ao_contentTemplate): void {
		if ($ao_contentTemplate->contentTemplateElements) {
			$ao_contentTemplate->contentTemplateElements = collection($ao_contentTemplate->contentTemplateElements)->indexBy('identifier')->toArray();
		}

		// Sort the available content elements by the order of the assigned content template elements
		$la_availableContentElements = $this->ContentTemplates->getAvailableContentElements();
		uksort($la_availableContentElements, function ($a, $b) use ($ao_contentTemplate) {
			$la_keys = array_keys($ao_contentTemplate->contentTemplateElements ?? []);
			$lx_aPos = array_search($a, $la_keys);
			$lx_bPos = array_search($b, $la_keys);

			// If $a is not found in the keys, set its position to a high value to sort it at the end
			if ($lx_aPos === false) {
				$lx_aPos = PHP_INT_MAX;
			}

			// Do the same for $b
			if ($lx_bPos === false) {
				$lx_bPos = PHP_INT_MAX;
			}

			// Compare the positions
			return $lx_aPos <=> $lx_bPos;
		});

		// Sort the available content attributes by the order of the assigned content template elements
		$la_availableContentAttributes = $this->ContentTemplates->getAvailableContentAttributes();
		uasort($la_availableContentAttributes, function ($a, $b) use ($ao_contentTemplate) {
			$la_keys = array_keys($ao_contentTemplate->contentTemplateElements ?? []);
			$ls_aIdentifier = 'attributes.' . $a['identifier'];
			$ls_bIdentifier = 'attributes.' . $b['identifier'];

			$lx_aPos = array_search($ls_aIdentifier, $la_keys);
			$lx_bPos = array_search($ls_bIdentifier, $la_keys);

			// If $a is not found in the keys, set its position to a high value to sort it at the end
			if ($lx_aPos === false) {
				$lx_aPos = PHP_INT_MAX;
			}

			// Do the same for $b
			if ($lx_bPos === false) {
				$lx_bPos = PHP_INT_MAX;
			}

			// Compare the positions
			return $lx_aPos <=> $lx_bPos;
		});

		$this->set([
			'contentTemplate' => $ao_contentTemplate,
			'availableContentElements' => $la_availableContentElements,
			'availableContentAttributes' => $la_availableContentAttributes,
			'availableFieldsets' => $this->ContentTemplates->getAvailableFieldsets(),
			'pageTemplates' => $this->getPageTemplates(),
		]);
	}
}
