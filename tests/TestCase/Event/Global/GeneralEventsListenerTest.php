<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Global;


use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\EventManager;
use Awyiss\Event\Global\GeneralEventsListener;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Event\Event;


/**
 * GeneralEventsListener Test Case
 *
 * @see \Awyiss\Event\Global\GeneralEventsListener
 */
class GeneralEventsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Global\GeneralEventsListener
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

		Cache::pool('persistent')->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Awyiss.getRealm' => 'awyissGetRealm',
			'Awyiss.setRealm' => 'awyissSetRealm',
			'Controller.initialize' => 'controllerInitialize',
			'Model.initialize' => 'modelInitialize',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::awyissGetRealm()
	 * @see \Awyiss\Event\Global\GeneralEventsListener::awyissSetRealm()
	 */
	public function testAwyissGetSetRealm(): void {
		EventListenersProvider::loadListener('GeneralEvents', 'Global');
		$eventManager = EventManager::instance();

		$setEvent = new Event('Awyiss.setRealm', null, ['realm' => 'Frontend']);
		$eventManager->dispatch($setEvent);

		$getEvent = new Event('Awyiss.getRealm');
		$result = $eventManager->dispatch($getEvent)->getResult();

		$this->assertSame('Frontend', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::awyissSetRealm()
	 */
	public function testSetRealmLoadsListenersForAlreadyLoadedModels(): void {
		EventListenersProvider::loadListener('GeneralEvents', 'Global');
		$eventManager = EventManager::instance();

		$this->assertEmpty($eventManager->listeners('Model.Usergroups.afterSave'));

		$tableLocator = $this->getTableLocator();
		$tableLocator->clear();

		$tableLocator->get('Usergroups');

		$setEvent = new Event('Awyiss.setRealm', null, ['realm' => 'Backend']);
		$eventManager->dispatch($setEvent);

		// Should not be empty since an usergroups listeners exists in the backend realm
		$this->assertNotEmpty($eventManager->listeners('Model.Usergroups.afterSave'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::modelInitialize()
	 */
	public function testModelInitializeLoadsModelListenersForCurrentRealm(): void {
		EventListenersProvider::loadListener('GeneralEvents', 'Global');
		$eventManager = EventManager::instance();

		$this->assertEmpty($eventManager->listeners('Model.Usergroups.afterSave'));

		$tableLocator = $this->getTableLocator();
		$tableLocator->clear();

		$tableLocator->get('Usergroups');

		// Should still be empty since no realm exists
		$this->assertEmpty($eventManager->listeners('Model.Usergroups.afterSave'));

		$tableLocator = $this->getTableLocator();
		$tableLocator->clear();

		$setEvent = new Event('Awyiss.setRealm', null, ['realm' => 'Backend']);
		$eventManager->dispatch($setEvent);

		$tableLocator->get('Usergroups');

		// Should not be empty since an usergroups listeners exists in the backend realm
		$this->assertNotEmpty($eventManager->listeners('Model.Usergroups.afterSave'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::modelInitialize()
	 */
	public function testModelInitializeNotLoadsModelListenersForDifferentRealm(): void {
		EventListenersProvider::loadListener('GeneralEvents', 'Global');
		$eventManager = EventManager::instance();

		$this->assertEmpty($eventManager->listeners('Model.Usergroups.afterSave'));

		$tableLocator = $this->getTableLocator();
		$tableLocator->clear();

		$tableLocator->get('Usergroups');

		// Should still be empty since no realm exists
		$this->assertEmpty($eventManager->listeners('Model.Usergroups.afterSave'));

		$tableLocator = $this->getTableLocator();
		$tableLocator->clear();

		$setEvent = new Event('Awyiss.setRealm', null, ['realm' => 'Frontend']);
		$eventManager->dispatch($setEvent);

		$tableLocator->get('Usergroups');

		// Should still be empty since no usergroups listeners exists in the frontend realm
		$this->assertEmpty($eventManager->listeners('Model.Usergroups.afterSave'));
	}


	/**
	 * Test that periodic events are executed when no cache exists
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsWhenNoCacheExists(): void {
		$eventDispatched = false;

		Configure::write('PeriodicEvents', [
			'hourly' => [
				'Test.periodicEvent',
			],
		]);

		$eventManager = EventManager::instance();
		$eventManager->on('Test.periodicEvent', function () use (&$eventDispatched) {
			$eventDispatched = true;
		});

		$event = new Event('Controller.initialize');
		$this->listener->controllerInitialize($event);

		$this->assertTrue($eventDispatched);
	}


	/**
	 * Test that hourly events are not executed before interval passes
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsHourlyNotExecutedBeforeInterval(): void {
		$executionCount = 0;

		Configure::write('PeriodicEvents', [
			'hourly' => [
				'Test.hourlyEvent',
			],
		]);

		$eventManager = EventManager::instance();
		$eventManager->on('Test.hourlyEvent', function () use (&$executionCount) {
			$executionCount++;
		});

		// First execution
		$event = new Event('Controller.initialize');
		$this->listener->controllerInitialize($event);
		$this->assertSame(1, $executionCount);

		// Second execution immediately after - should not run
		$this->listener->controllerInitialize($event);
		$this->assertSame(1, $executionCount);
	}


	/**
	 * Test that hourly events are executed after interval passes
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsHourlyExecutedAfterInterval(): void {
		$executionCount = 0;

		Configure::write('PeriodicEvents', [
			'hourly' => [
				'Test.hourlyEvent',
			],
		]);

		$eventManager = EventManager::instance();
		$eventManager->on('Test.hourlyEvent', function () use (&$executionCount) {
			$executionCount++;
		});

		// First execution
		$event = new Event('Controller.initialize');
		$this->listener->controllerInitialize($event);
		$this->assertSame(1, $executionCount);

		// Simulate time passing by modifying cache
		$cache = Cache::pool('persistent');
		$cache->set('periodic_event_last_run_hourly', time() - 3601);

		// Second execution after 1 hour - should run
		$this->listener->controllerInitialize($event);
		$this->assertSame(2, $executionCount);
	}


	/**
	 * Test that daily events are not executed before interval passes
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsDailyNotExecutedBeforeInterval(): void {
		$executionCount = 0;

		Configure::write('PeriodicEvents', [
			'daily' => [
				'Test.dailyEvent',
			],
		]);

		$eventManager = EventManager::instance();
		$eventManager->on('Test.dailyEvent', function () use (&$executionCount) {
			$executionCount++;
		});

		// First execution
		$event = new Event('Controller.initialize');
		$this->listener->controllerInitialize($event);
		$this->assertSame(1, $executionCount);

		// Second execution immediately after - should not run
		$this->listener->controllerInitialize($event);
		$this->assertSame(1, $executionCount);
	}


	/**
	 * Test that daily events are executed after interval passes
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsDailyExecutedAfterInterval(): void {
		$executionCount = 0;

		Configure::write('PeriodicEvents', [
			'daily' => [
				'Test.dailyEvent',
			],
		]);

		$eventManager = EventManager::instance();
		$eventManager->on('Test.dailyEvent', function () use (&$executionCount) {
			$executionCount++;
		});

		// First execution
		$event = new Event('Controller.initialize');
		$this->listener->controllerInitialize($event);
		$this->assertSame(1, $executionCount);

		// Simulate time passing by modifying cache
		$cache = Cache::pool('persistent');
		$cache->set('periodic_event_last_run_daily', time() - 86401);

		// Second execution after 24 hours - should run
		$this->listener->controllerInitialize($event);
		$this->assertSame(2, $executionCount);
	}


	/**
	 * Test that multiple events in same interval are all executed
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsMultipleEventsInSameInterval(): void {
		$event1Dispatched = false;
		$event2Dispatched = false;

		Configure::write('PeriodicEvents', [
			'hourly' => [
				'Test.event1',
				'Test.event2',
			],
		]);

		$eventManager = EventManager::instance();
		$eventManager->on('Test.event1', function () use (&$event1Dispatched) {
			$event1Dispatched = true;
		});
		$eventManager->on('Test.event2', function () use (&$event2Dispatched) {
			$event2Dispatched = true;
		});

		$event = new Event('Controller.initialize');
		$this->listener->controllerInitialize($event);

		$this->assertTrue($event1Dispatched);
		$this->assertTrue($event2Dispatched);
	}


	/**
	 * Test that callable events are executed directly
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsWithCallable(): void {
		$callableExecuted = false;

		Configure::write('PeriodicEvents', [
			'hourly' => [
				function () use (&$callableExecuted) {
					$callableExecuted = true;
				},
			],
		]);

		$event = new Event('Controller.initialize');
		$this->listener->controllerInitialize($event);

		$this->assertTrue($callableExecuted);
	}


	/**
	 * Test that mixed events and callables work together
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsWithMixedEventsAndCallables(): void {
		$callableExecuted = false;
		$eventDispatched = false;

		Configure::write('PeriodicEvents', [
			'daily' => [
				'Test.mixedEvent',
				function () use (&$callableExecuted) {
					$callableExecuted = true;
				},
			],
		]);

		$eventManager = EventManager::instance();
		$eventManager->on('Test.mixedEvent', function () use (&$eventDispatched) {
			$eventDispatched = true;
		});

		$event = new Event('Controller.initialize');
		$this->listener->controllerInitialize($event);

		$this->assertTrue($eventDispatched);
		$this->assertTrue($callableExecuted);
	}


	/**
	 * Test that different intervals are handled independently
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsMultipleIntervals(): void {
		$hourlyExecuted = false;
		$dailyExecuted = false;

		Configure::write('PeriodicEvents', [
			'hourly' => [
				function () use (&$hourlyExecuted) {
					$hourlyExecuted = true;
				},
			],
			'daily' => [
				function () use (&$dailyExecuted) {
					$dailyExecuted = true;
				},
			],
		]);

		$event = new Event('Controller.initialize');
		$this->listener->controllerInitialize($event);

		$this->assertTrue($hourlyExecuted);
		$this->assertTrue($dailyExecuted);
	}


	/**
	 * Test that empty config does not execute anything
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsWithEmptyConfig(): void {
		Configure::write('PeriodicEvents', []);

		$event = new Event('Controller.initialize');
		// Should not throw any errors
		$this->listener->controllerInitialize($event);

		$this->assertTrue(true);
	}


	/**
	 * Test that empty event arrays are skipped
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsWithEmptyEventArrays(): void {
		Configure::write('PeriodicEvents', [
			'hourly' => [],
			'daily' => [],
		]);

		$event = new Event('Controller.initialize');
		// Should not throw any errors
		$this->listener->controllerInitialize($event);

		$this->assertTrue(true);
	}


	/**
	 * Test that no config does not execute anything
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::executePeriodicEvents()
	 */
	public function testExecutePeriodicEventsWithNoConfig(): void {
		$event = new Event('Controller.initialize');
		// Should not throw any errors
		$this->listener->controllerInitialize($event);

		$this->assertTrue(true);
	}
}
