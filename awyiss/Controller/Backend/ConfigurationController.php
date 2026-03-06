<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;


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
		$this->selectedRealmSessionIdentifier = 'categories.' . Inflector::variable($this->getName()) . '.realm';

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
		$query = $this->Configuration->find()->where($this->getOverviewWhere())->orderBy([
			'identifier' => 'ASC',
			'languageShortcode' => 'ASC',
		]);
		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);
		$this->Search->filterQuery($query);

		return $query;
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

		$selectedScope = $this->Categories->getSelectedCategory();

		if (!$this->Authorization->withAdditionalData(['scope' => $selectedScope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			return $this->redirect(['action' => 'overview', 'scope' => 'System']);
		}

		$configOptions = ConfigOptionsProvider::loadConfigOptions($selectedScope);

		$query = $this->getOverviewQuery();

		$configuration = $query->all()->groupBy('realm')->map(function ($data) use ($configOptions) {
			return Hash::expand(collection($data)->groupBy(function (Configuration $entity) use ($configOptions) {
				$identifier = array_map(function (string $identifier) {
					return ConfigOptionsProvider::sanitizeIdentifier($identifier);
				}, explode('.', $entity->identifier));

				/** @noinspection PhpUndefinedFieldInspection */
				$entity->configOption = $configOptions->getConfigOption($entity->realm, implode('.', $identifier));

				return implode('.', $identifier);
			})->toArray());
		})->toArray();

		if ($this->Configuration->searchIsActive()) {
			$mergedConfiguration = $configuration;
		}
		else {
			$mergedConfiguration = $configOptions->getConfigOptions();
			/**
			 * @var string $realm
			 * @var \Awyiss\Configuration\ConfigOptionsCollection $configOptions
			 */
			foreach ($mergedConfiguration as $realm => $configOptions) {
				$mergedConfiguration[ $realm ] = Hash::merge([], $configOptions->toArray(), $configuration[ $realm ] ?? []);
				$mergedConfiguration[ $realm ] = $this->sortConfigOptions($mergedConfiguration[ $realm ], $realm, $selectedScope);
			}
		}

		$this->set([
			'configuration' => $configuration,
			'mergedConfiguration' => $mergedConfiguration,
			'realms' => Awyiss::getRealms(),
			'selectedScope' => $selectedScope,
			'attributes' => $this->Configuration->getAttributes(),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->setAdditionalData([
			'scope' => '',
		])->ensure('create');

		$configuration = $this->Configuration->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($configuration);
		}
		else {
			$session = $this->request->getSession();
			if ($session->read($this->selectedRealmSessionIdentifier)) {
				$configuration->realm = $session->read($this->selectedRealmSessionIdentifier);
			}
		}

		$configOptions = ConfigOptionsProvider::loadConfigOptions($configuration->scope);
		$configOptionsArray = $configOptions->getConfigOptions($configuration->realm);

		/** @noinspection PhpUndefinedFieldInspection */
		$configuration->configOption = $configuration->identifier ? $configOptions->getConfigOption($configuration->realm, $configuration->identifier) : null;

		$this->set([
			'configuration' => $configuration,
			'configOptions' => $configOptionsArray,
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

		/**
		 * @var \Awyiss\Model\Entity\Configuration $configuration
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$configuration = $this->Configuration->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
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
		$configOptionsArray = $configOptions->getConfigOptions($configuration->realm);

		/** @noinspection PhpUndefinedFieldInspection */
		$configuration->configOption = $configuration->identifier ? $configOptions->getConfigOption($configuration->realm, $configuration->identifier) : null;

		$this->set([
			'configuration' => $configuration,
			'configOptions' => $configOptionsArray,
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

		/** @var Configuration $configuration */
		$configuration = $this->Configuration->findById($id)->first();
		if (!$configuration) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Configuration->delete($configuration)) {
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
	 * @param Configuration $configuration
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	protected function save(Configuration $configuration, string $method = 'add'): void {
		$associated = [];
		if ($this->Configuration->hasAttributes()) {
			$associated[] = $this->Configuration->getAttributesTableName(true);
			$configuration->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();

		if (is_array($requestData['value'] ?? null)) {
			$requestData['value'] = json_encode(array_values($requestData['value']));
		}

		$this->Configuration->patchEntity($configuration, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->Authorization->withAdditionalData(['scope' => $configuration->scope])->isAccessible('read')) {
			$this->Flash->error(__('scope_not_accessible'));

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		$session = $this->request->getSession();
		$session->write($this->selectedRealmSessionIdentifier, $configuration->realm);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->Configuration->save($configuration, ['asCopy' => $saveAsCopy])) {
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
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function requestLock(string $method = 'update'): void {
		$configId = (int)$this->request->getData('id');

		/** @var \Awyiss\Model\Entity\Configuration $configuration */
		$configuration = $this->Configuration->findById($configId)->first();
		if (!$configuration) {
			$this->viewBuilder()->setClassName('Json')->setOption('serialize', ['data', 'status']);

			// Set the response data
			$this->set([
				'data' => [],
				'status' => 'error',
			]);

			return;
		}

		$this->Authorization->setAdditionalData(['scope' => $configuration->scope]);

		parent::requestLock($method);
	}


	/**
	 * @param string $method
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function releaseLock(string $method = 'update'): void {
		$configId = (int)$this->request->getData('id');

		/** @var \Awyiss\Model\Entity\Configuration $configuration */
		$configuration = $this->Configuration->findById($configId)->first();
		if (!$configuration) {
			$this->viewBuilder()->setClassName('Json')->setOption('serialize', ['data', 'status']);

			// Set the response data
			$this->set([
				'data' => [],
				'status' => 'error',
			]);

			return;
		}

		$this->Authorization->setAdditionalData(['scope' => $configuration->scope]);

		parent::releaseLock($method);
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
		$realm = Inflector::camelize($realm);

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
			// then it is not a category
			return array_any($value, fn ($configItem) => !$configItem instanceof Configuration);
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
