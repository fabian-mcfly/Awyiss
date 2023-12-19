<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Awyiss\Model\Table\UsersTable;
use Cake\Event\EventInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Http\Session;
use Cake\I18n\FrozenTime;
use Awyiss\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Security;


//awyiss: $2y$10$B1IWA5ic5yFJCbxB7kvKD.hnfrA3M34LPtOH5y.zrK0b6PpAHj.Eu


/**
 * Users Controller
 *
 * @property UsersTable $Users
 */
class UsersController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $categorize = [
		'allowUnassigned' => TRUE,
		'associationName' => 'Usergroups',
		'enabled' => TRUE,
		'identifier' => 'usergroupId',
	];


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeFilter (EventInterface $ao_event): void {
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
	public function overview (): void {
		$this->Authorization->ensure('read');

		$lo_users = $this->Users->find()->where($this->getOverviewWhere());
		$lo_users = $this->Categories->filterQuery($lo_users);
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
	public function add (): void {
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
			'ao_usergroups' => $this->Users->Usergroups->find()->applyOptions(['authorize' => ['skip' => TRUE]]),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?Response
	 *
	 * @throws \Exception
	 */
	public function edit () {
		$this->Authorization->ensure('update');

		/*
		 * Skip Authorization Check for Usergroups. Even without access to the scope "Usergroups"
		 * the current user can modify the affiliation(s) of users
		 */
		$this->Users->Usergroups->skipAuthorizationCheck();

		/** @var User $lo_user */
		$lo_user = $this->Users->findById((int) $this->request->getParam('id'))->contain(['Usergroups'])->first();
		if ( ! $lo_user) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		$this->Users->Usergroups->skipAuthorizationCheck(FALSE);

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_user, 'edit');
		}

		if (empty($lo_user->usergroups)) {
			$lo_user->usergroups = [];
		}

		$this->set([
			'ao_user' => $lo_user,
			'ao_usergroups' => $this->Users->Usergroups->find()->applyOptions(['authorize' => ['skip' => TRUE]]),
		]);
	}


	/**
	 * Delete method
	 *
	 * @return Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		$lo_user = $this->Users->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_user) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Users->delete($lo_user)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param User $ao_user
	 * @param string $as_method
	 *
	 * @return void
	 */
	protected function save (User $ao_user, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Users->hasAttributes()) {
			$la_associated[] = $this->Users->getAttributesTable(TRUE);
			$ao_user->setAccess('attributes', TRUE);
		}

		/*
		 * Skip Authorization Check for Usergroups. Even without access to the scope "Usergroups"
		 * the current user can modify the affiliation(s) of users
		 */
		$this->Users->Usergroups->skipAuthorizationCheck();

		$la_data = $this->request->getData();

		if (empty($la_data['password'])) {
			unset ($la_data['password']);
		}

		$la_associated['Usergroups'] = ['onlyIds' => TRUE];

		$this->Users->patchEntity($ao_user, $la_data, ['associated' => $la_associated]);

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
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
					$this->Categories->verifySelection(NULL, $la_usergroups ?? []);

					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_user->id], TRUE), 302);
			}

			$this->Flash->error(__($as_method .  '_failed'));
			foreach ($ao_user->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		//Enable Authorization Check for Usergroups
		$this->Users->Usergroups->skipAuthorizationCheck(FALSE);
	}


	/**
	 * Login method
	 *
	 * @return void|?Response
	 */
	public function login () {
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
					], ['guard' => FALSE]);

					$this->Users->save($lo_user, ['authorize' => ['skip' => TRUE], 'audit' => ['skip' => TRUE]]);
				}
				elseif ($lo_user instanceof UsersExternal) {
					$lo_usersExternal = $this->fetchTable('UsersExternal');
					//Track lastLogin
					$lo_user->set('lastLogin', FrozenTime::now());

					$lo_usersExternal->save($lo_user, ['authorize' => ['skip' => TRUE], 'audit' => ['skip' => TRUE]]);
				}

				/** @var Session $lo_session */
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

		if ($this->request->is('post') && ! $lo_result->isValid()) {
			/** @var User $lo_user */
			if (($ls_username = $this->request->getData('username')) && ($lo_user = $this->Users->find()->applyOptions(['authorize' => ['skip' => TRUE]])->where(['username' => $ls_username])->first())) {
				$lo_user->set([
					'failedAttempts' => $lo_user->failedAttempts + 1,
					'lastLogin' => FrozenTime::now(),
				], ['guard' => FALSE]);
				$this->Users->save($lo_user, ['authorize' => ['skip' => TRUE], 'audit' => ['skip' => TRUE]]);
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
	 * @return NULL|Response Redirects on logout
	 *
	 * @noinspection PhpUnused
	 */
	public function logout (): ?Response {
		$this->Authentication->logout();

		/** @var Session $lo_session */
		$lo_session = $this->getRequest()->getAttribute('session');
		$lo_session->delete('unauthenticatedRedirectUrl');

		return $this->redirect(Router::url([
			'_name' => Awyiss::REALM_BACKEND,
			'controller' => 'Users',
			'action' => 'login',
		]));
	}
}
