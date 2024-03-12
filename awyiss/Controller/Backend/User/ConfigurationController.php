<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend\User;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\UserConfiguration;
use Awyiss\Routing\Router;
use Cake\Event\EventManagerInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Utility\Hash;


/**
 * UserConfiguration Controller
 *
 * @property \Awyiss\Model\Table\UserConfigurationTable $UserConfiguration
 */
class ConfigurationController extends Controller {
	use IdentityAwareTrait;


	/**
	 * @var string Name of the route to be used in redirects and url builder
	 */
	protected string $routeName;


	/**
	 * @inheritDoc
	 * @param \Cake\Http\ServerRequest $ao_request
	 * @param string|null $as_name
	 * @param \Cake\Event\EventManagerInterface|null $ao_eventManager
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function __construct(ServerRequest $ao_request, ?string $as_name = null, ?EventManagerInterface $ao_eventManager = null) {
		parent::__construct($ao_request, 'UserConfiguration', $ao_eventManager);

		$this->routeName = Awyiss::REALM_BACKEND . '::user_configuration';
	}


	/**
	 * Called after the `__construct()` method
	 *
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function initialize(): void {
		parent::initialize();

		if (!$this->Categories->getSelectedCategory()) {
			$this->Categories->setConfig('selectedCategory', 'system');
		}

		$this->viewBuilder()->setTemplatePath('Backend/User/Configuration');
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


			return $this->redirect(['action' => 'overview', 'scope' => 'system', '_name' => $this->routeName]);
		}

		$lo_configuration = $this->UserConfiguration->find()->where($this->getOverviewWhere())->orderBy([
			'identifier' => 'ASC',
		]);

		$this->Categories->filterQuery($lo_configuration, null, !$this->paginate['enabled']);

		$la_configuration = Hash::expand($lo_configuration->all()->groupBy(function (UserConfiguration $ao_entity) {
			$la_identifier = array_map(function (string $as_identifier) {
				return ConfigOptionsProvider::sanitizeIdentifier($as_identifier);
			}, explode('.', $ao_entity->identifier));


			return implode('.', $la_identifier);
		})->toArray());

		$lo_configOptions = ConfigOptionsProvider::loadConfigOptions($ls_selectedScope);
		$la_configOptions = $lo_configOptions->getConfigOptions();

		/**
		 * @var string $ls_realm
		 * @var \Awyiss\Configuration\ConfigOptionCollection $lo_configOptions
		 */
		foreach ($la_configOptions as $ls_realm => $lo_configOptions) {
			$la_configOptions[ $ls_realm ] = Hash::merge([], $lo_configOptions->toArray(), $la_configuration);
		}
		unset($la_configOptions[ Awyiss::REALM_FRONTEND ]);

		$la_configOptions[ Awyiss::REALM_BACKEND ] = Hash::filter($la_configOptions[ Awyiss::REALM_BACKEND ], function (array|ConfigOption|UserConfiguration $ax_configOptions) {
			if (is_array($ax_configOptions)) {
				return !empty($ax_configOptions);
			}

			if ($ax_configOptions instanceof UserConfiguration) {
				return true;
			}

			return $ax_configOptions->isPersonalizable();
		});

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

		$lo_configuration = $this->UserConfiguration->newDefaultEntity([
			'scope' => $this->Categories->getSelectedCategory(),
		]);

		if ($this->request->is('post')) {
			$this->save($lo_configuration);
		}

		$lo_configOptions = ConfigOptionsProvider::loadConfigOptions($lo_configuration->scope);
		$la_configOptions = $lo_configOptions->getConfigOptions(Awyiss::REALM_BACKEND);

		$la_configOptions = Hash::filter($la_configOptions->toArray(), function (array|ConfigOption $ax_configOptions) {
			if (is_array($ax_configOptions)) {
				return !empty($ax_configOptions);
			}


			return $ax_configOptions->isPersonalizable();
		});

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configOptions' => $la_configOptions,
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

		/** @var \Awyiss\Model\Entity\UserConfiguration $lo_configuration */
		$lo_configuration = $this->UserConfiguration->findById($ai_id)->find('translations')->where(['user_id' => $this->getIdentity()->getIdentifier()])->first();
		if (!$lo_configuration) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview', '_name' => $this->routeName]);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_configuration, 'edit');
		}
		else {
			if (!$this->Authorization->withAdditionalData(['scope' => $lo_configuration->scope])->isAccessible('read')) {
				$this->Flash->error(__('scope_not_accessible'));


				return $this->redirect(['action' => 'overview', '_name' => $this->routeName]);
			}
		}

		$lo_configOptions = ConfigOptionsProvider::loadConfigOptions($lo_configuration->scope);
		$la_configOptions = $lo_configOptions->getConfigOptions(Awyiss::REALM_BACKEND);

		$la_configOptions = Hash::filter($la_configOptions->toArray(), function (array|ConfigOption $ax_configOptions) {
			if (is_array($ax_configOptions)) {
				return !empty($ax_configOptions);
			}


			return $ax_configOptions->isPersonalizable();
		});

		$this->set([
			'ao_configuration' => $lo_configuration,
			'aa_configOptions' => $la_configOptions,
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

		/** @var \Awyiss\Model\Entity\UserConfiguration $lo_configuration */
		$lo_configuration = $this->UserConfiguration->findById($ai_id)->find('translations')->first();
		if (!$lo_configuration) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview', '_name' => $this->routeName]);
		}

		if ($this->UserConfiguration->delete($lo_configuration)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_configuration->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview', '_name' => $this->routeName]);
	}


	/**
	 * @param \Awyiss\Model\Entity\UserConfiguration $ao_configuration
	 * @param string $as_method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(UserConfiguration $ao_configuration, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->UserConfiguration->hasAttributes()) {
			$la_associated[] = $this->UserConfiguration->getAttributesTableName(true);
			$ao_configuration->setAccess('attributes', true);
		}

		$this->UserConfiguration->patchEntity($ao_configuration, $this->request->getData() + ['userId' => $this->getIdentity()->getIdentifier()], ['associated' => $la_associated]);

		if (!$this->Authorization->withAdditionalData(['scope' => $ao_configuration->scope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			throw new RedirectException(Router::url(['action' => 'overview', '_name' => $this->routeName], true), 302);
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->UserConfiguration->save($ao_configuration, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'scope' => $ao_configuration->scope, '_name' => $this->routeName], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_configuration->id, '_name' => $this->routeName], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_configuration->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		$this->Categories->ensurePossibleCategory($ao_configuration);
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere['user_id'] = $this->getIdentity()->getIdentifier();
	}
}
