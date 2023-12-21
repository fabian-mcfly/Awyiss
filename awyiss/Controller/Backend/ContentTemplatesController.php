<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * ContentTemplates Controller
 *
 * @property \Awyiss\Model\Table\ContentTemplatesTable $ContentTemplates
 */
class ContentTemplatesController extends Controller {
	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_contentTemplates = $this->ContentTemplates->find()->where($this->getOverviewWhere())->contain(['ContentTemplateElements'])->all();

		$this->set([
			'ao_contentTemplates' => $lo_contentTemplates,
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

		$lo_contentTemplate = $this->ContentTemplates->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_contentTemplate);
		}

		$la_pageTemplates = $this->getPageTemplates();

		$la_assignedContentAreas = [];
		foreach (($lo_contentTemplate->contentAreas ?? []) as $lo_contentArea) {
			$la_assignedContentAreas[ $lo_contentArea->_joinData->pageTemplateId ][] = $lo_contentArea->id;
		}

		$la_availableFieldset = [];
		foreach ($this->ContentTemplates->getAvailableFieldsets() as $ls_fieldset) {
			$la_availableFieldset[ $ls_fieldset ] = __d('contents', 'fieldset_' . $ls_fieldset);
		}

		$this->set([
			'ao_contentTemplate' => $lo_contentTemplate,
			'aa_availableContentElements' => $this->ContentTemplates->getAvailableContentElements(),
			'aa_availableContentAttributes' => $this->ContentTemplates->getAvailableContentAttributes(),
			'aa_availableFieldsets' => $la_availableFieldset,
			'aa_assignedContentAreas' => $la_assignedContentAreas,
			'aa_pageTemplates' => $la_pageTemplates,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->ContentTemplates->findById($ai_id)->find('translations')->contain([
			'ContentTemplateContentAreas',
			'ContentTemplateElements',
		])->first();
		if (!$lo_contentTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_contentTemplate, 'edit');
		}

		$la_pageTemplates = $this->getPageTemplates();

		$la_assignedContentAreas = [];
		foreach ($lo_contentTemplate->contentTemplateContentAreas as $lo_contentArea) {
			$la_assignedContentAreas[ $lo_contentArea->pageTemplateId ][] = $lo_contentArea->contentAreaId;
		}

		$la_availableFieldset = [];
		foreach ($this->ContentTemplates->getAvailableFieldsets() as $ls_fieldset) {
			$la_availableFieldset[ $ls_fieldset ] = __d('contents', 'fieldset_' . $ls_fieldset);
		}

		$this->set([
			'ao_contentTemplate' => $lo_contentTemplate,
			'aa_availableContentElements' => $this->ContentTemplates->getAvailableContentElements(),
			'aa_availableContentAttributes' => $this->ContentTemplates->getAvailableContentAttributes(),
			'aa_availableFieldsets' => $la_availableFieldset,
			'aa_assignedContentAreas' => $la_assignedContentAreas,
			'aa_pageTemplates' => $la_pageTemplates,
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
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->ContentTemplates->findById($ai_id)->find('translations')->first();
		if (!$lo_contentTemplate) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->ContentTemplates->delete($lo_contentTemplate)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param ContentTemplate $ao_contentTemplate
	 * @param string $as_method
	 * @return void
	 */
	protected function save(ContentTemplate $ao_contentTemplate, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->ContentTemplates->hasAttributes()) {
			$la_associated[] = $this->ContentTemplates->getAttributesTable(true);
			$ao_contentTemplate->setAccess('attributes', true);
		}

		$la_requestData = $this->request->getData() + ['content_template_elements' => []];

		if (!empty($la_requestData['content_areas'])) {
			$la_requestData['content_template_content_areas'] = array_filter($la_requestData['content_areas'], function (array $aa_element) {
				return !empty($aa_element['content_area_id']);
			});
			unset($la_requestData['content_areas']);
			$la_associated[] = 'ContentTemplateContentAreas';
		}

		if (!empty($la_requestData['content_template_elements'])) {
			$la_requestData['content_template_elements'] = array_filter($la_requestData['content_template_elements'], function ($aa_element) {
				return !empty($aa_element['identifier']);
			});

			$la_associated[] = 'ContentTemplateElements';
		}

		$this->ContentTemplates->patchEntity($ao_contentTemplate, $la_requestData, ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->ContentTemplates->save($ao_contentTemplate)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_contentTemplate->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_contentTemplate->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * @return array
	 */
	protected function getPageTemplates(): array {
		$this->dispatchEvent('Authorization.disableBehavior');
		$lo_pageTemplates = $this->fetchTable('PageTemplates')->find()->contain(['ContentAreas', 'PageRoles'])->all();
		$this->dispatchEvent('Authorization.enableBehavior');

		$lo_pageTemplates = $lo_pageTemplates->sortBy('pageRole.systemOrder', SORT_ASC)->filter(function (PageTemplate $ao_entity) {
			return !empty($ao_entity->contentAreas);
		})->groupBy(function (PageTemplate $ao_entity) {
			return $ao_entity->pageRole->label;
		});


		return $lo_pageTemplates->toArray();
	}
}
