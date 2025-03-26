<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\WidgetTemplate;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * WidgetTemplates Controller
 *
 * @property \Awyiss\Model\Table\WidgetTemplatesTable $WidgetTemplates
 */
class WidgetTemplatesController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
		'defaultSortableFields' => ['used_for_widgets'],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->WidgetTemplates->find('withUsages')->where($this->getOverviewWhere());
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
			$lo_widgetTemplates = $this->paginate($lo_query);
		}
		else {
			$lo_widgetTemplates = $lo_query->all();
		}

		$this->set([
			'widgetTemplates' => $lo_widgetTemplates,
			'attributes' => $this->WidgetTemplates->getAttributes(),
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

		$lo_widgetTemplate = $this->WidgetTemplates->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_widgetTemplate);
		}

		$this->setViewVars($lo_widgetTemplate);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var \Awyiss\Model\Entity\WidgetTemplate $lo_widgetTemplate */
		$lo_widgetTemplate = $this->WidgetTemplates->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')
		->contain([
			'WidgetTemplateElements' => [
				'queryBuilder' => function (SelectQuery $query) {
					return $query->find('translations');
				},
			],
		])->first();

		if (!$lo_widgetTemplate) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_widgetTemplate, 'edit');
		}

		$this->setViewVars($lo_widgetTemplate);
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

		/** @var \Awyiss\Model\Entity\WidgetTemplate $lo_widgetTemplate */
		$lo_widgetTemplate = $this->WidgetTemplates->findById($id)->first();
		if (!$lo_widgetTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->WidgetTemplates->delete($lo_widgetTemplate)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_widgetTemplate->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate
	 * @param string $method
	 * @return void
	 */
	protected function save(WidgetTemplate $widgetTemplate, string $method = 'add'): void {
		$la_associated = [];
		if ($this->WidgetTemplates->hasAttributes()) {
			$la_associated[] = $this->WidgetTemplates->getAttributesTableName(true);
			$widgetTemplate->setAccess('attributes', true);
		}

		$la_requestData = $this->request->getData() + ['widget_template_elements' => []];

		if (!empty($la_requestData['widget_template_elements'])) {
			$la_requestData['widget_template_elements'] = array_filter($la_requestData['widget_template_elements'], function ($element) {
				return !empty($element['identifier']);
			});

			$la_requestData['widget_template_elements'] = array_map(function ($element) {
				static $li_systemOrder = 1;

				/** @noinspection PhpVariableNamingConventionInspection */
				$element['system_order'] = $li_systemOrder++;

				return $element;
			}, $la_requestData['widget_template_elements']);

			$lo_request = $this->request->withData('widget_template_elements', $la_requestData['widget_template_elements']);
			$this->setRequest($lo_request);

			$la_associated[] = 'WidgetTemplateElements';
		}

		$this->WidgetTemplates->patchEntity($widgetTemplate, $la_requestData, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->WidgetTemplates->save($widgetTemplate, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $widgetTemplate->id], true), 302);
			}

			$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
			foreach ($widgetTemplate->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->WidgetTemplates->getSystemOrderRelatedColumns($widgetTemplate)) {
				$widgetTemplate->systemOrder = null;
			}
			else {
				$widgetTemplate->systemOrder = $widgetTemplate->hasOriginal('systemOrder') ? $widgetTemplate->getOriginal('systemOrder') : $widgetTemplate->get('systemOrder');
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate
	 * @return void
	 */
	protected function setViewVars(WidgetTemplate $widgetTemplate): void {
		$lo_widgetTemplate = $widgetTemplate;

		// Sort the available widget elements by the order of the assigned widget template elements
		$la_availableWidgetElements = $this->WidgetTemplates->getAvailableWidgetElements();
		uksort($la_availableWidgetElements, function ($a, $b) use ($lo_widgetTemplate) {
			$la_keys = array_keys($lo_widgetTemplate->widgetTemplateElements ?? []);
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

		// Sort the available widget attributes by the order of the assigned widget template elements
		$la_availableWidgetAttributes = $this->WidgetTemplates->getAvailableWidgetAttributes();
		uasort($la_availableWidgetAttributes, function ($a, $b) use ($lo_widgetTemplate) {
			$la_keys = array_keys($lo_widgetTemplate->widgetTemplateElements ?? []);
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

		$la_columnSpans = $this->WidgetTemplates->WidgetTemplateElements->getColumnSpans();
		$la_columnSpans = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnSpans);


		$this->set([
			'widgetTemplate' => $widgetTemplate,
			'availableWidgetElements' => $la_availableWidgetElements,
			'availableWidgetAttributes' => $la_availableWidgetAttributes,
			'availableFieldsets' => $this->WidgetTemplates->getAvailableFieldsets(),
			'columnSpans' => $la_columnSpans,
		]);
	}
}
