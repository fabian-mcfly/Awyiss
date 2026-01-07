<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Awyiss;
use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Customer;
use Awyiss\Model\Table\CustomersTable;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Mail\MailSender;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\RedirectException;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Security;
use Exception;


/**
 * The Customer Center Controller handles customer account management
 * including login, logout, password changes, and profile updates.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class CustomerCenterController extends AppController {
	use LocatorAwareTrait;


	/**
	 * @var array<string>
	 */
	protected array $allowedProfileFields = [
		'firstname',
		'lastname',
		'email',
	];
	/**
	 * @var array<string>
	 */
	protected array $allowedRegistrationFields = [
		'firstname',
		'lastname',
		'email',
		'password',
		'password_confirm',
	];
	/**
	 * @var string|null
	 */
	protected ?string $languageShortcode = null;
	/**
	 * @var \Awyiss\Model\Table\CustomersTable
	 */
	protected CustomersTable $customersTable;


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function initialize(): void {
		parent::initialize();

		try {
			$this->languageShortcode = LocaleMiddleware::getLanguage()->shortcode;
		}
		catch (Exception) {
			throw new NotFoundException();
		}

		$this->customersTable = $this->fetchTable('Customers');

		$this->loadComponent('Authentication.Authentication', [
			'requireIdentity' => false,
		]);

		$this->loadComponent('Flash', ['key' => '*']);

		$this->viewBuilder()
			->setClassName('Frontend')
			->addHelper('Flash');
	}


	/**
	 * @inheritDoc
	 */
	public function beforeRender(EventInterface $event): void {
		/** @var class-string<\Awyiss\Widget\BreadcrumbsWidget> $breadcrumbsWidgetClass */
		$breadcrumbsWidgetClass = App::className('BreadcrumbsWidget', 'Widget');
		if (!$breadcrumbsWidgetClass) {
			return;
		}

		// Register a new crumb for the Customer Center
		$breadcrumbsWidgetClass::registerCrumb(
			__d('customers', 'link_customer_center'),
			Router::url([
				'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenter' . ucfirst($this->languageShortcode),
			])
		);

		// Register a crumb for the current action
		$action = $this->request->getParam('action');
		if ($action === 'dashboard') {
			return;
		}

		$breadcrumbsWidgetClass::registerCrumb(
			__d('customers', 'link_' . Inflector::underscore($action)),
			Router::url([
				'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenter' . Inflector::camelize($action) . ucfirst($this->languageShortcode),
			])
		);
	}


	/**
	 * Register action
	 * Allows customers to create a new account
	 *
	 * @return void
	 */
	public function register(): void {
		// Check if registration is enabled
		$registrationEnabled = Configure::read('Awyiss.Customers.Frontend.registration.enabled', false);

		if (!$registrationEnabled) {
			throw new NotFoundException();
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$customer = $this->createCustomer($this->request->getData());
		}

		$this->set([
			'customer' => $customer ?? null,
			'loginEnabled' => Configure::read('Awyiss.Customers.Frontend.login.enabled', false),
		]);
	}


	/**
	 * Create a new customer account
	 *
	 * @param array $data Customer data
	 * @return \Awyiss\Model\Entity\Customer
	 */
	protected function createCustomer(array $data): Customer {
		$requiresVerification = Configure::read('Awyiss.Customers.Frontend.registration.requiresVerification', true);
		$activeOnRegistration = Configure::read('Awyiss.Customers.Frontend.registration.activeOnRegistration', false);
		$defaultGroups = Configure::read('Awyiss.Customers.Frontend.registration.defaultGroups', []);

		// Generate email verification code if verification is required
		$verificationCode = null;
		if ($requiresVerification) {
			$verificationCode = hash('sha256', Security::randomBytes(32));
		}

		$customer = $this->customersTable->newDefaultEntity();

		$data = $this->sanitizeRegistrationData($data);

		$data['verified'] = !$requiresVerification;
		$data['verificationCode'] = $verificationCode;
		$data['active'] = $activeOnRegistration;

		if (!$requiresVerification) {
			$data['verifiedOn'] = DateTime::now();
		}

		$associated = [];

		// Add default groups if specified
		if (!empty($defaultGroups)) {
			$associated[] = 'CustomerGroups';
			$data['customerGroups'] = array_map(function ($groupId) {
				return ['id' => $groupId];
			}, (array)$defaultGroups);
		}

		$this->customersTable->patchEntity($customer, $data, [
			'validate' => 'registration',
			'associated' => $associated,
		]);

		if (
			$this->customersTable->save($customer, [
				'allowFrontendSave' => true,
			])
		) {
			$this->handlePostRegistration($requiresVerification, $customer, $verificationCode);

			return $customer;
		}

		// Handle validation errors
		if ($customer->getErrors()) {
			$this->Flash->error(__d('customers', 'error_registration_failed'));
		}

		return $customer;
	}


	/**
	 * @param bool $requiresVerification
	 * @param \Awyiss\Model\Entity\Customer $customer
	 * @param string|null $verificationCode
	 * @return void
	 */
	protected function handlePostRegistration(bool $requiresVerification, Customer $customer, ?string $verificationCode): void {
		if (!$requiresVerification) {
			$this->Flash->success(__d('customers', 'message_registration_success'));

			$this->redirect([
				'_name' => $this->getLoginRouteName(),
			]);

			return;
		}

		// Send email verification
		if ($this->sendVerificationEmail($customer, $verificationCode)) {
			$this->Flash->success(__d('customers', 'message_registration_success_verification_required'));

			$this->redirect([
				'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenterVerifyAccount' . ucfirst($this->languageShortcode),
			]);

			return;
		}

		// Email sending failed
		$this->Flash->error(__d('customers', 'error_sending_verification_email'));

		$this->customersTable->delete($customer, [
			'audit' => ['skip' => true],
			'softDelete' => ['skip' => true],
		]);
	}


	/**
	 * Send mail verification to customer
	 *
	 * @param \Awyiss\Model\Entity\Customer $customer
	 * @param string $verificationCode
	 * @return bool Whether the email was sent successfully
	 */
	protected function sendVerificationEmail(Customer $customer, string $verificationCode): bool {
		/** @var class-string<\Awyiss\Utility\Mail\MailSender> $mailSenderClass */
		$mailSenderClass = App::className('MailSender', 'Utility/Mail');

		$verifyUrl = Router::url([
			'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenterVerifyAccount' . ucfirst($this->languageShortcode),
			'code' => $verificationCode,
		], true);

		/** @var \Awyiss\Utility\Mail\MailSender $mailSender */
		$mailSender = new $mailSenderClass('customerCenter');

		$mailSender
			->setTemplate('account_verification')
			->setTemplatePath('customer_center')
			->setRecipientEmail($customer->email)
			->setSubject(__d('customers', 'email_subject_verify_account'))
			->setData([
				'customer' => $customer,
				'verifyUrl' => $verifyUrl,
				'verificationCode' => $verificationCode,
			]);

		$this->setMailerData($mailSender, $customer);

		return $mailSender->send();
	}


	/**
	 * Login action
	 * Handles customer authentication via email or username
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function login(): void {
		$loginAllowed = Configure::read('Awyiss.Customers.Frontend.login.enabled', false);

		if (!$loginAllowed) {
			throw new NotFoundException();
		}

		$result = $this->Authentication->getResult();

		// If the login was successful, redirect to the dashboard or intended URL
		if ($result?->isValid()) {
			/** @var Customer $customer */
			if ($this->request->is('post')) {
				$customer = $this->Authentication->getIdentity()->getOriginalData();

				/** @noinspection PhpConditionAlreadyCheckedInspection */
				if ($customer instanceof Customer) {
					$customer->clean();

					// Track last login and reset failed attempts
					$customer->patch([
						'failedAttempts' => 0,
						'lastLogin' => DateTime::now(),
					], [
						'guard' => false,
					]);

					$this->customersTable->save($customer, [
						'audit' => ['skip' => true],
						'allowFrontendSave' => true,
					]);
				}
			}

			$redirectUri = $this->Authentication->getLoginRedirect() ?? Router::url([
				'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenterDashboard' . ucfirst($this->languageShortcode),
			]);

			$this->redirect($redirectUri);
			return;
		}

		// Handle failed login attempts
		if ($this->request->is('post') && !$result->isValid()) {
			$email = $this->request->getData('email');
			if ($email) {
				/** @var \Awyiss\Model\Entity\Customer $customer */
				$customer = $this->customersTable->find()->where(['email' => $email])->first();
				if ($customer && !$customer->verified) {
					$errorMessage = __d('customers', 'error_account_not_verified');
				}
				elseif ($customer && !$customer->verified) {
					// Increment failed attempts
					$customer->patch([
						'failedAttempts' => $customer->failedAttempts + 1,
						'lastLogin' => DateTime::now(),
					], [
						'guard' => false,
					]);

					$this->customersTable->save($customer, [
						'audit' => ['skip' => true],
						'allowFrontendSave' => true,
					]);
				}
			}

			$this->Flash->error($errorMessage ?? __d('customers', 'error_invalid_login'));

			// Do something to slow down the process
			password_hash(md5(Security::randomString()), PASSWORD_BCRYPT, ['cost' => 16]);
		}

		$this->set([
			'registrationEnabled' => Configure::read('Awyiss.Customers.Frontend.registration.enabled', false),
			'passwordResetEnabled' => Configure::read('Awyiss.Customers.Frontend.passwordReset.enabled', false),
		]);
	}


	/**
	 * Logout action
	 * Logs out the current customer
	 *
	 * @return void
	 */
	public function logout(): void {
		/** @var \Cake\Http\Session $session */
		$session = $this->getRequest()->getAttribute('session');
		$session->delete(Awyiss::REALM_FRONTEND);

		$this->Authentication->logout();

		$this->redirect(Router::url([
			'_name' => $this->getLoginRouteName(),
		]));
	}


	/**
	 * Dashboard action - Customer center overview
	 * Shows customer profile information and account management options
	 *
	 * @return void
	 * @throws \Cake\Http\Exception\ForbiddenException
	 */
	public function dashboard(): void {
		/** @var \Awyiss\Model\Entity\Customer|null $customer */
		$customer = $this->Authentication->getIdentity()?->getOriginalData();

		if (!$customer instanceof Customer) {
			throw new RedirectException(Router::url([
				'_name' => $this->getLoginRouteName(),
			], true), 302);
		}

		$this->set([
			'customer' => $customer,
		]);
	}


	/**
	 * Edit profile action
	 * Allows customers to update their personal information
	 *
	 * @return void
	 * @throws \Cake\Http\Exception\ForbiddenException
	 * @noinspection PhpUnused
	 */
	public function editProfile(): void {
		/** @var \Awyiss\Model\Entity\Customer|null $customer */
		$customer = $this->Authentication->getIdentity()?->getOriginalData();

		if (!$customer instanceof Customer) {
			throw new ForbiddenException(__d('customers', 'error_not_authenticated'));
		}

		$emailChangeAllowed = Configure::read('Awyiss.Customers.Frontend.profile.emailChangeAllowed', false);
		$verificationRequired = Configure::read('Awyiss.Customers.Frontend.registration.requiresVerification', true);

		if ($this->request->is(['patch', 'post', 'put'])) {
			$customer->clean();

			$data = $this->request->getData();
			$data = $this->sanitizeProfileData($data);

			// Check if email is being changed and email changes are not allowed
			if (!$emailChangeAllowed) {
				unset($data['email']);
			}

			// Track if email was changed
			$emailChanged = false;
			if ($emailChangeAllowed && isset($data['email']) && $data['email'] !== $customer->email) {
				$emailChanged = true;
			}

			$this->customersTable->patchEntity($customer, $data);

			if (
				$this->customersTable->save($customer, [
					'audit' => ['skip' => true],
					'allowFrontendSave' => true,
				])
			) {
				// If email was changed and verification is required, start verification process and logout
				if ($emailChanged && $verificationRequired) {
					$verificationCode = hash('sha256', Security::randomBytes(32));
					$customer->patch([
						'verified' => false,
						'verificationCode' => $verificationCode,
					], ['guard' => false]);

					$this->customersTable->save($customer, [
						'audit' => ['skip' => true],
						'allowFrontendSave' => true,
					]);

					if ($this->sendVerificationEmail($customer, $verificationCode)) {
						$this->Flash->success(__d('customers', 'message_profile_updated'));
						$this->Authentication->logout();

						$this->redirect([
							'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenterVerifyAccount' . ucfirst($this->languageShortcode),
						]);

						return;
					}
				}

				$this->Flash->success(__d('customers', 'message_profile_updated'));

				$this->redirect([
					'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenterDashboard' . ucfirst($this->languageShortcode),
				]);

				return;
			}
			else {
				$this->Flash->error(__d('customers', 'error_profile_update_failed'));
			}
		}

		$this->set([
			'customer' => $customer,
			'emailChangeAllowed' => $emailChangeAllowed,
		]);
	}


	/**
	 * Change password action
	 * Allows customers to change their password
	 *
	 * @return void
	 * @throws \Cake\Http\Exception\ForbiddenException
	 * @noinspection PhpUnused
	 */
	public function changePassword(): void {
		/** @var \Awyiss\Model\Entity\Customer|null $customer */
		$customer = $this->Authentication->getIdentity()?->getOriginalData();

		if (!$customer instanceof Customer) {
			throw new ForbiddenException(__d('customers', 'error_not_authenticated'));
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$customer->clean();

			$data = $this->request->getData();
			$currentPassword = $data['current_password'] ?? null;

			// Validate current password
			if (!$currentPassword || !password_verify($currentPassword, $customer->password)) {
				$this->Flash->error(__d('customers', 'error_password_incorrect'));
			} else {
				$data = array_intersect_key($data, array_flip([
					'password',
					'password_confirm',
				]));

				$this->customersTable->patchEntity($customer, $data);

				if (
					$this->customersTable->save($customer, [
						'audit' => ['skip' => true],
						'allowFrontendSave' => true,
					])
				) {
					$this->Flash->success(__d('customers', 'message_password_changed'));

					$this->redirect([
						'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenterDashboard' . ucfirst($this->languageShortcode),
					]);

					return;
				}

				$this->Flash->error(__d('customers', 'error_password_change_failed'));
			}
		}

		$this->set([
			'customer' => $customer,
		]);
	}


	/**
	 * Forgot password action
	 * Initiates password reset process by sending a reset link via email
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function forgotPassword(): void {
		// Check if password reset is enabled
		$passwordResetEnabled = Configure::read('Awyiss.Customers.Frontend.passwordReset.enabled', true);

		if (!$passwordResetEnabled) {
			throw new NotFoundException();
		}

		$this->set([
			'loginEnabled' => Configure::read('Awyiss.Customers.Frontend.login.enabled', false),
			'registrationEnabled' => Configure::read('Awyiss.Customers.Frontend.registration.enabled', false),
		]);

		if (!$this->request->is(['patch', 'post', 'put'])) {
			return;
		}

		$email = $this->request->getData('email');

		if (!$email) {
			$this->Flash->error(__d('customers', 'error_email_required'));

			return;
		}

		/** @var \Awyiss\Model\Entity\Customer|null $customer */
		$customer = $this->customersTable->find('active')
			->where([
				'email' => $email,
				'OR' => [
					'password_reset_on IS' => null,
					'password_reset_on <=' => DateTime::now()->subMinutes(60),
				],
			])
			->first();

		if ($customer) {
			// Generate a reset code
			$resetCode = hash('sha256', Security::randomBytes(32));
			$customer->patch([
				'passwordResetCode' => $resetCode,
				'passwordResetOn' => DateTime::now(),
			], ['guard' => false]);

			$this->customersTable->save($customer, [
				'audit' => ['skip' => true],
				'allowFrontendSave' => true,
			]);

			// Send reset email
			if (!$this->sendPasswordResetEmail($customer, $resetCode)) {
				$this->Flash->error(__d('customers', 'error_sending_password_reset_email'));

				return;
			}
		}

		// Always show success message for security (don't reveal if email exists)
		$this->Flash->success(__d('customers', 'message_password_reset_sent'));

		$this->redirect([
			'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenterResetPassword' . ucfirst($this->languageShortcode),
		]);
	}


	/**
	 * Reset password action
	 * Allows customer to set a new password using a reset code
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function resetPassword(): void {
		if (!$this->request->is('post')) {
			$this->set([
				'code' => $this->request->getParam('code'),
				'codeVerified' => false,
			]);

			return;
		}

		$code = $this->request->getData('code');

		if (!$code) {
			$this->Flash->error(__d('customers', 'error_invalid_reset_code'));

			return;
		}

		/** @var \Awyiss\Model\Entity\Customer|null $customer */
		$customer = $this->customersTable->find('active')
			->where(['password_reset_code' => $code])
			->first();

		if (!$customer || !$this->checkPasswordResetCodeValidity($customer)) {
			$this->Flash->error(__d('customers', 'error_invalid_reset_code'));

			return;
		}

		// Check if password fields are being submitted (second step)
		$password = $this->request->getData('password');
		$passwordConfirm = $this->request->getData('password_confirm');

		if (!$password) {
			// First POST: Code was verified, show the password form
			$this->set([
				'code' => $code,
				'codeVerified' => true,
			]);

			return;
		}

		$this->customersTable->patchEntity($customer, [
			'password' => $password,
			'passwordConfirm' => $passwordConfirm,
			'passwordResetCode' => null,
			'passwordResetOn' => null,
		]);

		if (
			$this->customersTable->save($customer, [
				'audit' => ['skip' => true],
				'allowFrontendSave' => true,
			])
		) {
			$this->Flash->success(__d('customers', 'message_password_reset_success'));

			$this->redirect([
				'_name' => $this->getLoginRouteName(),
			]);

			return;
		}

		$this->Flash->error(__d('customers', 'error_password_reset_failed'));

		// First POST: Code was verified, show the password form
		$this->set([
			'customer' => $customer,
			'code' => $code,
			'codeVerified' => true,
		]);
	}


	/**
	 * Check if password reset code is valid and not expired
	 *
	 * @param \Awyiss\Model\Entity\Customer $customer
	 * @return bool
	 */
	protected function checkPasswordResetCodeValidity(Customer $customer): bool {
		// Check if code exists
		if (!$customer->passwordResetCode) {
			return false;
		}

		// Check if reset timestamp exists
		if (!$customer->passwordResetOn) {
			return false;
		}

		// Get validity duration in seconds from config
		$validitySeconds = Configure::read('Awyiss.Customers.Frontend.passwordReset.codeValidity', 3600);

		// Calculate expiration time
		$resetTime = $customer->passwordResetOn instanceof DateTime
			? $customer->passwordResetOn
			: new DateTime($customer->passwordResetOn);

		$expirationTime = $resetTime->addSeconds($validitySeconds);

		// Check if code is still valid
		return DateTime::now() <= $expirationTime;
	}


	/**
	 * Verify account action
	 * Confirms customer email address using verification code
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function verifyAccount(): void {
		if (!$this->request->is('post')) {
			$this->set([
				'code' => $this->request->getParam('code'),
			]);

			return;
		}

		$code = $this->request->getData('code');

		if (!$code) {
			$this->Flash->error(__d('customers', 'error_invalid_verification_code'));

			return;
		}

		/** @var \Awyiss\Model\Entity\Customer|null $customer */
		$customer = $this->customersTable->find()
			->where([
				'verification_code' => $code,
			])
			->first();

		if ($customer) {
			$customer->patch([
				'verified' => true,
				'verifiedOn' => DateTime::now(),
				'verificationCode' => null,
			], ['guard' => false]);

			if (
				$this->customersTable->save($customer, [
					'audit' => ['skip' => true],
					'allowFrontendSave' => true,
				])
			) {
				$this->Flash->success(__d('customers', 'message_account_verified'));

				$this->redirect([
					'_name' => $this->getLoginRouteName(),
				]);

				return;
			}
		}

		$this->Flash->error(__d('customers', 'error_account_verification_failed'));
	}


	/**
	 * Send password reset mail to customer
	 *
	 * @param \Awyiss\Model\Entity\Customer $customer
	 * @param string $resetCode
	 * @return bool Whether the email was sent successfully
	 */
	protected function sendPasswordResetEmail(Customer $customer, string $resetCode): bool {
		/** @var class-string<\Awyiss\Utility\Mail\MailSender> $mailSenderClass */
		$mailSenderClass = App::className('MailSender', 'Utility/Mail');

		$resetUrl = Router::url([
			'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenterResetPassword' . ucfirst($this->languageShortcode),
			'code' => $resetCode,
		], true);

		/** @var \Awyiss\Utility\Mail\MailSender $mailSender */
		$mailSender = new $mailSenderClass('customerCenter');

		// Get code validity in seconds and convert to hours
		$codeValiditySeconds = Configure::read('Awyiss.Customers.Frontend.passwordReset.codeValidity', 3600);
		$codeValidityHours = (int)ceil($codeValiditySeconds / 3600);

		$mailSender
			->setTemplate('password_reset')
			->setTemplatePath('customer_center')
			->setRecipientEmail($customer->email)
			->setSubject(__d('customers', 'email_subject_password_reset'))
			->setData([
				'customer' => $customer,
				'resetUrl' => $resetUrl,
				'codeValidityHours' => $codeValidityHours,
				'resetCode' => $resetCode,
			]);

		$this->setMailerData($mailSender, $customer);

		return $mailSender->send();
	}


	/**
	 * @return string
	 */
	protected function getLoginRouteName(): string {
		$language = $this->getRequest()->getParam('lang');

		return Awyiss::REALM_FRONTEND . 'CustomerCenterLogin' . ucfirst($language);
	}


	/**
	 * @param \Awyiss\Utility\Mail\MailSender $mailSender
	 * @param \Awyiss\Model\Entity\Customer $customer
	 * @return void
	 */
	protected function setMailerData(MailSender $mailSender, Customer $customer): void {
		$fromName = Configure::read('Awyiss.Customers.Frontend.emails.senderName');
		if ($fromName) {
			$mailSender->setSenderName($fromName);
		}

		$fromEmail = Configure::read('Awyiss.Customers.Frontend.emails.senderEmail');
		if ($fromEmail) {
			$mailSender->setSenderEmail($fromEmail);
		}

		$transportProfile = Configure::read('Awyiss.Customers.Frontend.emails.transportProfile');
		if ($transportProfile) {
			$mailSender->setTransportProfile($transportProfile);
		}

		$name = '';
		if ($customer->firstname) {
			$name .= $customer->firstname;
		}
		if ($customer->lastname) {
			$name .= ' ' . $customer->lastname;
		}
		$name = trim($name);
		if ($name) {
			$mailSender->setRecipientName($name);
		}
	}


	/**
	 * @param array $data
	 * @return array
	 */
	protected function sanitizeProfileData(array $data): array {
		return array_intersect_key($data, array_flip($this->allowedProfileFields));
	}


	/**
	 * @param array $data
	 * @return array
	 */
	protected function sanitizeRegistrationData(array $data): array {
		return array_intersect_key($data, array_flip($this->allowedRegistrationFields));
	}
}
