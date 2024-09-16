<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * FormEntrie Controller
 *
 * @property \Awyiss\Model\Table\FormEntriesTable $FormEntries
 */
class FormEntriesController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'uriParam' => 'form-id',
	];
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'defaultSortableFields' => ['form_id'],
		'enabled' => true,
		'order' => [
			'created_on' => 'desc',
		],
	];

	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->FormEntries->find()->where($this->getOverviewWhere());
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

		if (is_numeric($this->Categories->getSelectedCategory())) {
			$lo_form = $this->fetchTable('Forms')->findById($this->Categories->getSelectedCategory())->first();
		}

		$lo_query = $this->getOverviewQuery();
		$lo_query->contain([
			'Forms',
		]);
		$lo_formEntries = $this->paginate($lo_query);

		$this->set([
			'formEntries' => $lo_formEntries,
			'form' => $lo_form ?? null,
			'attributes' => $this->FormEntries->getAttributes(),
		]);
	}


	/**
	 * @return \Cake\Http\Response|null|void
	 * @throws \Exception
	 */
	public function view() {
		$this->Authorization->ensure('delete');

		$li_id = $this->request->getParam('id');

		/** @var \Awyiss\Model\Entity\FormEntry $lo_formEntry */
		$lo_formEntry = $this->FormEntries->findById($li_id)->first();
		if (!$lo_formEntry) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		$ls_body = gzuncompress(base64_decode($lo_formEntry->body));

		$this->set([
			'formEntry' => $lo_formEntry,
			'body' => $ls_body,
			'subject' => $lo_formEntry->subject,
		]);
	}


	/**
	 * @return \Cake\Http\Response|null|void
	 * @throws \Exception
	 */
	public function viewConfirmation() {
		$this->Authorization->ensure('delete');

		$li_id = $this->request->getParam('id');

		/** @var \Awyiss\Model\Entity\FormEntry $lo_formEntry */
		$lo_formEntry = $this->FormEntries->findById($li_id)->first();
		if (!$lo_formEntry) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		$ls_body = gzuncompress(base64_decode($lo_formEntry->bodyConfirmation));

		$this->set([
			'formEntry' => $lo_formEntry,
			'body' => $ls_body,
			'subject' => $lo_formEntry->subjectConfirmation,
		]);

		$this->viewBuilder()->setTemplate('view');
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

		/** @var \Awyiss\Model\Entity\FormEntry $lo_formEntry */
		$lo_formEntry = $this->FormEntries->findById($id)->first();
		if (!$lo_formEntry) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->FormEntries->delete($lo_formEntry)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_formEntry->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}
}
