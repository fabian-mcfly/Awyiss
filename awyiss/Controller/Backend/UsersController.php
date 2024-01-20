<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Awyiss\Routing\Router;
use Cake\Event\EventInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\Utility\Hash;
use Cake\Utility\Security;


//awyiss: $2y$10$B1IWA5ic5yFJCbxB7kvKD.hnfrA3M34LPtOH5y.zrK0b6PpAHj.Eu

/**
 * Users Controller
 *
 * @property \Awyiss\Model\Table\UsersTable $Users
 */
class UsersController extends Controller {
	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeFilter(EventInterface $ao_event): void {
		parent::beforeFilter($ao_event);

		$this->Authentication->allowUnauthenticated(['login', 'logout']);

		if (in_array($this->getRequest()->getParam('action'), ['login', 'logout'])) {
			$this->Categories->disable();
		}
	}


	/**
	 * Overview method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_users = $this->Users->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_users);

		$lo_users = $this->paginate($lo_users);

		$this->set([
			'ao_users' => $lo_users,
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

		$lo_user = $this->Users->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_user);
		}

		if (empty($lo_user->usergroups)) {
			$lo_user->usergroups = [];
		}

		$this->set([
			'ao_user' => $lo_user,
			'ao_usergroups' => $this->Users->Usergroups->find(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var User $lo_user */
		$lo_user = $this->Users->findById($ai_id)->find('translations')->contain(['Usergroups'])->first();
		if (!$lo_user) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_user, 'edit');
		}

		if (empty($lo_user->usergroups)) {
			$lo_user->usergroups = [];
		}

		$this->set([
			'ao_user' => $lo_user,
			'ao_usergroups' => $this->Users->Usergroups->find(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		$lo_user = $this->Users->findById($ai_id)->find('translations')->first();
		if (!$lo_user) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Users->delete($lo_user)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_user->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * Login method
	 *
	 * @return void|?Response
	 */
	public function login() {
		$lo_result = $this->Authentication->getResult();

		// If the user is logged in send them away.
		if ($lo_result->isValid()) {
			/** @var User $lo_user */
			if ($this->request->is('post')) {
				$lo_user = $this->Authentication->getIdentity()->getOriginalData();

				if ($lo_user instanceof User) {
					//Track lastLogin and reset the failed login attempts
					$lo_user->set([
						'failedAttempts' => 0,
						'lastLogin' => FrozenTime::now(),
					], ['guard' => false]);

					$this->Users->save($lo_user, ['audit' => ['skip' => true]]);
				}
				elseif ($lo_user instanceof UsersExternal) {
					$lo_usersExternal = $this->fetchTable('UsersExternal');
					//Track lastLogin
					$lo_user->set('lastLogin', FrozenTime::now());

					$lo_usersExternal->save($lo_user, ['audit' => ['skip' => true]]);
				}

				/** @var \Cake\Http\Session $lo_session */
				$lo_session = $this->request->getAttribute('session');
				$lo_session->write(LocaleMiddleware::getSessionIdentifier(), $this->request->getData('language_shortcode'));
			}

			$ls_redirectUri = $this->Authentication->getLoginRedirect() ?? Router::url([
				'_name' => Awyiss::REALM_BACKEND,
				'controller' => 'Dashboard',
				'action' => 'overview',
			]);


			return $this->redirect($ls_redirectUri);
		}

		if ($this->request->is('post') && !$lo_result->isValid()) {
			/** @var User $lo_user */
			$ls_username = $this->request->getData('username');
			if ($ls_username) {
				$lo_user = $this->Users->find()->where(['username' => $ls_username])->first();
				if ($lo_user) {
					$lo_user->set([
						'failedAttempts' => $lo_user->failedAttempts + 1,
						'lastLogin' => FrozenTime::now(),
					], ['guard' => false]);
					$this->Users->save($lo_user, ['audit' => ['skip' => true]]);
				}
			}

			$this->request = $this->request->withoutData('password');
			$this->Flash->error('Invalid username or password');

			//Do something to slow down the process
			password_hash(md5(Security::randomString()), PASSWORD_BCRYPT, ['cost' => 16]);
		}

		$this->viewBuilder()->setLayout('login');

		$this->set([
			'as_languageRealm' => Awyiss::REALM_BACKEND,
		]);
	}


	/**
	 * Logout method
	 *
	 * @return Response|null Redirects on logout
	 * @noinspection PhpUnused
	 */
	public function logout(): ?Response {
		$this->Authentication->logout();

		/** @var \Cake\Http\Session $lo_session */
		$lo_session = $this->getRequest()->getAttribute('session');
		$lo_session->destroy();


		return $this->redirect(Router::url([
			'_name' => Awyiss::REALM_BACKEND,
			'controller' => 'Users',
			'action' => 'login',
		]));
	}


	/**
	 * @param User $ao_user
	 * @param string $as_method
	 * @return void
	 */
	protected function save(User $ao_user, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Users->hasAttributes()) {
			$la_associated[] = $this->Users->getAttributesTable(true);
			$ao_user->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		if (empty($la_data['password'])) {
			unset($la_data['password']);
		}

		$la_associated['Usergroups'] = ['onlyIds' => true];

		$this->Users->patchEntity($ao_user, $la_data, ['associated' => $la_associated]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Users->save($ao_user)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					if ($ao_user->usergroups) {
						$la_usergroups = Hash::combine($ao_user->usergroups, '{n}.id', '{n}.label');
					}
					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user, otherwise the overview.
					 * Otherwise a redirect would show a site without the modified user, which could be a bit confusing.
					 */
					$this->Categories->verifySelection(null, $la_usergroups ?? []);

					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_user->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_user->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}
}
