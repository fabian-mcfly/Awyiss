<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Form;


use Awyiss\Awyiss;
use Awyiss\Event\EventManager;
use Awyiss\Model\Entity\EmailTemplate;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormEntry;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\CallableMock;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Form\FormSender;
use Awyiss\View\FrontendView;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Mailer\Mailer;
use InvalidArgumentException;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use RuntimeException;


/**
 * Test case for FormSender
 *
 * @see \Awyiss\Utility\Form\FormSender
 */
class FormSenderTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Entity\Form $form
	 */
	protected Form $form;


	/**
	 * Setup test dependencies
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$request = new ServerRequest([
			'url' => '/',
			'params' => [
				'lang' => 'de',
				'slug' => 'dummy',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->form = $this->fetchTable('Forms')->get(1);
		$this->form->sendEmail = true;
		$this->form->sendConfirmationEmail = true;
	}


	/**
	 * Test constructor loads templates into the form
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::__construct()
	 * @throws \Exception
	 */
	public function testConstructorLoadsMissingData(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		new FormSender($form);

		// Check if emailTemplate and confirmationEmailTemplate are set
		$this->assertInstanceOf(EmailTemplate::class, $form->emailTemplate);
		$this->assertInstanceOf(EmailTemplate::class, $form->confirmationEmailTemplate);
		$this->assertInstanceOf(Collection::class, $form->getFormElements());
	}



	/**
	 * Test handle method replaces placeholders in form
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 * @throws \Exception
	 */
	public function testHandleReplacesPlaceholdersInForm(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		// Don't send emails in this test
		$form->sendEmail = false;
		$form->sendConfirmationEmail = false;

		$this->assertSame('$email', $form->userEmail);
		$this->assertSame('Betreff für E-Mail{{ von $vorname $nachname}}', $form->subject);
		$this->assertSame('Hallo $vorname $nachname', $form->salutationConfirmation);
		$this->assertStringContainsString('Wir werden uns zeitnah mit Ihnen, {{$vorname $nachname|lieber Kunde oder Kunde-to-be}},', $form->successMessage);

		$sender = new FormSender($form);
		$handle = $sender->handle();

		$this->assertTrue($handle);
		$this->assertSame('example@domain.com', $form->userEmail);
		$this->assertSame('Betreff für E-Mail von Max Mustermann', $form->subject);
		$this->assertSame('Hallo Max Mustermann', $form->salutationConfirmation);
		$this->assertStringContainsString('Wir werden uns zeitnah mit Ihnen, Max Mustermann', $form->successMessage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 */
	public function testHandleSendsEmail(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		// Don't send confirmation email in this test
		$form->sendConfirmationEmail = false;

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods([
			'sendEmail',
			'sendConfirmationEmail',
			'saveFormEntry',
		])->getMock();
		$sender->expects($this->once())->method('sendEmail')->willReturn(true);
		$sender->expects($this->never())->method('sendConfirmationEmail');
		$sender->expects($this->once())->method('saveFormEntry')->willReturn(true);

		$result = $sender->handle();

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 */
	public function testHandleSendsConfirmationEmail(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		// Don't send email in this test
		$form->sendEmail = false;

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods([
			'sendEmail',
			'sendConfirmationEmail',
			'saveFormEntry',
		])->getMock();
		$sender->expects($this->never())->method('sendEmail');
		$sender->expects($this->once())->method('sendConfirmationEmail')->willReturn(true);
		$sender->expects($this->once())->method('saveFormEntry')->willReturn(true);

		$result = $sender->handle();

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 */
	public function testHandleNotSendsConfirmationWhenSendEmailFails(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods([
			'sendEmail',
			'sendConfirmationEmail',
		])->getMock();
		$sender->expects($this->once())->method('sendEmail')->willReturn(false);
		$sender->expects($this->never())->method('sendConfirmationEmail');

		$result = $sender->handle();

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 */
	public function testHandleSavesEntryWhenMailsSent(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods([
			'sendEmail',
			'sendConfirmationEmail',
			'saveFormEntry',
		])->getMock();
		$sender->expects($this->once())->method('sendEmail')->willReturn(true);
		$sender->expects($this->once())->method('sendConfirmationEmail')->willReturn(true);
		$sender->expects($this->once())->method('saveFormEntry')->willReturn(true);

		$result = $sender->handle();

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 */
	public function testHandleSavesEntryWhenNoMailToSend(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		// Don't send emails in this test
		$form->sendEmail = false;
		$form->sendConfirmationEmail = false;

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods([
			'sendEmail',
			'sendConfirmationEmail',
			'saveFormEntry',
		])->getMock();
		$sender->expects($this->never())->method('sendEmail');
		$sender->expects($this->never())->method('sendConfirmationEmail');
		$sender->expects($this->once())->method('saveFormEntry')->willReturn(true);

		$result = $sender->handle();

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 */
	public function testHandleNotSavesEntryWhenSendEmailFails(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		// Don't send emails in this test
		$form->sendConfirmationEmail = false;

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods([
			'sendEmail',
			'saveFormEntry',
		])->getMock();
		$sender->expects($this->once())->method('sendEmail')->willReturn(false);
		$sender->expects($this->never())->method('saveFormEntry');

		$result = $sender->handle();

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 */
	public function testHandleNotSavesEntryWhenSendConfirmationFails(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		// Don't send emails in this test
		$form->sendEmail = false;

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods([
			'sendEmail',
			'sendConfirmationEmail',
			'saveFormEntry',
		])->getMock();
		$sender->expects($this->never())->method('sendEmail');
		$sender->expects($this->once())->method('sendConfirmationEmail')->willReturn(false);
		$sender->expects($this->never())->method('saveFormEntry');

		$result = $sender->handle();

		$this->assertFalse($result);
	}


	/**
	 * Test handle method sends events
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 */
	public function testHandleSendsEvents(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$callableBeforeSend = function (Event $event) use ($form) {
			$this->assertSame($form, $event->getSubject());
		};

		$eventManager = EventManager::instance();
		$eventManager->on('FormSender.beforeSend', $callableBeforeSend);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods([
			'sendEmail',
			'sendConfirmationEmail',
			'saveFormEntry',
		])->getMock();
		$sender->expects($this->once())->method('sendEmail')->willReturn(true);
		$sender->expects($this->once())->method('sendConfirmationEmail')->willReturn(true);
		$sender->expects($this->once())->method('saveFormEntry')->willReturn(true);

		$result = $sender->handle();

		$eventManager->off('FormSender.beforeSend', $callableBeforeSend);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::handle()
	 */
	public function testHandleNotSendsEmailsWhenEventStopped(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$callableBeforeSend = function ($event) {
			$event->stopPropagation();
		};

		EventManager::instance()->on('FormSender.beforeSend', $callableBeforeSend);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods([
			'sendEmail',
			'sendConfirmationEmail',
			'saveFormEntry',
		])->getMock();
		$sender->expects($this->never())->method('sendEmail');
		$sender->expects($this->never())->method('sendConfirmationEmail');
		$sender->expects($this->never())->method('saveFormEntry');

		$result = $sender->handle();

		$this->assertFalse($result);
	}


	/**
	 * Test sendEmail method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \ReflectionException
	 */
	public function testSendEmail(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->once())->method('send')->willReturn(true);
		$sender->replacePlaceholdersInForm();

		$emailSent = $this->callProtectedMethod($sender, 'sendEmail');

		$this->assertTrue($emailSent);
	}


	/**
	 * Test sendEmail method sends events
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailSendsEvents(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeSendEmail = function (Event $event) use ($form) {
			$this->assertSame($form, $event->getSubject());

			$data = $event->getData();

			$this->assertArrayHasKey('bodyHtml', $data);
			$this->assertArrayHasKey('bodyPlain', $data);

			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Vorname">', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyPlain']);
			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Nachname">', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyPlain']);
		};

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			$this->assertSame($form, $event->getSubject());

			$data = $event->getData();

			$this->assertArrayHasKey('bodyHtml', $data);
			$this->assertArrayHasKey('bodyPlain', $data);
			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Vorname">', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyPlain']);

			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Nachname">', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyPlain']);

			$this->assertArrayHasKey('mailer', $data);
			$this->assertInstanceOf(Mailer::class, $data['mailer']);
		};

		$callableAfterEmailDeliver = function (Event $event) use ($form) {
			$this->assertSame($form, $event->getSubject());

			$data = $event->getData();

			$this->assertArrayHasKey('bodyHtml', $data);
			$this->assertArrayHasKey('bodyPlain', $data);
			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Vorname">', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyPlain']);

			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Nachname">', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyPlain']);

			$this->assertArrayHasKey('mailer', $data);
			$this->assertInstanceOf(Mailer::class, $data['mailer']);

			$this->assertArrayHasKey('sent', $data);
			$this->assertTrue($data['sent']);
		};

		$eventManager->on('FormSender.beforeSendEmail', $callableBeforeSendEmail);
		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$emailSent = $this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeSend', $callableBeforeSendEmail);
		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$this->assertTrue($emailSent);
	}


	/**
	 * Test sendEmail method stops when beforeSendEmail event stops propagation
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \ReflectionException
	 */
	public function testSendEmailStopsWhenBeforeSendEmailStops(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeSendEmail = function (Event $event) use ($form) {
			$event->stopPropagation();
		};

		$callableBeforeEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableBeforeEmailDeliver->expects($this->never())->method('__invoke');

		$callableAfterEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableAfterEmailDeliver->expects($this->never())->method('__invoke');

		$eventManager->on('FormSender.beforeSendEmail', $callableBeforeSendEmail);
		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->never())->method('send');
		$sender->replacePlaceholdersInForm();

		$emailSent = $this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeSend', $callableBeforeSendEmail);
		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$this->assertFalse($emailSent);
	}


	/**
	 * Test sendEmail method stops when beforeEmailDeliver event stops propagation
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \ReflectionException
	 */
	public function testSendEmailStopsWhenBeforeEmailDeliverStops(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			$event->stopPropagation();
		};

		$callableAfterEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableAfterEmailDeliver->expects($this->never())->method('__invoke');

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->never())->method('send');
		$sender->replacePlaceholdersInForm();

		$emailSent = $this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$this->assertFalse($emailSent);
	}


	/**
	 * Test that Event FormSender.beforeSendEmail allows to modify bodyHtml and bodyPlain
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailAllowsModifyingBodyHtmlAndBodyPlain(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeSendEmail = function (Event $event) use ($form) {
			$event->setResult([
				'bodyHtml' => '<p>Modified HTML body</p>',
				'bodyPlain' => 'Modified plain text body',
			]);
		};

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			$data = $event->getData();

			$this->assertEquals('<p>Modified HTML body</p>', $data['bodyHtml']);
			$this->assertEquals('Modified plain text body', $data['bodyPlain']);
		};

		$eventManager->on('FormSender.beforeSendEmail', $callableBeforeSendEmail);
		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeSend', $callableBeforeSendEmail);
		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
	}

	/**
	 * Test that Event FormSender.beforeEmailDeliver allows to modify bodyHtml and bodyPlain
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailAllowsModifyingBodyHtmlAndBodyPlainBeforeDeliver(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			$event->setResult([
				'bodyHtml' => '<p>Modified HTML body before deliver</p>',
				'bodyPlain' => 'Modified plain text body before deliver',
			]);
		};

		$callableAfterEmailDeliver = function (Event $event) use ($form) {
			$data = $event->getData();

			$this->assertArrayHasKey('bodyHtml', $data);
			$this->assertArrayHasKey('bodyPlain', $data);

			// Modify the body content
			$data['bodyHtml'] = '<p>Modified HTML body before deliver</p>';
			$data['bodyPlain'] = 'Modified plain text body before deliver';
		};

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);
	}


	/**
	 * Test that sending stops when FormSender.beforeSendEmail
	 * unset the bodyHtml and bodyPlain
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \ReflectionException
	 */
	public function testSendEmailStopsWhenBodyHtmlAndBodyPlainUnsetInBeforeSendEmail(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeSendEmail = function (Event $event) use ($form) {
			// Unset the bodyHtml and bodyPlain to stop sending
			$event->setResult([
				'bodyHtml' => null,
				'bodyPlain' => null,
			]);
		};

		$callableBeforeEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableBeforeEmailDeliver->expects($this->never())->method('__invoke');

		$eventManager->on('FormSender.beforeSendEmail', $callableBeforeSendEmail);
		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->never())->method('send');
		$sender->replacePlaceholdersInForm();

		$result = $this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeSendEmail', $callableBeforeSendEmail);
		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);

		$this->assertFalse($result);
	}


	/**
	 * Test that sending stops when FormSender.beforeEmailDeliver
	 * unset the bodyHtml and bodyPlain
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \ReflectionException
	 */
	public function testSendEmailStopsWhenBodyHtmlAndBodyPlainUnsetInBeforeEmailDeliver(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Unset the bodyHtml and bodyPlain to stop sending
			$event->setResult([
				'bodyHtml' => null,
				'bodyPlain' => null,
			]);
		};

		$callableAfterEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableAfterEmailDeliver->expects($this->never())->method('__invoke');

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->never())->method('send');
		$sender->replacePlaceholdersInForm();

		$result = $this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$this->assertFalse($result);
	}


	/**
	 * Test that sendEmail sets the transport profile from the form
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailSetsTransportProfile(): void {
		$form = $this->form;
		$form->transportProfile = 'unknown_profile';

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The `unknown_profile` transport configuration does not exist');
		$this->callProtectedMethod($sender, 'sendEmail');
	}


	/**
	 * Test that sendEmail sets `subject`, `from` and `to` from the form
	 * in the mailer
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailSetsSubjectFromAndTo(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			// Subject must be encoded in UTF-8
			$this->assertEquals('Betreff =?UTF-8?B?ZsO8ciBFLU1haWwgdm9uIE1heCBNdXN0ZXJtYW5u?=', $mailer->getSubject());
			// From in the email is the email address and name of the page visitor
			$this->assertEquals(['example@domain.com' => 'Max Mustermann'], $mailer->getFrom());
			// To is the email address and name of the page owner
			$this->assertEquals(['awyiss@cms.de' => 'Awyiss CMS'], $mailer->getTo());
		};

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendEmail sets the name of `to` to the value of
	 * `Awyiss.System.Frontend.meta.titleAppendix` if `ownerName` is empty
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailSetsToNameFromMetaTitleAppendix(): void {
		$form = $this->form;

		$form->ownerName = null; // Ensure ownerName is empty
		Configure::write('Awyiss.System.Frontend.meta.titleAppendix', 'Test Title Appendix');

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertEquals(['awyiss@cms.de' => 'Test Title Appendix'], $mailer->getTo());
		};

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendEmail sets `cc` and `bcc` from the form
	 * in the mailer
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailSetsCcAndBcc(): void {
		$form = $this->form;

		$form->cc = [
			['email' => 'dummy1@example.com', 'name' => 'Dummy CC 1'],
			['email' => 'dummy2@example.com', 'name' => 'Dummy CC 2'],
		];

		$form->bcc = [
			['email' => 'dummy3@example.com', 'name' => 'Dummy BCC 1'],
			['email' => 'dummy4@example.com', 'name' => 'Dummy BCC 2'],
		];

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertInstanceOf(Mailer::class, $mailer);
			$this->assertEquals([
				'dummy1@example.com' => 'Dummy CC 1',
				'dummy2@example.com' => 'Dummy CC 2',
			], $mailer->getCc());
			$this->assertEquals([
				'dummy3@example.com' => 'Dummy BCC 1',
				'dummy4@example.com' => 'Dummy BCC 2',
			], $mailer->getBcc());
		};

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendEmail sets safe real sender
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailSetsSafeRealSender(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$safeRealSender = 'noreply@' . $mailer->getMessage()->getDomain();

			$this->assertEquals([$safeRealSender => 'Max Mustermann'], $mailer->getSender());
			$this->assertEquals(['example@domain.com' => 'Max Mustermann'], $mailer->getReplyTo());
		};

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$formOptions = $form->getFormOptions();
		$this->assertNotEmpty($formOptions->getSafeRealSender());
		$this->assertStringContainsString('noreply@', $formOptions->getSafeRealSender());

		$this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendEmail not sets safe real sender when empty
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailNotSetsSafeRealSenderWhenEmpty(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertEquals([], $mailer->getSender());
			$this->assertEquals([], $mailer->getReplyTo());
		};

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$formOptions = $form->getFormOptions();
		$formOptions->setSafeRealSender(null);

		$this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendEmail sets the email format to `html`
	 * when `bodyPlain` is empty
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailSetsEmailFormatToHtmlWhenBodyPlainIsEmpty(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Set bodyPlain to empty to force HTML format
			$event->setResult([
				'bodyPlain' => '',
			]);
		};

		$callableAfterEmailDeliver = function (Event $event) use ($form) {
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertEquals('html', $mailer->getEmailFormat());
		};

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$formOptions = $form->getFormOptions();
		$formOptions->setSafeRealSender(null);

		$this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);
	}


	/**
	 * Test that sendEmail sets the email format to `text`
	 * when `bodyHtml` is empty
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendEmailSetsEmailFormatToTextWhenBodyHtmlIsEmpty(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Set bodyPlain to empty to force HTML format
			$event->setResult([
				'bodyHtml' => '',
			]);
		};

		$callableAfterEmailDeliver = function (Event $event) use ($form) {
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertEquals('text', $mailer->getEmailFormat());
		};

		$eventManager->on('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$formOptions = $form->getFormOptions();
		$formOptions->setSafeRealSender(null);

		$this->callProtectedMethod($sender, 'sendEmail');

		$eventManager->off('FormSender.beforeEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterEmailDeliver', $callableAfterEmailDeliver);
	}


	/**
	 * Test that sendEmail calls `addFormAttachments`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendEmail()
	 * @see \Awyiss\Utility\Form\FormSender::addFormAttachments()
	 * @throws \ReflectionException
	 */
	public function testSendEmailCallsAddFormAttachments(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = $this->getMockBuilder(FormSender::class)
			->setConstructorArgs([$form])
			->onlyMethods(['addFormAttachments'])
			->getMock();
		$sender->expects($this->once())
			->method('addFormAttachments');
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendEmail');
	}


	/**
	 * Test sendConfirmationEmail method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmail(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->once())->method('send')->willReturn(true);
		$sender->replacePlaceholdersInForm();

		$emailSent = $this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$this->assertTrue($emailSent);
	}


	/**
	 * Test sendConfirmationEmail method sends events
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailSendsEvents(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeSendConfirmationEmail = function (Event $event) use ($form) {
			$this->assertSame($form, $event->getSubject());

			$data = $event->getData();

			$this->assertArrayHasKey('bodyHtml', $data);
			$this->assertArrayHasKey('bodyPlain', $data);

			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Vorname">', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyPlain']);
			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Nachname">', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyPlain']);
		};

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			$this->assertSame($form, $event->getSubject());

			$data = $event->getData();

			$this->assertArrayHasKey('bodyHtml', $data);
			$this->assertArrayHasKey('bodyPlain', $data);
			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Vorname">', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyPlain']);

			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Nachname">', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyPlain']);

			$this->assertArrayHasKey('mailer', $data);
			$this->assertInstanceOf(Mailer::class, $data['mailer']);
		};

		$callableAfterEmailDeliver = function (Event $event) use ($form) {
			$this->assertSame($form, $event->getSubject());

			$data = $event->getData();

			$this->assertArrayHasKey('bodyHtml', $data);
			$this->assertArrayHasKey('bodyPlain', $data);
			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Vorname">', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyHtml']);
			$this->assertStringContainsString('Max', $data['bodyPlain']);

			$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Nachname">', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyHtml']);
			$this->assertStringContainsString('Mustermann', $data['bodyPlain']);

			$this->assertArrayHasKey('mailer', $data);
			$this->assertInstanceOf(Mailer::class, $data['mailer']);

			$this->assertArrayHasKey('sent', $data);
			$this->assertTrue($data['sent']);
		};

		$eventManager->on('FormSender.beforeSendConfirmationEmail', $callableBeforeSendConfirmationEmail);
		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$emailSent = $this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeSend', $callableBeforeSendConfirmationEmail);
		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$this->assertTrue($emailSent);
	}


	/**
	 * Test sendConfirmationEmail method stops when beforeSendConfirmationEmail event stops propagation
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailStopsWhenBeforeSendConfirmationEmailStops(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeSendConfirmationEmail = function (Event $event) use ($form) {
			$event->stopPropagation();
		};

		$callableBeforeEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableBeforeEmailDeliver->expects($this->never())->method('__invoke');

		$callableAfterEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableAfterEmailDeliver->expects($this->never())->method('__invoke');

		$eventManager->on('FormSender.beforeSendConfirmationEmail', $callableBeforeSendConfirmationEmail);
		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->never())->method('send');
		$sender->replacePlaceholdersInForm();

		$emailSent = $this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeSend', $callableBeforeSendConfirmationEmail);
		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$this->assertFalse($emailSent);
	}


	/**
	 * Test sendConfirmationEmail method stops when beforeConfirmationEmailDeliver event stops propagation
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailStopsWhenBeforeEmailDeliverStops(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			$event->stopPropagation();
		};

		$callableAfterEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableAfterEmailDeliver->expects($this->never())->method('__invoke');

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->never())->method('send');
		$sender->replacePlaceholdersInForm();

		$emailSent = $this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$this->assertFalse($emailSent);
	}


	/**
	 * Test that Event FormSender.beforeSendConfirmationEmail allows to modify bodyHtml and bodyPlain
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailAllowsModifyingBodyHtmlAndBodyPlain(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeSendConfirmationEmail = function (Event $event) use ($form) {
			$event->setResult([
				'bodyHtml' => '<p>Modified HTML body</p>',
				'bodyPlain' => 'Modified plain text body',
			]);
		};

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			$data = $event->getData();

			$this->assertEquals('<p>Modified HTML body</p>', $data['bodyHtml']);
			$this->assertEquals('Modified plain text body', $data['bodyPlain']);
		};

		$eventManager->on('FormSender.beforeSendConfirmationEmail', $callableBeforeSendConfirmationEmail);
		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeSend', $callableBeforeSendConfirmationEmail);
		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that Event FormSender.beforeConfirmationEmailDeliver allows to modify bodyHtml and bodyPlain
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailAllowsModifyingBodyHtmlAndBodyPlainBeforeDeliver(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			$event->setResult([
				'bodyHtml' => '<p>Modified HTML body before deliver</p>',
				'bodyPlain' => 'Modified plain text body before deliver',
			]);
		};

		$callableAfterEmailDeliver = function (Event $event) use ($form) {
			$data = $event->getData();

			$this->assertArrayHasKey('bodyHtml', $data);
			$this->assertArrayHasKey('bodyPlain', $data);

			// Modify the body content
			$data['bodyHtml'] = '<p>Modified HTML body before deliver</p>';
			$data['bodyPlain'] = 'Modified plain text body before deliver';
		};

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);
	}


	/**
	 * Test that sending stops when FormSender.beforeSendConfirmationEmail
	 * unset the bodyHtml and bodyPlain
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailStopsWhenBodyHtmlAndBodyPlainUnsetInBeforeSendConfirmationEmail(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeSendConfirmationEmail = function (Event $event) use ($form) {
			// Unset the bodyHtml and bodyPlain to stop sending
			$event->setResult([
				'bodyHtml' => null,
				'bodyPlain' => null,
			]);
		};

		$callableBeforeEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableBeforeEmailDeliver->expects($this->never())->method('__invoke');

		$eventManager->on('FormSender.beforeSendConfirmationEmail', $callableBeforeSendConfirmationEmail);
		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->never())->method('send');
		$sender->replacePlaceholdersInForm();

		$result = $this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeSendConfirmationEmail', $callableBeforeSendConfirmationEmail);
		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);

		$this->assertFalse($result);
	}


	/**
	 * Test that sending stops when FormSender.beforeConfirmationEmailDeliver
	 * unset the bodyHtml and bodyPlain
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailStopsWhenBodyHtmlAndBodyPlainUnsetInBeforeEmailDeliver(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Unset the bodyHtml and bodyPlain to stop sending
			$event->setResult([
				'bodyHtml' => null,
				'bodyPlain' => null,
			]);
		};

		$callableAfterEmailDeliver = $this->getMockBuilder(CallableMock::class)->getMock();
		$callableAfterEmailDeliver->expects($this->never())->method('__invoke');

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$sender = $this->getMockBuilder(FormSender::class)->setConstructorArgs([$form])->onlyMethods(['send'])->getMock();
		$sender->expects($this->never())->method('send');
		$sender->replacePlaceholdersInForm();

		$result = $this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$this->assertFalse($result);
	}


	/**
	 * Test that sendConfirmationEmail sets the transport profile from the form
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailSetsTransportProfile(): void {
		$form = $this->form;
		$form->transportProfile = 'unknown_profile';

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The `unknown_profile` transport configuration does not exist');
		$this->callProtectedMethod($sender, 'sendConfirmationEmail');
	}


	/**
	 * Test that sendConfirmationEmail sets `subject`, `from` and `to` from the form
	 * in the mailer
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailSetsSubjectFromAndTo(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			// Subject must be encoded in UTF-8
			$this->assertEquals('Betreff =?UTF-8?B?ZsO8ciBCZXN0w6R0aWd1bmc=?=', $mailer->getSubject());
			// From in the email is the email address and name of the page visitor
			$this->assertEquals(['awyiss@cms.de' => 'Awyiss CMS'], $mailer->getFrom());
			// To is the email address and name of the page owner
			$this->assertEquals(['example@domain.com' => 'Max Mustermann'], $mailer->getTo());
		};

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendConfirmationEmail sets the name of `from` to the value of
	 * `Awyiss.System.Frontend.meta.titleAppendix` if `ownerName` is empty
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailSetsFromNameFromMetaTitleAppendix(): void {
		$form = $this->form;

		$form->ownerName = null; // Ensure ownerName is empty
		Configure::write('Awyiss.System.Frontend.meta.titleAppendix', 'Test Title Appendix');

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertEquals(['awyiss@cms.de' => 'Test Title Appendix'], $mailer->getFrom());
		};

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendConfirmationEmail sets `cc` and `bcc` from the form
	 * in the mailer
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailNotSetsCcAndBcc(): void {
		$form = $this->form;

		$form->cc = [
			['email' => 'dummy1@example.com', 'name' => 'Dummy CC 1'],
			['email' => 'dummy2@example.com', 'name' => 'Dummy CC 2'],
		];

		$form->bcc = [
			['email' => 'dummy3@example.com', 'name' => 'Dummy BCC 1'],
			['email' => 'dummy4@example.com', 'name' => 'Dummy BCC 2'],
		];

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertInstanceOf(Mailer::class, $mailer);
			$this->assertEquals([], $mailer->getCc());
			$this->assertEquals([], $mailer->getBcc());
		};

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendConfirmationEmail sets safe real sender
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailSetsSafeRealSender(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$safeRealSender = 'noreply@' . $mailer->getMessage()->getDomain();

			$this->assertEquals([$safeRealSender => 'Awyiss CMS'], $mailer->getSender());
			$this->assertEquals(['awyiss@cms.de' => 'Awyiss CMS'], $mailer->getReplyTo());
		};

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$formOptions = $form->getFormOptions();
		$this->assertNotEmpty($formOptions->getSafeRealSender());
		$this->assertStringContainsString('noreply@', $formOptions->getSafeRealSender());

		$this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendConfirmationEmail not sets safe real sender when empty
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailNotSetsSafeRealSenderWhenEmpty(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Make sure the mailer has the correct subject, from and to
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertEquals([], $mailer->getSender());
			$this->assertEquals([], $mailer->getReplyTo());
		};

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$formOptions = $form->getFormOptions();
		$formOptions->setSafeRealSender(null);

		$this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
	}


	/**
	 * Test that sendConfirmationEmail sets the email format to `html`
	 * when `bodyPlain` is empty
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailSetsEmailFormatToHtmlWhenBodyPlainIsEmpty(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Set bodyPlain to empty to force HTML format
			$event->setResult([
				'bodyPlain' => '',
			]);
		};

		$callableAfterEmailDeliver = function (Event $event) use ($form) {
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertEquals('html', $mailer->getEmailFormat());
		};

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$formOptions = $form->getFormOptions();
		$formOptions->setSafeRealSender(null);

		$this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);
	}


	/**
	 * Test that sendConfirmationEmail sets the email format to `text`
	 * when `bodyHtml` is empty
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::sendConfirmationEmail()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendConfirmationEmailSetsEmailFormatToTextWhenBodyHtmlIsEmpty(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$eventManager = EventManager::instance();

		$callableBeforeEmailDeliver = function (Event $event) use ($form) {
			// Set bodyPlain to empty to force HTML format
			$event->setResult([
				'bodyHtml' => '',
			]);
		};

		$callableAfterEmailDeliver = function (Event $event) use ($form) {
			/** @var \Cake\Mailer\Mailer $mailer */
			$mailer = $event->getData('mailer');

			$this->assertEquals('text', $mailer->getEmailFormat());
		};

		$eventManager->on('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->on('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$formOptions = $form->getFormOptions();
		$formOptions->setSafeRealSender(null);

		$this->callProtectedMethod($sender, 'sendConfirmationEmail');

		$eventManager->off('FormSender.beforeConfirmationEmailDeliver', $callableBeforeEmailDeliver);
		$eventManager->off('FormSender.afterConfirmationEmailDeliver', $callableAfterEmailDeliver);
	}


	/**
	 * Test saveFormEntry
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::saveFormEntry()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSaveFormEntry(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->assertNull($sender->getFormEntryIdentifier());

		$result = $this->callProtectedMethod($sender, 'saveFormEntry');

		$this->assertTrue($result);
		$this->assertIsString($sender->getFormEntryIdentifier());
	}


	/**
	 * Test saveFormEntry saves correct data
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::saveFormEntry()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSaveFormEntrySavesCorrectData(): void {
		$form = $this->form;

		$testData = [
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message that will be saved.',
		];
		$form->setFormData($testData);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->assertTrue($sender->handle());

		$formIdentifier = $sender->getFormEntryIdentifier();

		/** @noinspection PhpUndefinedMethodInspection */
		$formEntry = $this->fetchTable('FormEntries')->findByIdentifier($formIdentifier)->first();
		$this->assertInstanceOf(FormEntry::class, $formEntry);

		$this->assertSame(1, $formEntry->formId);

		$this->assertSame('Betreff für E-Mail von Max Mustermann', $formEntry->subject);
		$this->assertSame('Betreff für Bestätigung', $formEntry->subjectConfirmation);

		$body = gzuncompress(base64_decode($formEntry->body));
		$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Vorname">', $body);
		$this->assertStringContainsString('Max', $body);
		$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Nachname">', $body);
		$this->assertStringContainsString('Mustermann', $body);

		$bodyConfirmation = gzuncompress(base64_decode($formEntry->bodyConfirmation));
		$this->assertStringContainsString('Max', $bodyConfirmation);
		$this->assertStringContainsString('Mustermann', $bodyConfirmation);

		$data = json_decode(gzuncompress(base64_decode($formEntry->data)), true);
		$this->assertSame($testData, $data);

		$this->assertSame('789b9b5407e6f6c47d406a4c9f4f89d717a078fe', $formEntry->postHash);
	}


	/**
	 * Test saveFormEntry allows protection methods to
	 * modify the form entry before saving
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::saveFormEntry()
	 * @see \Awyiss\Form\Protection\FormProtectionInterface::modifyFormEntry()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSaveFormEntryAllowsProtectionMethodsToModifyFormEntry(): void {
		Configure::write('Awyiss.Forms.Frontend.protection.methods', ['dummy']);

		$form = $this->form;

		$request = new ServerRequest([
			'url' => '/',
			'params' => [
				'lang' => 'de',
				'slug' => 'dummy',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$form->initialize(new FrontendView($request));

		$testData = [
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message that will be saved but with a different confirmation subject.',
		];
		$form->setFormData($testData);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->assertNotEmpty($form->getProtectionMethods());

		$this->assertTrue($sender->handle());

		$formIdentifier = $sender->getFormEntryIdentifier();

		/** @noinspection PhpUndefinedMethodInspection */
		$formEntry = $this->fetchTable('FormEntries')->findByIdentifier($formIdentifier)->first();
		$this->assertInstanceOf(FormEntry::class, $formEntry);

		$this->assertSame('Dummy Subject Confirmation', $formEntry->subjectConfirmation);
	}


	/**
	 * Test saveFormEntry allows protection methods to
	 * cancel saving the form entry
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::saveFormEntry()
	 * @see \Awyiss\Form\Protection\FormProtectionInterface::modifyFormEntry()
	 * @throws \Exception
	 */
	public function testSaveFormEntryAllowsProtectionMethodsToCancelSaving(): void {
		Configure::write('Awyiss.Forms.Frontend.protection.methods', ['dummyStopsFormEntry']);

		$form = $this->form;

		$request = new ServerRequest([
			'url' => '/',
			'params' => [
				'lang' => 'de',
				'slug' => 'dummy',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$form->initialize(new FrontendView($request));

		$testData = [
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message that will not be saved.',
		];
		$form->setFormData($testData);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->assertNotEmpty($form->getProtectionMethods());

		$this->assertFalse($sender->handle());
		$this->assertNull($sender->getFormEntryIdentifier());
	}


	/**
	 * Test createBody unwraps `{{$data}}` within p tags
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateBodyUnwrapsDataWithinPTags(): void {
		$form = $this->form;

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$this->assertSame('<p>Folgende Daten wurden gesendet:</p><p>{{$data}}</p>', $form->emailTemplate->textHtml);

		$body = $this->callProtectedMethod($sender, 'createBody', $form->emailTemplate);
		$body = trim(preg_replace('/\s+/', ' ', $body));

		$this->assertStringContainsString('<p>Folgende Daten wurden gesendet:</p> <table class="Data">', $body);
		$this->assertStringEndsWith('</table>', $body);
	}


	/**
	 * Test createBody replaces `$salutation` within form's `salutation`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateBodyReplacesSalutation(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$form->salutation = 'Dear Custom Salutation';
		$form->emailTemplate->textHtml = 'Hallo {{$salutation}}, hier sind Ihre Daten: {{$data}}';

		$body = $this->callProtectedMethod($sender, 'createBody', $form->emailTemplate);

		$this->assertStringNotContainsString('$salutation', $body);
		$this->assertStringContainsString('Hallo Dear Custom Salutation, hier sind Ihre Daten:', $body);
	}


	/**
	 * Test createBody replaces `$salutation` within form's `salutationConfirmation`
	 * for the confirmation email
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateBodyReplacesSalutationConfirmation(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$form->confirmationEmailTemplate->textHtml = '{{$salutation}}, hier sind Ihre Daten: {{$data}}';

		$body = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'html', 'confirmation');

		$this->assertStringNotContainsString('$salutation', $body);
		$this->assertStringContainsString('Hallo Max Mustermann, hier sind Ihre Daten:', $body);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateBodyNotReplacesPlaceholdersInFormData(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message containing a placeholder {{$nachname}} that should not be replaced.',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$form->confirmationEmailTemplate->textHtml = '{{$salutation}}, hier sind Ihre Daten: {{$data}}';

		$body = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'html', 'confirmation');

		$this->assertStringContainsString('{{$nachname}}', $body);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateBodyReplacesFormFieldPlaceholders(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$form->confirmationEmailTemplate->textHtml = '$form.title: {{$form.identifier}}';

		$body = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'html', 'confirmation');

		$this->assertSame('Kontaktformular: contact', $body);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateBodyReplacesPageFieldPlaceholders(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $this->fetchTable('Pages')->get(20);

		$sender = new FormSender($form, $page);
		$sender->replacePlaceholdersInForm();

		$form->confirmationEmailTemplate->textHtml = '$page.title: {{$page.languageShortcode/$page.slug}}';

		$body = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'html', 'confirmation');

		$this->assertSame('Anmeldung/Registrierung: de/kundenbereich/registrierung', $body);
	}


	/**
	 * Test createBody creates html body
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateBodyCreatesHtmlBody(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$body = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'html');

		$this->assertStringContainsString('<table', $body);
		$this->assertStringContainsString('</table>', $body);
		$this->assertStringContainsString('<a href="de/impressum">Impressum</a>', $body);
		$this->assertStringContainsString('<a href="de/datenschutz">Datenschutz</a>', $body);
	}


	/**
	 * Test createBody creates plaintext body
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateBodyCreatesPlaintextBody(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		$body = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'text');

		$this->assertStringNotContainsString('<table', $body);
		$this->assertStringNotContainsString('</table>', $body);

		$this->assertStringContainsString('-----------------' . PHP_EOL . 'Persönliche Daten', $body);
		$this->assertStringContainsString('Impressum: http://localhost/de/impressum', $body);
		$this->assertStringContainsString('Datenschutz: http://localhost/de/datenschutz', $body);
	}


	/**
	 * Test replacePlaceholders method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::replacePlaceholders()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 * @throws \Exception
	 */
	public function testReplacePlaceholders(): void {
		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(2);

		$sender = new FormSender($form);

		// Test simple placeholder replacement
		$template = 'Hello {{$name}}, your email is {{$email}}.';
		$values = [
			'name' => 'John Doe',
			'email' => 'john@example.com',
		];

		$result = $sender->replacePlaceholders($template, $values);
		$this->assertEquals('Hello John Doe, your email is john@example.com.', $result);

		// Test with HTML escaping
		$values = [
			'name' => '<script>alert("XSS")</script>',
			'email' => 'john@example.com',
		];

		$result = $sender->replacePlaceholders($template, $values);
		$this->assertEquals('Hello &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;, your email is john@example.com.', $result);

		// Test with safelist (no escaping for specified fields)
		$result = $sender->replacePlaceholders($template, $values, ['name']);
		$this->assertEquals('Hello <script>alert("XSS")</script>, your email is john@example.com.', $result);

		// Test with alternative text for missing values
		$template = 'Hello {{$name|Anonymous}}, your email is {{$missing|not provided}}.';
		$values = ['name' => 'John Doe'];

		$result = $sender->replacePlaceholders($template, $values);
		$this->assertEquals('Hello John Doe, your email is not provided.', $result);

		// Test with multiple placeholders
		$template = 'Hello {{$firstname $lastname}}, your email is {{$email}}.';
		$values = [
			'firstname' => 'John',
			'lastname' => 'Doe',
			'email' => 'john@example.com',
		];
		$result = $sender->replacePlaceholders($template, $values);
		$this->assertEquals('Hello John Doe, your email is john@example.com.', $result);

		// Test with multiple placeholders but missing values
		$template = 'Hello {{$firstname $lastname}}, your email is {{$email}}.';
		$values = [
			'firstname' => 'John',
			'email' => 'john@example.com',
		];
		$result = $sender->replacePlaceholders($template, $values);
		$this->assertEquals('Hello John $lastname, your email is john@example.com.', $result);

		// Test with multiple placeholders but missing values and alternative text
		$template = 'Hello {{$firstname $lastname|customer}}, your email is {{$email}}.';
		$values = [
			'firstname' => 'John',
			'email' => 'john@example.com',
		];
		$result = $sender->replacePlaceholders($template, $values);
		$this->assertEquals('Hello customer, your email is john@example.com.', $result);
	}


	/**
	 * Test addFormAttachments
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::addFormAttachments()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testAddFormAttachments(): void {
		$form = $this->form;

		$stream = $this->getMockBuilder(Stream::class)
			->disableOriginalConstructor()
			->getMock();
		$stream->method('getContents')->willReturn('This is the content of the uploaded file.');
		$stream->method('getMetadata')->willReturn('text/dummy');

		$file = $this->getMockBuilder(UploadedFile::class)->setConstructorArgs([
			$stream,
			1024,
			UPLOAD_ERR_OK,
			'testfile.txt',
			'text/plain',
		])->onlyMethods(['__construct'])->getMock();

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
			'dateiupload' => $file,
		]);

		$sender = new FormSender($form);

		$mailer = new Mailer();

		$this->callProtectedMethod($sender, 'addFormAttachments', $mailer);

		$attachments = $mailer->getAttachments();

		$this->assertArrayHasKey('testfile.txt', $attachments);
		$this->assertEquals('VGhpcyBpcyB0aGUgY29udGVudCBvZiB0aGUgdXBsb2FkZWQgZmlsZS4=', trim($attachments['testfile.txt']['data']));
		$this->assertEquals('text/dummy', $attachments['testfile.txt']['mimetype']);
	}


	/**
	 * Test send calls `deliver` on the mailer
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::send()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendCallsDeliverOnMailer(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$mailer = $this->getMockBuilder(Mailer::class)->onlyMethods(['deliver'])->getMock();
		$mailer->expects($this->once())->method('deliver')->willReturn([
			'headers' => [
				'X-Mailer' => 'Awyiss CMS Mailer',
			],
			'message' => 'Email sent successfully',
		]);

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		// Build the mail body
		$bodyHtml = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'html', 'confirmation');
		$bodyPlain = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'text', 'confirmation');

		$this->callProtectedMethod($sender, 'send', $mailer, $bodyHtml, $bodyPlain);
	}


	/**
	 * Test send sets errors when `deliver` on the mailer fails
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::send()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testSendSetsErrorsWhenDeliverFails(): void {
		$form = $this->form;

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$mailer = $this->getMockBuilder(Mailer::class)->onlyMethods(['deliver'])->getMock();
		$mailer->expects($this->once())->method('deliver')->willThrowException(new RuntimeException('Email delivery failed'));

		$sender = new FormSender($form);
		$sender->replacePlaceholdersInForm();

		// Build the mail body
		$bodyHtml = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'html', 'confirmation');
		$bodyPlain = $this->callProtectedMethod($sender, 'createBody', $form->confirmationEmailTemplate, 'text', 'confirmation');

		$this->callProtectedMethod($sender, 'send', $mailer, $bodyHtml, $bodyPlain);

		$this->assertIsArray($sender->getErrors());
		$this->assertEquals(['Form::error_email_send'], $sender->getErrors());
	}


	/**
	 * Test unwrapDataString removes phrasing-only tags around {{$data}} placeholder
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\FormSender::unwrapDataString()
	 * @throws \ReflectionException
	 * @throws \Exception
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testUnwrapDataString(): void {
		$form = $this->form;
		$sender = new FormSender($form);

		// Scenario 1: Simple p tag with only {{$data}}
		$input = '<p>{{$data}}</p>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap simple p tag with only {{$data}}');

		// Scenario 2: P tag with attributes and only {{$data}}
		$input = '<p class="foo" id="bar">{{$data}}</p>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap p tag with attributes');

		// Scenario 3: Nested phrasing tags with only {{$data}}
		$input = '<p><span class="highlight"><strong>{{$data}}</strong></span></p>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap nested phrasing tags');

		// Scenario 4: Span tag with only {{$data}}
		$input = '<span class="wrapper">{{$data}}</span>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap span tag');

		// Scenario 5: Multiple levels of nested tags
		$input = '<p><em><i><b><span>{{$data}}</span></b></i></em></p>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap multiple nested levels');

		// Scenario 6: P tag with text before {{$data}}
		$input = '<p>Some text before {{$data}}</p>';
		$expected = '<p>Some text before</p>' . PHP_EOL . '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to handle text before {{$data}}');

		// Scenario 7: P tag with text after {{$data}}
		$input = '<p>{{$data}} and text after</p>';
		$expected = '{{$data}}' . PHP_EOL . '<p>and text after</p>' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to handle text after {{$data}}');

		// Scenario 8: P tag with text before and after {{$data}}
		$input = '<p>Text before {{$data}} text after</p>';
		$expected = '<p>Text before</p>' . PHP_EOL . '{{$data}}' . PHP_EOL . '<p>text after</p>' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to handle text before and after {{$data}}');

		// Scenario 9: P tag with nested tags and text
		$input = '<p><strong>Bold text</strong> {{$data}} <em>italic</em></p>';
		$expected = '<p><strong>Bold text</strong></p>' . PHP_EOL . '{{$data}}' . PHP_EOL . '<p><em>italic</em></p>' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to handle nested tags with text');

		// Scenario 10: Nested span within p with content
		$input = '<p><span class="wrapper">Text {{$data}}</span></p>';
		$expected = '<p><span class="wrapper">Text</span></p>' . PHP_EOL . '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to handle nested span with content');

		// Scenario 11: Label tag with only {{$data}}
		$input = '<label for="input">{{$data}}</label>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap label tag');

		// Scenario 12: Multiple {{$data}} placeholders (should handle each independently)
		$input = '<p>{{$data}}</p> Some text <span>{{$data}}</span>';
		$expected = '{{$data}}' . PHP_EOL . ' Some text {{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to handle multiple {{$data}} placeholders');

		// Scenario 13: Whitespace only around {{$data}}
		$input = '<p>   {{$data}}   </p>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to handle whitespace-only content');

		// Scenario 14: Empty nested tags around {{$data}}
		$input = '<p><span></span>{{$data}}<em></em></p>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to handle empty nested tags');

		// Scenario 15: Button tag (phrasing-only) with only {{$data}}
		$input = '<button type="button" class="btn">{{$data}}</button>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap button tag');

		// Scenario 16: Caption tag with only {{$data}}
		$input = '<caption>{{$data}}</caption>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap caption tag');

		// Scenario 17: Complex nested structure with only {{$data}}
		$input = '<p class="outer"><span class="middle"><strong class="inner">{{$data}}</strong></span></p>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap complex nested structure');

		// Scenario 18: Mixed phrasing tags with attributes
		$input = '<p id="p1"><em class="emphasis"><code data-lang="php">{{$data}}</code></em></p>';
		$expected = '{{$data}}' . PHP_EOL;
		$result = $this->callProtectedMethod($sender, 'unwrapDataString', $input);
		$this->assertEquals($expected, $result, 'Failed to unwrap mixed phrasing tags with attributes');
	}
}
