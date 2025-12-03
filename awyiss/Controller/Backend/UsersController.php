<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\User;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use Cake\Utility\Security;


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
		$query = $this->Users->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query, null, false);
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

		$paginated = $this->paginate['enabled'];
		unset($this->paginate['enabled']);
		if ($paginated) {
			$users = $this->paginate($query);
		}
		else {
			$users = $query->all();
		}

		$this->set([
			'users' => $users,
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

		$user = $this->Users->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($user);
		}

		if (empty($user->usergroups)) {
			$user->usergroups = [];
		}

		$this->set([
			'user' => $user,
			'usergroups' => $this->Users->Usergroups->find()->all()->toArray(),
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

		/**
		 * @var \Awyiss\Model\Entity\User $user
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$user = $this->Users->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->contain(['Usergroups'])->first();
		if (!$user) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($user, 'edit');
		}

		if (empty($user->usergroups)) {
			$user->usergroups = [];
		}

		$this->set([
			'user' => $user,
			'usergroups' => $this->Users->Usergroups->find()->all()->toArray(),
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

		/** @var \Awyiss\Model\Entity\User $user */
		$user = $this->Users->findById($id)->first();
		if (!$user) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Users->delete($user)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($user->getError('_general') as $error) {
					$this->Flash->error($error);
				}
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
		$result = $this->Authentication->getResult();

		// If the user is logged in send them away.
		if ($result->isValid()) {
			/** @var User $user */
			if ($this->request->is('post')) {
				$user = $this->Authentication->getIdentity()->getOriginalData();

				$lastLogin = $user->lastLogin;

				/** @noinspection PhpConditionAlreadyCheckedInspection */
				if ($user instanceof User) {
					//Track lastLogin and reset the failed login attempts
					$user->patch([
						'failedAttempts' => 0,
						'lastLogin' => DateTime::now(),
					], ['guard' => false]);

					$this->Users->save($user, ['audit' => ['skip' => true]]);
				}

				/** @var \Cake\Http\Session $session */
				$session = $this->request->getAttribute('session');
				$session->write(LocaleMiddleware::getSessionIdentifier(), $this->request->getData('language_shortcode'));
				$session->write('Backend.lastLogin', $lastLogin);
			}

			$redirectUri = $this->Authentication->getLoginRedirect() ?? Router::url([
				'_name' => Awyiss::REALM_BACKEND,
				'controller' => 'Dashboard',
				'action' => 'overview',
				'_base' => false,
			]);


			return $this->redirect($redirectUri);
		}

		if ($this->request->is('post') && !$result->isValid()) {
			$username = $this->request->getData('username');
			if ($username) {
				/** @var \Awyiss\Model\Entity\User $user */
				$user = $this->Users->find()->where(['username' => $username])->first();
				if ($user) {
					$user->patch([
						'failedAttempts' => $user->failedAttempts + 1,
						'lastLogin' => DateTime::now(),
					], ['guard' => false]);
					$this->Users->save($user, ['audit' => ['skip' => true]]);
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
		/** @var \Cake\Http\Session $session */
		$session = $this->getRequest()->getAttribute('session');
		$lockIdentifier = $session->read('Backend.lockIdentifier');
		$session->destroy();

		$identity = $this->Authentication->getIdentity();

		$this->Authentication->logout();

		if ($identity && $lockIdentifier) {
			// Remove all locks
			$lockTable = $this->fetchTable('Locks');

			$where = ['created_by' => $identity->getIdentifier()];

			$sessionBased = Configure::read('Awyiss.System.Backend.lock.sessionBased', true);
			if ($sessionBased) {
				$where['unique_id'] = $lockIdentifier;
			}

			$lockTable->deleteAll($where);
		}

		return $this->redirect(Router::url([
			'_name' => Awyiss::REALM_BACKEND,
			'controller' => 'Users',
			'action' => 'login',
			'_base' => false,
		]));
	}


	/**
	 * @param User $user
	 * @param string $method
	 * @return void
	 */
	protected function save(User $user, string $method = 'add'): void {
		$associated = [];
		if ($this->Users->hasAttributes()) {
			$associated[] = $this->Users->getAttributesTableName(true);
			$user->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();

		if (empty($requestData['password'])) {
			unset($requestData['password']);
		}

		$associated['Usergroups'] = ['onlyIds' => true];

		$this->Users->patchEntity($user, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Users->save($user, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					$usergroups = [];

					if ($user->usergroups) {
						$usergroups = Hash::combine($user->usergroups, '{n}.id', '{n}.label');

						if ($this->Categories->getConfig('allowAggregation')) {
							$usergroups += [$this->Categories->getConfig('aggregationKey') => 'dummy'];
						}
					}
					else {
						if ($this->Categories->getConfig('allowUnassigned')) {
							$usergroups += [$this->Categories->getConfig('unassignedKey') => 'dummy'];
						}
					}

					/*
					 * Make sure the currently selected category is still part of the categories assigned to the user.
					 * Otherwise the next redirect to the overview would show a site without the modified user, which could be a bit confusing.
					 */
					$this->Categories->verifySelection(null, $usergroups);

					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($user),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $user->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($user->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}
}
