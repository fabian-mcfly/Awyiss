<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


/**
 * ContentTemplates Controller
 *
 * @property \Awyiss\Model\Table\ContentTemplatesTable $ContentTemplates
 */
class ContentTemplatesController extends Controller {
	protected array $availableElements = [
		'parent_id',
		'columnwidth',
		'title',
		'subtitle',
		'text',
		'link',
		'media_id',
		'media_alt_id',
		'media_folders_id',
		'duplicate_of',
		'forms_id',
		'tags',
	];


	/**
	 * Overview method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensureOne('create', 'update', 'delete');

		$lo_contentTemplates = $this->ContentTemplates->find('withAttributes')->where($this->overviewWhere);

		$this->set([
			'ao_contentTemplates' => $lo_contentTemplates,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_contentTemplate = $this->ContentTemplates->newDefaultEntity();
		if ($this->request->is('post')) {
			if ($la_available_elements = $this->request->getData('available_elements', [])) {
				$la_available_elements = array_filter($la_available_elements, function($aa_element) {
					return ! is_numeric($aa_element['name']);
				});
			}

			$la_assigned_content_areas = $this->request->getData('assigned_content_areas', []);

			$lo_contentTemplate = $this->ContentTemplates->patchEntity($lo_contentTemplate, [
					'available_elements' => $la_available_elements,
					'assigned_content_areas' => $la_assigned_content_areas,
				] + $this->request->getData());

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->ContentTemplates->save($lo_contentTemplate)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_contentTemplate->id]);
				}

				$this->Flash->error(__('::add_failed'));
			}
		}

		$this->set([
			'ao_contentTemplate' => $lo_contentTemplate,
			'aa_availableElements' => $this->availableElements,
			'ao_pageTemplates' => $this->getPageTemplates(),
		]);
	}


	protected function getPageTemplates (): \Cake\ORM\ResultSet {
		return $this->getTableLocator()->get('PageTemplates')->find('withAttributes')->all();
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		$li_id = $this->request->getParam('id');
		$lo_contentTemplate = $this->ContentTemplates->find()->where(['id' => $li_id])->first();

		if ( ! $lo_contentTemplate) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			if ($la_availableElements = $this->request->getData('available_elements', [])) {
				$la_availableElements = array_filter($la_availableElements, function($aa_element) {
					return ! is_numeric($aa_element['name']);
				});
			}

			$la_assignedContentAreas = $this->request->getData('assigned_content_areas', []);

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lb_availableElementsDirty = ! ($lo_contentTemplate->available_elements == $la_availableElements);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lb_assignedContentAreasDirty = ! ($lo_contentTemplate->assigned_content_areas == $la_assignedContentAreas);

			$lo_contentTemplate = $this->ContentTemplates->patchEntity($lo_contentTemplate, [
					'available_elements' => $la_availableElements,
					'assigned_content_areas' => $la_assignedContentAreas,
				] + $this->request->getData());

			$lo_contentTemplate->setDirty('available_elements', $lb_availableElementsDirty);
			$lo_contentTemplate->setDirty('assigned_content_areas', $lb_assignedContentAreasDirty);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->ContentTemplates->save($lo_contentTemplate)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_contentTemplate->id]);
				}

				$this->Flash->error(__('::edit_failed'));
			}
		}

		$this->set([
			'ao_contentTemplate' => $lo_contentTemplate,
			'aa_availableElements' => $this->availableElements,
			'ao_pageTemplates' => $this->getPageTemplates(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');
		$lo_contentTemplate = $this->ContentTemplates->find()->where(['id' => $li_id])->first();

		if ( ! $lo_contentTemplate) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->ContentTemplates->delete($lo_contentTemplate)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}
}
