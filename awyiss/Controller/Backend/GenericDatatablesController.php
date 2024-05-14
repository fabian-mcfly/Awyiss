<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use AllowDynamicProperties;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\LocalConfig;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Routing\Router;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;
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
	public function getOverviewQuery(): ?SelectQuery {
		if ($this->splitIntoLanguages) {
			$lo_query = $this->Datatable->find('forCurrentLanguage');
		}
		else {
			$lo_query = $this->Datatable->find();
		}

		$lo_query->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, !$this->paginate['enabled']);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();

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
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var \Awyiss\Model\Entity $lo_entity */
		$lo_entity = $this->Datatable->findById($ai_id)->find('translations')->first();

		if (!$lo_entity) {
			$this->Flash->error(__('record_not_found'));


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
	 * @param int $ai_id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Datatable $lo_datatable */
		$lo_datatable = $this->Datatable->findById($ai_id)->first();
		if (!$lo_datatable) {
			$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables','record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Datatable->delete($lo_datatable)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__df($this->datatable->identifier, 'generic_datatables','delete_succeeded'));
			}
		}
		else {
			$this->Flash->error(__df($this->datatable->identifier, 'generic_datatables','delete_failed'));

			foreach ($lo_datatable->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity $ao_entity
	 * @param string $as_method
	 * @return void
	 */
	protected function save(Entity $ao_entity, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Datatable->hasAttributes()) {
			$la_associated[] = $this->Datatable->getAttributesTableName(true);
			$ao_entity->setAccess('attributes', true);
		}

		$this->Datatable->patchEntity($ao_entity, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		$this->Categories->setConfig('finder', [
			'forCurrentLanguage' => [
				'entity' => $ao_entity,
			],
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Datatable->save($ao_entity, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($as_method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user.
					 * Otherwise it would show a site without the modified user, which could be a bit confusing.
					 *
					 */
					$this->verifyCategorySelection($ao_entity);

					/** @noinspection PhpPossiblePolymorphicInvocationInspection */
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'lang' => $ao_entity->languageShortcode,
						'page' => $this->Paginate->calculateEntityPagePosition($ao_entity),
					], true), 302);
				}

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				throw new RedirectException(Router::url(['action' => 'edit', 'lang' => $ao_entity->languageShortcode, 'id' => $ao_entity->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_entity->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity $ao_record
	 * @return void
	 */
	protected function setViewVars(Entity $ao_entity): void {
		$this->Categories->ensurePossibleCategory($ao_entity);

		if ($this->nestable) {
			$lo_threadedRecords = $this->getThreadedRecords($ao_entity);

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_possibleParentRecords = $this->Datatable->getPossibleParents($ao_entity, $lo_threadedRecords);
			$this->ensurePossibleParentId($ao_entity, $lo_possibleParentRecords);
		}
		else {
			$lo_possibleParentRecords = null;
		}

		$this->set([
			'record' => $ao_entity,
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
	 * @param \Awyiss\Model\Entity $ao_entity
	 * @param \Cake\Collection\CollectionInterface $ao_threadedContents
	 * @return void
	 */
	protected function ensurePossibleParentId(Entity $ao_entity, CollectionInterface $ao_threadedRecords): void {
		if ($this->Categories->getConfig('enabled') && $this->Categories->getConfig('field') === 'parentId') {
			//No parent id check if categories behavior is enabled and the field is parent id
			return;
		}

		$la_possibleParentIds = $ao_threadedRecords->extract('id')->toList();

		if (!empty($ao_entity->parentId) && !in_array($ao_entity->parentId, $la_possibleParentIds)) {
			$la_errors = $ao_entity->getError('parentId');

			$ao_entity->parentId = reset($la_possibleParentIds);

			if ($la_errors) {
				$ao_entity->setError('parentId', $la_errors, true);
			}
		}
	}


	/**
	 * Uses this controller with another datatable, so we don't need to bake one for every single one.
	 * This is supposed to only handle non-existing controllers as a fallback.
	 *
	 * @param \Awyiss\Model\Entity\Datatable $ao_datatable
	 * @param string $as_identifier
	 * @return \Awyiss\Controller\Backend\GenericDatatablesController
	 * @throws \ReflectionException
	 */
	public function forDatatable(Datatable $ao_datatable, string $as_identifier): static {
		$this->datatable = $ao_datatable;

		$this->Datatable = $this->{$as_identifier} = $this->fetchTable($as_identifier);

		$this->nestable = LocalConfig::read('nest.enabled');
		if ($this->nestable) {
			$this->isNestableWithCategoriesEnabled();
		}

		$this->sortable = Inflector::variable(LocalConfig::read('systemOrder.field', 'systemOrder')) === 'systemOrder';

		$this->splitIntoLanguages = LocalConfig::read('splitIntoLanguages');
		$this->translatable = LocalConfig::read('translatable');

		/** @var \Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $this->getRequest()->getAttribute('authorization');
		$ls_policyClass = $lo_authorizationService->getPolicy($this->Authorization->getScope(), $this->Authorization->getConfig('policiesRealm'));

		$this->Authorization->setScope($as_identifier);

		$this->SystemOrder->setConfig('entityName', Inflector::variable(Inflector::singularize($as_identifier)));

		$this->set([
			'policyClass' => $ls_policyClass,
		]);


		return $this;
	}


	/**
	 * Return a collection of records for the currently set languageShortcode,
	 * using `\Cake\Collection\CollectionTrait::listNested()` to be used in a form-select
	 *
	 * @param \Awyiss\Model\Entity $ao_entity
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Cake\Collection\CollectionTrait::listNested()
	 */
	protected function getThreadedRecords(Entity $ao_entity): CollectionInterface {
		if (!isset($this->threadedRecords)) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_query = $this->Datatable->find('forCurrentLanguage', languageShortcode: $ao_entity->languageShortcode, includeGlobal: false)->where(
				$this->getOverviewWhere() + $this->Categories->getQueryConditions(
					$this->Categories->getSelectedCategory($ao_entity)
				)
			);

			$this->threadedRecords = $this->Datatable->listNested($lo_query);
		}


		return $this->threadedRecords;
	}


	/**
	 * @param \Awyiss\Model\Entity $ao_entity
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	protected function verifyCategorySelection(Entity $ao_entity): void {
		if (!$this->Categories->getConfig('enabled')) {
			return;
		}

		$la_categories = [];

		$ls_field = $this->Categories->getConfig('field');
		if ($ao_entity->get($ls_field)) {
			$la_categories[ $ao_entity->get($ls_field) ] = $ls_field;

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
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function render(?string $as_template = null, ?string $as_layout = null): Response {
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
			$ls_contents = parent::render($as_template, $as_layout);
		}
		catch (MissingTemplateException) {
			$la_templatePathParts = explode('/', $lo_viewBuilder->getTemplatePath());
			array_pop($la_templatePathParts);

			$lo_viewBuilder->setTemplatePath(implode('/', $la_templatePathParts) . '/GenericDatatables');

			$ls_contents = parent::render($as_template, $as_layout);
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
