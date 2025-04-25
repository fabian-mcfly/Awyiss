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
	 * @var bool Translation of datatabe records enabled
	 */
	protected bool $translatable;


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		if ($this->splitIntoLanguages) {
			$lo_query = $this->Datatable->find('forCurrentLanguage');
		}
		else {
			$lo_query = $this->Datatable->find();
		}

		$lo_query->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);
		$this->Search->filterQuery($lo_query);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery()->find('mediaAssignments');

		// Disable sorting if the current category is the aggregation category or the unassigned category
		if (
			$this->Categories->getSelectedCategory() === $this->Categories->getConfig('aggregationKey') ||
			$this->Categories->getSelectedCategory() === $this->Categories->getConfig('unassignedKey')
		) {
			$this->sortable = false;
		}

		$lb_paginated = $this->paginate['enabled'];
		if ($lb_paginated) {
			$lo_records = $this->paginate($lo_query);
		}
		elseif ($this->nestable) {
			$this->isNestableWithCategoriesEnabled();

			$lo_records = $lo_query->find('threaded')->all();
		}
		else {
			$lo_records = $lo_query->all();
		}

		$this->set([
			'records' => $lo_records,
			'datatable' => $this->datatable,
			'paginated' => $lb_paginated,
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

		$lo_record = $this->Datatable->newDefaultEntity([
			'languageShortcode' => $this->splitIntoLanguages ? LocaleMiddleware::getLanguage()->shortcode : null,
		]);

		if ($this->request->is('post')) {
			$this->save($lo_record);
		}

		$this->setViewVars($lo_record);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var \Awyiss\Model\Entity $lo_entity */
		$lo_entity = $this->Datatable->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();

		if (!$lo_entity) {
			$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables', 'record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_entity, 'edit');
		}
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		elseif ($this->splitIntoLanguages && $lo_entity->languageShortcode && $lo_entity->languageShortcode != LocaleMiddleware::getLanguage()->shortcode) {
			//Don't allow modifying a record in another language
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			throw new RedirectException(Router::url([
				'lang' => $lo_entity->languageShortcode,
				'id' => $lo_entity->id,
			], true), 302);
		}

		$this->setViewVars($lo_entity);
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

		/** @var Datatable $lo_datatable */
		$lo_datatable = $this->Datatable->findById($id)->first();
		if (!$lo_datatable) {
			$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables', 'record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Datatable->delete($lo_datatable)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__df($this->datatable->identifier, 'generic_datatables', 'delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables', 'delete_failed'));
				foreach ($lo_datatable->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $method
	 * @return void
	 */
	protected function save(Entity $entity, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Datatable->hasAttributes()) {
			$la_associated[] = $this->Datatable->getAttributesTableName(true);
			$entity->setAccess('attributes', true);
		}

		$this->Datatable->patchEntity($entity, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		$this->Categories->setConfig('finder', [
			'forCurrentLanguage' => [
				'entity' => $entity,
			],
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Datatable->save($entity, ['asCopy' => $lb_saveAsCopy])) {
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
				$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables', ($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($entity->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		elseif ($this->Datatable->hasBehavior('SystemOrder')) {
			if ($this->Datatable->getSystemOrderRelatedColumns($entity)) {
				$entity->systemOrder = null;
			}
			else {
				$entity->systemOrder = $entity->hasOriginal('systemOrder') ? $entity->getOriginal('systemOrder') : $entity->get('systemOrder');
			}

			// Update the request data. Otherwise, the SystemOrderHelper would use the outdated request data
			$lo_request = $this->request->withData('system_order', $entity->systemOrder);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity $record
	 * @return void
	 */
	protected function setViewVars(Entity $entity): void {
		$this->Categories->ensurePossibleCategory($entity);

		if ($this->nestable) {
			$lo_threadedRecords = $this->getThreadedRecords($entity);

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_possibleParentRecords = $this->Datatable->getPossibleParents($entity, $lo_threadedRecords);
			$this->ensurePossibleParentId($entity, $lo_possibleParentRecords);
		}
		else {
			$lo_possibleParentRecords = null;
		}

		$this->set([
			'record' => $entity,
			'possibleParentRecords' => $lo_possibleParentRecords,
			'datatable' => $this->datatable,
			'nestable' => $this->nestable,
			'sortable' => $this->sortable,
			'splitIntoLanguages' => $this->splitIntoLanguages,
			'translatable' => $this->translatable,
			'languageRealm' => Awyiss::REALM_FRONTEND,
			'isGenericDatatable' => true,
			//'localConfig' => LocalConfig::read(),
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $threadedContents
	 * @return void
	 */
	protected function ensurePossibleParentId(Entity $entity, CollectionInterface $threadedRecords): void {
		if ($this->Categories->getConfig('enabled') && $this->Categories->getConfig('field') === 'parentId') {
			//No parent id check if categories behavior is enabled and the field is parent id
			return;
		}

		$la_possibleParentIds = $threadedRecords->extract('id')->toList();

		if (!empty($entity->parentId) && !in_array($entity->parentId, $la_possibleParentIds)) {
			$la_errors = $entity->getError('parentId');

			$entity->parentId = reset($la_possibleParentIds);

			if ($la_errors) {
				$entity->setError('parentId', $la_errors, true);
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
	 * @throws \ReflectionException
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

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$ls_policyClass = $lo_authorizationService->getPolicy($this->Authorization->getScope(), $this->Authorization->getConfig('policiesRealm'));

		$this->Authorization->setScope($identifier);

		$this->SystemOrder->setConfig('entityName', Inflector::variable(Inflector::singularize($identifier)));

		$this->set([
			'policyClass' => $ls_policyClass,
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
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_query = $this->Datatable->find('forCurrentLanguage', languageShortcode: $entity->languageShortcode, includeGlobal: false)->where(
				$this->getOverviewWhere() + $this->Categories->getQueryConditions(
					$this->Categories->getSelectedCategory($entity)
				)
			);

			$this->threadedRecords = $this->Datatable->listNested($lo_query);
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

		$la_categories = [];

		$ls_field = $this->Categories->getConfig('field');
		if ($entity->get($ls_field)) {
			$la_categories[ $entity->get($ls_field) ] = $ls_field;

			if ($this->Categories->getConfig('allowAggregation')) {
				$la_categories += [$this->Categories->getConfig('aggregationKey') => 'dummy'];
			}
		}
		elseif ($this->Categories->getConfig('allowUnassigned')) {
			$la_categories += [$this->Categories->getConfig('unassignedKey') => 'dummy'];
		}

		/*
		 * Make sure the currently selected category is still part of the entity.
		 * Otherwise the next redirect to the overview would show a site without the modified entity, which could be a bit confusing.
		 */
		$this->Categories->verifySelection(null, $la_categories, true);
	}


	/**
	 * Try to render the view using the default render-method
	 * If this fails because the view template could not be found, try again with a view-template
	 * in templates/Backend/GenericDatatables
	 */
	public function render(?string $template = null, ?string $layout = null): Response {
		$lo_viewBuilder = $this->viewBuilder();

		$ls_entitiesName = Inflector::variable($this->getName());
		$ls_entityName = Inflector::variable(Inflector::singularize($this->getName()));
		$ls_threadedName = Inflector::variable('threaded ' . $this->getName());
		$ls_parentName = Inflector::variable('parent ' . $this->getName());

		$lo_viewBuilder->setVars([
			$ls_entitiesName => $lo_viewBuilder->getVar('records'),
			$ls_entityName => $lo_viewBuilder->getVar('record'),
			$ls_threadedName => $lo_viewBuilder->getVar('threadedRecords'),
			$ls_parentName => $lo_viewBuilder->getVar('parentRecords'),
		]);

		try {
			$ls_contents = parent::render($template, $layout);
		}
		catch (MissingTemplateException) {
			$la_templatePathParts = explode('/', $lo_viewBuilder->getTemplatePath());
			array_pop($la_templatePathParts);

			$lo_viewBuilder->setTemplatePath(implode('/', $la_templatePathParts) . '/GenericDatatables');

			$ls_contents = parent::render($template, $layout);
		}


		return $ls_contents;
	}


	/**
	 * @return void
	 */
	protected function isNestableWithCategoriesEnabled(): void {
		if (!$this->Datatable->hasBehavior('Categories')) {
			return;
		}

		$lo_categoriesBehavior = $this->Datatable->getBehavior('Categories');

		if (
			$lo_categoriesBehavior->getConfig('enabled') && $lo_categoriesBehavior->getConfig('foreignKey') === 'parent_id'
		) {
			throw new RuntimeException('Cannot use nesting with categories that uses `parent_id` as the foreign key.');
		}
	}
}
