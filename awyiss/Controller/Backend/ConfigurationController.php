<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Table\ConfigurationTable;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Awyiss\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;


/**
 * TODO: modify inputs in add/edit, based on option type
 *
 * Configuration Controller
 *
 * @property ConfigurationTable $Configuration
 */
class ConfigurationController extends Controller {
	/**
	 * @var array
	 */
	protected array $configScopes = [];
	/**
	 * @var string
	 */
	protected string $selectedScopeSessionIdentifier = '';
	/**
	 * @var string
	 */
	protected string $selectedRealmSessionIdentifier = '';


	/**
	 * Called after the `__construct()` method
	 *
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function initialize (): void {
		parent::initialize();

		foreach ($this->Configuration->getScopes() AS $ls_scope => $ls_configOptionsClass) {
			$this->configScopes[ Inflector::underscore($ls_scope) ] = $ls_configOptionsClass;
		}

		//Remember an identifier that will be used to save the selected scope in the session
		$this->selectedScopeSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.scope';
		$this->selectedRealmSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.realm';

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
				//Default to scope 'system'
				$ls_scope = 'system';

				if ($lo_session->started()) {
					$lo_session->write($this->selectedScopeSessionIdentifier, $ls_scope);
				}
			}

			//If the selected scope is not inside the available configuration scopes, reset it to the first available one.
			if (! array_key_exists($ls_scope, $this->configScopes)) {
				$ls_scope = array_key_first($this->configScopes);

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
	 * @return void|?Response
	 *
	 * @throws \Exception
	 */
	public function overview () {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('read');

		if (! $this->Authorization->withAdditionalData([
			'scope' => $this->getOverviewWhere('scope'),
		])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			return $this->redirect(['action' => 'overview', 'scope' => 'system']);
		}

		$lo_configuration = $this->Configuration->find()->where($this->getOverviewWhere())
		->orderBy([
			'identifier' => 'ASC',
			'language_shortcode' => 'ASC',
		]);

		$la_configuration = $lo_configuration->all()->groupBy('realm')->map(function($aa_data) {
			return Hash::expand(collection($aa_data)->groupBy('identifier')->toArray());
		})->toArray();

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configuration' => $la_configuration,
			'aa_configScopes' => $this->configScopes,
			'aa_realms' => Awyiss::getRealms(),
			'as_selectedScope' => $this->getOverviewWhere('scope'),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 *
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function add (): void {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('create');

		$lo_configuration = $this->Configuration->newDefaultEntity([
			'scope' => $this->request->getSession()->read($this->selectedScopeSessionIdentifier),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_configuration);
		}
		else {
			$lo_session = $this->request->getSession();
			//$lo_configuration->scope = $lo_session->read($this->selectedScopeSessionIdentifier);
			if ($lo_session->read($this->selectedRealmSessionIdentifier)) {
				$lo_configuration->realm = $lo_session->read($this->selectedRealmSessionIdentifier);
			}
		}

		$lo_configOptions = ConfigOptionsProvider::loadConfigOptions($lo_configuration->scope);
		$la_configOptions = $lo_configOptions->getConfigOptions($lo_configuration->realm);

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configScopes' => $this->configScopes,
			'aa_configOptions' => $la_configOptions,
			'aa_realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?Response
	 *
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function edit () {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('update');

			/** @var Configuration $lo_configuration */
		$lo_configuration = $this->Configuration->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_configuration) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_configuration, 'edit');
		}
		else {
			if (! $this->Authorization->withAdditionalData([
				'scope' => $lo_configuration->scope,
			])->isAccessible('read')) {
				$this->Flash->error(__('scope_not_accessible'));

				return $this->redirect(['action' => 'overview']);
			}
		}

		$lo_configOptions = ConfigOptionsProvider::loadConfigOptions($lo_configuration->scope);
		$la_configOptions = $lo_configOptions->getConfigOptions($lo_configuration->realm);

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configScopes' => $this->configScopes,
			'aa_configOptions' => $la_configOptions,
			'aa_realms' => Awyiss::getRealms(),
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
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

			/** @var Configuration $lo_configuration */
		$lo_configuration = $this->Configuration->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_configuration) {
			$this->Flash->error(__('record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Configuration->delete($lo_configuration)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Configuration $ao_configuration
	 * @param string $as_method
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function save (Configuration $ao_configuration, string $as_method = 'add'): void {
		if ($this->Configuration->hasAttributes()) {
			$ao_configuration->setAccess('attributes', TRUE);
		}

		$this->Configuration->patchEntity($ao_configuration, $this->request->getData());

		if (! $this->Authorization->withAdditionalData([
			'scope' => $ao_configuration->scope,
		])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
		}

		$lo_session = $this->request->getSession();
		$lo_session->write($this->selectedScopeSessionIdentifier, $ao_configuration->scope);
		$lo_session->write($this->selectedRealmSessionIdentifier, $ao_configuration->realm);

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Configuration->save($ao_configuration)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'scope' => $ao_configuration->scope], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_configuration->id], TRUE), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_configuration->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeOverviewWhere (): void {
		$this->overviewWhere = [
			'scope' => 'system',
		];
	}
}
