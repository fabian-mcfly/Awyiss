<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Table\AttributesTable;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Awyiss\Routing\Router;


//use Cake\Datasource\ConnectionManager;


/**
 * Attributes Controller
 *
 * @property AttributesTable $Attributes
 */
class AttributesController extends Controller {
	/**
	 * @var array
	 */
	protected array $attributeScopes;
	/**
	 * @inheritDoc
	 */
	protected array $categorize = [
		'allowAggregation' => FALSE,
		'enabled' => TRUE,
		'identifier' => 'scope',
		'useDatasource' => FALSE,
	];


	/**
	 * Called after the `__construct()` method
	 *
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function initialize (): void {
		$this->attributeScopes = $this->Attributes->getAvailableScopes();

		$la_attributeScopes = $this->attributeScopes;
		array_walk($la_attributeScopes, function (&$as_className, $as_identifier) {
			$as_className = __d($as_identifier, 'title_menu');
		});

		$this->categorize['categories'] = $la_attributeScopes;

		parent::initialize();
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview (): void {
		$this->Authorization->ensure('read');

		$lo_attributes = $this->Attributes->find()->where($this->getOverviewWhere());

		$la_attributesGroupedByFieldset = $lo_attributes->all()->groupBy('fieldset')->toArray();

		$la_availableFieldsets = [''];
		if ($this->getOverviewWhere()['scope'] !== 'contents') {
			$la_availableFieldsets = $this->Attributes->getAvailableFieldsets($this->getOverviewWhere()['scope']);
		}

		$this->set([
			'aa_attributesGroupedByFieldset' => $la_attributesGroupedByFieldset,
			'aa_availableFieldsets' => $la_availableFieldsets,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function add (): void {
		$this->Authorization->ensure('create');

		$lo_attribute = $this->Attributes->newDefaultEntity([
			'scope' => $this->Categories->getSelectedCategory(),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_attribute);
		}

		$la_availableFieldsets = $this->Attributes->getAvailableFieldsets($lo_attribute->scope);
		$this->ensurePossibleFieldset($lo_attribute, $la_availableFieldsets);

		$la_pageRoles = array_keys(array_filter($this->attributeScopes, function($ax_table) {
			return !is_string($ax_table);
		}));

		$this->set([
			'ao_attribute' => $lo_attribute,
			'aa_availableFieldsets' => $la_availableFieldsets,
			'aa_availableInputTypes' => $this->Attributes->getAvailableInputTypes(),
			'aa_pageRoles' => $la_pageRoles,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?Response
	 *
	 * @throws \Exception
	 */
	public function edit () {
		$this->Authorization->ensure('update');

		/** @var Attribute $lo_attribute */
		$lo_attribute = $this->Attributes->findById((int) $this->request->getParam('id'))->find('translations')->first();
		if ( ! $lo_attribute) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_attribute, 'edit');
		}

		$la_availableFieldsets = $this->Attributes->getAvailableFieldsets($lo_attribute->scope);
		$this->ensurePossibleFieldset($lo_attribute, $la_availableFieldsets);

		$la_pageRoles = array_keys(array_filter($this->attributeScopes, function($ax_table) {
			return ! is_string($ax_table);
		}));

		$this->set([
			'ao_attribute' => $lo_attribute,
			'aa_availableFieldsets' => $la_availableFieldsets,
			'aa_availableInputTypes' => $this->Attributes->getAvailableInputTypes(),
			'aa_pageRoles' => $la_pageRoles,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Attribute $lo_attribute */
		$lo_attribute = $this->Attributes->findById((int) $this->request->getParam('id'))->find('translations')->first();
		if ( ! $lo_attribute) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Attributes->delete($lo_attribute)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Attribute $ao_attribute
	 * @param string $as_method
	 *
	 * @return void
	 */
	protected function save (Attribute $ao_attribute, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Attributes->hasAttributes()) {
			$la_associated[] = $this->Attributes->getAttributesTable(TRUE);
			$ao_attribute->setAccess('attributes', TRUE);
		}

		$this->Attributes->patchEntity($ao_attribute, $this->request->getData(), ['associated' => $la_associated]);

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Attributes->save($ao_attribute)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'scope' => $ao_attribute->scope], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_attribute->id], TRUE), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_attribute->getError('_general') AS $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * @param Attribute $ao_attribute
	 * @param array     $aa_availableFieldsets
	 *
	 * @return void
	 */
	protected function ensurePossibleFieldset (Attribute $ao_attribute, array $aa_availableFieldsets): void {
		if (empty($ao_attribute->fieldset) || !in_array($ao_attribute->fieldset, $aa_availableFieldsets)) {
			$la_errors = $ao_attribute->getError('fieldset');

			$ao_attribute->fieldset = reset($aa_availableFieldsets);

			if ($la_errors) {
				$ao_attribute->setError('fieldset', $la_errors);
			}

			$lo_request = $this->getRequest();
			//When fieldset is part of the request data, overwrite it since it might be outdated
			if ($lo_request->getData('fieldset') !== NULL) {
				$lo_request = $lo_request->withData('fieldset', $ao_attribute->fieldset);
				$this->setRequest($lo_request);
			}
		}
	}
}

