<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\EmailTemplate;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * EmailTemplates Controller
 *
 * @property \Awyiss\Model\Table\EmailTemplatesTable $EmailTemplates
 */
class EmailTemplatesController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
		'defaultSortableFields' => ['used_for_emails', 'used_for_confirmation_emails'],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->EmailTemplates->find('withUsages')->where($this->getOverviewWhere());
		$this->Search->filterQuery($lo_query);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();
		$lo_emailTemplates = $this->paginate($lo_query);

		$this->set([
			'emailTemplates' => $lo_emailTemplates,
			'attributes' => $this->EmailTemplates->getAttributes(),
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

		$lo_emailTemplate = $this->EmailTemplates->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_emailTemplate);
		}

		$this->setViewVars($lo_emailTemplate);
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

		/** @var EmailTemplate $lo_emailTemplate */
		$lo_emailTemplate = $this->EmailTemplates->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_emailTemplate) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_emailTemplate, 'edit');
		}

		$this->setViewVars($lo_emailTemplate);
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

		/** @var EmailTemplate $lo_emailTemplate */
		$lo_emailTemplate = $this->EmailTemplates->findById($id)->first();
		if (!$lo_emailTemplate) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->EmailTemplates->delete($lo_emailTemplate)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_emailTemplate->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	#[NoDirectAccess]
	public function preview(): void {
		$this->Authorization->ensure('read');

		$li_id = (int)$this->request->getParam('id');

		/** @var EmailTemplate $lo_emailTemplate */
		$lo_emailTemplate = $this->EmailTemplates->findById($li_id)->first();
		if (!$lo_emailTemplate) {
			$this->Flash->error(__('record_not_found'));

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		$lo_view = $this->createView();
		// The view uses the prefix (Backend, in this case) to determine the correct view path
		// We need to set the correct view path manually
		$lo_view->setRequest($this->getRequest()->withParam('prefix', 'Frontend'));

		$this->viewBuilder()->setClassName('Frontend');

		$lo_view
		->setTemplatePath('Frontend/email')
		->setLayoutPath('email')
		->setLayout(str_replace('.twig', '', $lo_emailTemplate->layout));

		/** @var \Cake\View\HelperRegistry $lo_helpersRegistry */
		$lo_helperRegistry = $lo_view->helpers();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_helperRegistry->get('Asset')->setRealm(Awyiss::REALM_FRONTEND);

		$lo_view->set([
			'textHtml' => $lo_emailTemplate->textHtml,
			'textPlain' => $lo_emailTemplate->textPlain,
			'emailTemplate' => $lo_emailTemplate,
		]);

		$ls_body = $lo_view->render($lo_emailTemplate->fileName);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_helperRegistry->get('Asset')->setRealm(Awyiss::REALM_BACKEND);

		$this->viewBuilder()->setClassName('Backend');

		$this->set([
			'emailTemplate' => $lo_emailTemplate,
			'bodyHtml' => $ls_body,
			'bodyText' => '<pre>' . $lo_emailTemplate->textPlain . '</pre>',
		]);
	}


	/**
	 * @param EmailTemplate $emailTemplate
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(EmailTemplate $emailTemplate, string $method = 'add'): void {
		$la_associated = [];
		if ($this->EmailTemplates->hasAttributes()) {
			$la_associated[] = $this->EmailTemplates->getAttributesTableName(true);
			$emailTemplate->setAccess('attributes', true);
		}

		$this->EmailTemplates->patchEntity($emailTemplate, $this->request->getData(), [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->EmailTemplates->save($emailTemplate, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($emailTemplate),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $emailTemplate->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($emailTemplate->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\EmailTemplate $emailTemplate
	 * @return void
	 */
	protected function setViewVars(EmailTemplate $emailTemplate): void {
		$la_placeholders = [];

		$la_forms = $this->fetchTable('Forms')->find()->contain(['FormElements'])->toArray();
		foreach ($la_forms as $lo_form) {
			/** @var \Awyiss\Model\Entity\Form $lo_form */
			foreach ($lo_form->formElements as $lo_formElement) {
				if (in_array($lo_formElement->type, ['fieldset', 'hidden', 'submit'])) {
					continue;
				}
				$la_placeholders[ $lo_form->label ][ $lo_formElement->identifier ] = $lo_formElement->label . ' ($' . $lo_formElement->identifier . ')';
			}
		}

		$la_placeholders['data'] = __('placeholder_data') . ' ($data)';
		$la_placeholders['salutation'] = __d('forms', 'salutation') . ' ($salutation)';

		$this->set([
			'emailTemplate' => $emailTemplate,
			'availableLayouts' => $this->EmailTemplates->getAvailableLayouts(),
			'availablePlaceholders' => $la_placeholders,
		]);
	}
}
