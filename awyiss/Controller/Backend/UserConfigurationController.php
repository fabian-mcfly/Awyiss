<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionsInterface;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Entity\UserConfiguration;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
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
		$this->Search->filterQuery($lo_query);

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

		$la_globalConfiguration = $this->getGlobalConfiguration($lo_configOptions);

		$lo_query = $this->getOverviewQuery();

		$la_configuration = Hash::expand(
			$lo_query->all()->groupBy(function (UserConfiguration $entity) use ($lo_configOptions) {
				$la_identifier = array_map(function (string $identifier) {
					return ConfigOptionsProvider::sanitizeIdentifier($identifier);
				}, explode('.', $entity->identifier));

				$ls_path = implode('.', $la_identifier);
				$entity->configOption = $lo_configOptions->getConfigOption('Backend', $ls_path);

				return implode('.', $la_identifier);
			})->toArray()
		);

		if ($this->UserConfiguration->searchIsActive()) {
			$la_configOptions[ Awyiss::REALM_BACKEND ] = $la_configuration;
		}
		else {
			/**
			 * @var string $ls_realm
			 * @var \Awyiss\Configuration\ConfigOptionCollection $lo_configOptions
			 */
			foreach ($la_configOptions as $ls_realm => $lo_configOptions) {
				$la_configOptions[ $ls_realm ] = Hash::merge([], $lo_configOptions->toArray(), $la_globalConfiguration, $la_configuration);

				uksort($la_configOptions[ $ls_realm ], function ($a, $b) {
					$ls_titleA = __df('user_configuration', 'configuration', 'category_' . Inflector::underscore($a));
					$ls_titleB = __df('user_configuration', 'configuration', 'category_' . Inflector::underscore($b));

					if (str_contains($ls_titleA, '::')) {
						$ls_titleA = $a;
					}

					if (str_contains($ls_titleB, '::')) {
						$ls_titleB = $b;
					}

					return strcoll(mb_strtolower($ls_titleA), mb_strtolower($ls_titleB));
				});
			}
			unset($la_configOptions[ Awyiss::REALM_FRONTEND ]);

			$la_configOptions[ Awyiss::REALM_BACKEND ] = Hash::filter(
				$la_configOptions[ Awyiss::REALM_BACKEND ],
				function (array|ConfigOption|Configuration|UserConfiguration $configOptions) {
					if (is_array($configOptions)) {
						return !empty($configOptions);
					}

					if ($configOptions instanceof UserConfiguration) {
						return true;
					}

					if ($configOptions instanceof Configuration) {
						$configOptions->isGlobal = true;

						return $configOptions->configOption?->isPersonalizable() ?? false;
					}

					return $configOptions->isPersonalizable();
				}
			);

			// Deeply clean the array and remove Configuration objects if a UserConfiguration object exists in the same array
			$la_configOptions[ Awyiss::REALM_BACKEND ] = $this->cleanConfigurationArray($la_configOptions[ Awyiss::REALM_BACKEND ]);
		}


		$this->set([
			'configuration' => $la_configuration,
			'mergedConfiguration' => $la_configOptions,
			'globalConfiguration' => $la_globalConfiguration,
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

		$la_configOptions = Hash::filter($la_configOptions->toArray(), function (array|ConfigOption $configOptions) {
			if (is_array($configOptions)) {
				return !empty($configOptions);
			}


			return $configOptions->isPersonalizable();
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
	 * @noinspection DuplicatedCode
	 */
	public function edit(int $id) {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('update');

		/** @var \Awyiss\Model\Entity\UserConfiguration $lo_configuration */
		$lo_configuration = $this->UserConfiguration->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->where(['user_id' => $this->getIdentity()->getIdentifier()])->first();
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

		$la_configOptions = Hash::filter($la_configOptions->toArray(), function (array|ConfigOption $configOptions) {
			if (is_array($configOptions)) {
				return !empty($configOptions);
			}


			return $configOptions->isPersonalizable();
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
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\UserConfiguration $lo_configuration */
		$lo_configuration = $this->UserConfiguration->findById($id)->first();
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
	 * @param \Awyiss\Model\Entity\UserConfiguration $configuration
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(UserConfiguration $configuration, string $method = 'add'): void {
		$la_associated = [];
		if ($this->UserConfiguration->hasAttributes()) {
			$la_associated[] = $this->UserConfiguration->getAttributesTableName(true);
			$configuration->setAccess('attributes', true);
		}

		$this->UserConfiguration->patchEntity($configuration, $this->request->getData() + ['userId' => $this->getIdentity()->getIdentifier()], [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->Authorization->withAdditionalData(['scope' => $configuration->scope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->UserConfiguration->save($configuration, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'scope' => $configuration->scope], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $configuration->id], true), 302);
			}

			$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
			foreach ($configuration->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		$this->Categories->ensurePossibleCategory($configuration);
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere['user_id'] = $this->getIdentity()->getIdentifier();
	}


	/**
	 * Get global configuration for the current scope
	 *
	 * @param \Awyiss\Configuration\ConfigOptionsInterface|null $configOptions
	 * @return array
	 */
	protected function getGlobalConfiguration(?ConfigOptionsInterface $configOptions): array {
		$lo_configTable = $this->fetchTable('Configuration');
		$lo_query = $lo_configTable->find()->orderBy([
			'identifier' => 'ASC',
			'language_shortcode' => 'ASC',
		]);

		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);

		$lo_configOptions = $configOptions;
		$la_configuration = $lo_query->all()->groupBy(function (Configuration $entity) use ($lo_configOptions) {
			$la_identifier = array_map(function (string $identifier) use ($lo_configOptions) {
				return ConfigOptionsProvider::sanitizeIdentifier($identifier);
			}, explode('.', $entity->identifier));

			$ls_path = implode('.', $la_identifier);
			$entity->configOption = $lo_configOptions->getConfigOption('Backend', $ls_path);

			return implode('.', $la_identifier);
		})->toArray();

		return Hash::expand($la_configuration);
	}

	/**
	 * Deeply clean the array and remove Configuration objects if a UserConfiguration object exists in the same array
	 *
	 * @param array $configurations
	 * @return array
	 */
	protected function cleanConfigurationArray(array $configurations): array {
		$la_configurations = $configurations;

		foreach ($la_configurations as $ls_key => $lx_configOptions) {
			if (is_array($lx_configOptions)) {
				if (isset($lx_configOptions[0]) && $lx_configOptions[0] instanceof Configuration && count($la_configurations[ $ls_key ]) > 1) {
					unset($la_configurations[ $ls_key ][0]);
					$la_configurations[ $ls_key ] = array_values($la_configurations[ $ls_key ]);
				}
				else {
					$la_configurations[ $ls_key ] = $this->cleanConfigurationArray($lx_configOptions);
				}
			}
		}

		return $la_configurations;
	}
}
