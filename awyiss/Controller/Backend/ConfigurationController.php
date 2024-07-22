<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
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
		$this->selectedRealmSessionIdentifier = 'categories.' . Inflector::underscore($this->getName()) . '.realm';

		parent::initialize();

		if (!$this->Categories->getSelectedCategory()) {
			$this->Categories->setConfig('selectedCategory', 'system');
		}
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->Configuration->find()->where($this->getOverviewWhere())->orderBy([
			'identifier' => 'ASC',
			'language_shortcode' => 'ASC',
		]);
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);

		return $lo_query;
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

		$lo_configOptions = ConfigOptionsProvider::loadConfigOptions($ls_selectedScope);
		$la_configOptions = $lo_configOptions->getConfigOptions();

		$lo_query = $this->getOverviewQuery();

		$la_configuration = $lo_query->all()->groupBy('realm')->map(function ($data) use ($lo_configOptions) {
			return Hash::expand(collection($data)->groupBy(function (Configuration $entity) use ($lo_configOptions) {
				$la_identifier = array_map(function (string $identifier) {
					return ConfigOptionsProvider::sanitizeIdentifier($identifier);
				}, explode('.', $entity->identifier));

				$entity->configOption = $lo_configOptions->getConfigOption($entity->realm, implode('.', $la_identifier));

				return implode('.', $la_identifier);
			})->toArray());
		})->toArray();

		/**
		 * @var string $ls_realm
		 * @var \Awyiss\Configuration\ConfigOptionCollection $lo_configOptions
		 */
		foreach ($la_configOptions as $ls_realm => $lo_configOptions) {
			$la_configOptions[ $ls_realm ] = Hash::merge([], $lo_configOptions->toArray(), $la_configuration[ $ls_realm ] ?? []);
		}

		$this->set([
			'configuration' => $la_configuration,
			'mergedConfiguration' => $la_configOptions,
			'realms' => Awyiss::getRealms(),
			'selectedScope' => $ls_selectedScope,
			'attributes' => $this->Configuration->getAttributes(),
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

		$lo_configuration->configOption = $lo_configuration->identifier ? $lo_configOptions->getConfigOption($lo_configuration->realm, $lo_configuration->identifier) : null;

		$this->set([
			'configuration' => $lo_configuration,
			'configOptions' => $la_configOptions,
			'realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	public function edit(int $id) {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('update');

		/** @var Configuration $lo_configuration */
		$lo_configuration = $this->Configuration->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
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

		$lo_configuration->configOption = $lo_configuration->identifier ? $lo_configOptions->getConfigOption($lo_configuration->realm, $lo_configuration->identifier) : null;

		$this->set([
			'configuration' => $lo_configuration,
			'configOptions' => $la_configOptions,
			'realms' => Awyiss::getRealms(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Configuration $lo_configuration */
		$lo_configuration = $this->Configuration->findById($id)->first();
		if (!$lo_configuration) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Configuration->delete($lo_configuration)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
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
	 * @param Configuration $configuration
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Configuration $configuration, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Configuration->hasAttributes()) {
			$la_associated[] = $this->Configuration->getAttributesTableName(true);
			$configuration->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		if (is_array($la_data['value'] ?? null)) {
			$la_data['value'] = json_encode(array_values($la_data['value']));
		}

		$this->Configuration->patchEntity($configuration, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->Authorization->withAdditionalData(['scope' => $configuration->scope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		$lo_session = $this->request->getSession();
		$lo_session->write($this->selectedRealmSessionIdentifier, $configuration->realm);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Configuration->save($configuration, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'scope' => $configuration->scope], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $configuration->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($configuration->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		$this->Categories->ensurePossibleCategory($configuration);
	}
}
