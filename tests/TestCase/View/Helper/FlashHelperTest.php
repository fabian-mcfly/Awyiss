<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\FlashHelper;
use Cake\Http\ServerRequest;


/**
 * FlashHelperTest class
 */
class FlashHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $view;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function setUp(): void {
		parent::setUp();

		$request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'TheController',
				'action' => 'theAction',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		Router::setRequest($request);

		$this->view = new BackendView($request);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FlashHelper::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRender(): void {
		$flashHelper = new FlashHelper($this->view);

		$this->view->getRequest()->getFlash()->set('Message 1', ['key' => '*']);
		$this->view->getRequest()->getFlash()->set('Message 2', ['key' => '*']);

		$result = $flashHelper->render();

		$this->assertStringContainsString('Message 1', $result);
		$this->assertStringContainsString('Message 2', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FlashHelper::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderSpecificFlashMessageRendersOnlySpecifiedMessage(): void {
		$flashHelper = new FlashHelper($this->view);

		$this->view->getRequest()->getFlash()->set('Message 1', ['key' => '*']);
		$this->view->getRequest()->getFlash()->set('Message 2', ['key' => 'pages']);

		$result = $flashHelper->render('pages');

		$this->assertStringNotContainsString('Message 1', $result);
		$this->assertStringContainsString('Message 2', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FlashHelper::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderNoFlashMessagesReturnsNull(): void {
		$flashHelper = new FlashHelper($this->view);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $flashHelper->render('*');

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FlashHelper::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderFlashMessageEscapesHtml(): void {
		$flashHelper = new FlashHelper($this->view);

		$this->view->getRequest()->getFlash()->set('<script>alert("XSS")</script>', ['key' => '*']);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $flashHelper->render('*');

		$this->assertStringNotContainsString('<script>alert("XSS")</script>', $result);
		$this->assertStringContainsString('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FlashHelper::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderFlashMessageWithoutEscapingDoesNotEscapeHtml(): void {
		$flashHelper = new FlashHelper($this->view);

		$this->view->getRequest()->getFlash()->set('<script>alert("XSS")</script>', ['key' => '*', 'params' => ['escape' => false]]);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $flashHelper->render('*');

		$this->assertStringContainsString('<script>alert("XSS")</script>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FlashHelper::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderFlashMessageWithClassIncludesClassAttribute(): void {
		$flashHelper = new FlashHelper($this->view);
		$this->view->getRequest()->getFlash()->set('Message with class', ['key' => '*', 'params' => ['class' => 'AlertClass']]);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $flashHelper->render('*');

		$this->assertMatchesRegularExpression('/class="[^"]*AlertClass[^"]*"/', $result);
	}
}
