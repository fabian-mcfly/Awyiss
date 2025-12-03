<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use AllowDynamicProperties;
use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\LocalConfig;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\View\Exception\MissingTemplateException;
use RuntimeException;


/**
 * Generic Datatables Controller
 * Used to instantiate controllers for dynamically created datatables that have no own controller
 *
 * @property \Awyiss\Model\Table $Datatable
 */
#[AllowDynamicProperties]
abstract class GenericDatatablesController extends Controller {
	/**
	 * @var \Awyiss\Model\Entity\Datatable
	 */
	protected Datatable $datatable;
	/**
	 * @var bool Nesting enabled
	 */
	protected bool $nestable;
	/**
	 * @var bool Manual sorting enabled
	 */
	protected bool $sortable;
	/**
	 * @var bool Elements split up into different languages
	 */
	protected bool $splitIntoLanguages;
	/**
	 * @var \Cake\Collection\Iterator\TreeIterator
	 */
	protected CollectionInterface $threadedRecords;
	/**
	 * @var bool Translation of datatable records enabled
	 */
	protected bool $translatable;


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		if ($this->splitIntoLanguages) {
			/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
			$query = $this->Datatable->find('forCurrentLanguage');
		}
		else {
			$query = $this->Datatable->find();
		}

		$query->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery()->find('mediaAssignments');

		// Disable sorting if the current category is the aggregation category or the unassigned category
		if (
			$this->Categories->getSelectedCategory() === $this->Categories->getConfig('aggregationKey') ||
			$this->Categories->getSelectedCategory() === $this->Categories->getConfig('unassignedKey')
		) {
			$this->sortable = false;
		}

		$paginated = $this->paginate['enabled'];
		if ($paginated) {
			$records = $this->paginate($query);
		}
		elseif ($this->nestable) {
			$this->isNestableWithCategoriesEnabled();

			$records = $query->find('threaded')->all();
		}
		else {
			$records = $query->all();
		}

		$this->set([
			'records' => $records,
			'datatable' => $this->datatable,
			'paginated' => $paginated,
			'nestable' => $this->nestable,
			'sortable' => $this->sortable,
			'splitIntoLanguages' => $this->splitIntoLanguages,
			'translatable' => $this->translatable,
			'attributes' => $this->Datatable->getAttributes(),
			'isGenericDatatable' => true,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->ensure('create');

		$entity = $this->Datatable->newDefaultEntity([
			'languageShortcode' => $this->splitIntoLanguages ? LocaleMiddleware::getLanguage()->shortcode : null,
		]);

		if ($this->request->is('post')) {
			$this->save($entity);
		}

		$this->setViewVars($entity);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/**
		 * @var \Awyiss\Model\Entity $entity
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$entity = $this->Datatable->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();

		if (!$entity) {
			$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables', 'record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($entity, 'edit');
		}
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		elseif ($this->splitIntoLanguages && $entity->languageShortcode && $entity->languageShortcode != LocaleMiddleware::getLanguage()->shortcode) {
			//Don't allow modifying a record in another language
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			throw new RedirectException(Router::url([
				'lang' => $entity->languageShortcode,
				'id' => $entity->id,
			], true), 302);
		}

		$this->setViewVars($entity);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\Datatable $datatable */
		$datatable = $this->Datatable->findById($id)->first();
		if (!$datatable) {
			$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables', 'record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Datatable->delete($datatable)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__df($this->datatable->identifier, 'generic_datatables', 'delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables', 'delete_failed'));
				foreach ($datatable->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $method
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function save(Entity $entity, string $method = 'add'): void {
		$associated = [];
		if ($this->Datatable->hasAttributes()) {
			$associated[] = $this->Datatable->getAttributesTableName(true);
			$entity->setAccess('attributes', true);
		}

		$this->Datatable->patchEntity($entity, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (LocalConfig::read('splitIntoLanguages')) {
			$this->Categories->setConfig('finder', [
				'forCurrentLanguage' => [
					'entity' => $entity,
				],
			]);
		}
		else {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			if ($entity->languageShortcode) {
				$entity->set('languageShortcode');
			}
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Datatable->save($entity, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__df($this->datatable->identifier, 'generic_datatables', $method . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user.
					 * Otherwise it would show a site without the modified user, which could be a bit confusing.
					 *
					 */
					$this->verifyCategorySelection($entity);

					/** @noinspection PhpPossiblePolymorphicInvocationInspection */
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'lang' => $entity->languageShortcode,
						'page' => $this->Paginate->calculateEntityPagePosition($entity),
					], true), 302);
				}

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $entity->languageShortcode, 'id' => $entity->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables', ($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($entity->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @return void
	 */
	protected function setViewVars(Entity $entity): void {
		$this->Categories->ensurePossibleCategory($entity);

		if ($this->nestable) {
			$threadedRecords = $this->getThreadedRecords($entity);

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$possibleParentRecords = $this->Datatable->getPossibleParents($entity, $threadedRecords);
			$this->ensurePossibleParentId($entity, $possibleParentRecords);
		}
		else {
			$possibleParentRecords = null;
		}

		$this->set([
			'record' => $entity,
			'possibleParentRecords' => $possibleParentRecords,
			'datatable' => $this->datatable,
			'nestable' => $this->nestable,
			'sortable' => $this->sortable,
			'splitIntoLanguages' => $this->splitIntoLanguages,
			'translatable' => $this->translatable,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'isGenericDatatable' => true,
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $threadedRecords
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function ensurePossibleParentId(Entity $entity, CollectionInterface $threadedRecords): void {
		if ($this->Categories->getConfig('enabled') && $this->Categories->getConfig('field') === 'parentId') {
			//No parent id check if categories behavior is enabled and the field is parent id
			return;
		}

		$possibleParentIds = $threadedRecords->extract('id')->toList();

		if (!empty($entity->parentId) && !in_array($entity->parentId, $possibleParentIds)) {
			$errors = $entity->getError('parentId');

			$entity->parentId = reset($possibleParentIds);

			if ($errors) {
				$entity->setError('parentId', $errors, true);
			}
		}
	}


	/**
	 * Uses this controller with another datatable, so we don't need to bake one for every single one.
	 * This is supposed to only handle non-existing controllers as a fallback.
	 *
	 * @param \Awyiss\Model\Entity\Datatable $datatable
	 * @param string $identifier
	 * @return \Awyiss\Controller\Backend\GenericDatatablesController
	 * @noinspection DuplicatedCode
	 */
	#[NoDirectAccess]
	public function forDatatable(Datatable $datatable, string $identifier): static {
		$this->datatable = $datatable;

		$this->Datatable = $this->{$identifier} = $this->fetchTable($identifier);

		$this->nestable = LocalConfig::read('nest.enabled', false);
		if ($this->nestable) {
			$this->isNestableWithCategoriesEnabled();
		}

		$this->sortable = Inflector::variable(LocalConfig::read('systemOrder.field', 'systemOrder')) === 'systemOrder';

		$this->splitIntoLanguages = LocalConfig::read('splitIntoLanguages');
		$this->translatable = LocalConfig::read('translatable');

		/** @var \Awyiss\Authorization\AuthorizationService $authorizationService */
		$authorizationService = $this->getRequest()->getAttribute('authorization');
		$policyClass = $authorizationService->getPolicy($this->Authorization->getScope(), $this->Authorization->getConfig('policiesRealm'));

		$this->Authorization->setScope($identifier);

		$this->SystemOrder->setConfig('entityName', Inflector::variable(Inflector::singularize($identifier)));

		$this->set([
			'policyClass' => $policyClass,
		]);


		return $this;
	}


	/**
	 * Return a collection of records for the currently set languageShortcode,
	 * using `\Cake\Collection\CollectionTrait::listNested()` to be used in a form-select
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Cake\Collection\CollectionTrait::listNested()
	 */
	protected function getThreadedRecords(Entity $entity): CollectionInterface {
		if (!isset($this->threadedRecords)) {
			/**
			 * @uses \Awyiss\Model\Table::findForCurrentLanguage()
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$query = $this->Datatable->find('forCurrentLanguage', languageShortcode: $entity->languageShortcode, includeGlobal: false)->where(
				$this->getOverviewWhere() + $this->Categories->getQueryConditions(
					$this->Categories->getSelectedCategory($entity)
				)
			);

			$this->threadedRecords = $this->Datatable->listNested($query);
		}


		return $this->threadedRecords;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function verifyCategorySelection(Entity $entity): void {
		if (!$this->Categories->getConfig('enabled')) {
			return;
		}

		$categories = [];

		$field = $this->Categories->getConfig('field');
		if ($entity->get($field)) {
			$categories[ $entity->get($field) ] = $field;

			if ($this->Categories->getConfig('allowAggregation')) {
				$categories += [$this->Categories->getConfig('aggregationKey') => 'dummy'];
			}
		}
		elseif ($this->Categories->getConfig('allowUnassigned')) {
			$categories += [$this->Categories->getConfig('unassignedKey') => 'dummy'];
		}

		/*
		 * Make sure the currently selected category is still part of the entity.
		 * Otherwise the next redirect to the overview would show a site without the modified entity, which could be a bit confusing.
		 */
		$this->Categories->verifySelection(null, $categories, true);
	}


	/**
	 * Try to render the view using the default render-method
	 * If this fails because the view template could not be found, try again with a view-template
	 * in templates/Backend/GenericDatatables
	 */
	public function render(?string $template = null, ?string $layout = null): Response {
		$viewBuilder = $this->viewBuilder();

		$entitiesName = Inflector::variable($this->getName());
		$entityName = Inflector::variable(Inflector::singularize($this->getName()));
		$threadedName = Inflector::variable('threaded ' . $this->getName());
		$parentName = Inflector::variable('parent ' . $this->getName());

		$viewBuilder->setVars([
			$entitiesName => $viewBuilder->getVar('records'),
			$entityName => $viewBuilder->getVar('record'),
			$threadedName => $viewBuilder->getVar('threadedRecords'),
			$parentName => $viewBuilder->getVar('parentRecords'),
		]);

		try {
			$contents = parent::render($template, $layout);
		}
		catch (MissingTemplateException) {
			$templatePathParts = explode('/', $viewBuilder->getTemplatePath());
			array_pop($templatePathParts);

			$viewBuilder->setTemplatePath(implode('/', $templatePathParts) . '/GenericDatatables');

			$contents = parent::render($template, $layout);
		}


		return $contents;
	}


	/**
	 * @return void
	 */
	protected function isNestableWithCategoriesEnabled(): void {
		if (!$this->Datatable->hasBehavior('Categories')) {
			return;
		}

		$categoriesBehavior = $this->Datatable->getBehavior('Categories');

		if (
			$categoriesBehavior->getConfig('enabled') &&
			$categoriesBehavior->getConfig('foreignKey') === 'parent_id'
		) {
			throw new RuntimeException('Cannot use nesting with categories that uses `parent_id` as the foreign key.');
		}
	}
}
