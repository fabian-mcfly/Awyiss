<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Global;


use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\EventManager;
use Awyiss\Event\Global\GeneralEventsListener;
use Awyiss\Test\TestSuite\TestCase;
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
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Awyiss.getRealm' => 'awyissGetRealm',
			'Awyiss.setRealm' => 'awyissSetRealm',
			'Model.initialize' => 'modelInitialize',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\GeneralEventsListener::awyissGetRealm()
	 * @see \Awyiss\Event\Global\GeneralEventsListener::awyissSetRealm()
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
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
}
