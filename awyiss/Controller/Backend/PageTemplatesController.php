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
		/** @uses \Awyiss\Model\Table\PageTemplatesTable::findWithUsages() */
		$query = $this->PageTemplates->find('withUsages')->where($this->getOverviewWhere())->contain(['ContentAreas', 'PageRoles']);
		$this->Categories->filterQuery($query, null, false);
		$this->Search->filterQuery($query);

		return $query;
	}

	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();

		$paginated = $this->paginate['enabled'];
		$pageTemplatesGroupdByPageRole = [];
		if ($paginated) {
			$pageTemplates = $this->paginate($query);
		}
		else {
			$pageTemplates = $this->Categories->groupResult($query)->all();
			$pageTemplatesGroupdByPageRole = $pageTemplates->toArray();
		}

		$this->set([
			'pageTemplates' => $pageTemplates,
			'pageTemplatesGroupdByPageRole' => $pageTemplatesGroupdByPageRole,
			'attributes' => $this->PageTemplates->getAttributes(),
			'paginated' => $paginated,
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

		$pageRoleId = $this->request->getParam('pageRoleId') ?? $this->Categories->getSelectedCategory();
		if (is_numeric($pageRoleId)) {
			$pageRoleId = (int)$pageRoleId;
		}
		else {
			$pageRoleId = key($this->Categories->getCategories());
		}

		$pageTemplate = $this->PageTemplates->newDefaultEntity([
			'mediaElementAssignments' => [],
			'pageRoleId' => $pageRoleId,
		]);

		if ($this->request->is('post')) {
			$this->save($pageTemplate);
		}

		$this->setViewVars($pageTemplate);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/**
		 * @var \Awyiss\Model\Entity\PageTemplate $pageTemplate
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$pageTemplate = $this->PageTemplates->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->contain(['ContentAreas'])->first();
		if (!$pageTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($pageTemplate, 'edit');
		}

		$this->setViewVars($pageTemplate);
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

		/** @var PageTemplate $pageTemplate */
		$pageTemplate = $this->PageTemplates->findById($id)->first();
		if (!$pageTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->PageTemplates->delete($pageTemplate)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($pageTemplate->getError('_general') as $error) {
					$this->Flash->error($error);
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
		$associated = [];
		if ($this->PageTemplates->hasAttributes()) {
			$associated[] = $this->PageTemplates->getAttributesTableName(true);
			$pageTemplate->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();

		if (!empty($requestData['content_areas'])) {
			$requestData['content_areas'] = array_values(array_filter($requestData['content_areas'], function (array $element) {
				return !empty($element['id']);
			}));

			$systemOrder = 1;
			array_walk($requestData['content_areas'], function (array &$contentArea) use (&$systemOrder): void {
				$contentArea['_joinData']['system_order'] = $systemOrder;
				$systemOrder++;
			});
		}

		if (!empty($requestData['content_template_content_areas'])) {
			if (empty($requestData['content_areas'])) {
				unset($requestData['content_template_content_areas']);
			}
			else {
				$contentAreaIds = array_column($requestData['content_areas'], 'id');
				$requestData['content_template_content_areas'] = array_merge(...$requestData['content_template_content_areas']);
				$requestData['content_template_content_areas'] = array_filter($requestData['content_template_content_areas'], function (array $element) use ($contentAreaIds) {
					return !empty($element['content_template_id']) && in_array($element['content_area_id'], $contentAreaIds);
				});
			}
		}

		$this->PageTemplates->patchEntity($pageTemplate, $requestData, [
			'associated' => array_merge($associated, [
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
			$saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->PageTemplates->save($pageTemplate, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
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
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($pageTemplate->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		$this->Categories->ensurePossibleCategory($pageTemplate);
	}


	/**
	 * @param \Awyiss\Model\Entity\PageTemplate $pageTemplate
	 * @return void
	 */
	protected function setViewVars(PageTemplate $pageTemplate): void {
		$query = $this->PageTemplates->ContentAreas->find();

		if ($pageTemplate->contentAreas && !$pageTemplate->isNew()) {
			$query->orderByAsc(
				$query->func()->coalesce([
					'ContentAreas_title_translation.content' => 'literal',
					'ContentAreas.title' => 'literal',
				])
			);
			$query->orderByAsc('ContentAreas_title_translation.content');
			$query->orderByAsc('ContentAreas.title');

			$query->contain([
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

		$contentAreas = $query->all()->toArray();

		/** @uses \Awyiss\Model\Table::findTranslations() */
		$contentTemplates = $this->PageTemplates->ContentAreas->ContentTemplates->find('translations')->all()->toArray();

		$this->set([
			'pageTemplate' => $pageTemplate,
			'contentAreas' => $contentAreas,
			'contentTemplates' => $contentTemplates,
		]);
	}
}
