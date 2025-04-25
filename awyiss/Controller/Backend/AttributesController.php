<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Table;
use Awyiss\Model\Table\GenericDatatablesTable;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnInterface;
use Awyiss\Utility\Inflector;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


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
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->Attributes->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);
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
			$lo_attributes = $this->paginate($lo_query);
		}
		else {
			$lo_attributes = $lo_query->all();
			$la_attributesGroupedByFieldset = $lo_query->all()->groupBy('fieldset')->toArray();
		}

		$ls_selectedScope = $this->Categories->getSelectedCategory();

		$la_availableFieldsets = [''];
		if (!in_array($ls_selectedScope, ['contents', 'widgets'])) {
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

		if (!$lo_attribute->scope) {
			$lo_attribute->scope = key($this->Categories->getCategories());
		}

		$this->setViewVars($lo_attribute);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var Attribute $lo_attribute */
		$lo_attribute = $this->Attributes->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
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
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Attribute $lo_attribute */
		$lo_attribute = $this->Attributes->findById($id)->first();
		if (!$lo_attribute) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Attributes->delete($lo_attribute)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));

				foreach ($lo_attribute->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Attribute $attribute
	 * @param string $method
	 * @return void
	 */
	protected function save(Attribute $attribute, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Attributes->hasAttributes()) {
			$la_associated[] = $this->Attributes->getAttributesTableName(true);
			$attribute->setAccess('attributes', true);
		}

		$this->Attributes->patchEntity($attribute, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Attributes->save($attribute, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'scope' => $attribute->scope,
						'page' => $this->Paginate->calculateEntityPagePosition($attribute),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $attribute->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($attribute->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		else {
			if ($this->Attributes->getSystemOrderRelatedColumns($attribute)) {
				$attribute->systemOrder = null;
			}
			else {
				$attribute->systemOrder = $attribute->hasOriginal('systemOrder') ? $attribute->getOriginal('systemOrder') : $attribute->get('systemOrder');
			}
		}

		$this->Categories->ensurePossibleCategory($attribute);
	}


	/**
	 * @param Attribute $attribute
	 * @param array $availableFieldsets
	 * @return void
	 */
	protected function ensurePossibleFieldset(Attribute $attribute, array $availableFieldsets): void {
		if (empty($attribute->fieldset) || !in_array($attribute->fieldset, $availableFieldsets)) {
			$la_errors = $attribute->getError('fieldset');

			/** @noinspection PhpVariableNamingConventionInspection */
			$attribute->fieldset = reset($availableFieldsets);

			if ($la_errors) {
				$attribute->setError('fieldset', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When fieldset is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('fieldset') !== null) {
				$lo_request = $lo_request->withData('fieldset', $attribute->fieldset);
				$this->setRequest($lo_request);
			}
		}
	}


	/**
	 * @param array $requestData
	 * @param \Awyiss\Model\Table $table
	 * @return int
	 */
	protected function _saveSystemOrder(array $requestData, Table $table): int {
		/*
		 * Build an array of the order data
		 * In the first level, the key is the fieldset, the value is an array of the child ids
		 * In the second level, the value is the child id, the key is the order, offset by -1
		 */
		$la_orderData = [];
		foreach ($requestData as $ls_fieldset => $la_children) {
			foreach ($la_children as $li_order => $li_id) {
				$la_orderData[] = [
					'id' => $li_id,
					'fieldset' => $ls_fieldset,
					'systemOrder' => $li_order + 1,
				];
			}
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$li_affectedRows = $table->updateAll(function (QueryExpression $expression) use ($la_orderData) {
			$lo_fieldsetCase = $expression->case();
			$lo_systemOrderCase = $expression->case();

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
	 * @param \Awyiss\Model\Entity\Attribute $attribute
	 * @return void
	 */
	protected function setViewVars(Attribute $attribute): void {
		$la_availableFieldsets = $this->Attributes->getAvailableFieldsets($attribute->scope);
		$this->ensurePossibleFieldset($attribute, $la_availableFieldsets);

		if (in_array($attribute->scope, ['contents', 'widgets'])) {
			$attribute->fieldset = '';
		}

		$la_pageRoles = array_keys(array_filter($this->attributeScopes, function ($table) {
			return !is_string($table);
		}));

		$lb_translatableDisabled = in_array($attribute->scope, array_merge($la_pageRoles, ['contents', 'menu_entries', 'pages']));
		$lb_requiredDisabled = in_array($attribute->scope, ['contents', 'widgets']);
		$lb_columnSpanDisabled = in_array($attribute->scope, ['contents', 'widgets']);

		if (!$lb_translatableDisabled) {
			$lo_table = $this->fetchTable(Inflector::camelize($attribute->scope));
			// If table is a generic data one and the records are not translatable, disable the translatable option
			if ($lo_table instanceof GenericDatatablesTable && !$lo_table->hasBehavior('Translate')) {
				$lb_translatableDisabled = true;
			}
		}

		$la_columnSpans = $this->Attributes->getColumnSpans();
		$la_columnSpans = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnSpans);

		$this->set([
			'attribute' => $attribute,
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
