<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Form\FormConditionalRecipients;
use Awyiss\Middleware\LocaleMiddleware;
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
		$query = $this->Forms->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($query);
		$query->contain(['EmailTemplates']);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();
		$forms = $this->paginate($query);

		$this->set([
			'forms' => $forms,
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

		$form = $this->Forms->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($form);
		}

		$this->setViewVars($form);
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

		/**
		 * @var \Awyiss\Model\Entity\Form $form
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$form = $this->Forms
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->contain(['FormConditionalRecipients'])
			->first()
		;
		if (!$form) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($form, 'edit');
		}

		$this->setViewVars($form);
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

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->Forms->findById($id)->first();
		if (!$form) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Forms->delete($form)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($form->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
		$associated = [
			'FormConditionalRecipients',
		];
		if ($this->Forms->hasAttributes()) {
			$associated[] = $this->Forms->getAttributesTableName(true);
			$form->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();
		$requestData = $this->formatCcBcc($requestData, 'cc');
		$requestData = $this->formatCcBcc($requestData, 'bcc');
		$requestData = $this->formatConditionalRecipients($requestData);

		if (isset($requestData['formTemplate'])) {
			$form->setAccess('formElements', true);
			$requestData = $this->buildElementsFromTemplate($requestData['formTemplate'], $requestData);
			$associated['FormElements'] = [
				'accessibleFields' => ['childFormElements' => true],
				'associated' => [
					'ChildFormElements' => [
						'validate' => false,
					],
				],
				'validate' => false,
			];
		}

		$this->Forms->patchEntity($form, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->Forms->save($form, ['asCopy' => $saveAsCopy])) {
				/** @noinspection DuplicatedCode */
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($form),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $form->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($form->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * @param array $data
	 * @param string $key
	 * @return array
	 */
	protected function formatCcBcc(array $data, string $key): array {
		if (empty($data[ $key ])) {
			unset($data[ $key ]);

			return $data;
		}

		$options = [];

		/** @noinspection PhpRedundantArrayCallInForeachIteratedValueInspection */
		foreach (array_values((array)$data[ $key ]) as $value) {
			if (empty($value['email'])) {
				continue;
			}

			$options[] = [
				'email' => $value['email'],
				'name' => $value['name'] ?? '',
			];
		}

		$data[ $key ] = $options;

		// Update the request data
		$request = $this->request->withData($key, $options);
		$this->setRequest($request);

		return $data;
	}


	/**
	 * @param array $data
	 * @return array|null
	 */
	protected function formatConditionalRecipients(array $data): ?array {
		if (!isset($data['conditionalRecipients']) || !is_array($data['conditionalRecipients'])) {
			return $data;
		}

		$systemOrder = 1;
		foreach ($data['conditionalRecipients'] as $key => &$conditionalRecipient) {
			if (empty($conditionalRecipient['type'])) {
				unset($data['conditionalRecipients'][ $key ]);
				continue;
			}

			$conditionalRecipient['systemOrder'] = $systemOrder;
			$systemOrder++;
		}
		unset($conditionalRecipient);

		// Update the request data
		$request = $this->request->withData('conditionalRecipients', $data['conditionalRecipients']);
		$this->setRequest($request);

		return $data;
	}


	/**
	 * @param \Awyiss\Model\Entity\Form $form
	 * @return void
	 * @throws \Exception
	 */
	protected function setViewVars(Form $form): void {
		/** @uses \Awyiss\Model\Table::findActive() */
		$emailTemplates = $this
			->fetchTable('EmailTemplates')
			->find('active')
			->orderByAsc('title')
		;

		$formConditionalRecipientTypes = [
			'elementIdentifier',
			'currentPage',
		];
		$formConditionalRecipientOperators = ComparisonOperator::cases();

		$this->Forms->loadInto($form, ['FormElements' => ['finder' => 'threaded']]);
		if ($form->formElements) {
			$formElements = collection($form->formElements)->listNested()->toList();
			$form->formElements = [];

			foreach ($formElements as $formElement) {
				if (!in_array($formElement->type, ['fieldset', 'hidden', 'freeText', 'submit'])) {
					$form->formElements[] = $formElement;
				}
			}
		}

		$pageProperties = $this
			->fetchTable('Pages')
			->getSchema()
			->columns()
		;
		$pageProperties = array_combine($pageProperties, $pageProperties);
		$pageProperties = array_diff($pageProperties, [
			'metaTitle',
			'metaDescription',
			'robotsFollow',
			'robotsIndex',
			'deleted',
			'createdBy',
			'createdOn',
			'changedBy',
			'changedOn',
			'deletedBy',
			'deletedOn',
		]);
		foreach ($pageProperties as $value) {
			$pageProperties[ $value ] = __d('Pages', Inflector::underscore($value)) . ' (' . $value . ')';
		}

		/** @var array<\Awyiss\Model\Entity\PageRole> $pageRoles */
		$pageRoles = $this
			->fetchTable('PageRoles')
			->find()
			->all()
			->indexBy(function (PageRole $pageRole) {
				return Inflector::camelize(Inflector::pluralize($pageRole->identifier));
			})
			->toArray()
		;

		$attributes = $this
			->fetchTable('Attributes')
			->find()
			->where(['scope IN' => array_keys($pageRoles)])
			->toArray()
		;
		/** @var \Awyiss\Model\Entity\Attribute $attribute */
		foreach ($attributes as $attribute) {
			$pageProperties[ $pageRoles[ $attribute->scope ]->title ][ $attribute->identifier ] = $attribute->label
				. ' (' . $attribute->identifier . ')'
			;
		}

		$conditionalRecipientsStrategies = [
			FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
			FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST,
			FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL,
		];

		$this->set([
			'form' => $form,
			'emailTemplates' => $emailTemplates,
			'formConditionalRecipientTypes' => $formConditionalRecipientTypes,
			'formConditionalRecipientOperators' => $formConditionalRecipientOperators,
			'pageProperties' => $pageProperties,
			'conditionalRecipientsStrategies' => $conditionalRecipientsStrategies,
			'formTemplates' => $form->isNew() ? $this->Forms->getFormTemplates() : [],
			'transportProfiles' => $this->Forms->getTransportProfiles(),
		]);
	}


	/**
	 * @param string $formTemplate
	 * @param array $data
	 * @return array
	 */
	protected function buildElementsFromTemplate(string $formTemplate, array $data): array {
		/** @var class-string<\Awyiss\Utility\Form\Templates\FormTemplateInterface>|null $class */
		$class = App::className($formTemplate, 'Utility\Form\Templates');

		if (!$class) {
			return $data;
		}

		$formElements = $class::getElements(LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND));

		return array_merge($data, [
			'formElements' => $formElements,
		]);
	}
}
