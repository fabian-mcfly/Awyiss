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
	 * @var array<string, string>
	 */
	protected array $csvEncoding = [
		'csv' => [
			'csvEncoding' => 'UTF-8',
			'dataEncoding' => 'UTF-8',
		],
		'csvExcel' => [
			'csvEncoding' => 'ISO-8859-1',
			'dataEncoding' => 'UTF-8',
		],
	];
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'defaultSortableFields' => ['formId'],
		'enabled' => true,
		'order' => [
			'createdOn' => 'desc',
		],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->FormEntries->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query, null, !$this->paginate['enabled']);
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		if (is_numeric($this->Categories->getSelectedCategory())) {
			$form = $this
				->fetchTable('Forms')
				->findById($this->Categories->getSelectedCategory())
				->first()
			;
		}

		$query = $this
			->getOverviewQuery()
			->contain([
				'Forms',
				'Languages',
			])
		;
		$formEntries = $this->paginate($query);

		$this->set([
			'formEntries' => $formEntries,
			'form' => $form ?? null,
			'attributes' => $this->FormEntries->getAttributes(),
		]);
	}


	/**
	 * @return \Cake\Http\Response|null
	 * @throws \Exception
	 */
	public function export(): ?Response {
		$this->Authorization->ensure('read');

		$formId = $this->request->getData('exportFormId');
		$languages = $this->request->getData('exportLanguages') ?? [];
		$format = $this->request->getData('exportFormat');

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this
			->fetchTable('Forms')
			->findById($formId)
			->contain(['FormElements'])
			->first()
		;

		if (!$form) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		$form->initialize($this->viewBuilder()->build());

		$query = $this->FormEntries
			->find()
			->where([
				'OR' => [
					'languageShortcode IS' => null,
					'languageShortcode IN' => $languages,
				],
				'formId' => $formId,
			])
		;

		$headlines = [];
		if (in_array($format, ['csv', 'csvExcel'])) {
			/** @var \Awyiss\Model\Entity\FormElement $formElement */
			foreach ($form->formElements->listNested() as $formElement) {
				if (in_array($formElement->type, ['fieldset', 'freeText', 'submit'])) {
					continue;
				}

				$headlines[ $formElement->identifier ] = $formElement->title;
			}
		}

		$entries = [];
		/** @var \Awyiss\Model\Entity\FormEntry $entry */
		foreach ($query->all() as $entry) {
			$entry->data = json_decode(gzuncompress(base64_decode($entry->data)), true) ?: [];

			if (empty($entry->data) || !is_array($entry->data)) {
				continue;
			}

			// Filter out all keys that start with an underscore
			$entry->data = array_filter($entry->data, function (mixed $key): bool {
				return !str_starts_with((string)$key, '_');
			}, ARRAY_FILTER_USE_KEY);

			if ($format === 'xml') {
				$this->cleanFieldNamesForXml($entry->data);
			}
			elseif (in_array($format, ['csv', 'csvExcel'])) {
				$this->cleanFieldsForCsv($entry->data, $headlines);
				$headlines['_created_on'] = __('createdOn');
			}

			$entries[ $entry->id ] = $entry->data;
			$entries[ $entry->id ]['_created_on'] = $entry->createdOn->i18nFormat('yyyy-MM-dd HH:mm:ss');
		}

		if ($format === 'xml') {
			$this
				->viewBuilder()
				->setClassName(XmlView::class)
				->setOption('rootNode', 'entries')
				->setOption('serialize', ['entry'])
			;
			$this->set('entry', $entries);

			return $this->render();
		}

		if ($format === 'json') {
			$this
				->viewBuilder()
				->setClassName(JsonView::class)
				->setOption('serialize', 'entries')
			;
			$this->set('entries', $entries);

			return $this->render();
		}

		$now = date('YmdHis');
		$this->setResponse($this->getResponse()->withDownload(sprintf('form_entries_%s_%s.csv', $form->identifier, $now)));
		$this
			->viewBuilder()
			->setClassName('CsvView.Csv')
			->setOptions([
				'serialize' => 'entries',
				'header' => $headlines,
				'csvEncoding' => $this->csvEncoding[ $format ]['csvEncoding'],
				'dataEncoding' => $this->csvEncoding[ $format ]['dataEncoding'],
				'bom' => $format === 'csvExcel',
				'setSeparator' => $format === 'csvExcel' ? ',' : false,
			])
		;
		$this->set('entries', $entries);

		return $this->render();
	}


	/**
	 * @return \Cake\Http\Response|null|void
	 * @throws \Exception
	 */
	public function view() {
		$this->Authorization->ensure('read');

		$id = (int)$this->request->getParam('id');

		/** @var \Awyiss\Model\Entity\FormEntry $formEntry */
		$formEntry = $this->FormEntries->findById($id)->first();
		if (!$formEntry) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		$body = gzuncompress(base64_decode($formEntry->body));

		$this->set([
			'formEntry' => $formEntry,
			'body' => $body,
			'subject' => $formEntry->subject,
		]);
	}


	/**
	 * @return \Cake\Http\Response|null|void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function viewConfirmation() {
		$this->Authorization->ensure('read');

		$id = $this->request->getParam('id');

		/** @var \Awyiss\Model\Entity\FormEntry $formEntry */
		$formEntry = $this->FormEntries->findById($id)->first();
		if (!$formEntry) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		$body = gzuncompress(base64_decode($formEntry->bodyConfirmation));

		$this->set([
			'formEntry' => $formEntry,
			'body' => $body,
			'subject' => $formEntry->subjectConfirmation,
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

		/** @var \Awyiss\Model\Entity\FormEntry $formEntry */
		$formEntry = $this->FormEntries->findById($id)->first();
		if (!$formEntry) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->FormEntries->delete($formEntry)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($formEntry->getError('_general') as $error) {
					$this->Flash->error($error);
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
		$cleanedData = [];
		foreach ($data as $key => $value) {
			// If the key does not start with a letter or underscore, prepend an underscore
			if (!preg_match('/^[a-zA-Z_]/', (string)$key)) {
				$key = 'field_' . $key;
			}

			if (str_starts_with(strtolower((string)$key), 'xml')) {
				$key = 'field_' . $key;
			}

			if (is_array($value)) {
				if (array_is_list($value)) {
					$value = implode(',', $value);
				}
				else {
					$this->cleanFieldNamesForXml($value);
				}
			}

			$cleanedData[ $key ] = $value;
		}

		$data = $cleanedData;
	}


	/**
	 * Concatenates array fields into a string for CSV export and skips fields not in headlines
	 *
	 * @param array $data
	 * @param array $headlines
	 * @return void
	 */
	protected function cleanFieldsForCsv(array &$data, array $headlines): void {
		$cleanedData = [];
		foreach (array_keys($headlines) as $key) {
			if (!array_key_exists($key, $data)) {
				continue;
			}

			$value = $data[ $key ];

			if (is_array($value)) {
				$value = implode(',', Hash::flatten($value));
			}

			// If value is a string that looks like a number with leading zeros, force Excel to treat it as text:
			if (is_string($value) && preg_match('/^0[0-9]+$/', $value)) {
				$safeValue = str_replace('"', '""', $value);
				$value = '="' . $safeValue . '"';
			}

			$cleanedData[ $key ] = $value;
		}

		$data = $cleanedData;
	}
}
