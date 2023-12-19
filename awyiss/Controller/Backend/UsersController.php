<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;


//awyiss: $2y$10$B1IWA5ic5yFJCbxB7kvKD.hnfrA3M34LPtOH5y.zrK0b6PpAHj.Eu


/**
 * Users Controller
 *
 * @property \Awyiss\Model\Table\UsersTable $Users
 * @method \Awyiss\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = NULL, array $settings = [])
 */
class UsersController extends Controller {
	/**
	 * {@inheritDoc}
	 */
	public function beforeFilter (\Cake\Event\EventInterface $event) {
		parent::beforeFilter($event);
		$this->Authentication->allowUnauthenticated(['login']);
	}


	/**
	 * Overview method
	 *
	 * @return \Cake\Http\Response|null|void Renders view
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function overview () {
		$users = $this->paginate($this->Users->find('withAttributes')->contain(['Usergroups']));

		$this->set(compact('users'));
	}


	/**
	 * Add method
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection RedundantSuppression
	 */
	public function add () {
		$lo_user = $this->Users->newEmptyEntity();
		if ($this->request->is('post')) {
			$lo_user = $this->Users->patchEntity($lo_user, $this->request->getData(), ['associated' => ['Usergroups']]);
			if ($this->Users->save($lo_user)) {
				$this->Flash->success(__('The user has been saved.'));

				return $this->redirect(['action' => 'overview']);
			}
			$this->Flash->error(__('The user could not be saved. Please, try again.'));
		}

		$this->set([
			'user' => $lo_user,
			'usergroups' => $this->Users->Usergroups->find()->all(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @param string|null $id User id.
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection RedundantSuppression
	 */
	public function edit () {
		$id = $this->request->getParam('id');
		$lo_user = $this->Users->get($id, [
			'contain' => ['Usergroups'],
		]);

		if ($this->request->is(['patch', 'post', 'put'])) {
			$la_data = $this->request->getData();
			if (empty($la_data['password'])) {
				unset($la_data['password']);
			}

			$lo_user = $this->Users->patchEntity($lo_user, $la_data, ['associated' => ['Usergroups']]);

			if ($this->Users->save($lo_user)) {
				$this->Flash->success(__('The user has been saved.'));

				return $this->redirect(['action' => 'overview']);
			}

			$this->Flash->error(__('The user could not be saved. Please, try again.'));
		}

		$this->set([
			'user' => $lo_user,
			'usergroups' => $this->Users->Usergroups->find()->all(),
		]);
	}


	/**
	 * Delete method
	 *
	 * @return \Cake\Http\Response|null|void Redirects to overview.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function delete () {
		$this->request->allowMethod(['get', 'delete']);
		$id = $this->request->getParam('id');
		$lo_user = $this->Users->get($id);
		if ($this->Users->delete($lo_user)) {
			$this->Flash->success(__('The user has been deleted.'));
		}
		else {
			$this->Flash->error(__('The user could not be deleted. Please, try again.'));
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

				if (is_object($lo_user) && $lo_user instanceof \Awyiss\Model\Entity\User) {
					//Track last_login and reset the failed login attempts
					$lo_user->set([
						'failed_attempts' => 0,
						'last_login' => \Cake\I18n\Time::now(),
					], ['guard' => FALSE]);

					$this->Users->save($lo_user, ['saveAudit' => FALSE, 'setTimeOnUpdate' => FALSE]);
				}
				elseif (is_object($lo_user) && $lo_user instanceof \Awyiss\Model\Entity\UsersExternal) {
					$lo_usersExternal = $this->getTableLocator()->get('UsersExternal');
					//Track last_login
					$lo_user->set('last_login', \Cake\I18n\Time::now());

					$lo_usersExternal->save($lo_user, ['saveAudit' => FALSE]);
				}
			}

			$target = $this->Authentication->getLoginRedirect() ?? \Cake\Routing\Router::url([
				'_name' => 'backend',
				'controller' => 'Dashboard',
				'action' => 'overview',
			]);

			return $this->redirect($target);
		}

		if ($this->request->is('post') && ! $lo_result->isValid()) {
			/** @var \Awyiss\Model\Entity\User $lo_user */
			if (($ls_username = $this->request->getData('username')) && ($lo_user = $this->Users->find()->where(['username' => $ls_username])->first())) {
				$lo_user->set([
					'failed_attempts' => $lo_user->failed_attempts + 1,
					'last_login' => \Cake\I18n\Time::now(),
				], ['guard' => FALSE]);
				$this->Users->save($lo_user, ['saveAudit' => FALSE, 'setTimeOnUpdate' => FALSE]);
			}

			dump($lo_result->getErrors());

			$this->request = $this->request->withoutData('password');
			$this->Flash->error('Invalid username or password');

			//Do something to slow down the process
			password_hash(md5(\Cake\Utility\Security::randomString()), PASSWORD_BCRYPT, ['cost' => 16]);
		}
	}


	public function logout () {
		$this->Authentication->logout();

		return $this->redirect(\Cake\Routing\Router::url([
			'_name' => 'backend',
			'controller' => 'Users',
			'action' => 'login',
		]));
	}
}
