<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Cake\Event\EventInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;
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
	 */
	public array $categorize = [
		'allowUnassigned' => TRUE,
		'associationName' => 'Usergroups',
		'enabled' => TRUE,
		'name' => 'usergroup',
	];


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeFilter (EventInterface $ao_event) {
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

		$lo_users = $this->Users->find('withAttributes')->where($this->getOverviewWhere());
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
			'ao_usergroups' => $this->Users->Usergroups->find()->applyOptions(['authorization' => ['skip' => TRUE]]),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response
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
			$this->Flash->error(__('::record_not_found'));

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
			'ao_usergroups' => $this->Users->Usergroups->find()->applyOptions(['authorization' => ['skip' => TRUE]]),
		]);
	}


	/**
	 * Delete method
	 *
	 * @return \Cake\Http\Response
	 *
	 * @throws \Exception
	 */
	public function delete (): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		$lo_user = $this->Users->findById((int) $this->request->getParam('id'))->first();
		if ( ! $lo_user) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Users->delete($lo_user)) {
			$this->Flash->success(__('::delete_succeeded'));
		}
		else {
			$this->Flash->error(__('::delete_failed'));
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
		/*
		 * Skip Authorization Check for Usergroups. Even without access to the scope "Usergroups"
		 * the current user can modify the affiliation(s) of users
		 */
		$this->Users->Usergroups->skipAuthorizationCheck();

		$la_data = $this->request->getData();

		if (empty($la_data['password'])) {
			unset ($la_data['password']);
		}

		$this->Users->patchEntity($ao_user, $la_data, [
			'associated' => ['Usergroups' => ['onlyIds' => TRUE]]
		]);

		if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Users->save($ao_user)) {
				$this->Flash->success(__('::' . $as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], TRUE), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_user->id], TRUE), 302);
			}

			$this->Flash->error(__('::' . $as_method .  '_failed'));
			$this->Flash->error(implode('<br>' . PHP_EOL, $ao_user->getError('_general')));
		}

		//Enable Authorization Check for Usergroups
		$this->Users->Usergroups->skipAuthorizationCheck(FALSE);
	}


	/**
	 * Login method
	 *
	 * @return void|?\Cake\Http\Response
	 */
	public function login () {
		$lo_result = $this->Authentication->getResult();

		// If the user is logged in send them away.
		if ($lo_result->isValid()) {
			/** @var User $lo_user */
			if ($this->request->is('post')) {
				$lo_user = $this->Authentication->getIdentity()->getOriginalData();

				if ($lo_user instanceof User) {
					//Track last_login and reset the failed login attempts
					$lo_user->set([
						'failed_attempts' => 0,
						'last_login' => FrozenTime::now(),
					], ['guard' => FALSE]);

					$this->Users->save($lo_user, ['authorization' => ['skip' => TRUE], 'audit' => ['skip' => TRUE]]);
				}
				elseif ($lo_user instanceof UsersExternal) {
					$lo_usersExternal = $this->fetchTable('UsersExternal');
					//Track last_login
					$lo_user->set('last_login', FrozenTime::now());

					$lo_usersExternal->save($lo_user, ['authorization' => ['skip' => TRUE], 'audit' => ['skip' => TRUE]]);
				}

				/** @var \Cake\Http\Session $lo_session */
				$lo_session = $this->request->getAttribute('session');
				$lo_session->write('backend.languageShortcode', $this->request->getData('language_shortcode'));
			}

			$ls_redirectUri = $this->Authentication->getLoginRedirect() ?? Router::url([
				'_name' => 'backend',
				'controller' => 'Dashboard',
				'action' => 'overview',
			]);

			return $this->redirect($ls_redirectUri);
		}

		if ($this->request->is('post') && ! $lo_result->isValid()) {
			/** @var User $lo_user */
			if (($ls_username = $this->request->getData('username')) && ($lo_user = $this->Users->find()->applyOptions(['authorization' => ['skip' => TRUE]])->where(['username' => $ls_username])->first())) {
				$lo_user->set([
					'failed_attempts' => $lo_user->failed_attempts + 1,
					'last_login' => FrozenTime::now(),
				], ['guard' => FALSE]);
				$this->Users->save($lo_user, ['authorization' => ['skip' => TRUE], 'audit' => ['skip' => TRUE]]);
			}

			$this->request = $this->request->withoutData('password');
			$this->Flash->error('Invalid username or password');

			//Do something to slow down the process
			password_hash(md5(Security::randomString()), PASSWORD_BCRYPT, ['cost' => 16]);
		}

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->request->getAttribute('locale');
		$la_languages = $lo_locale->getLanguages('backend');

		$la_languages = array_combine(array_column($la_languages, 'shortcode'), array_column($la_languages, 'title'));

		$this->set([
			'aa_languages' => $la_languages,
		]);

		$this->viewBuilder()->setLayout('login');
	}


	/**
	 * Logout method
	 *
	 * @return NULL|\Cake\Http\Response Redirects on logout
	 *
	 * @noinspection PhpUnused
	 */
	public function logout (): ?Response {
		$this->Authentication->logout();

		/** @var \Cake\Http\Session $lo_session */
		$lo_session = $this->getRequest()->getAttribute('session');
		$lo_session->delete('unauthenticatedRedirectUrl');

		return $this->redirect(Router::url([
			'_name' => 'backend',
			'controller' => 'Users',
			'action' => 'login',
		]));
	}
}
