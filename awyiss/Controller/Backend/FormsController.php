<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Form;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * Forms Controller
 *
 * @property \Awyiss\Model\Table\FormsTable $Forms
 */
class FormsController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->Forms->find()->where($this->getOverviewWhere());
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();
		$lo_forms = $this->paginate($lo_query);

		$this->set([
			'forms' => $lo_forms,
			'attributes' => $this->Forms->getAttributes(),
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

		$lo_form = $this->Forms->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_form);
		}

		$lo_emailTemplates = $this->fetchTable('EmailTemplates')->find('active');

		$this->set([
			'form' => $lo_form,
			'emailTemplates' => $lo_emailTemplates,
		]);
	}


	/**
	 * Edit method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var Form $lo_form */
		$lo_form = $this->Forms->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_form) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_form, 'edit');
		}

		$lo_emailTemplates = $this->fetchTable('EmailTemplates')->find('active')->orderByAsc('title');

		$this->set([
			'form' => $lo_form,
			'emailTemplates' => $lo_emailTemplates,
		]);
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

		/** @var Form $lo_form */
		$lo_form = $this->Forms->findById($id)->first();
		if (!$lo_form) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Forms->delete($lo_form)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_form->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Form $form
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(Form $form, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Forms->hasAttributes()) {
			$la_associated[] = $this->Forms->getAttributesTableName(true);
			$form->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();
		$la_data = $this->formatCcBcc($la_data, 'cc');
		$la_data = $this->formatCcBcc($la_data, 'bcc');

		$this->Forms->patchEntity($form, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Forms->save($form, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($form),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $form->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($form->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * @param array $data
	 * @param string $key
	 * @return array
	 */
	protected function formatCcBcc(array $data, string $key): array {
		$la_data = $data;

		if (empty($la_data[ $key ])) {
			unset($la_data[ $key ]);

			return $la_data;
		}

		$la_options = [];

		foreach (array_values((array)$la_data[ $key ]) as $lx_value) {
			if (empty($lx_value['email'])) {
				continue;
			}

			$la_options[] = [
				'email' => $lx_value['email'],
				'name' => $lx_value['name'],
			];
		}

		$la_data[ $key ] = $la_options;

		// Update the request data
		$lo_request = $this->request->withData($key, $la_options);
		$this->setRequest($lo_request);

		return $la_data;
	}
}
