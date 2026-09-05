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
use Cake\Http\Session;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use OTPHP\TOTP;


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

		if (
			in_array(
				$this->getRequest()->getParam('action'),
				['login', 'logout', 'twoFactorSetup', 'twoFactorAuth', 'twoFactorEnable', 'twoFactorDisable']
			)
		) {
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
			'usergroups' => $this->Users->Usergroups
				->find()
				->all()
				->toArray(),
		]);
	}


	/**
	 * Edit method
	 *
	 * @param int $id User id.
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
		$user = $this->Users
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->contain(['Usergroups'])
			->first()
		;
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
			'usergroups' => $this->Users->Usergroups
				->find()
				->all()
				->toArray(),
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
	 * @return Response|void
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
				$session->write(LocaleMiddleware::getSessionIdentifier(), $this->request->getData('languageShortcode'));
				$session->write('Backend.lastLogin', $lastLogin);
			}

			return $this->redirectAfterLogin();
		}

		if ($this->request->is('post') && !$result->isValid()) {
			$username = $this->request->getData('username');
			if ($username) {
				/** @var \Awyiss\Model\Entity\User $user */
				$user = $this->Users
					->find()
					->where(['username' => $username])
					->first()
				;
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
	 * Set up TOTP authentication for the currently logged-in backend user.
	 *
	 * A new setup secret is generated for every page load and failed verification attempt.
	 *
	 * @return \Cake\Http\Response|void
	 * @noinspection PhpUnused
	 */
	public function twoFactorSetup() {
		$user = $this->Authentication->getIdentity()?->getOriginalData();
		if (!$user instanceof User) {
			return $this->redirect(['action' => 'login']);
		}

		$session = $this->request->getAttribute('session');

		if ($this->request->is('post')) {
			if ($this->request->getData('submit') === 'cancel') {
				// If the user cancels the two-factor setup, disable two-factor authentication and remove the secret.
				$this->disableTwoFactorForUser($user, $session);

				return $this->redirectAfterLogin();
			}

			$secret = $session->read('Backend.twoFactorSetupSecret');

			if (!$secret) {
				$this->Flash->error(__d('Users', 'two_factor_setup_failed'));

				return $this->redirect(['action' => 'twoFactorSetup']);
			}

			$totp = TOTP::createFromSecret($secret);
			if ($totp->verify($this->request->getData('code'))) {
				$user->patch([
					'twoFactorEnabled' => true,
					'twoFactorSecret' => Security::encrypt($secret, hash('sha256', Security::getSalt(), true)),
				], ['guard' => false]);

				$this->Users->save($user);

				$session->delete('Backend.twoFactorSetupSecret');
				$session->write('Backend.twoFactorVerified', true);
				$session->write(Awyiss::REALM_BACKEND . '.Auth', $user);

				return $this->redirectAfterLogin();
			}

			$this->Flash->error(__d('Users', 'two_factor_code_invalid'));
		}

		// Always generate a new secret for every page load and failed verification attempt, to prevent brute-force attacks.
		$totp = TOTP::generate()
			->withIssuer('Awyiss')
			->withLabel($user->username)
		;

		$secret = $totp->getSecret();
		$session->write('Backend.twoFactorSetupSecret', $secret);

		$qrCode = new QrCode($totp->getProvisioningUri());
		$qrCode = new PngWriter()->write($qrCode)->getDataUri();

		$this->viewBuilder()->setLayout('login');
		$this->set([
			'qrCode' => $qrCode,
			'loginLogoPath' => $qrCode,
			'secret' => $secret,
			'cancelable' => Configure::read('Awyiss.Users.Backend.forceTwoFactor', false) === false,
		]);
	}


	/**
	 * Start two-factor setup for the currently logged-in backend user.
	 *
	 * @return \Cake\Http\Response|null
	 * @noinspection PhpUnused
	 */
	public function twoFactorEnable(): ?Response {
		$user = $this->Authentication->getIdentity()?->getOriginalData();
		if (!$user instanceof User) {
			return $this->redirect(['action' => 'login']);
		}

		if ($user->twoFactorEnabled) {
			return $this->redirectAfterLogin();
		}

		return $this->redirect(['action' => 'twoFactorSetup']);
	}


	/**
	 * Disable two-factor authentication after verifying the current TOTP code.
	 *
	 * @return \Cake\Http\Response|void
	 * @noinspection PhpUnused
	 */
	public function twoFactorDisable() {
		$user = $this->Authentication->getIdentity()?->getOriginalData();
		if (!$user instanceof User) {
			return $this->redirect(['action' => 'login']);
		}

		if (!$user->twoFactorEnabled || Configure::read('Awyiss.Users.Backend.forceTwoFactor', false)) {
			return $this->redirectAfterLogin();
		}

		$encryptedSecret = $user->twoFactorSecret;
		if (!is_string($encryptedSecret) || $encryptedSecret === '') {
			return $this->redirect(['action' => 'twoFactorSetup']);
		}

		$session = $this->request->getAttribute('session');
		$secret = Security::decrypt($encryptedSecret, hash('sha256', Security::getSalt(), true));

		if (!$secret) {
			// If no secret has been set, the user needs to have the two-factor flag removed
			$this->disableTwoFactorForUser($user, $session);

			return $this->redirectAfterLogin();
		}

		if ($this->request->is('post')) {
			$totp = TOTP::createFromSecret($secret);

			if ($totp->verify($this->request->getData('code'))) {
				// If the code is valid, disable two-factor authentication for the user.
				$this->disableTwoFactorForUser($user, $session);

				return $this->redirectAfterLogin();
			}

			$user->patch([
				'failedAttempts' => $user->failedAttempts + 1,
				'lastLogin' => DateTime::now(),
			], ['guard' => false]);

			$this->Users->save($user);

			if ($user->failedAttempts >= 5) {
				$this->Flash->error(__d('Users', 'two_factor_too_many_failed_attempts'));
				$this->Authentication->logout();

				return $this->redirect(['action' => 'logout']);
			}

			$this->Flash->error(__d('Users', 'two_factor_code_invalid'));
		}

		$this->viewBuilder()->setLayout('login');
		$this->viewBuilder()->setTemplate('two_factor_disable');
		$this->set([
			'account' => $user->username,
		]);
	}


	/**
	 * Verify the TOTP code after a successful backend password login.
	 *
	 * @return \Cake\Http\Response|void
	 * @noinspection PhpUnused
	 */
	public function twoFactorAuth() {
		$user = $this->Authentication->getIdentity()?->getOriginalData();
		if (!$user instanceof User) {
			return $this->redirect(['action' => 'login']);
		}

		$encryptedSecret = $user->twoFactorSecret;

		if (!$encryptedSecret) {
			return $this->redirect(['action' => 'twoFactorSetup']);
		}

		$secret = Security::decrypt($encryptedSecret, hash('sha256', Security::getSalt(), true));

		$session = $this->request->getAttribute('session');
		if (!$secret) {
			return $this->redirect(['action' => 'twoFactorSetup']);
		}

		if ($this->request->is('post')) {
			$totp = TOTP::createFromSecret($secret);
			if ($totp->verify($this->request->getData('code'))) {
				$session->write('Backend.twoFactorVerified', true);

				return $this->redirectAfterLogin();
			}

			// Increase the failed attempts counter for the user, to prevent brute-force attacks.
			$user->patch([
				'failedAttempts' => $user->failedAttempts + 1,
				'lastLogin' => DateTime::now(),
			], ['guard' => false]);

			$this->Users->save($user, ['audit' => ['skip' => true]]);

			if ($user->failedAttempts >= 5) {
				// Redirect to log out to force the user to re-login and reset the failed attempts counter.
				$this->Flash->error(__d('Users', 'two_factor_too_many_failed_attempts'));
				$this->Authentication->logout();
				return $this->redirect(['action' => 'logout']);
			}

			$this->Flash->error(__d('Users', 'two_factor_code_invalid'));
		}

		$this->viewBuilder()->setLayout('login');
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
		$session->delete(Awyiss::REALM_BACKEND);

		$identity = $this->Authentication->getIdentity();

		$this->Authentication->logout();

		if ($identity && $lockIdentifier) {
			// Remove all locks
			$lockTable = $this->fetchTable('Locks');

			$where = ['createdBy' => $identity->getIdentifier()];

			$sessionBased = Configure::read('Awyiss.System.Backend.lock.sessionBased', true);
			if ($sessionBased) {
				$where['uniqueId'] = $lockIdentifier;
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
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			$twoFactorEnabledChanged = $user->isDirty('twoFactorEnabled')
				&& $user->twoFactorEnabled != $user->getOriginal('twoFactorEnabled');

			if ($this->Users->save($user, ['asCopy' => $saveAsCopy])) {
				// If the user saved themselves and activated two-factor authentication, redirect to the two-factor setup page.
				if (
					$user->id === $this->Authentication->getIdentity()->getIdentifier()
					&& $twoFactorEnabledChanged
					&& $user->twoFactorEnabled
				) {
					// Update the session to reflect the new two-factor status, so that the user is not immediately logged out.
					$session = $this->request->getAttribute('session');
					/** @var \Awyiss\Model\Entity\User $sessionUser */
					$sessionUser = $session->read(Awyiss::REALM_BACKEND . '.Auth');
					$sessionUser->twoFactorEnabled = true;

					throw new RedirectException(Router::url([
						'action' => 'twoFactorSetup',
					], true), 302);
				}

				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
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
					 * Otherwise the next redirect to the overview would show a site without the modified user,
					 * which could be a bit confusing.
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


	/**
	 * @return \Cake\Http\Response|null
	 */
	protected function redirectAfterLogin(): ?Response {
		$redirectUri = $this->Authentication->getLoginRedirect() ?? Router::url([
			'_name' => Awyiss::REALM_BACKEND,
			'controller' => 'Dashboard',
			'action' => 'overview',
			'_base' => false,
		]);

		return $this->redirect($redirectUri);
	}


	/**
	 * @param \Awyiss\Model\Entity\User $user
	 * @param \Cake\Http\Session $session
	 * @return void
	 */
	protected function disableTwoFactorForUser(User $user, Session $session): void {
		$user->patch([
			'twoFactorEnabled' => false,
			'twoFactorSecret' => null,
		], ['guard' => false]);

		$this->Users->save($user);

		/** @var \Awyiss\Model\Entity\User $sessionUser */
		$sessionUser = $session->read(Awyiss::REALM_BACKEND . '.Auth');
		$sessionUser->twoFactorEnabled = false;
		$sessionUser->twoFactorSecret = null;

		$session->delete('Backend.twoFactorVerified');
		$session->delete('Backend.twoFactorSetupSecret');
	}
}
