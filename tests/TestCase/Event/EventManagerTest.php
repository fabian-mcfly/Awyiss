<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;
use Cake\Event\EventInterface;


/**
 * EventManager Test Case
 *
 * @see \Awyiss\Event\EventManager
 */
class EventManagerTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Event\EventManager::dispatch()
	 */
	public function testDispatchWithEventObject(): void {
		$event = new Event('Test.event', null, ['test' => 'data']);

		$result = $this->_eventManager->dispatch($event);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(EventInterface::class, $result);
		$this->assertSame('Test.event', $result->getName());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventManager::dispatch()
	 */
	public function testDispatchWithEventString(): void {
		$result = $this->_eventManager->dispatch('Test.event');

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(EventInterface::class, $result);
		$this->assertSame('Test.event', $result->getName());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventManager::dispatch()
	 */
	public function testDispatchPreservesEventData(): void {
		$originalData = ['key' => 'value', 'number' => 42];
		$event = new Event('Test.event', null, $originalData);

		$result = $this->_eventManager->dispatch($event);

		$this->assertSame($originalData, $result->getData());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventManager::lazyLoadListeners()
	 */
	public function testLazyLoadListenersWithSimpleScope(): void {
		$this->assertEmpty($this->_eventManager->listeners('Model.Designs.afterSave'));

		$this->_eventManager->dispatch('Designs.testEvent');

		$this->assertNotEmpty($this->_eventManager->listeners('Model.Designs.afterSave'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventManager::lazyLoadListeners()
	 */
	public function testLazyLoadListenersWithGeneralScope(): void {
		$this->assertEmpty($this->_eventManager->listeners('Model.Designs.afterSave'));

		$this->_eventManager->dispatch('Model.Designs.testEvent');

		$this->assertNotEmpty($this->_eventManager->listeners('Model.Designs.afterSave'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventManager::lazyLoadListeners()
	 */
	public function testLazyLoadListenersWithPageRole(): void {
		$this->assertEmpty($this->_eventManager->listeners('Model.Pages.afterSave'));
		$this->assertEmpty($this->_eventManager->listeners('Model.News.afterSave'));

		$this->_eventManager->dispatch('Model.Pages.testEvent');

		$this->assertNotEmpty($this->_eventManager->listeners('Model.Pages.afterSave'));
		$this->assertNotEmpty($this->_eventManager->listeners('Model.News.afterSave'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventManager::lazyLoadListeners()
	 */
	public function testLazyLoadListenersWithPageRoleFallback(): void {
		$this->assertEmpty($this->_eventManager->listeners('Model.Pages.afterSave'));
		$this->assertEmpty($this->_eventManager->listeners('Model.News.afterSave'));

		$this->_eventManager->dispatch('Model.News.testEvent');

		$this->assertNotEmpty($this->_eventManager->listeners('Model.Pages.afterSave'));
		$this->assertNotEmpty($this->_eventManager->listeners('Model.News.afterSave'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventManager::lazyLoadListeners()
	 */
	public function testLazyLoadListenersForDifferentRealm(): void {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);

		$this->assertEmpty($this->_eventManager->listeners('Model.Pages.afterSave'));
		$this->assertEmpty($this->_eventManager->listeners('Model.News.afterSave'));

		$this->_eventManager->dispatch('Model.News.testEvent');

		$this->assertEmpty($this->_eventManager->listeners('Model.Pages.afterSave'));
		$this->assertEmpty($this->_eventManager->listeners('Model.News.afterSave'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventManager::lazyLoadListeners()
	 */
	public function testLazyLoadListenersWithoutRealm(): void {
		Awyiss::setRealm(null);

		$this->assertEmpty($this->_eventManager->listeners('Model.Pages.afterSave'));
		$this->assertEmpty($this->_eventManager->listeners('Model.News.afterSave'));

		$this->_eventManager->dispatch('Model.News.testEvent');

		$this->assertEmpty($this->_eventManager->listeners('Model.Pages.afterSave'));
		$this->assertEmpty($this->_eventManager->listeners('Model.News.afterSave'));
	}
}
