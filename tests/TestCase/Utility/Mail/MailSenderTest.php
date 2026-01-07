<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Mail;


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Mail\MailSender;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\EmailTrait;


/**
 * Test case for MailSender
 *
 * @see \Awyiss\Utility\Mail\MailSender
 */
class MailSenderTest extends TestCase {
	use EmailTrait;


	/**
	 * @inheritDoc
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
	}


	/**
	 * @inheritDoc
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->cleanupEmailTrait();
	}


	/**
	 * Test setter methods return instance for chaining
	 *
	 * @return void
	 */
	public function testSettersReturnInstanceForChaining(): void {
		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setSenderName('Sender Name')
			->setRecipientEmail('recipient@example.com')
			->setRecipientName('Recipient Name')
			->setSubject('Test Subject')
			->setTransportProfile('default')
			->setLayout('default')
			->setFormat('both')
			->setData(['key' => 'value'])
			->addCc('cc@example.com', 'CC Name')
			->addBcc('bcc@example.com', 'BCC Name')
			->setTemplate('test-template')
			->setTemplatePath('Test');

		$this->assertInstanceOf(MailSender::class, $result);
	}


	/**
	 * Test send method sends email with basic configuration
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendBasicEmail(): void {
		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setSenderName('Sender Name')
			->setRecipientEmail('recipient@example.com')
			->setRecipientName('Recipient Name')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setData(['content' => 'Test content'])
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
		$this->assertMailSubjectContains('Test Subject');
		$this->assertMailContains('Test content');
	}


	/**
	 * Test send method sends email with CC recipients
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithCc(): void {
		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setSenderName('Sender Name')
			->setRecipientEmail('recipient@example.com')
			->setRecipientName('Recipient Name')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setData(['content' => 'Test content'])
			->addCc('cc1@example.com', 'CC1 Name')
			->addCc('cc2@example.com', 'CC2 Name')
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
		$this->assertMailSentWith('cc1@example.com', 'cc');
		$this->assertMailSentWith('cc2@example.com', 'cc');
	}


	/**
	 * Test send method sends email with BCC recipients
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithBcc(): void {
		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setSenderName('Sender Name')
			->setRecipientEmail('recipient@example.com')
			->setRecipientName('Recipient Name')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setData(['content' => 'Test content'])
			->addBcc('bcc1@example.com', 'BCC1 Name')
			->addBcc('bcc2@example.com')
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
		$this->assertMailSentWith('bcc1@example.com', 'bcc');
		$this->assertMailSentWith('bcc2@example.com', 'bcc');
	}


	/**
	 * Test send method uses safe sender and reply-to when sender email is external
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithSafeSenderAndReplyTo(): void {
		Configure::write('Awyiss.System.Frontend.meta.titleAppendix', 'Test Site');

		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('user@external.com')
			->setSenderName('External User')
			->setRecipientEmail('recipient@example.com')
			->setRecipientName('Recipient Name')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setData(['content' => 'Test content'])
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
		$this->assertMailSentWith('user@external.com', 'replyTo');
	}


	/**
	 * Test send method uses default sender when no sender email provided
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithoutSenderEmail(): void {
		Configure::write('Awyiss.System.Frontend.meta.titleAppendix', 'Test Site');

		$sender = new MailSender('default');

		$result = $sender
			->setRecipientEmail('recipient@example.com')
			->setRecipientName('Recipient Name')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setData(['content' => 'Test content'])
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
		$this->assertMailSubjectContains('Test Subject');
	}


	/**
	 * Test send method with HTML format only
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithHtmlFormat(): void {
		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setRecipientEmail('recipient@example.com')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setFormat('html')
			->setData(['content' => 'Test content'])
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
		$this->assertMailContains('Test content');
	}


	/**
	 * Test send method with text format only
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithTextFormat(): void {
		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setRecipientEmail('recipient@example.com')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setFormat('text')
			->setData(['content' => 'Test content'])
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
		$this->assertMailContains('Test content');
	}


	/**
	 * Test send method with both format (default)
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithBothFormat(): void {
		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setRecipientEmail('recipient@example.com')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setFormat('both')
			->setData(['content' => 'Test content'])
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
		$this->assertMailContains('Test content');
	}


	/**
	 * Test send method with custom layout
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithCustomLayout(): void {
		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setRecipientEmail('recipient@example.com')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setLayout('default')
			->setData(['content' => 'Test content'])
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
	}


	/**
	 * Test send method with custom template path
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithCustomTemplatePath(): void {
		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setRecipientEmail('recipient@example.com')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath(
				'CustomPath' . DIRECTORY_SEPARATOR,
				'Frontend' . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR
			)
			->setData(['content' => 'Test content'])
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
	}


	/**
	 * Test send method with empty sender name uses default from config
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailWithoutSenderNameUsesConfigDefault(): void {
		Configure::write('Awyiss.System.Frontend.meta.titleAppendix', 'Test Site');

		$sender = new MailSender('default');

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setRecipientEmail('recipient@example.com')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setData(['content' => 'Test content'])
			->send();

		$this->assertTrue($result);
		$this->assertMailSentTo('recipient@example.com');
		$this->assertMailSentFrom([
			'noreply@localhost' => 'Test Site',
		]);
	}


	/**
	 * Test send method passes data to template
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailSender::send()
	 */
	public function testSendEmailPassesDataToTemplate(): void {
		$sender = new MailSender('default');

		$testData = [
			'content' => 'Custom test content',
			'userName' => 'John Doe',
			'message' => 'Test message',
			'items' => ['item1', 'item2', 'item3'],
		];

		$result = $sender
			->setSenderEmail('sender@example.com')
			->setRecipientEmail('recipient@example.com')
			->setSubject('Test Subject')
			->setTemplate('dummy')
			->setTemplatePath('')
			->setData($testData)
			->send();

		$this->assertTrue($result);
		$this->assertMailContains('Custom test content');
		$this->assertMailContains('John Doe');
		$this->assertMailContains('Test message');
		$this->assertMailContains('item1');
		$this->assertMailContains('item2');
		$this->assertMailContains('item3');
	}
}
