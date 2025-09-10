<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\ORM\Locator;


use Awyiss\Event\EventManager;
use Awyiss\ORM\Locator\TableLocator;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Customer\Model\Table\CarsTable;


/**
 * Test case for TableLocator
 *
 * @see \Awyiss\ORM\Locator\TableLocator
 */
class TableLocatorTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\ORM\Locator\TableLocator
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFactoryLocatorReturnsTableLocatorInstance(): void {
		$locator = FactoryLocator::get('Table');
		$this->assertInstanceOf(TableLocator::class, $locator);
	}


	/**
	 * @return void
	 * @see \Awyiss\ORM\Locator\TableLocator::getInstances()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetInstancesReturnsEmptyArrayInitially(): void {
		/** @var \Awyiss\ORM\Locator\TableLocator $locator */
		$locator = FactoryLocator::get('Table');
		$locator->clear();

		$instances = $locator->getInstances();
		$this->assertIsArray($instances);
		$this->assertEmpty($instances);
	}


	/**
	 * @return void
	 * @see \Awyiss\ORM\Locator\TableLocator::getInstances()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetInstancesReturnsNonEmptyArrayAfterCreatingInstance(): void {
		/** @var \Awyiss\ORM\Locator\TableLocator $locator */
		$locator = FactoryLocator::get('Table');
		$locator->clear();

		// Create a dummy table instance
		$contentsTable = $locator->get('Contents');

		$instances = $locator->getInstances();
		$this->assertIsArray($instances);
		$this->assertNotEmpty($instances);
		$this->assertArrayHasKey('Contents', $instances);
		$this->assertSame($contentsTable, $instances['Contents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\ORM\Locator\TableLocator::get()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetReturnsCustomerClass(): void {
		/** @var \Awyiss\ORM\Locator\TableLocator $locator */
		$locator = FactoryLocator::get('Table');

		$carsTable = $locator->get('Cars');
		$this->assertInstanceOf(CarsTable::class, $carsTable);
	}


	/**
	 * @return void
	 * @see \Awyiss\ORM\Locator\TableLocator::get()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLoadsGlobalEventListeners(): void {
		/** @var \Awyiss\ORM\Locator\TableLocator $locator */
		$locator = FactoryLocator::get('Table');
		$locator->clear();

		$eventListeners = EventManager::instance()->listeners('Model.Cars.dummyListener');
		$this->assertEmpty($eventListeners, 'Event listeners should be empty before getting the Cars table.');

		// Create a dummy table instance
		$carsTable = $locator->get('Cars');

		$eventListeners = EventManager::instance()->listeners('Model.Cars.dummyListener');
		$this->assertNotEmpty($eventListeners, 'Event listeners should not be empty after getting the Cars table.');

		$event = new Event('Model.Cars.dummyListener', $carsTable);
		$carsTable->getEventManager()->dispatch($event);

		$this->assertSame('dummyListener called in CarsListener', $event->getResult(), 'Event listener should have been called and set the result.');
	}
}
