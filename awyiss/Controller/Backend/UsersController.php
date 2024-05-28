<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Awyiss\Routing\Router;
use Cake\Event\EventInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
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
	 */
	protected array $paginate = [
		'enabled' => true,
		'order' => [
			'username' => 'asc',
		],
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		$this->Authentication->allowUnauthenticated(['login', 'logout']);

		if (in_array($this->getRequest()->getParam('action'), ['login', 'logout'])) {
			$this->Categories->disable();
		}
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->Users->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query, null, false);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();

		$lb_paginated = $this->paginate['enabled'];
		unset($this->paginate['enabled']);
		if ($lb_paginated) {
			$lo_users = $this->paginate($lo_query);
		}
		else {
			$lo_users = $lo_query->all();
		}

		$this->set([
			'users' => $lo_users,
			'attributes' => $this->Users->getAttributes(),
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

		$lo_query = $this->Users->Usergroups->find();
		$this->paginate = [];
		$lo_usergroups = $this->paginate($lo_query);

		$this->set([
			'user' => $lo_user,
			'usergroups' => $lo_usergroups,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/** @var User $lo_user */
		$lo_user = $this->Users->findById($id)->find('translations')->find('mediaAssignments')->find('mediaCompositeAssignments')->contain(['Usergroups'])->first();
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

		$lo_query = $this->Users->Usergroups->find();
		$this->paginate = [];
		$lo_usergroups = $this->paginate($lo_query);

		$this->set([
			'user' => $lo_user,
			'usergroups' => $lo_usergroups,
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		$lo_user = $this->Users->findById($id)->first();
		if (!$lo_user) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Users->delete($lo_user)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
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
						'lastLogin' => DateTime::now(),
					], ['guard' => false]);

					$this->Users->save($lo_user, ['audit' => ['skip' => true]]);
				}
				elseif ($lo_user instanceof UsersExternal) {
					$lo_usersExternal = $this->fetchTable('UsersExternal');
					//Track lastLogin
					$lo_user->set('lastLogin', DateTime::now());

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
						'lastLogin' => DateTime::now(),
					], ['guard' => false]);
					$this->Users->save($lo_user, ['audit' => ['skip' => true]]);
				}
			}

			$this->request = $this->request->withoutData('password');
			$this->Flash->error(__('login_failed'));

			//Do something to slow down the process
			password_hash(md5(Security::randomString()), PASSWORD_BCRYPT, ['cost' => 16]);
		}

		$this->viewBuilder()->setLayout('login');

		$this->set([
			'languageRealm' => Awyiss::REALM_BACKEND,
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
	 * @param User $user
	 * @param string $method
	 * @return void
	 */
	protected function save(User $user, string $method = 'add'): void {
		$la_associated = [];
		if ($this->Users->hasAttributes()) {
			$la_associated[] = $this->Users->getAttributesTableName(true);
			$user->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		if (empty($la_data['password'])) {
			unset($la_data['password']);
		}

		$la_associated['Usergroups'] = ['onlyIds' => true];

		$this->Users->patchEntity($user, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Users->save($user, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__($method . '_succeeded'));
				}

				if ($this->request->getData('submit') == 'submit_close') {
					$la_usergroups = [];

					if ($user->usergroups) {
						$la_usergroups = Hash::combine($user->usergroups, '{n}.id', '{n}.label');

						if ($this->Categories->getConfig('allowAggregation')) {
							$la_usergroups += [$this->Categories->getConfig('aggregationKey') => 'dummy'];
						}
					}
					else {
						if ($this->Categories->getConfig('allowUnassigned')) {
							$la_usergroups += [$this->Categories->getConfig('unassignedKey') => 'dummy'];
						}
					}

					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user.
					 * Otherwise the next redirect to the overview would show a site without the modified user, which could be a bit confusing.
					 */
					$this->Categories->verifySelection(null, $la_usergroups);

					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($user),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $user->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($user->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}
}
