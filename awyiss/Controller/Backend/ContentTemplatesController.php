<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;


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
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensure('read');

		$lo_contentTemplates = $this->ContentTemplates->find('withAttributes')->where($this->getOverviewWhere());

		$this->set([
			'ao_contentTemplates' => $lo_contentTemplates,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_contentTemplate = $this->ContentTemplates->newDefaultEntity();
		if ($this->request->is('post')) {
			$la_requestData = $this->request->getData() + ['assigned_template_positions' => []];
			if (isset($la_requestData['available_elements'])) {
				$la_requestData['available_elements'] = array_filter($la_requestData['available_elements'], function($aa_element) {
					return ! is_numeric($aa_element['name']);
				});
			}
			$this->ContentTemplates->patchEntity($lo_contentTemplate, $la_requestData);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->ContentTemplates->save($lo_contentTemplate)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_contentTemplate->id]);
				}

				$this->Flash->error(__('::add_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_contentTemplate->getError('_general')));
			}
		}

		$this->set([
			'ao_contentTemplate' => $lo_contentTemplate,
			'aa_availableElements' => $this->availableElements,
			'ao_pageTemplates' => $this->getPageTemplates(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		try {
			$li_id = $this->request->getParam('id');
			/** @var \Awyiss\Model\Entity\ContentTemplate $lo_contentTemplate */
			$lo_contentTemplate = $this->ContentTemplates->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ( ! $lo_contentTemplate) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$la_requestData = $this->request->getData() + ['assigned_template_positions' => []];
			if (isset($la_requestData['available_elements'])) {
				$la_requestData['available_elements'] = array_filter($la_requestData['available_elements'], function($aa_element) {
					return ! is_numeric($aa_element['name']);
				});
			}
			$this->ContentTemplates->patchEntity($lo_contentTemplate, $la_requestData);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->ContentTemplates->save($lo_contentTemplate)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_contentTemplate->id]);
				}

				$this->Flash->error(__('::edit_failed'));
				$this->Flash->error(implode('<br>' . PHP_EOL, $lo_contentTemplate->getError('_general')));
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
	 *
	 * @throws \Exception
	 *
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		try {
			$li_id = $this->request->getParam('id');
			/** @var \Awyiss\Model\Entity\ContentTemplate $lo_contentTemplate */
			$lo_contentTemplate = $this->ContentTemplates->get($li_id);
		}
		catch (RecordNotFoundException|InvalidPrimaryKeyException) {
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


	protected function getPageTemplates (): \Cake\ORM\Query {
		return $this->getTableLocator()->get('PageTemplates')->find('withAttributes');
	}
}
