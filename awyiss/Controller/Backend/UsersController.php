<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Cake\Event\EventInterface;

//awyiss: $2y$10$B1IWA5ic5yFJCbxB7kvKD.hnfrA3M34LPtOH5y.zrK0b6PpAHj.Eu


/**
 * Users Controller
 *
 * @property \Awyiss\Model\Table\UsersTable $Users
 * @method \Awyiss\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = NULL, array $settings = [])
 */
class UsersController extends Controller {
	public array $categorize = [
		'allowUnassigned' => TRUE,
		'associationName' => 'Usergroups',
		'enabled' => TRUE,
		'name' => 'usergroup',
	];


	/**
	 * {@inheritDoc}
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeFilter (EventInterface $ao_event) {
		parent::beforeFilter($ao_event);
		$this->Authentication->allowUnauthenticated(['login']);
	}


	/**
	 * Overview method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$this->Access->ensureOne('create', 'update', 'delete');

		$lo_users = $this->Categories->filterQuery($this->Users->find('withAttributes'));
		$lo_users = $this->paginate($lo_users);

		$this->set([
			'ao_users' => $lo_users,
		]);
	}


	/**
	 * Add method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function add () {
		$this->Access->ensure('create');

		$lo_user = $this->Users->newDefaultEntity();
		if ($this->request->is('post')) {
			$lo_user = $this->Users->patchEntity($lo_user, $this->request->getData(), ['associated' => ['Usergroups']]);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Users->save($lo_user)) {
					$this->Flash->success(__('::add_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_user->id]);
				}

				$this->Flash->error(__('::add_failed'));
			}
		}

		if (empty($lo_user->usergroups)) {
			$lo_user->usergroups = [];
		}

		$this->set([
			'ao_user' => $lo_user,
			'ao_usergroups' => $this->Users->Usergroups->find()->all(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function edit () {
		$this->Access->ensure('update');

		$li_id = $this->request->getParam('id');

		$lo_user = $this->Users->find()->contain([
			'Usergroups',
		])->where(['id' => $li_id])->first();

		if ( ! $lo_user) {
			$this->Flash->error(__('::record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$la_data = $this->request->getData();
			if (empty($la_data['password'])) {
				unset($la_data['password']);
			}

			$lo_user = $this->Users->patchEntity($lo_user, $la_data, ['associated' => ['Usergroups']]);

			if ( ! $this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
				if ($this->Users->save($lo_user)) {
					$this->Flash->success(__('::edit_succeeded'));

					if ($this->request->getData('submit') == 'submit_close') {
						return $this->redirect(['action' => 'overview']);
					}

					return $this->redirect(['action' => 'edit', 'id' => $lo_user->id]);
				}

				$this->Flash->error(__('::edit_failed'));
			}
		}

		$this->set([
			'ao_user' => $lo_user,
			'ao_usergroups' => $this->Users->Usergroups->find()->all(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @return void|?\Cake\Http\Response Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function delete () {
		$this->Access->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);
		$li_id = $this->request->getParam('id');
		$lo_user = $this->Users->get($li_id);

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
	 * Login method
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful login, renders view otherwise.
	 */
	public function login () {
		$lo_result = $this->Authentication->getResult();

		// If the user is logged in send them away.
		if ($lo_result->isValid()) {
			/** @var \Awyiss\Model\Entity\User $lo_user */
			if ($this->request->is('post')) {
				$lo_user = $this->Authentication->getIdentity()->getOriginalData();

				if ($lo_user instanceof \Awyiss\Model\Entity\User) {
					//Track last_login and reset the failed login attempts
					$lo_user->set([
						'failed_attempts' => 0,
						'last_login' => \Cake\I18n\FrozenTime::now(),
					], ['guard' => FALSE]);

					$this->Users->save($lo_user, ['skipAuditBehavior' => TRUE, 'setTimeOnUpdate' => FALSE]);
				}
				elseif ($lo_user instanceof \Awyiss\Model\Entity\UsersExternal) {
					$lo_usersExternal = $this->getTableLocator()->get('UsersExternal');
					//Track last_login
					$lo_user->set('last_login', \Cake\I18n\FrozenTime::now());

					$lo_usersExternal->save($lo_user, ['skipAuditBehavior' => TRUE]);
				}

				/** @var \Cake\Http\Session $lo_session */
				$lo_session = $this->request->getAttribute('session');
				$lo_session->write('backend.languageShortcode', $this->request->getData('languages_shortcode'));
			}

			$ls_redirectUri = $this->Authentication->getLoginRedirect() ?? \Cake\Routing\Router::url([
				'_name' => 'backend',
				'controller' => 'Dashboard',
				'action' => 'overview',
			]);

			return $this->redirect($ls_redirectUri);
		}

		if ($this->request->is('post') && ! $lo_result->isValid()) {
			/** @var \Awyiss\Model\Entity\User $lo_user */
			if (($ls_username = $this->request->getData('username')) && ($lo_user = $this->Users->find()->where(['username' => $ls_username])->first())) {
				$lo_user->set([
					'failed_attempts' => $lo_user->failed_attempts + 1,
					'last_login' => \Cake\I18n\FrozenTime::now(),
				], ['guard' => FALSE]);
				$this->Users->save($lo_user, ['skipAuditBehavior' => TRUE, 'setTimeOnUpdate' => FALSE]);
			}

			dump($lo_result->getErrors());

			$this->request = $this->request->withoutData('password');
			$this->Flash->error('Invalid username or password');

			//Do something to slow down the process
			password_hash(md5(\Cake\Utility\Security::randomString()), PASSWORD_BCRYPT, ['cost' => 16]);
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
	 * @noinspection PhpMissingReturnTypeInspection
	 * @noinspection PhpUnused
	 */
	public function logout () {
		$this->Authentication->logout();

		/** @var \Cake\Http\Session $lo_session */
		$lo_session = $this->getRequest()->getAttribute('session');
		$lo_session->delete('unauthenticatedRedirectUrl');

		return $this->redirect(\Cake\Routing\Router::url([
			'_name' => 'backend',
			'controller' => 'Users',
			'action' => 'login',
		]));
	}
}
