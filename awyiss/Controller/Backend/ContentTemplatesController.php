<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


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
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->ContentTemplates->find('withUsages')->where($this->getOverviewWhere());
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
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->ContentTemplates->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->contain([
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
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->ContentTemplates->findById($id)->first();
		if (!$lo_contentTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->ContentTemplates->delete($lo_contentTemplate)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
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
	 * @param ContentTemplate $contentTemplate
	 * @param string $method
	 * @return void
	 */
	protected function save(ContentTemplate $contentTemplate, string $method = 'add'): void {
		$la_associated = [];
		if ($this->ContentTemplates->hasAttributes()) {
			$la_associated[] = $this->ContentTemplates->getAttributesTableName(true);
			$contentTemplate->setAccess('attributes', true);
		}

		$la_requestData = $this->request->getData() + ['content_template_elements' => []];

		if (!empty($la_requestData['content_template_elements'])) {
			$la_requestData['content_template_elements'] = array_filter($la_requestData['content_template_elements'], function ($element) {
				return !empty($element['identifier']);
			});

			$la_associated[] = 'ContentTemplateElements';
		}

		$this->ContentTemplates->patchEntity($contentTemplate, $la_requestData, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		$contentTemplate->set('contentAreas', []);
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

				$contentTemplate->contentAreas[] = $lo_contentArea;
			}
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->ContentTemplates->save($contentTemplate, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($contentTemplate),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $contentTemplate->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($contentTemplate->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->ContentTemplates->getSystemOrderRelatedColumns($contentTemplate)) {
				$contentTemplate->systemOrder = null;
			}
			else {
				$contentTemplate->systemOrder = $contentTemplate->hasOriginal('systemOrder') ? $contentTemplate->getOriginal('systemOrder') : $contentTemplate->get('systemOrder');
			}
		}
	}


	/**
	 * @param bool $returnGrouped
	 * @return array
	 */
	protected function getPageTemplates(bool $returnGrouped = true): array {
		static $lo_pageTemplates;

		if (!isset($lo_pageTemplates)) {
			$lo_pageTemplates = $this->fetchTable('PageTemplates')->find()->contain(['ContentAreas', 'PageRoles'])->all()->sortBy('pageRole.systemOrder', SORT_ASC);
		}

		if ($returnGrouped) {
			$lo_groupedPageTemplates = $lo_pageTemplates->filter(function (PageTemplate $entity) {
				return !empty($entity->contentAreas);
			})->groupBy(function (PageTemplate $entity) {
				return $entity->pageRole->label;
			});

			return $lo_groupedPageTemplates->toArray();
		}


		return $lo_pageTemplates->toArray();
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $contentTemplate
	 * @return void
	 */
	protected function setViewVars(ContentTemplate $contentTemplate): void {
		if ($contentTemplate->contentTemplateElements) {
			$contentTemplate->contentTemplateElements = collection($contentTemplate->contentTemplateElements)->indexBy('identifier')->toArray();
		}

		$lo_contentTemplate = $contentTemplate;

		// Sort the available content elements by the order of the assigned content template elements
		$la_availableContentElements = $this->ContentTemplates->getAvailableContentElements();
		uksort($la_availableContentElements, function ($a, $b) use ($lo_contentTemplate) {
			$la_keys = array_keys($lo_contentTemplate->contentTemplateElements ?? []);
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
		uasort($la_availableContentAttributes, function ($a, $b) use ($lo_contentTemplate) {
			$la_keys = array_keys($lo_contentTemplate->contentTemplateElements ?? []);
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

		$la_columnSpans = $this->ContentTemplates->ContentTemplateElements->getColumnSpans();
		$la_columnSpans = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnSpans);


		$this->set([
			'contentTemplate' => $contentTemplate,
			'availableContentElements' => $la_availableContentElements,
			'availableContentAttributes' => $la_availableContentAttributes,
			'availableFieldsets' => $this->ContentTemplates->getAvailableFieldsets(),
			'columnSpans' => $la_columnSpans,
			'pageTemplates' => $this->getPageTemplates(),
		]);
	}
}
