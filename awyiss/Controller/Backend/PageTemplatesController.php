<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\PageTemplate;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Routing\Router;


/**
 * PageTemplates Controller
 *
 * @property \Awyiss\Model\Table\PageTemplatesTable $PageTemplates
 */
class PageTemplatesController extends Controller {
	/**
	 * @inheritDoc
	 */
	public array $categorize = [
		'associationName' => 'PageRoles',
		'enabled' => TRUE,
	];


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview (): void {
		$this->Access->ensure('read');

		//$lo_pageTemplates = $this->Categories->filterQuery($this->PageTemplates->find('withAttributes'));
		$lo_pageTemplates = $this->PageTemplates->find('withAttributes')->where($this->getOverviewWhere());
		$lo_pageTemplates = $this->Categories->groupQuery($lo_pageTemplates);

		$this->set([
			'ao_pageTemplates' => $lo_pageTemplates,
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
		$this->Access->ensure('create');

		$lo_pageTemplate = $this->PageTemplates->newDefaultEntity();
		if ($this->request->is('post')) {
			$this->save($lo_pageTemplate);
		}
		else {
			$this->Categories->ensurePossibleCategorySelection($lo_pageTemplate);
		}

		$this->set([
			'ao_pageTemplate' => $lo_pageTemplate,
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
		$this->Access->ensure('update');

		/** @var PageTemplate $lo_pageTemplate */
		$lo_pageTemplate = $this->PageTemplates->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_pageTemplate) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_pageTemplate, 'edit');
		}
		else {
			$this->Categories->ensurePossibleCategorySelection($lo_pageTemplate);
		}

		$this->set([
			'ao_pageTemplate' => $lo_pageTemplate,
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
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var PageTemplate $lo_pageTemplate */
		$lo_pageTemplate = $this->PageTemplates->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_pageTemplate) {
			$this->Flash->error(__('::record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		if ($this->PageTemplates->delete($lo_pageTemplate)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param PageTemplate $ao_pageTemplate
	 * @param string $as_method
	 *
	 * @return void
	 */
	protected function save (PageTemplate $ao_pageTemplate, string $as_method = 'add'): void {
		$this->PageTemplates->patchEntity($ao_pageTemplate, $this->request->getData());

		$this->Categories->ensurePossibleCategorySelection($ao_pageTemplate);

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->PageTemplates->save($ao_pageTemplate)) {
				$this->Flash->success(__('::' . $as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_pageTemplate->id], TRUE), 302);
			}

			$this->Flash->error(__('::' . $as_method . '_failed'));
			$this->Flash->error(implode('<br>' . PHP_EOL, $ao_pageTemplate->getError('_general')));
		}
		else {
			$ao_pageTemplate->system_order = NULL;
		}
	}
}

