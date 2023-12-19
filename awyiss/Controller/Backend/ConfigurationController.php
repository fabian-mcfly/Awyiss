<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Utility\Inflector;


/**
 * Configuration Controller
 *
 * @property \Awyiss\Model\Table\ConfigurationTable $Configuration
 *
 * TODO: modify inputs in add/edit, based on option type
 */
class ConfigurationController extends Controller {
	protected array $configScopes;
	protected string $selectedScopeSessionIdentifier = '';


	public function initialize (): void {
		parent::initialize();

		$this->configScopes = $this->Configuration->getScopes();

		$this->selectedScopeSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.scope';

		if ($this->request->getParam('action') === 'overview') {
			$lo_session = $this->request->getSession();

			if ($ls_scope = $this->request->getParam('scope')) {
				$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);
			}
			elseif ( ! $lo_session->started() || ! ($ls_scope = $lo_session->read($this->selectedScopeSessionIdentifier))) {
				$ls_scope = 'system';

				$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);
			}

			if (! array_key_exists($ls_scope, $this->configScopes)) {
				$ls_scope = array_key_first($this->configScopes);

				$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);

				$this->redirect(['action' => 'overview']);
			}

			$this->setOverviewWhere('scope', $ls_scope);
		}
	}


	protected function initializeOverviewWhere () {
		$this->overviewWhere = [
			'scope' => 'system',
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

		if ($this->getOverviewWhere('scope') !== 'system') {
			if (!$this->Access->scopeIsAccessible($this->getOverviewWhere('scope'), NULL, 'configure')) {
				$this->Flash->error(__('::scope_not_accessible'));

				return $this->redirect(['action' => 'overview', 'scope' => 'system']);
			}
		}

		$lo_configuration = $this->Configuration->find('withAttributes')->where($this->getOverviewWhere())
		->order([
			'name' => 'ASC',
			'languages_shortcode' => 'ASC',
		]);

		$la_configuration = \Cake\Utility\Hash::expand($lo_configuration->all()->groupBy('name')->toArray());

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configuration' => $la_configuration,
			'aa_configScopes' => $this->configScopes,
			'as_selectedScope' => $this->getOverviewWhere('scope'),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \ReflectionException
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_configuration = $this->Configuration->newDefaultEntity();
		if ($this->request->is('post')) {
			/** @noinspection DuplicatedCode */
			$this->Configuration->patchEntity($lo_configuration, $this->request->getData());

			$lo_session = $this->request->getSession();
			$lo_session->write($this->selectedScopeSessionIdentifier, $lo_configuration->scope);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Configuration->save($lo_configuration)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_configuration->id]);
				}

				$this->Flash->error(__('::add_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_configuration->getError('_general')));
			}
		}
		else {
			$lo_session = $this->request->getSession();
			$lo_configuration->scope = $lo_session->read($this->selectedScopeSessionIdentifier);
		}

		/** @noinspection DuplicatedCode */
		if ($lo_configuration->scope !== 'system') {
			if (!$this->Access->scopeIsAccessible($lo_configuration->scope, NULL, 'configure')) {
				$this->Flash->error(__('::scope_not_accessible'));

				return $this->redirect(['action' => 'overview']);
			}
		}

		$lo_configOptions = \Awyiss\Configuration\ConfigOptionsProvider::loadConfiguration($lo_configuration->scope);

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configScopes' => $this->configScopes,
			'ao_configOptions' => $lo_configOptions,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \ReflectionException
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		try {
			$li_id = $this->request->getParam('id');
			/** @var \Awyiss\Model\Entity\Configuration $lo_configuration */
			$lo_configuration = $this->Configuration->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			/** @noinspection DuplicatedCode */
			$this->Configuration->patchEntity($lo_configuration, $this->request->getData());

			$lo_session = $this->request->getSession();
			$lo_session->write($this->selectedScopeSessionIdentifier, $lo_configuration->scope);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Configuration->save($lo_configuration)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_configuration->id]);
				}

				$this->Flash->error(__('::edit_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_configuration->getError('_general')));
			}
		}

		/** @noinspection DuplicatedCode */
		if ($lo_configuration->scope !== 'system') {
			if (!$this->Access->scopeIsAccessible($lo_configuration->scope, NULL, 'configure')) {
				$this->Flash->error(__('::scope_not_accessible'));

				return $this->redirect(['action' => 'overview']);
			}
		}

		$lo_configOptions = \Awyiss\Configuration\ConfigOptionsProvider::loadConfiguration($lo_configuration->scope);

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configScopes' => $this->configScopes,
			'ao_configOptions' => $lo_configOptions,
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
			/** @var \Awyiss\Model\Entity\Configuration $lo_configuration */
			$lo_configuration = $this->Configuration->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Configuration->delete($lo_configuration)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}
