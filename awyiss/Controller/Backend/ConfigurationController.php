<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Cake\Utility\Inflector;


/**
 * Configuration Controller
 *
 * @property \Awyiss\Model\Table\ConfigurationTable $Configuration
 *
 * TODO: check access based on chosen scope.
 */
class ConfigurationController extends Controller {
	protected array $configScopes = [];
	protected array $overviewWhere = [
		'scope' => 'system',
	];


	public function initialize (): void {
		parent::initialize();

		$this->configScopes = \Awyiss\ConfigOptions\ConfigOptionsProvider::getConfigurationFiles();

		$lo_request = $this->getRequest();

		if ($lo_request->getParam('action') === 'overview') {
			$lo_session = $lo_request->getSession();
			$ls_sessionIdentifier = 'categories.' . ($lo_request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.scope';

			if ($ls_scope = $lo_request->getParam('scope')) {
				if ($lo_session->started()) {
					$lo_session->write($ls_sessionIdentifier, $ls_scope);
				}
			}
			elseif ( ! $lo_session->started() || ! ($ls_scope = $lo_session->read($ls_sessionIdentifier))) {
				$ls_scope = 'system';

				if ($lo_session->started()) {
					$lo_session->write($ls_sessionIdentifier, $ls_scope);
				}
			}


			if (! array_key_exists($ls_scope, $this->configScopes)) {
				$ls_scope = array_key_first($this->configScopes);

				if ($lo_session->started()) {
					$lo_session->write($ls_sessionIdentifier, $ls_scope);
				}
			}

			$this->overviewWhere['scope'] = $ls_scope;

		}
	}


	/**
	 * Overview method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensureOne('create', 'update', 'delete');

		$lo_configuration = $this->Configuration->find('withAttributes')->where($this->overviewWhere)
		->order([
			'name' => 'ASC',
			'languages_shortcode' => 'ASC',
		])->all();

		$la_configuration = \Cake\Utility\Hash::expand($lo_configuration->groupBy('name')->toArray());

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configuration' => $la_configuration,
			'aa_configScopes' => $this->configScopes,
			'as_selectedScope' => $this->overviewWhere['scope']
		]);
	}


	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_configuration = $this->Configuration->newDefaultEntity();
		if ($this->request->is('post')) {
			$lo_configuration = $this->Configuration->patchEntity($lo_configuration, $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Configuration->save($lo_configuration)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_configuration->id]);
				}

				$this->Flash->error(__('::add_failed'));
			}
		}

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configScopes' => $this->configScopes,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		$li_id = $this->request->getParam('id');
		$lo_configuration = $this->Configuration->find()->where(['id' => $li_id])->first();

		if ( ! $lo_configuration) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$lo_configuration = $this->Configuration->patchEntity($lo_configuration, $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Configuration->save($lo_configuration)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_configuration->id]);
				}

				$this->Flash->error(__('::edit_failed'));
			}
		}

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configScopes' => $this->configScopes,
		]);
	}


	/**
	 * Delete method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');
		$lo_configuration = $this->Configuration->find()->where(['id' => $li_id])->first();

		if ( ! $lo_configuration) {
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
