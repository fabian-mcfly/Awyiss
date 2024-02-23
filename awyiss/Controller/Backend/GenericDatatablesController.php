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
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingTemplateException;


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
	 * @var \Cake\Collection\Iterator\TreeIterator
	 */
	protected CollectionInterface $threadedRecords;
	protected bool $nesting;
	protected bool $splitIntoLanguages;
	protected bool $translatable;


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		if ($this->splitIntoLanguages) {
			$lo_query = $this->Datatable->find('forCurrentLanguage');
		}
		else {
			$lo_query = $this->Datatable->find();
		}

		$lo_query->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query);

		if ($this->nesting) {
			$lo_records = $this->Datatable->listNested($lo_query);
			$lb_paginated = false;
		}
		else {
			$lo_records = $this->paginate($lo_query);
			$lb_paginated = true;
		}

		$this->set([
			'ao_records' => $lo_records,
			'ao_datatable' => $this->datatable,
			'ab_paginated' => $lb_paginated,
			'ab_nesting' => $this->nesting,
			'ab_splitIntoLanguages' => $this->splitIntoLanguages,
			'ab_translatable' => $this->translatable,
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
		elseif ($this->splitIntoLanguages && $lo_entity->languageShortcode && $lo_entity->languageShortcode != LocaleMiddleware::getLanguage()->shortcode) {
			//Don't allow modifying a record in another language
			throw new RedirectException(Router::url([
				'lang' => $lo_entity->languageShortcode,
				'id' => $lo_entity->id,
			], true), 302);
		}

		$this->setViewVars($lo_entity);
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

		$this->Datatable->patchEntity($ao_entity, $this->request->getData(), ['associated' => $la_associated]);

		$this->Categories->setConfig('finder', [
			'forCurrentLanguage' => [
				'entity' => $ao_entity,
			],
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Datatable->save($ao_entity)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user.
					 * Otherwise it would show a site without the modified user, which could be a bit confusing.
					 *
					 */
					$this->verifyCategorySelection($ao_entity);

					throw new RedirectException(Router::url(['action' => 'overview', 'lang' => $ao_entity->languageShortcode], true), 302);
				}

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

		if ($this->nesting) {
			$lo_threadedRecords = $this->getThreadedRecords($ao_entity);

			$lo_parentRecords = $this->getParentRecords($ao_entity, $lo_threadedRecords);
			$this->ensurePossibleParentId($ao_entity, $lo_parentRecords);
		}
		else {
			$lo_parentRecords = null;
		}

		$this->set([
			'ao_record' => $ao_entity,
			'ao_parentRecords' => $lo_parentRecords,
			'ao_datatable' => $this->datatable,
			'ab_nesting' => $this->nesting,
			'ab_splitIntoLanguages' => $this->splitIntoLanguages,
			'ab_translatable' => $this->translatable,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
			'localConfig' => LocalConfig::read(),
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity $ao_entity
	 * @param \Cake\Collection\CollectionInterface $ao_threadedContents
	 * @return void
	 */
	protected function ensurePossibleParentId(Entity $ao_entity, CollectionInterface $ao_threadedRecords): void {
		if ($this->Categories->getConfig('enabled') && $this->Categories->getConfig('fieldname') === 'parentId') {
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

		$this->nesting = LocalConfig::read('nest.enabled');
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
	 * @param \Awyiss\Model\Entity $ao_entity
	 * @param \Cake\Collection\CollectionInterface $ao_threadedRecords
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getParentRecords(Entity $ao_entity, CollectionInterface $ao_threadedRecords): CollectionInterface {
		//We only want to find threaded records for an existing entity (id equals not null)
		$li_originalId = $ao_entity->get('id');
		if (!$li_originalId) {
			return $ao_threadedRecords;
		}

		$li_foundAtLevel = null;
		$lo_threadedRecords = new Collection($ao_threadedRecords->toList());

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$lo_threadedRecords = $lo_threadedRecords->filter(function (Entity $ao_entity) use ($li_originalId, &$li_foundAtLevel) {
			if ($ao_entity->get('id') === $li_originalId) {
				$li_foundAtLevel = $ao_entity->level;
			}
			elseif (is_null($li_foundAtLevel) || $ao_entity->level <= $li_foundAtLevel) {
				$li_foundAtLevel = null;


				return true;
			}


			return false;
		});


		return $lo_threadedRecords;
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
	 */
	protected function verifyCategorySelection(Entity $ao_entity): void {
		if (!$this->Categories->getConfig('enabled')) {
			return;
		}

		$la_categories = [];

		$ls_field = $this->Categories->getConfig('fieldname');
		if ($ao_entity->get($ls_field)) {
			$la_categories[ $ao_entity->get($ls_field) ] = $ls_field;

			if ($this->Categories->getConfig('allowAggregation')) {
				$la_categories += [$this->Categories->getConfig('aggregationKey') => 'dummy'];
			}
		}
		else {
			if ($this->Categories->getConfig('allowUnassigned')) {
				$la_categories += [$this->Categories->getConfig('unassignedKey') => 'dummy'];
			}
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
			'ao_' . $ls_entitiesName => $lo_viewBuilder->getVar('ao_records'),
			'ao_' . $ls_entityName => $lo_viewBuilder->getVar('ao_record'),
			'ao_' . $ls_threadedName => $lo_viewBuilder->getVar('ao_threadedRecords'),
			'ao_' . $ls_parentName => $lo_viewBuilder->getVar('ao_parentRecords'),
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
}
