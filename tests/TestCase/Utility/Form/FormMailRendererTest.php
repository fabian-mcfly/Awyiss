<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Form;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Form\FormMailRenderer;
use Cake\Http\ServerRequest;


/**
 * Test case for FormMailRenderer
 *
 * @see \Awyiss\Utility\Form\FormMailRenderer
 */
class FormMailRendererTest extends TestCase {
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
	 * @see \Awyiss\Utility\Form\FormMailRenderer::render();
	 */
	public function testRenderWithoutTemplate(): void {
		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		/** @var \Cake\Mailer\Mailer $mailer */
		$mailer = new $className('form');

		$bodyPlain = 'This is a plain text email body.';
		$bodyHtml = '<p>This is an HTML email body.</p>';

		$renderer = new FormMailRenderer();

		$mailer->setRenderer($renderer);
		$mailer->viewBuilder()->setVars([
			'textHtml' => $bodyHtml,
			'textPlain' => $bodyPlain,
			'layout' => 'email/default',
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
	 * @see \Awyiss\Utility\Form\FormMailRenderer::render();
	 */
	public function testRenderWithTemplate(): void {
		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		$mailer = new $className('form');

		$bodyPlain = 'This is a plain text email body.';
		$bodyHtml = '<p>This is an HTML email body.</p>';

		$renderer = new FormMailRenderer();

		$mailer->setRenderer($renderer);
		$mailer->viewBuilder()->setVars([
			'textHtml' => $bodyHtml,
			'textPlain' => $bodyPlain,
			'layout' => 'email/default',
		])
		->setTemplate('Frontend/email/dummy')
		->setLayout('email/default');

		$output = $renderer->render('', ['html', 'text']);

		$this->assertArrayHasKey('html', $output);
		$this->assertArrayHasKey('text', $output);

		$this->assertStringContainsString('<html lang="de">', $output['html']);
		$this->assertStringContainsString('<body>', $output['html']);
		$this->assertStringContainsString('<img src="http://localhost/assets/img/login-logo.png" alt="" class="Logo">', $output['html']);
		$this->assertStringContainsString('<p>This is an HTML email body.</p>', $output['html']);
		$this->assertSame('This is a plain text email body.', $output['text']);
	}
}
