<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;


/**
 * Configuration Controller
 *
 * @property \Awyiss\Model\Table\ConfigurationTable $Configuration
 */
class ConfigurationController extends Controller {
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
	public function initialize(): void {
		$this->selectedRealmSessionIdentifier = 'categories.' . ($this->request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($this->getName()) . '.realm';

		parent::initialize();

		if (!$this->Categories->getSelectedCategory()) {
			$this->Categories->setConfig('selectedCategory', 'system');
		}
	}


	/**
	 * Overview method
	 *
	 * @return void|?Response
	 * @throws \Exception
	 */
	public function overview() {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('read');

		$ls_selectedScope = $this->Categories->getSelectedCategory();

		if (!$this->Authorization->withAdditionalData(['scope' => $ls_selectedScope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));


			return $this->redirect(['action' => 'overview', 'scope' => 'system']);
		}

		$lo_configuration = $this->Configuration->find()->where($this->getOverviewWhere())->orderBy([
			'identifier' => 'ASC',
			'language_shortcode' => 'ASC',
		]);
		$this->Categories->filterQuery($lo_configuration);

		$la_configuration = $lo_configuration->all()->groupBy('realm')->map(function ($aa_data) {
			return Hash::expand(collection($aa_data)->groupBy(function (Configuration $ao_entity) {
				$la_identifier = array_map(function (string $as_identifier) {
					return ConfigOptionsProvider::sanitizeIdentifier($as_identifier);
				}, explode('.', $ao_entity->identifier));


				return implode('.', $la_identifier);
			})->toArray());
		})->toArray();

		$lo_configOptions = ConfigOptionsProvider::loadConfigOptions($ls_selectedScope);
		$la_configOptions = $lo_configOptions->getConfigOptions();

		/**
		 * @var string $ls_realm
		 * @var \Awyiss\Configuration\ConfigOptionCollection $lo_configOptions
		 */
		foreach ($la_configOptions as $ls_realm => $lo_configOptions) {
			$la_configOptions[ $ls_realm ] = Hash::merge([], $lo_configOptions->toArray(), $la_configuration[ $ls_realm ] ?? []);
		}

		$this->set([
			'aa_configuration' => $la_configuration,
			'aa_mergedConfiguration' => $la_configOptions,
			'aa_realms' => Awyiss::getRealms(),
			'as_selectedScope' => $ls_selectedScope,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('create');

		$lo_configuration = $this->Configuration->newDefaultEntity([
			'scope' => $this->Categories->getSelectedCategory(),
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
			'aa_configOptions' => $la_configOptions,
			'aa_realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('update');

		/** @var Configuration $lo_configuration */
		$lo_configuration = $this->Configuration->findById($ai_id)->find('translations')->first();
		if (!$lo_configuration) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_configuration, 'edit');
		}
		else {
			if (!$this->Authorization->withAdditionalData(['scope' => $lo_configuration->scope])->isAccessible('read')) {
				$this->Flash->error(__('scope_not_accessible'));


				return $this->redirect(['action' => 'overview']);
			}
		}

		$lo_configOptions = ConfigOptionsProvider::loadConfigOptions($lo_configuration->scope);
		$la_configOptions = $lo_configOptions->getConfigOptions($lo_configuration->realm);

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configOptions' => $la_configOptions,
			'aa_realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Configuration $lo_configuration */
		$lo_configuration = $this->Configuration->findById($ai_id)->find('translations')->first();
		if (!$lo_configuration) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Configuration->delete($lo_configuration)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_configuration->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Configuration $ao_configuration
	 * @param string $as_method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Configuration $ao_configuration, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Configuration->hasAttributes()) {
			$la_associated[] = $this->Configuration->getAttributesTableName(true);
			$ao_configuration->setAccess('attributes', true);
		}

		$this->Configuration->patchEntity($ao_configuration, $this->request->getData(), ['associated' => $la_associated]);

		if (!$this->Authorization->withAdditionalData(['scope' => $ao_configuration->scope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		$lo_session = $this->request->getSession();
		$lo_session->write($this->selectedRealmSessionIdentifier, $ao_configuration->realm);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Configuration->save($ao_configuration)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'scope' => $ao_configuration->scope], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_configuration->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_configuration->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		$this->Categories->ensurePossibleCategory($ao_configuration);
	}
}
