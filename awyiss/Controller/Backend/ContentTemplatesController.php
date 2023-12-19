<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\ContentTemplate;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Routing\Router;


/**
 * ContentTemplates Controller
 *
 * @property \Awyiss\Model\Table\ContentTemplatesTable $ContentTemplates
 */
class ContentTemplatesController extends Controller {
	/**
	 * @var array<int, string>
	 */
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
	 * @throws \Exception
	 */
	public function overview (): void {
		$this->Authorization->ensure('read');

		$lo_contentTemplates = $this->ContentTemplates->find('withAttributes')->where($this->getOverviewWhere());

		$this->set([
			'ao_contentTemplates' => $lo_contentTemplates,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function add (): void {
		$this->Authorization->ensure('create');

		$lo_contentTemplate = $this->ContentTemplates->newDefaultEntity();
		if ($this->request->is('post')) {
			$this->save($lo_contentTemplate);
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
	 * @return void|?\Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function edit () {
		$this->Authorization->ensure('update');

		/** @var ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->ContentTemplates->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_contentTemplate) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_contentTemplate, 'edit');
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
	 * @return \Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var ContentTemplate $lo_contentTemplate */
		$lo_contentTemplate = $this->ContentTemplates->findById((int) $this->request->getParam('id'))->first();
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


	/**
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function getPageTemplates (): ResultSetInterface {
		return $this->fetchTable('PageTemplates')->find('withAttributes')->all();
	}


	/**
	 * @param ContentTemplate $ao_contentTemplate
	 * @param string $as_method
	 *
	 * @return void
	 */
	protected function save (ContentTemplate $ao_contentTemplate, string $as_method = 'add'): void {
		$la_requestData = $this->request->getData() + ['assigned_template_positions' => []];
		if (isset($la_requestData['available_elements'])) {
			$la_requestData['available_elements'] = array_filter($la_requestData['available_elements'], function($aa_element) {
				return ! is_numeric($aa_element['name']);
			});
		}
		$this->ContentTemplates->patchEntity($ao_contentTemplate, $la_requestData);

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->ContentTemplates->save($ao_contentTemplate)) {
				$this->Flash->success(__('::' . $as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_contentTemplate->id], TRUE), 302);
			}

			$this->Flash->error(__('::' . $as_method . '_failed'));
			$this->Flash->error(implode('<br>' . PHP_EOL, $ao_contentTemplate->getError('_general')));
		}
	}
}
