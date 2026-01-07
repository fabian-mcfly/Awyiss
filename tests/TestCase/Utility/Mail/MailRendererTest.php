<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Mail;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Mail\MailRenderer;
use Cake\Http\ServerRequest;
use stdClass;


/**
 * Test case for MailRenderer
 *
 * @see \Awyiss\Utility\Mail\MailRenderer
 */
class MailRendererTest extends TestCase {
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
	 * Test render method without a template set
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailRenderer::render();
	 */
	public function testRenderWithoutTemplate(): void {
		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		/** @var \Cake\Mailer\Mailer $mailer */
		$mailer = new $className('default');

		$bodyPlain = 'This is a plain text email body.';
		$bodyHtml = '<p>This is an HTML email body.</p>';

		$renderer = new MailRenderer();

		$mailer->setRenderer($renderer);
		$mailer
			->viewBuilder()
			->setVars([
				'textHtml' => $bodyHtml,
				'textPlain' => $bodyPlain,
			])
			->setLayout('email/default');

		$this->assertSame([
			'html' => '',
			'text' => '',
		], $renderer->render('', ['html', 'text']));
	}


	/**
	 * Test render method with a template set
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailRenderer::render();
	 */
	public function testRenderWithTemplate(): void {
		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		$mailer = new $className('default');

		$renderer = new MailRenderer();

		$mailer->setRenderer($renderer);
		$mailer->viewBuilder()
			->setVars([
				'resetCode' => 'ABC123',
				'resetUrl' => 'https://example.com/reset',
				'codeValidityHours' => 24,
			])
			->setTemplate('password_reset')
			->setTemplatePath('Frontend/email/customer_center')
			->setLayout('email/default');

		$output = $renderer->render('', ['html', 'text']);

		$this->assertArrayHasKey('html', $output);
		$this->assertArrayHasKey('text', $output);

		// HTML output should contain HTML tags and layout
		$this->assertStringContainsString('<html lang="de">', $output['html']);
		$this->assertStringContainsString('<body>', $output['html']);
		$this->assertStringContainsString('ABC123', $output['html']);
		$this->assertStringContainsString('<a href="https://example.com/reset">', $output['html']);

		// Text output should be plain text (no HTML tags)
		$this->assertIsString($output['text']);
		$this->assertStringNotContainsString('<html', $output['text']);
		$this->assertStringContainsString('ABC123', $output['text']);
		$this->assertStringContainsString('https://example.com/reset', $output['text']);
		$this->assertStringNotContainsString('<a href="https://example.com/reset">', $output['text']);
	}


	/**
	 * Test render method with only HTML type
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailRenderer::render();
	 */
	public function testRenderWithHtmlOnly(): void {
		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		$mailer = new $className('default');

		$customer = new stdClass();
		$customer->firstname = 'Jane';
		$customer->lastname = 'Smith';

		$renderer = new MailRenderer();

		$mailer->setRenderer($renderer);
		$mailer->viewBuilder()
			->setVars([
				'customer' => $customer,
				'resetCode' => 'XYZ789',
				'resetUrl' => 'https://example.com/reset',
				'codeValidityHours' => 48,
			])
			->setTemplate('password_reset')
			->setTemplatePath('Frontend/email/customer_center')
			->setLayout('email/default');

		$output = $renderer->render('', ['html']);

		$this->assertArrayHasKey('html', $output);
		$this->assertArrayNotHasKey('text', $output);
		$this->assertStringContainsString('<html lang="de">', $output['html']);
		$this->assertStringContainsString('<body>', $output['html']);
		$this->assertStringContainsString('XYZ789', $output['html']);
	}


	/**
	 * Test render method with only text type
	 *
	 * @return void
	 * @see \Awyiss\Utility\Mail\MailRenderer::render();
	 */
	public function testRenderWithTextOnly(): void {
		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		$mailer = new $className('default');

		$customer = new stdClass();
		$customer->firstname = 'Bob';
		$customer->lastname = 'Johnson';

		$renderer = new MailRenderer();

		$mailer->setRenderer($renderer);
		$mailer->viewBuilder()
			->setVars([
				'customer' => $customer,
				'resetCode' => 'DEF456',
				'resetUrl' => 'https://example.com/reset',
				'codeValidityHours' => 12,
			])
			->setTemplate('password_reset')
			->setTemplatePath('Frontend/email/customer_center')
			->setLayout('email/default');

		$output = $renderer->render('', ['text']);

		$this->assertArrayHasKey('text', $output);
		$this->assertArrayNotHasKey('html', $output);
		$this->assertIsString($output['text']);
		$this->assertStringNotContainsString('<html', $output['text']);
		$this->assertStringContainsString('DEF456', $output['text']);
	}
}
