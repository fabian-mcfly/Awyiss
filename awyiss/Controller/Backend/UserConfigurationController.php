<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\UserConfiguration;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;


/**
 * UserConfiguration Controller
 *
 * @property \Awyiss\Model\Table\UserConfigurationTable $UserConfiguration
 */
class UserConfigurationController extends Controller {
	use IdentityAwareTrait;


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
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->UserConfiguration->find()->where($this->getOverviewWhere())->orderBy([
			'identifier' => 'ASC',
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

		$la_configuration = Hash::expand(
			$lo_query->all()->groupBy(function (UserConfiguration $ao_entity) use ($lo_configOptions) {
				$la_identifier = array_map(function (string $as_identifier) {
					return ConfigOptionsProvider::sanitizeIdentifier($as_identifier);
				}, explode('.', $ao_entity->identifier));

				$ao_entity->configOption = $lo_configOptions->getConfigOption('Backend', implode('.', $la_identifier));

				return implode('.', $la_identifier);
			})->toArray()
		);

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
			'configuration' => $la_configuration,
			'mergedConfiguration' => $la_configOptions,
			'selectedScope' => $ls_selectedScope,
			'attributes' => $this->UserConfiguration->getAttributes(),
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

		$lo_configuration->configOption = $lo_configuration->identifier ? $lo_configOptions->getConfigOption('Backend', $lo_configuration->identifier) : null;

		$this->set([
			'configuration' => $lo_configuration,
			'configOptions' => $la_configOptions,
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
		$la_configOptions = $lo_configOptions->getConfigOptions(Awyiss::REALM_BACKEND);

		$la_configOptions = Hash::filter($la_configOptions->toArray(), function (array|ConfigOption $ax_configOptions) {
			if (is_array($ax_configOptions)) {
				return !empty($ax_configOptions);
			}


			return $ax_configOptions->isPersonalizable();
		});

		$lo_configuration->configOption = $lo_configuration->identifier ? $lo_configOptions->getConfigOption('Backend', $lo_configuration->identifier) : null;

		$this->set([
			'configuration' => $lo_configuration,
			'configOptions' => $la_configOptions,
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


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->UserConfiguration->delete($lo_configuration)) {
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

		$this->UserConfiguration->patchEntity($ao_configuration, $this->request->getData() + ['userId' => $this->getIdentity()->getIdentifier()], [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->Authorization->withAdditionalData(['scope' => $ao_configuration->scope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->UserConfiguration->save($ao_configuration, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($as_method . '_succeeded'));
				}

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


	/**
	 * @inheritDoc
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere['user_id'] = $this->getIdentity()->getIdentifier();
	}
}
