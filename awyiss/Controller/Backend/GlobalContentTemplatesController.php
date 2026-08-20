<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\GlobalContentTemplate;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnSystem\ColumnInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * GlobalContentTemplates Controller
 *
 * @property \Awyiss\Model\Table\GlobalContentTemplatesTable $GlobalContentTemplates
 */
class GlobalContentTemplatesController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
		'defaultSortableFields' => ['usedForGlobalContents'],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		/** @uses \Awyiss\Model\Table\GlobalContentTemplatesTable::findWithUsages() */
		$query = $this->GlobalContentTemplates->find('withUsages')->where($this->getOverviewWhere());
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
			$globalContentTemplates = $this->paginate($query);
		}
		else {
			$globalContentTemplates = $query->all();
		}

		$this->set([
			'globalContentTemplates' => $globalContentTemplates,
			'attributes' => $this->GlobalContentTemplates->getAttributes(),
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

		$globalContentTemplate = $this->GlobalContentTemplates->newDefaultEntity([
			'mediaElementAssignments' => [],
		]);

		if ($this->request->is('post')) {
			$this->save($globalContentTemplate);
		}

		$this->setViewVars($globalContentTemplate);
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
		 * @var \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$globalContentTemplate = $this->GlobalContentTemplates
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->contain([
				'GlobalContentTemplateElements' => [
					/** @uses \Awyiss\Model\Table::findTranslations() */
					'queryBuilder' => fn(SelectQuery $query) => $query->find('translations'),
				],
			])
			->first()
		;

		if (!$globalContentTemplate) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($globalContentTemplate, 'edit');
		}

		$this->setViewVars($globalContentTemplate);
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

		/** @var \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate */
		$globalContentTemplate = $this->GlobalContentTemplates->findById($id)->first();
		if (!$globalContentTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->GlobalContentTemplates->delete($globalContentTemplate)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($globalContentTemplate->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate
	 * @param string $method
	 * @return void
	 */
	protected function save(GlobalContentTemplate $globalContentTemplate, string $method = 'add'): void {
		$associated = [];
		if ($this->GlobalContentTemplates->hasAttributes()) {
			$associated[] = $this->GlobalContentTemplates->getAttributesTableName(true);
			$globalContentTemplate->setAccess('attributes', true);
		}

		$requestData = $this->request->getData() + ['globalContentTemplateElements' => []];

		if (!empty($requestData['globalContentTemplateElements'])) {
			$requestData['globalContentTemplateElements'] = array_filter(
				$requestData['globalContentTemplateElements'],
				fn($element) => !empty($element['identifier'])
			);

			$currentFieldset = '';
			$systemOrder = 1;
			$requestData['globalContentTemplateElements'] = array_map(
				function (array $element) use (&$currentFieldset, &$systemOrder): array {
					if ($element['fieldset'] !== $currentFieldset) {
						$currentFieldset = $element['fieldset'];
						$systemOrder = 1;
					}

					$element['systemOrder'] = $systemOrder++;

					return $element;
				},
				$requestData['globalContentTemplateElements']
			);

			$request = $this->request->withData('globalContentTemplateElements', $requestData['globalContentTemplateElements']);
			$this->setRequest($request);

			$associated[] = 'GlobalContentTemplateElements';
		}

		$this->GlobalContentTemplates->patchEntity($globalContentTemplate, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->GlobalContentTemplates->save($globalContentTemplate, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($globalContentTemplate),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $globalContentTemplate->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($globalContentTemplate->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate
	 * @return void
	 */
	protected function setViewVars(GlobalContentTemplate $globalContentTemplate): void {
		// Sort the available content elements by the order of the assigned Global Content Template Elements
		$availableGlobalContentElements = $this->GlobalContentTemplates->getAvailableGlobalContentElements();
		uksort($availableGlobalContentElements, function ($a, $b) use ($globalContentTemplate) {
			$keys = array_keys($globalContentTemplate->globalContentTemplateElements ?? []);
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

		// Sort the available content attributes by the order of the assigned Global Content Template Elements
		$availableGlobalContentAttributes = $this->GlobalContentTemplates->getAvailableGlobalContentAttributes();
		uasort($availableGlobalContentAttributes, function (array $a, array $b) use ($globalContentTemplate): int {
			$keys = array_keys($globalContentTemplate->globalContentTemplateElements ?? []);
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

		$columnSpans = $this->GlobalContentTemplates->GlobalContentTemplateElements->getColumnSpans();
		$columnSpans = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnSpans);


		$this->set([
			'globalContentTemplate' => $globalContentTemplate,
			'availableGlobalContentElements' => $availableGlobalContentElements,
			'availableGlobalContentAttributes' => $availableGlobalContentAttributes,
			'availableFieldsets' => $this->GlobalContentTemplates->getAvailableFieldsets(),
			'columnSpans' => $columnSpans,
		]);
	}
}
