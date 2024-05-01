<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * Attributes Controller
 *
 * @property \Awyiss\Model\Table\AttributesTable $Attributes
 */
class AttributesController extends Controller {
	/**
	 * @var array
	 */
	protected array $attributeScopes;


	/**
	 * Called after the `__construct()` method
	 *
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function initialize(): void {
		$this->attributeScopes = $this->Attributes->getAvailableScopes();

		parent::initialize();
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->Attributes->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);

		$lb_paginated = $this->paginate['enabled'];
		if ($lb_paginated) {
			$lo_attributes = $this->paginate($lo_query);
		}
		else {
			$lo_attributes = $lo_query->all();
			$la_attributesGroupedByFieldset = $lo_query->all()->groupBy('fieldset')->toArray();
		}

		$ls_selectedScope = $this->Categories->getSelectedCategory();

		$la_availableFieldsets = [''];
		if ($ls_selectedScope !== 'contents') {
			$la_availableFieldsets = $this->Attributes->getAvailableFieldsets($ls_selectedScope);
		}

		$this->set([
			'attributes' => $lo_attributes,
			'attributesGroupedByFieldset' => $la_attributesGroupedByFieldset ?? [],
			'availableFieldsets' => $la_availableFieldsets,
			'paginated' => $lb_paginated,
			'selectedScope' => $ls_selectedScope,
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

		$lo_attribute = $this->Attributes->newDefaultEntity([
			'scope' => $this->request->getParam('scope') ?? $this->Categories->getSelectedCategory(),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_attribute);
		}

		$this->setViewVars($lo_attribute);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var Attribute $lo_attribute */
		$lo_attribute = $this->Attributes->findById($ai_id)->find('translations')->first();
		if (!$lo_attribute) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_attribute, 'edit');
		}

		$this->setViewVars($lo_attribute);
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

		/** @var Attribute $lo_attribute */
		$lo_attribute = $this->Attributes->findById($ai_id)->find('translations')->first();
		if (!$lo_attribute) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Attributes->delete($lo_attribute)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_attribute->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Attribute $ao_attribute
	 * @param string $as_method
	 * @return void
	 */
	protected function save(Attribute $ao_attribute, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Attributes->hasAttributes()) {
			$la_associated[] = $this->Attributes->getAttributesTableName(true);
			$ao_attribute->setAccess('attributes', true);
		}

		$this->Attributes->patchEntity($ao_attribute, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Attributes->save($ao_attribute, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'scope' => $ao_attribute->scope], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_attribute->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_attribute->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		$this->Categories->ensurePossibleCategory($ao_attribute);
	}


	/**
	 * @param Attribute $ao_attribute
	 * @param array $aa_availableFieldsets
	 * @return void
	 */
	protected function ensurePossibleFieldset(Attribute $ao_attribute, array $aa_availableFieldsets): void {
		if (empty($ao_attribute->fieldset) || !in_array($ao_attribute->fieldset, $aa_availableFieldsets)) {
			$la_errors = $ao_attribute->getError('fieldset');

			$ao_attribute->fieldset = reset($aa_availableFieldsets);

			if ($la_errors) {
				$ao_attribute->setError('fieldset', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When fieldset is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('fieldset') !== null) {
				$lo_request = $lo_request->withData('fieldset', $ao_attribute->fieldset);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param array $aa_requestData
	 * @param \Awyiss\Model\Table $ao_table
	 * @return int
	 */
	protected function _saveSystemOrder(array $aa_requestData, Table $ao_table): int {
		/*
		 * Build an array of the order data
		 * In the first level, the key is the fieldset, the value is an array of the child ids
		 * In the second level, the value is the child id, the key is the order, offset by -1
		 */
		$la_orderData = [];
		foreach ($aa_requestData as $ls_fieldset => $la_children) {
			foreach ($la_children as $li_order => $li_id) {
				$la_orderData[] = [
					'id' => $li_id,
					'fieldset' => $ls_fieldset,
					'systemOrder' => $li_order + 1,
				];
			}
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$li_affectedRows = $ao_table->updateAll(function (QueryExpression $ao_expression) use ($la_orderData) {
			$lo_fieldsetCase = $ao_expression->case();
			$lo_systemOrderCase = $ao_expression->case();

			foreach ($la_orderData as $la_data) {
				$lo_fieldsetCase->when(['id' => $la_data['id']])->then($la_data['fieldset'], 'string');
				$lo_systemOrderCase->when(['id' => $la_data['id']])->then($la_data['systemOrder'], 'integer');
			}

			return [
				'fieldset' => $lo_fieldsetCase,
				'system_order' => $lo_systemOrderCase,
			];
		}, [
			'id IN' => array_column($la_orderData, 'id'),
		]);


		return $li_affectedRows;
	}


	/**
	 * @param \Awyiss\Model\Entity\Attribute $ao_attribute
	 * @return void
	 */
	protected function setViewVars(Attribute $ao_attribute): void {
		$la_availableFieldsets = $this->Attributes->getAvailableFieldsets($ao_attribute->scope);
		$this->ensurePossibleFieldset($ao_attribute, $la_availableFieldsets);

		if ($ao_attribute->scope === 'contents') {
			$ao_attribute->fieldset = '';
		}

		$la_pageRoles = array_keys(array_filter($this->attributeScopes, function ($ax_table) {
			return !is_string($ax_table);
		}));

		$lb_translatableDisabled = in_array($ao_attribute->scope, array_merge($la_pageRoles, ['contents', 'menu_entries', 'pages']));
		$lb_requiredDisabled = in_array($ao_attribute->scope, ['contents']);
		$lb_columnSpanDisabled = in_array($ao_attribute->scope, ['contents']);

		$la_columnSpans = $this->Attributes->getColumnSpans();
		$la_columnSpans = array_map(function (ColumnInterface $ao_column): string {
			return $ao_column->getLabel();
		}, $la_columnSpans);

		$this->set([
			'attribute' => $ao_attribute,
			'availableFieldsets' => $la_availableFieldsets,
			'availableInputTypes' => $this->Attributes->getAvailableInputTypes(),
			'pageRoles' => $la_pageRoles,
			'translatableDisabled' => $lb_translatableDisabled,
			'requiredDisabled' => $lb_requiredDisabled,
			'columnSpans' => $la_columnSpans,
			'columnSpanDisabled' => $lb_columnSpanDisabled,
		]);
	}
}
