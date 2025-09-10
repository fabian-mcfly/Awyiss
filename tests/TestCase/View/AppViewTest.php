<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\AppView;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\Exception\MissingCellException;
use Twig\Environment;
use Twig\Markup;


/**
 * AppViewTest class
 */
class AppViewTest extends TestCase {
	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::setRealm('Backend');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\AppView::initialize()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitialize(): void {
		$view = new AppView($this->createMock(ServerRequest::class), $this->createMock(Response::class));

		$this->assertArrayHasKey('VERSION', $view->get('Awyiss'));
		$this->assertArrayHasKey('VERSION_NAME', $view->get('Awyiss'));

		$this->assertInstanceOf(Environment::class, $view->getTwig());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\AppView::cell()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCell(): void {
		$view = $this->getMockBuilder(AppView::class)->disableOriginalConstructor()->onlyMethods([])->getMock();

		$view->setRequest($this->createMock(ServerRequest::class));
		$view->setResponse($this->createMock(Response::class));

		$cell = $view->cell('Test');

		$this->assertIsObject($cell);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\AppView::cell()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCellUnknownCellThrowsException(): void {
		$view = $this->getMockBuilder(AppView::class)->disableOriginalConstructor()->onlyMethods([])->getMock();

		$view->setRequest($this->createMock(ServerRequest::class));
		$view->setResponse($this->createMock(Response::class));

		$this->expectException(MissingCellException::class);
		$view->cell('Unknown');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\AppView::loadHelpers()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadHelpers(): void {
		$twig = $this->createMock(Environment::class);
		$calls = [];
		$twig->expects($this->atLeastOnce())->method('addGlobal')->willReturnCallback(function ($name, $value) use (&$calls) {
			$calls[ $name ] = $value;
		});

		$view = $this->getMockBuilder(AppView::class)->disableOriginalConstructor()->onlyMethods(['getTwig'])->getMock();

		$view->expects($this->once())->method('getTwig')->willReturn($twig);

		$view->helpers()->load('Dummy');
		$view->loadHelpers();

		$this->assertArrayHasKey('DummyHelper', $calls);

		$dummyResult = $calls['DummyHelper']->dummyMethod();
		$this->assertSame('dummy', $dummyResult);

		$dummyHtmlResult = $calls['DummyHelper']->dummyHtmlMethod();
		$this->assertInstanceOf(Markup::class, $dummyHtmlResult);
	}
}
