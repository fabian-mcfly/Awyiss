<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\AppView;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\Cell;
use Cake\View\Exception\MissingCellException;
use ReflectionClass;
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
	public static function tearDownAfterClass(): void {
		$reflection = new ReflectionClass(AppView::class);
		$property = $reflection->getProperty('twig');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null);

		$property = $reflection->getProperty('twigInitialized');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(false);
	}


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
	 * @throws \Twig\Error\LoaderError
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitialize(): void {
		$view = $this->getMockBuilder(AppView::class)
			->onlyMethods(['set', 'initTwig'])
			->getMock();

		$view->expects($this->once())->method('set')->with('Awyiss', [
			'VERSION' => Awyiss::VERSION,
			'VERSION_NAME' => Awyiss::VERSION_NAME,
		]);

		$view->expects($this->once())->method('initTwig');

		$view->initialize();
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCell(): void {
		$view = $this->getMockBuilder(AppView::class)->disableOriginalConstructor()->onlyMethods([])->getMock();

		$view->setRequest($this->createMock(ServerRequest::class));
		$view->setResponse($this->createMock(Response::class));

		$cell = $view->cell('Test');

		$this->assertInstanceOf(Cell::class, $cell);
	}


	/**
	 * @return void
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
