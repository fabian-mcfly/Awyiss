<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Attribute;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Cake\Utility\Inflector;


//use Cake\Datasource\ConnectionManager;


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
	 * @var string
	 */
	protected string $selectedScopeSessionIdentifier = '';


	/**
	 * Called after the `__construct()` method
	 *
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function initialize (): void {
		parent::initialize();

		$this->attributeScopes = $this->Attributes->getScopes();

		//Remember an identifier that will be used to save the selected scope in the session
		$this->selectedScopeSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.scope';

		if ($this->request->getParam('action') === 'overview') {
			$lo_session = $this->request->getSession();

			//Is there a request parameter with the name 'scope'?
			if ($ls_scope = $this->request->getParam('scope')) {
				if ($lo_session->started()) {
					//Session started? Save the scope that's inside the url parameter in the session
					$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);
				}
			}
			//Session not started OR there's no scope saved in the session
			elseif ( ! $lo_session->started() || ! ($ls_scope = $lo_session->read($this->selectedScopeSessionIdentifier))) {
				//Default to scope to the first available item
				$ls_scope = array_key_first($this->attributeScopes);

				if ($lo_session->started()) {
					$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);
				}
			}

			//If the selected scope is not inside the available attribute scopes, reset it to the first available one.
			if (! array_key_exists($ls_scope, $this->attributeScopes)) {
				$ls_scope = array_key_first($this->attributeScopes);

				if ($lo_session->started()) {
					$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);
				}

				//Redirect to remove the invalid scope parameter from the URL
				$this->redirect(['action' => 'overview']);
			}

			$this->setOverviewWhere('scope', $ls_scope);
		}
	}

	
	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview (): void {
		$this->Authorization->ensure('read');

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
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function add (): void {
		$this->Authorization->ensure('create');

		$lo_attribute = $this->Attributes->newDefaultEntity();
		if ($this->request->is('post')) {
			$this->save($lo_attribute);
		}

		$this->set([
			'ao_attribute' => $lo_attribute,
			'aa_attributeScopes' => $this->attributeScopes,
		]);
	}
	

	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function edit () {
		$this->Authorization->ensure('update');

		/** @var Attribute $lo_attribute */
		$lo_attribute = $this->Attributes->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_attribute) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_attribute, 'edit');
		}

		$this->set([
			'ao_attribute' => $lo_attribute,
			'aa_attributeScopes' => $this->attributeScopes,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return \Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Attribute $lo_attribute */
		$lo_attribute = $this->Attributes->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_attribute) {
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


	/**
	 * @param Attribute $ao_attribute
	 * @param string $as_method
	 *
	 * @return void
	 */
	protected function save (Attribute $ao_attribute, string $as_method = 'add'): void {
		$this->Attributes->patchEntity($ao_attribute, $this->request->getData());

		$lo_session = $this->request->getSession();
		$lo_session->write($this->selectedScopeSessionIdentifier, $ao_attribute->scope);

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Attributes->save($ao_attribute)) {
				$this->Flash->success(__('::' . $as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_attribute->id], TRUE), 302);
			}

			$this->Flash->error(__('::' . $as_method . '_failed'));
			$this->Flash->error(implode('<br>' . PHP_EOL, $ao_attribute->getError('_general')));
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeOverviewWhere (): void {
		$this->overviewWhere = [
			'scope' => array_key_first($this->attributeScopes),
		];
	}
}

