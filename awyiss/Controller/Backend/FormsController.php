<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Form\FormConditionalRecipients;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
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

		$this->setViewVars($lo_form);
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
		$lo_form = $this->Forms->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->contain(['FormConditionalRecipients'])
			->first();
		if (!$lo_form) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_form, 'edit');
		}

		$this->setViewVars($lo_form);
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
		$la_associated = [
			'FormConditionalRecipients',
		];
		if ($this->Forms->hasAttributes()) {
			$la_associated[] = $this->Forms->getAttributesTableName(true);
			$form->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();
		$la_data = $this->formatCcBcc($la_data, 'cc');
		$la_data = $this->formatCcBcc($la_data, 'bcc');
		$la_data = $this->formatConditionalRecipients($la_data);

		$this->Forms->patchEntity($form, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Forms->save($form, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($form),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $form->id], true), 302);
			}

			$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
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

		/** @noinspection PhpRedundantArrayCallInForeachIteratedValueInspection */
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


	/**
	 * @param array $data
	 * @return array|null
	 */
	protected function formatConditionalRecipients(array $data): ?array {
		if (!isset($data['form_conditional_recipients']) || !is_array($data['form_conditional_recipients'])) {
			return $data;
		}

		$la_data = $data;
		$li_systemOrder = 1;
		foreach ($la_data['form_conditional_recipients'] as $ls_key => &$la_conditionalRecipient) {
			if (empty($la_conditionalRecipient['type'])) {
				unset($la_data['form_conditional_recipients'][ $ls_key ]);
				continue;
			}

			$la_conditionalRecipient['system_order'] = $li_systemOrder;
			$li_systemOrder++;
		}
		unset($la_conditionalRecipient);

		// Update the request data
		$lo_request = $this->request->withData('form_conditional_recipients', $la_data['form_conditional_recipients']);
		$this->setRequest($lo_request);

		return $la_data;
	}


	/**
	 * @param \Awyiss\Model\Entity\Form $form
	 * @return void
	 */
	protected function setViewVars(Form $form): void {
		$lo_emailTemplates = $this->fetchTable('EmailTemplates')->find('active')->orderByAsc('title');

		$la_formConditionalRecipientTypes = [
			'element_identifier',
			'current_page',
		];
		$la_formConditionalRecipientOperators = ComparisonOperator::cases();

		$this->Forms->loadInto($form, ['FormElements' => ['finder' => 'threaded']]);
		if ($form->formElements) {
			$la_formElements = collection($form->formElements)->listNested()->toList();
			$form->formElements = [];

			foreach ($la_formElements as $lo_formElement) {
				if (!in_array($lo_formElement->type, ['fieldset', 'hidden', 'free_text', 'submit'])) {
					$form->formElements[] = $lo_formElement;
				}
			}
		}

		$la_pageProperties = $this->fetchTable('Pages')->getSchema()->columns();
		$la_pageProperties = array_combine($la_pageProperties, $la_pageProperties);
		$la_pageProperties = array_diff($la_pageProperties, ['meta_title', 'meta_description', 'robots_follow', 'robots_index', 'deleted', 'created_by', 'created_on', 'changed_by', 'changed_on', 'deleted_by', 'deleted_on']);
		foreach ($la_pageProperties as $ls_value) {
			$la_pageProperties[ $ls_value ] = __d('pages', $ls_value) . ' (' . $ls_value . ')';
		}

		/** @var array<\Awyiss\Model\Entity\PageRole> $la_pageRoles */
		$la_pageRoles = $this->fetchTable('PageRoles')->find()->all()->indexBy(function (PageRole $pageRole) {
			return Inflector::pluralize($pageRole->identifier);
		})->toArray();

		$la_attributes = $this->fetchTable('Attributes')->find()->where(['scope IN' => array_keys($la_pageRoles)])->toArray();
		/** @var \Awyiss\Model\Entity\Attribute $lo_attribute */
		foreach ($la_attributes as $lo_attribute) {
			$la_pageProperties[$la_pageRoles[ $lo_attribute->scope ]->title ][ $lo_attribute->identifier ] = $lo_attribute->label . ' (' . $lo_attribute->identifier . ')';
		}

		$la_conditionalRecipientsStrategies = [
			FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
			FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST,
			FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL,
		];

		$this->set([
			'form' => $form,
			'emailTemplates' => $lo_emailTemplates,
			'formConditionalRecipientTypes' => $la_formConditionalRecipientTypes,
			'formConditionalRecipientOperators' => $la_formConditionalRecipientOperators,
			'pageProperties' => $la_pageProperties,
			'conditionalRecipientsStrategies' => $la_conditionalRecipientsStrategies,
		]);
	}
}
