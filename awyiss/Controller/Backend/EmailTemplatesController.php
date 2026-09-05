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
		'defaultSortableFields' => ['usedForEmails', 'usedForConfirmationEmails'],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		/** @uses \Awyiss\Model\Table\EmailTemplatesTable::findWithUsages() */
		$query = $this->EmailTemplates->find('withUsages')->where($this->getOverviewWhere());
		$this->Search->filterQuery($query);

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
		$emailTemplates = $this->paginate($query);

		$this->set([
			'emailTemplates' => $emailTemplates,
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

		$emailTemplate = $this->EmailTemplates->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($emailTemplate);
		}

		$this->setViewVars($emailTemplate);
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
		 * @var \Awyiss\Model\Entity\EmailTemplate $emailTemplate
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$emailTemplate = $this->EmailTemplates
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->first()
		;
		if (!$emailTemplate) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($emailTemplate, 'edit');
		}

		$this->setViewVars($emailTemplate);
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

		/** @var \Awyiss\Model\Entity\EmailTemplate $emailTemplate */
		$emailTemplate = $this->EmailTemplates->findById($id)->first();
		if (!$emailTemplate) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->EmailTemplates->delete($emailTemplate)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($emailTemplate->getError('_general') as $error) {
					$this->Flash->error($error);
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

		$id = (int)$this->request->getParam('id');

		/** @var \Awyiss\Model\Entity\EmailTemplate $emailTemplate */
		$emailTemplate = $this->EmailTemplates->findById($id)->first();
		if (!$emailTemplate) {
			$this->Flash->error(__('record_not_found'));

			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		$view = $this->createView();
		// The view uses the prefix (Backend, in this case) to determine the correct view path
		// We need to set the correct view path manually
		$view->setRequest($this->getRequest()->withParam('prefix', 'Frontend'));

		$this->viewBuilder()->setClassName('Frontend');

		$view
			->setTemplatePath('Frontend/email')
			->setLayoutPath('email')
			->setLayout(str_replace('.twig', '', $emailTemplate->layout))
		;

		/** @var \Cake\View\HelperRegistry $helpersRegistry */
		$helperRegistry = $view->helpers();
		/** @var \Awyiss\View\Helper\AssetHelper $assetHelper */
		$assetHelper = $helperRegistry->get('Asset');
		$assetHelper->setRealm(Awyiss::REALM_FRONTEND);

		$view->set([
			'textHtml' => $emailTemplate->textHtml,
			'textPlain' => $emailTemplate->textPlain,
			'emailTemplate' => $emailTemplate,
		]);

		$body = $view->render($emailTemplate->fileName);

		$assetHelper->setRealm(Awyiss::REALM_BACKEND);

		$this->viewBuilder()->setClassName('Backend');

		$this->set([
			'emailTemplate' => $emailTemplate,
			'bodyHtml' => $body,
			'bodyText' => '<pre>' . $emailTemplate->textPlain . '</pre>',
		]);
	}


	/**
	 * @param EmailTemplate $emailTemplate
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(EmailTemplate $emailTemplate, string $method = 'add'): void {
		$associated = [];
		if ($this->EmailTemplates->hasAttributes()) {
			$associated[] = $this->EmailTemplates->getAttributesTableName(true);
			$emailTemplate->setAccess('attributes', true);
		}

		$this->EmailTemplates->patchEntity($emailTemplate, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->EmailTemplates->save($emailTemplate, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($emailTemplate),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $emailTemplate->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($emailTemplate->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\EmailTemplate $emailTemplate
	 * @return void
	 */
	protected function setViewVars(EmailTemplate $emailTemplate): void {
		$placeholders = [];

		$forms = $this
			->fetchTable('Forms')
			->find()
			->contain(['FormElements'])
			->toArray()
		;
		foreach ($forms as $form) {
			/** @var \Awyiss\Model\Entity\Form $form */
			foreach ($form->formElements as $formElement) {
				if (in_array($formElement->type, ['fieldset', 'hidden', 'submit'])) {
					continue;
				}
				$placeholders[ $form->label ][ $formElement->identifier ] = $formElement->label . ' ($' . $formElement->identifier . ')';
			}
		}

		$placeholders['data'] = __('placeholder_data') . ' ($data)';
		$placeholders['salutation'] = __d('Forms', 'salutation') . ' ($salutation)';

		$this->set([
			'emailTemplate' => $emailTemplate,
			'availableLayouts' => $this->EmailTemplates->getAvailableLayouts(),
			'availablePlaceholders' => $placeholders,
		]);
	}
}
