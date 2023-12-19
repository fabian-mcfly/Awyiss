<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Attribute;
//use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Utility\Inflector;


/**
 * Attributes Controller
 *
 * @property \Awyiss\Model\Table\AttributesTable $Attributes
 */
class AttributesController extends Controller {
	protected array $attributeScopes;
	protected string $selectedScopeSessionIdentifier = '';


	public function initialize (): void {
		parent::initialize();

		$this->attributeScopes = $this->Attributes->getScopes();

		$this->selectedScopeSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.scope';

		if ($this->request->getParam('action') === 'overview') {
			$lo_session = $this->request->getSession();

			if ($ls_scope = $this->request->getParam('scope')) {
				$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);
			}
			elseif ( ! $lo_session->started() || ! ($ls_scope = $lo_session->read($this->selectedScopeSessionIdentifier))) {
				$ls_scope = array_key_first($this->attributeScopes);

				$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);
			}

			if (! array_key_exists($ls_scope, $this->attributeScopes)) {
				$ls_scope = array_key_first($this->attributeScopes);

				$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);

				$this->redirect(['action' => 'overview']);
			}

			$this->setOverviewWhere('scope', $ls_scope);
		}
	}


	protected function initializeOverviewWhere () {
		$this->overviewWhere = [
			'scope' => array_key_first($this->attributeScopes),
		];
	}

	
	/**
	 * Overview method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensure('read');

		$lo_attributes = $this->paginate($this->Attributes->find('withAttributes')->where($this->getOverviewWhere()));

		$this->set([
			'ao_attributes' => $lo_attributes,
			'aa_attributeScopes' => $this->attributeScopes,
			'as_selectedScope' => $this->getOverviewWhere('scope'),
		]);
	}
	

	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_attribute = $this->Attributes->newDefaultEntity();
		if ($this->request->is('post')) {
			$this->Attributes->patchEntity($lo_attribute, $this->request->getData());

			$lo_session = $this->request->getSession();
			$lo_session->write($this->selectedScopeSessionIdentifier, $lo_attribute->scope);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Attributes->save($lo_attribute)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_attribute->id]);
				}

				$this->Flash->error(__('::add_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_attribute->getError('_general')));
			}
		}

		$this->set([
			'ao_attribute' => $lo_attribute,
			'aa_attributeScopes' => $this->attributeScopes,
		]);
	}
	

	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		try {
			$li_id = $this->request->getParam('id');
			/** @var Attribute $lo_attribute */
			$lo_attribute = $this->Attributes->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->Attributes->patchEntity($lo_attribute, $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Attributes->save($lo_attribute)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_attribute->id]);
				}

				$this->Flash->error(__('::edit_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_attribute->getError('_general')));
			}
		}

		$this->set([
			'ao_attribute' => $lo_attribute,
			'aa_attributeScopes' => $this->attributeScopes,
		]);
	}
	

	/**
	 * Delete method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		try {
			$li_id = $this->request->getParam('id');
			/** @var Attribute $lo_attribute */
			$lo_attribute = $this->Attributes->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Attributes->delete($lo_attribute)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}

