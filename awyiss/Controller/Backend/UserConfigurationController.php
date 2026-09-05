<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionsInterface;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
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
	 * @inheritDoc
	 */
	protected array $lock = ['autoload' => false];


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		if (!$this->Categories->getSelectedCategory()) {
			$this->Categories->setConfig('selectedCategory', 'System');
		}
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->UserConfiguration
			->find()
			->where($this->getOverviewWhere())
			->orderBy([
				'identifier' => 'ASC',
			])
		;

		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function overview() {
		$this->Authorization->setAdditionalData(['scope' => ''])->ensure('read');

		$selectedScope = $this->Categories->getSelectedCategory();

		if (!$this->Authorization->withAdditionalData(['scope' => $selectedScope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));


			return $this->redirect(['action' => 'overview', 'scope' => 'System']);
		}

		$configOptions = ConfigOptionsProvider::loadConfigOptions($selectedScope);
		$flattenedConfigOptions = $configOptions->getConfigOptions();

		$globalConfiguration = $this->getGlobalConfiguration($configOptions);

		$query = $this->getOverviewQuery();

		$configuration = Hash::expand(
			$query
				->all()
				->groupBy(function (UserConfiguration $entity) use ($configOptions) {
					$identifier = array_map(
						fn(string $identifier) => ConfigOptionsProvider::sanitizeIdentifier($identifier),
						explode('.', $entity->identifier)
					);

					$path = implode('.', $identifier);
					/** @noinspection PhpUndefinedFieldInspection */
					$entity->configOption = $configOptions->getConfigOption('Backend', $path);

					return implode('.', $identifier);
				})
				->toArray()
		);

		if ($this->UserConfiguration->searchIsActive()) {
			$flattenedConfigOptions[ Awyiss::REALM_BACKEND ] = $configuration;
		}
		else {
			/**
			 * @var string $realm
			 * @var \Awyiss\Configuration\ConfigOptionsCollection $configOptions
			 */
			foreach ($flattenedConfigOptions as $realm => $configOptions) {
				$flattenedConfigOptions[ $realm ] = Hash::merge([], $configOptions->toArray(), $globalConfiguration, $configuration);
				$flattenedConfigOptions[ $realm ] = $this->sortConfigOptions($flattenedConfigOptions[ $realm ], $realm, $selectedScope);
			}
			unset($flattenedConfigOptions[ Awyiss::REALM_FRONTEND ]);

			$flattenedConfigOptions[ Awyiss::REALM_BACKEND ] = Hash::filter(
				$flattenedConfigOptions[ Awyiss::REALM_BACKEND ],
				function (array|ConfigOption|Configuration|UserConfiguration $configOptions) {
					if (is_array($configOptions)) {
						return !empty($configOptions);
					}

					if ($configOptions instanceof UserConfiguration) {
						return true;
					}

					if ($configOptions instanceof Configuration) {
						/** @noinspection PhpUndefinedFieldInspection */
						$configOptions->isGlobal = true;

						/** @noinspection PhpUndefinedFieldInspection */
						return $configOptions->configOption?->isPersonalizable() ?? false;
					}

					return $configOptions->isPersonalizable();
				}
			);

			// Deeply clean the array and remove Configuration objects if a UserConfiguration object exists in the same array
			$flattenedConfigOptions[ Awyiss::REALM_BACKEND ] = $this->cleanConfigurationArray(
				$flattenedConfigOptions[ Awyiss::REALM_BACKEND ]
			);
		}


		$this->set([
			'configuration' => $configuration,
			'mergedConfiguration' => $flattenedConfigOptions,
			'globalConfiguration' => $globalConfiguration,
			'selectedScope' => $selectedScope,
			'attributes' => $this->UserConfiguration->getAttributes(),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->setAdditionalData(['scope' => ''])->ensure('create');

		$configuration = $this->UserConfiguration->newDefaultEntity([
			'scope' => Inflector::camelize($this->Categories->getSelectedCategory()),
		]);

		if ($this->request->is('post')) {
			$this->save($configuration);
		}

		$configOptions = ConfigOptionsProvider::loadConfigOptions($configuration->scope);
		$flattenedConfigOptions = $configOptions->getConfigOptions(Awyiss::REALM_BACKEND);

		$flattenedConfigOptions = Hash::filter($flattenedConfigOptions->toArray(), function (array|ConfigOption $configOptions) {
			if (is_array($configOptions)) {
				return !empty($configOptions);
			}


			return $configOptions->isPersonalizable();
		});

		/** @noinspection PhpUndefinedFieldInspection */
		$configuration->configOption = $configuration->identifier
			? $configOptions->getConfigOption('Backend', $configuration->identifier)
			: null;

		$this->set([
			'configuration' => $configuration,
			'configOptions' => $flattenedConfigOptions,
		]);
	}


	/**
	 * Edit method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->setAdditionalData(['scope' => ''])->ensure('update');

		/**
		 * @var \Awyiss\Model\Entity\UserConfiguration $configuration
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$configuration = $this->UserConfiguration
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->where(['userId' => $this->getIdentity()->getIdentifier()])
			->first()
		;
		if (!$configuration) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($configuration, 'edit');
		}
		else {
			if (!$this->Authorization->withAdditionalData(['scope' => $configuration->scope])->isAccessible('read')) {
				$this->Flash->error(__('scope_not_accessible'));


				return $this->redirect(['action' => 'overview']);
			}
		}

		$configOptions = ConfigOptionsProvider::loadConfigOptions($configuration->scope);
		$flattenedConfigOptions = $configOptions->getConfigOptions(Awyiss::REALM_BACKEND);

		$flattenedConfigOptions = Hash::filter($flattenedConfigOptions->toArray(), function (array|ConfigOption $configOptions) {
			if (is_array($configOptions)) {
				return !empty($configOptions);
			}


			return $configOptions->isPersonalizable();
		});

		/** @noinspection PhpUndefinedFieldInspection */
		$configuration->configOption = $configuration->identifier
			? $configOptions->getConfigOption('Backend', $configuration->identifier)
			: null;

		$this->set([
			'configuration' => $configuration,
			'configOptions' => $flattenedConfigOptions,
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
		$this->Authorization->setAdditionalData(['scope' => ''])->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\UserConfiguration $configuration */
		$configuration = $this->UserConfiguration->findById($id)->first();
		if (!$configuration) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->UserConfiguration->delete($configuration)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($configuration->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
		$associated = [];
		if ($this->UserConfiguration->hasAttributes()) {
			$associated[] = $this->UserConfiguration->getAttributesTableName(true);
			$configuration->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();

		if (is_array($requestData['value'] ?? null)) {
			$requestData['value'] = json_encode(array_values($requestData['value']));
		}
		$requestData['userId'] = $this->getIdentity()->getIdentifier();

		$this->UserConfiguration->patchEntity($configuration, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->Authorization->withAdditionalData(['scope' => $configuration->scope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->UserConfiguration->save($configuration, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url(['action' => 'overview', 'scope' => $configuration->scope], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $configuration->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($configuration->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		$this->Categories->ensurePossibleCategory($configuration);
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere['userId'] = $this->getIdentity()->getIdentifier();
	}


	/**
	 * Get global configuration for the current scope
	 *
	 * @param \Awyiss\Configuration\ConfigOptionsInterface|null $configOptions
	 * @return array
	 */
	protected function getGlobalConfiguration(?ConfigOptionsInterface $configOptions): array {
		$configTable = $this->fetchTable('Configuration');
		$query = $configTable
			->find()
			->orderBy([
				'identifier' => 'ASC',
				'languageShortcode' => 'ASC',
			])
		;

		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);

		$configuration = $query
			->all()
			->groupBy(function (Configuration $entity) use ($configOptions) {
				$identifier = array_map(
					fn(string $identifier) => ConfigOptionsProvider::sanitizeIdentifier($identifier),
					explode('.', $entity->identifier)
				);

				$path = implode('.', $identifier);
				/** @noinspection PhpUndefinedFieldInspection */
				$entity->configOption = $configOptions->getConfigOption('Backend', $path);

				return implode('.', $identifier);
			})
			->toArray()
		;

		return Hash::expand($configuration);
	}


	/**
	 * Deeply clean the array and remove Configuration objects if a UserConfiguration object exists in the same array
	 *
	 * @param array $configurations
	 * @return array
	 */
	protected function cleanConfigurationArray(array $configurations): array {
		foreach ($configurations as $key => $configOptions) {
			if (is_array($configOptions)) {
				if (isset($configOptions[0]) && $configOptions[0] instanceof Configuration && count($configOptions) > 1) {
					unset($configurations[ $key ][0]);
					$configurations[ $key ] = array_values($configurations[ $key ]);
				}
				else {
					$configurations[ $key ] = $this->cleanConfigurationArray($configOptions);
				}
			}
		}

		return $configurations;
	}


	/**
	 * @param array $configOptions
	 * @param string $realm
	 * @param string $selectedScope
	 * @param string|null $parentCategories
	 * @return array
	 */
	protected function sortConfigOptions(
		array $configOptions,
		string $realm,
		string $selectedScope,
		?string $parentCategories = null
	): array {
		$realm = Inflector::underscore($realm);

		uksort($configOptions, function ($a, $b) use ($configOptions, $parentCategories, $realm, $selectedScope) {
			$i18nKeyA = 'configuration_' . ($this->isCategory($configOptions[ $a ]) ? 'category' : 'identifier') . '_' . $realm;
			$i18nKeyB = 'configuration_' . ($this->isCategory($configOptions[ $b ]) ? 'category' : 'identifier') . '_' . $realm;

			if ($parentCategories) {
				$i18nKeyA .= '_' . $parentCategories;
				$i18nKeyB .= '_' . $parentCategories;
			}

			$i18nKeyA .= '_' . Inflector::underscore($a);
			$i18nKeyB .= '_' . Inflector::underscore($b);

			$titleA = __df($selectedScope, 'Configuration', $i18nKeyA);
			$titleB = __df($selectedScope, 'Configuration', $i18nKeyB);

			if (str_contains($titleA, '::')) {
				if ($this->isPageRole($selectedScope)) {
					$titleA = __d('GenericPages', $i18nKeyA);
				}
				else {
					$titleA = $a;
				}
			}

			if (str_contains($titleB, '::')) {
				if ($this->isPageRole($selectedScope)) {
					$titleB = __d('GenericPages', $i18nKeyB);
				}
				else {
					$titleB = $b;
				}
			}

			return strcoll(mb_strtolower($titleA), mb_strtolower($titleB));
		});

		foreach ($configOptions as $key => $value) {
			if (is_array($value) && $this->isCategory($value)) {
				$parentCategory = Inflector::underscore($key);
				if ($parentCategories) {
					$parentCategory = $parentCategories . '_' . $parentCategory;
				}

				$configOptions[ $key ] = $this->sortConfigOptions($value, $realm, $selectedScope, $parentCategory);
			}
		}

		return $configOptions;
	}


	/**
	 * @param mixed $value
	 * @return bool
	 */
	protected function isCategory(mixed $value): bool {
		if ($value instanceof ConfigOption) {
			return false;
		}

		if (is_array($value)) {
			// If the array contains only instances of \Awyiss\Model\Entity\Configuration,
			// and \Awyiss\Model\Entity\UserConfiguration, then it is not a category
			return array_any($value, fn($configItem) => !$configItem instanceof Configuration && !$configItem instanceof UserConfiguration);
		}

		return false;
	}


	/**
	 * @param string $selectedScope
	 * @return bool
	 */
	protected function isPageRole(string $selectedScope): bool {
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');

		return $pageRoleEnum::tryFromName($selectedScope) !== null;
	}
}
