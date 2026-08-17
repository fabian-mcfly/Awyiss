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
		'defaultSortableFields' => ['usedForContents'],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		/** @uses \Awyiss\Model\Table\ContentTemplatesTable::findWithUsages() */
		$query = $this->ContentTemplates->find('withUsages')->where($this->getOverviewWhere());
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
		if ($paginated) {
			$contentTemplates = $this->paginate($query);
		}
		else {
			$contentTemplates = $query->all();
		}

		$this->set([
			'contentTemplates' => $contentTemplates,
			'attributes' => $this->ContentTemplates->getAttributes(),
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

		$contentTemplate = $this->ContentTemplates->newDefaultEntity([
			'mediaElementAssignments' => [],
		]);

		if ($this->request->is('post')) {
			$this->save($contentTemplate);
		}

		$this->setViewVars($contentTemplate);
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
		 * @var \Awyiss\Model\Entity\ContentTemplate $contentTemplate
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$contentTemplate = $this->ContentTemplates
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->contain([
				'ContentAreas',
				'ContentTemplateElements' => [
					'queryBuilder' => function (SelectQuery $query) {
						/** @uses \Awyiss\Model\Table::findTranslations() */
						return $query->find('translations');
					},
				],
			])
			->first()
		;

		if (!$contentTemplate) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($contentTemplate, 'edit');
		}

		$this->setViewVars($contentTemplate);
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

		/** @var \Awyiss\Model\Entity\ContentTemplate $contentTemplate */
		$contentTemplate = $this->ContentTemplates->findById($id)->first();
		if (!$contentTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->ContentTemplates->delete($contentTemplate)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($contentTemplate->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $contentTemplate
	 * @param string $method
	 * @return void
	 */
	protected function save(ContentTemplate $contentTemplate, string $method = 'add'): void {
		$associated = [];
		if ($this->ContentTemplates->hasAttributes()) {
			$associated[] = $this->ContentTemplates->getAttributesTableName(true);
			$contentTemplate->setAccess('attributes', true);
		}

		$requestData = $this->request->getData() + ['contentTemplateElements' => []];

		if (!empty($requestData['contentTemplateElements'])) {
			$requestData['contentTemplateElements'] = array_filter($requestData['contentTemplateElements'], function ($element) {
				return !empty($element['identifier']);
			});

			$requestData['contentTemplateElements'] = array_map(function ($element) {
				static $systemOrder = 1;

				$element['systemOrder'] = $systemOrder++;

				return $element;
			}, $requestData['contentTemplateElements']);

			$request = $this->request->withData('contentTemplateElements', $requestData['contentTemplateElements']);
			$this->setRequest($request);

			$associated[] = 'ContentTemplateElements';
		}

		$this->ContentTemplates->patchEntity($contentTemplate, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		$contentTemplate->set('contentAreas', []);
		if (!empty($requestData['contentAreas'])) {
			$contentAreas = collection(array_column($this->getPageTemplates(false), 'contentAreas'))->unfold();
			$contentAreas = $contentAreas->indexBy('id')->toArray();

			$throughTable = $this->ContentTemplates->ContentAreas->getThrough();
			$throughTable = $this->fetchTable($throughTable);

			foreach ($requestData['contentAreas'] as $contentAreaData) {
				if (empty($contentAreaData['contentAreaId'])) {
					continue;
				}

				$contentArea = clone $contentAreas[ $contentAreaData['contentAreaId'] ];
				unset($contentArea->_joinData);
				$contentArea->_joinData = $throughTable->newEntity([
					'pageTemplateId' => $contentAreaData['pageTemplateId'],
				]);

				$contentTemplate->contentAreas[] = $contentArea;
			}
		}

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->ContentTemplates->save($contentTemplate, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($contentTemplate),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $contentTemplate->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($contentTemplate->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * @param bool $returnGrouped
	 * @return array
	 */
	protected function getPageTemplates(bool $returnGrouped = true): array {
		static $pageTemplates;

		if (!isset($pageTemplates)) {
			$pageTemplates = $this
				->fetchTable('PageTemplates')
				->find()
				->contain(['ContentAreas', 'PageRoles'])
				->all()
				->sortBy(
					'pageRole.systemOrder',
					SORT_ASC
				)
			;
		}

		if ($returnGrouped) {
			$groupedPageTemplates = $pageTemplates
				->filter(function (PageTemplate $entity) {
					return !empty($entity->contentAreas);
				})
				->groupBy(function (PageTemplate $entity) {
					return $entity->pageRole->label;
				})
			;

			return $groupedPageTemplates->toArray();
		}


		return $pageTemplates->toArray();
	}


	/**
	 * @param \Awyiss\Model\Entity\ContentTemplate $contentTemplate
	 * @return void
	 */
	protected function setViewVars(ContentTemplate $contentTemplate): void {
		// Sort the available content elements by the order of the assigned content template elements
		$availableContentElements = $this->ContentTemplates->getAvailableContentElements();
		uksort($availableContentElements, function ($a, $b) use ($contentTemplate) {
			$keys = array_keys($contentTemplate->contentTemplateElements ?? []);
			$aPos = array_search($a, $keys);
			$bPos = array_search($b, $keys);

			// If $a is not found in the keys, set its position to a high value to sort it at the end
			if ($aPos === false) {
				$aPos = PHP_INT_MAX;
			}

			// Do the same for $b
			if ($bPos === false) {
				$bPos = PHP_INT_MAX;
			}

			// Compare the positions
			return $aPos <=> $bPos;
		});

		// Sort the available content attributes by the order of the assigned content template elements
		$availableContentAttributes = $this->ContentTemplates->getAvailableContentAttributes();
		uasort($availableContentAttributes, function ($a, $b) use ($contentTemplate) {
			$keys = array_keys($contentTemplate->contentTemplateElements ?? []);
			$aIdentifier = 'attributes.' . $a['identifier'];
			$bIdentifier = 'attributes.' . $b['identifier'];

			$aPos = array_search($aIdentifier, $keys);
			$bPos = array_search($bIdentifier, $keys);

			// If $a is not found in the keys, set its position to a high value to sort it at the end
			if ($aPos === false) {
				$aPos = PHP_INT_MAX;
			}

			// Do the same for $b
			if ($bPos === false) {
				$bPos = PHP_INT_MAX;
			}

			// Compare the positions
			return $aPos <=> $bPos;
		});
		$availableContentAttributes = array_column($availableContentAttributes, null, 'identifier');

		$columnSpans = $this->ContentTemplates->ContentTemplateElements->getColumnSpans();
		$columnSpans = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnSpans);


		$this->set([
			'contentTemplate' => $contentTemplate,
			'availableContentElements' => $availableContentElements,
			'availableContentAttributes' => $availableContentAttributes,
			'availableFieldsets' => $this->ContentTemplates->getAvailableFieldsets(),
			'columnSpans' => $columnSpans,
			'pageTemplates' => $this->getPageTemplates(),
		]);
	}
}
