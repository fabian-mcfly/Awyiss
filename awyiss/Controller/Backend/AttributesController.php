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
use Cake\Utility\Text;


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
	 * @throws \Exception
	 */
	public function initialize(): void {
		$this->attributeScopes = $this->Attributes->getAvailableScopes();

		$inputTypes = $this->Attributes->getAvailableInputTypes();
		$this->paginate['fieldTranslations'] = [
			'inputType' => array_combine($inputTypes, array_map(function ($type) {
				return Text::slug(__('input_type_' . Inflector::underscore($type)));
			}, $this->Attributes->getAvailableInputTypes())),
		];

		$fieldsets = $this->Attributes->getAvailableFieldsets();
		$this->paginate['fieldTranslations']['fieldset'] = array_combine($fieldsets, array_map(function ($fieldset) {
			return Text::slug(__('fieldset_' . $fieldset));
		}, $fieldsets));

		parent::initialize();
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->Attributes->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);
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
			$attributes = $this->paginate($query);
		}
		else {
			$attributes = $query->all();
			$attributesGroupedByFieldset = $query->all()->groupBy('fieldset')->toArray();
		}

		$selectedScope = $this->Categories->getSelectedCategory();

		$availableFieldsets = [''];
		if (!in_array($selectedScope, ['Contents', 'GlobalContents'])) {
			$availableFieldsets = $this->Attributes->getAvailableFieldsets($selectedScope);
		}

		$this->set([
			'attributes' => $attributes,
			'attributesGroupedByFieldset' => $attributesGroupedByFieldset ?? [],
			'availableFieldsets' => $availableFieldsets,
			'availableInputTypes' => $this->Attributes->getAvailableInputTypes(),
			'paginated' => $paginated,
			'selectedScope' => $selectedScope,
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

		$attribute = $this->Attributes->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($attribute);
		}

		if (!$attribute->scope) {
			$attribute->scope = key($this->Categories->getCategories());
		}

		$this->setViewVars($attribute);
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
		 * @var \Awyiss\Model\Entity\Attribute $attribute
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$attribute = $this->Attributes->findById($id)->find('translations')->first();
		if (!$attribute) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($attribute, 'edit');
		}

		$this->setViewVars($attribute);
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

		/** @var Attribute $attribute */
		$attribute = $this->Attributes->findById($id)->first();
		if (!$attribute) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Attributes->delete($attribute)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));

				foreach ($attribute->getError('_general') as $error) {
					$this->Flash->error($error);
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
		$associated = [];
		if ($this->Attributes->hasAttributes()) {
			$associated[] = $this->Attributes->getAttributesTableName(true);
			$attribute->setAccess('attributes', true);
		}

		$this->Attributes->patchEntity($attribute, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->Attributes->save($attribute, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'scope' => $attribute->scope,
						'page' => $this->Paginate->calculateEntityPagePosition($attribute),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $attribute->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($attribute->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
			$errors = $attribute->getError('fieldset');

			$attribute->fieldset = reset($availableFieldsets);

			if ($errors) {
				$attribute->setError('fieldset', $errors);
			}

			$request = $this->getRequest();
			//When fieldset is part of the request data, overwrite it since it might be outdated
			if ($request->getData('fieldset') !== null) {
				$request = $request->withData('fieldset', $attribute->fieldset);
				$this->setRequest($request);
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
		 * Build an array of the order data.
		 * In the first level, the key is the fieldset, the value is an array of the child ids.
		 * In the second level, the value is the child id, the key is the order, offset by -1.
		 */
		$orderData = [];
		foreach ($requestData as $fieldset => $children) {
			foreach ($children as $order => $id) {
				$orderData[] = [
					'id' => $id,
					'fieldset' => $fieldset,
					'systemOrder' => $order + 1,
				];
			}
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$affectedRows = $table->updateAll(function (QueryExpression $expression) use ($orderData) {
			$fieldsetCase = $expression->case();
			$systemOrderCase = $expression->case();

			foreach ($orderData as $data) {
				$fieldsetCase->when(['id' => $data['id']])->then($data['fieldset'], 'string');
				$systemOrderCase->when(['id' => $data['id']])->then($data['systemOrder'], 'integer');
			}

			return [
				'fieldset' => $fieldsetCase,
				'systemOrder' => $systemOrderCase,
			];
		}, [
			'id IN' => array_column($orderData, 'id'),
		]);


		return $affectedRows;
	}


	/**
	 * @param \Awyiss\Model\Entity\Attribute $attribute
	 * @return void
	 */
	protected function setViewVars(Attribute $attribute): void {
		$availableFieldsets = $this->Attributes->getAvailableFieldsets($attribute->scope);
		$this->ensurePossibleFieldset($attribute, $availableFieldsets);

		if (in_array($attribute->scope, ['Contents', 'GlobalContents'])) {
			$attribute->fieldset = null;
		}

		$pageRoles = array_keys(array_filter($this->attributeScopes, function ($table) {
			return !is_string($table);
		}));

		$isInputList = in_array($attribute->inputType, ['inputList', 'inputKeyValueList']);
		$translatableDisabled = in_array($attribute->scope, array_merge($pageRoles, ['Contents', 'MenuEntries', 'Pages'])) || $isInputList;
		$requiredDisabled = in_array($attribute->scope, ['Contents', 'GlobalContents']) || $isInputList;
		$columnSpanDisabled = in_array($attribute->scope, ['Contents', 'GlobalContents']);

		if (!$translatableDisabled) {
			$table = $this->fetchTable(Inflector::camelize($attribute->scope));
			// If table is a generic data one and the records are not translatable, disable the translatable option
			if ($table instanceof GenericDatatablesTable && !$table->hasBehavior('Translate')) {
				$translatableDisabled = true;
			}
		}

		$columnSpans = $this->Attributes->getColumnSpans();
		$columnSpans = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnSpans);

		$this->set([
			'attribute' => $attribute,
			'availableFieldsets' => $availableFieldsets,
			'availableInputTypes' => $this->Attributes->getAvailableInputTypes(),
			'pageRoles' => $pageRoles,
			'translatableDisabled' => $translatableDisabled,
			'requiredDisabled' => $requiredDisabled,
			'columnSpans' => $columnSpans,
			'columnSpanDisabled' => $columnSpanDisabled,
		]);
	}
}
