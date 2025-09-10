<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Bake;


use Awyiss\Command\Bake\EnumCommand;
use Awyiss\Event\Bake\GeneralEventsListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\EventManager;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Event\Event;
use Cake\View\View;


/**
 * GeneralEventsListener Test Case
 *
 * @see \Awyiss\Event\Bake\GeneralEventsListener
 */
class GeneralEventsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Bake\GeneralEventsListener
	 */
	protected GeneralEventsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new GeneralEventsListener();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Bake.beforeRender.Controller.controller' => 'beforeRenderControllerController',
			'Command.afterExecute' => 'afterCommandExecute',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::afterCommandExecute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCommandExecuteWithEnumCommandAndPageRoleOption(): void {
		EventListenersProvider::loadListener('GeneralEvents', 'Bake');
		$eventManager = EventManager::instance();

		$eventDispatched = false;
		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventDispatched) {
			$eventDispatched = true;
		});

		$command = $this->createMock(EnumCommand::class);
		$args = $this->createMock(Arguments::class);

		$args->expects($this->once())->method('getOption')->with('is-pagerole')->willReturn(true);

		$event = new Event('Command.afterExecute', $command);

		$this->listener->afterCommandExecute($event, $args);

		$this->assertTrue($eventDispatched);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::afterCommandExecute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCommandExecuteWithEnumCommandButNoPageRoleOption(): void {
		$eventManager = EventManager::instance();

		$eventDispatched = false;
		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventDispatched) {
			$eventDispatched = true;
		});

		$command = $this->createMock(EnumCommand::class);
		$args = $this->createMock(Arguments::class);

		$args->expects($this->once())->method('getOption')->with('is-pagerole')->willReturn(false);

		$event = new Event('Command.afterExecute', $command);

		$this->listener->afterCommandExecute($event, $args);

		$this->assertFalse($eventDispatched);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::afterCommandExecute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCommandExecuteWithEnumCommandAndNullPageRoleOption(): void {
		$eventManager = EventManager::instance();

		$eventDispatched = false;
		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventDispatched) {
			$eventDispatched = true;
		});

		$command = $this->createMock(EnumCommand::class);
		$args = $this->createMock(Arguments::class);

		$args->expects($this->once())->method('getOption')->with('is-pagerole')->willReturn(null);

		$event = new Event('Command.afterExecute', $command);

		$this->listener->afterCommandExecute($event, $args);

		$this->assertFalse($eventDispatched);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::afterCommandExecute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterCommandExecuteWithDifferentCommand(): void {
		$eventManager = EventManager::instance();

		$eventDispatched = false;
		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventDispatched) {
			$eventDispatched = true;
		});

		$command = $this->createMock(Command::class);
		$args = $this->createMock(Arguments::class);

		$args->expects($this->never())->method('getOption');

		$event = new Event('Command.afterExecute', $command);

		$this->listener->afterCommandExecute($event, $args);

		$this->assertFalse($eventDispatched);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::beforeRenderControllerController()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeRenderControllerControllerWithDefaultActions(): void {
		$view = $this->createMock(View::class);

		$view->expects($this->once())->method('get')->with('actions')->willReturn(['index', 'view', 'add', 'edit', 'delete']);

		$view->expects($this->once())->method('set')->with('actions', ['overview', 'add', 'edit', 'delete', 'save']);

		$event = new Event('Bake.beforeRender.Controller.controller', $view);

		$this->listener->beforeRenderControllerController($event);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::beforeRenderControllerController()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeRenderControllerControllerWithCustomActions(): void {
		$view = $this->createMock(View::class);

		$view->expects($this->once())->method('get')->with('actions')->willReturn(['custom', 'actions', 'list']);

		$view->expects($this->never())->method('set');

		$event = new Event('Bake.beforeRender.Controller.controller', $view);

		$this->listener->beforeRenderControllerController($event);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::beforeRenderControllerController()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeRenderControllerControllerWithPartialDefaultActions(): void {
		$view = $this->createMock(View::class);

		$view->expects($this->once())->method('get')->with('actions')->willReturn(['index', 'view', 'add', 'edit']);

		$view->expects($this->never())->method('set');

		$event = new Event('Bake.beforeRender.Controller.controller', $view);

		$this->listener->beforeRenderControllerController($event);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::beforeRenderControllerController()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeRenderControllerControllerWithDifferentOrder(): void {
		$view = $this->createMock(View::class);

		$view->expects($this->once())->method('get')->with('actions')->willReturn(['view', 'index', 'add', 'edit', 'delete']);

		$view->expects($this->once())->method('set')->with('actions', ['overview', 'add', 'edit', 'delete', 'save']);

		$event = new Event('Bake.beforeRender.Controller.controller', $view);

		$this->listener->beforeRenderControllerController($event);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Bake\GeneralEventsListener::beforeRenderControllerController()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeRenderControllerControllerWithEmptyActions(): void {
		$view = $this->createMock(View::class);

		$view->expects($this->once())->method('get')->with('actions')->willReturn([]);

		$view->expects($this->never())->method('set');

		$event = new Event('Bake.beforeRender.Controller.controller', $view);

		$this->listener->beforeRenderControllerController($event);
	}
}
