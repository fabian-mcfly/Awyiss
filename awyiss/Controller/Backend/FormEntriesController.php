<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use Cake\View\JsonView;
use Cake\View\XmlView;


/**
 * FormEntries Controller
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
		$this->Search->filterQuery($lo_query);
		$lo_query->contain(['Languages']);

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
	 * @return \Cake\Http\Response|null
	 * @throws \Exception
	 */
	public function export(): ?Response {
		$this->Authorization->ensure('read');

		$li_formId = $this->request->getData('export_form_id');
		$la_languages = $this->request->getData('export_languages') ?? [];
		$ls_format = $this->request->getData('export_format');

		/** @var \Awyiss\Model\Entity\Form $lo_form */
		$lo_form = $this->fetchTable('Forms')->findById($li_formId)->contain(['FormElements'])->first();

		if (!$lo_form) {
			$this->Flash->error(__('record_not_found'));
			return $this->redirect(['action' => 'overview']);
		}

		$lo_form->initialize($this->viewBuilder()->build());

		$lo_query = $this->FormEntries->find()->where([
			'OR' => [
				'language_shortcode IS' => null,
				'language_shortcode IN' => $la_languages,
			],
			'form_id' => $li_formId,
		]);

		$la_headlines = [];
		if (in_array($ls_format, ['csv', 'csv_excel'])) {
			/** @var \Awyiss\Model\Entity\FormElement $lo_formElement */
			foreach ($lo_form->formElements->listNested() as $lo_formElement) {
				if (in_array($lo_formElement->type, ['fieldset', 'free_text', 'submit'])) {
					continue;
				}

				$la_headlines[ $lo_formElement->identifier ] = $lo_formElement->title;
			}
		}

		$lo_entries = $lo_query->all();
		$la_entries = [];
		/** @var \Awyiss\Model\Entity\FormEntry $lo_entry */
		foreach ($lo_entries as $lo_entry) {
			$lo_entry->data = json_decode(gzuncompress(base64_decode($lo_entry->data)), true) ?: [];

			if (empty($lo_entry->data) || !is_array($lo_entry->data)) {
				continue;
			}

			// Filter out all keys that start with an underscore
			$lo_entry->data = array_filter($lo_entry->data, function (mixed $key): bool {
				return !str_starts_with((string)$key, '_');
			}, ARRAY_FILTER_USE_KEY);

			if ($ls_format === 'xml') {
				$this->cleanFieldNamesForXml($lo_entry->data);
			}
			elseif (in_array($ls_format, ['csv', 'csv_excel'])) {
				$this->cleanFieldsForCsv($lo_entry->data, $la_headlines);
				$la_headlines['_created_on'] = __('created_on');
			}

			$la_entries[ $lo_entry->id ] = $lo_entry->data;
			$la_entries[ $lo_entry->id ]['_created_on'] = $lo_entry->createdOn->i18nFormat('yyyy-MM-dd HH:mm:ss');
		}

		if ($ls_format === 'xml') {
			$this->viewBuilder()
				->setClassName(XmlView::class)
				->setOption('rootNode', 'entries')
				->setOption('serialize', ['entry']);
			$this->set('entry', $la_entries);

			return $this->render();
		}

		if ($ls_format === 'json') {
			$this->viewBuilder()
				->setClassName(JsonView::class)
				->setOption('serialize', 'entries');
			$this->set('entries', $la_entries);

			return $this->render();
		}

		$ls_now = date('YmdHis');
		$this->setResponse($this->getResponse()->withDownload(sprintf('form_entries_%s_%s.csv', $lo_form->identifier, $ls_now)));
		$this->viewBuilder()
			->setClassName('CsvView.Csv')
			->setOptions([
				'serialize' => 'entries',
				'header' => $la_headlines,
				'dataEncoding' => 'UTF-8',
				'csvEncoding' => 'ISO-8859-1',
				'bom' => $ls_format === 'csv_excel',
				'setSeparator' => $ls_format === 'csv_excel' ? ',' : false,
			]);
		$this->set('entries', $la_entries);

		return $this->render();
	}


	/**
	 * @return \Cake\Http\Response|null|void
	 * @throws \Exception
	 */
	public function view() {
		$this->Authorization->ensure('read');

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
		$this->Authorization->ensure('read');

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
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_formEntry->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Prepends `field_` to field names that are not valid XML element names
	 *
	 * @param array $data
	 * @return void
	 */
	protected function cleanFieldNamesForXml(array &$data): void {
		$la_cleanedData = [];
		foreach ($data as $ls_key => $lx_value) {
			// If the key does not start with a letter or underscore, prepend an underscore
			if (!preg_match('/^[a-zA-Z_]/', (string)$ls_key)) {
				$ls_key = 'field_' . $ls_key;
			}

			if (str_starts_with(strtolower((string)$ls_key), 'xml')) {
				$ls_key = 'field_' . $ls_key;
			}

			if (is_array($lx_value)) {
				if (array_is_list($lx_value)) {
					$lx_value = implode(',', $lx_value);
				}
				else {
					$this->cleanFieldNamesForXml($lx_value);
				}
			}

			$la_cleanedData[ $ls_key ] = $lx_value;
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		$data = $la_cleanedData;
	}


	/**
	 * Concatenates array fields into a string for CSV export and skips fields not in headlines
	 *
	 * @param array $data
	 * @param array $headlines
	 * @return void
	 */
	protected function cleanFieldsForCsv(array &$data, array $headlines): void {
		$la_cleanedData = [];
		foreach (array_keys($headlines) as $ls_key) {
			if (!array_key_exists($ls_key, $data)) {
				continue;
			}

			$lx_value = $data[ $ls_key ];

			if (is_array($lx_value)) {
				$lx_value = implode(',', Hash::flatten($lx_value));
			}

			// If value is a string that looks like a number with leading zeros, force Excel to treat it as text:
			if (is_string($lx_value) && preg_match('/^0[0-9]+$/', $lx_value)) {
				$ls_safeValue = str_replace('"', '""', $lx_value);
				$lx_value = '="' . $ls_safeValue . '"';
			}

			$la_cleanedData[ $ls_key ] = $lx_value;
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		$data = $la_cleanedData;
	}
}
