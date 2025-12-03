<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\RulesChecker;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use RuntimeException;


/**
 * EventTriggerBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\EventTriggerBehavior
 */
class EventTriggerBehaviorTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\EventTriggerBehavior::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$table = TableRegistry::getTableLocator()->get('Cars');
		$behavior = $table->getBehavior('EventTrigger');

		$this->assertSame([
			'Model.beforeMarshal' => 'beforeMarshal',
			'Model.afterMarshal' => 'afterMarshal',
			'Model.beforeFind' => 'beforeFind',
			'Model.buildValidator' => 'buildValidator',
			'Model.buildRules' => 'buildRules',
			'Model.beforeRules' => 'beforeRules',
			'Model.afterRules' => 'afterRules',
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
			'Model.afterSaveCommit' => 'afterSaveCommit',
			'Model.beforeSaveAssociations' => 'beforeSaveAssociations',
			'Model.afterSaveAssociations' => 'afterSaveAssociations',
			'Model.beforeDelete' => 'beforeDelete',
			'Model.afterDelete' => 'afterDelete',
			'Model.afterDeleteCommit' => 'afterDeleteCommit',
			'Model.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.afterSoftDelete' => 'afterSoftDelete',
			'Model.afterSoftDeleteCommit' => 'afterSoftDeleteCommit',
		], $behavior->implementedEvents());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\EventTriggerBehavior::dispatchEvent()
	 */
	public function testBehaviorDispatchesScopedEvents(): void {
		$eventsSent = [
			'beforeMarshal' => false,
			'afterMarshal' => false,
			'beforeFind' => false,
			'buildValidator' => false,
			'buildRules' => false,
			'beforeRules' => false,
			'afterRules' => false,
			'beforeSave' => false,
			'afterSave' => false,
			'afterSaveCommit' => false,
			'beforeSaveAssociations' => false,
			'afterSaveAssociations' => false,
			'beforeDelete' => false,
			'afterDelete' => false,
			'afterDeleteCommit' => false,
			'beforeSoftDelete' => false,
			'afterSoftDelete' => false,
			'afterSoftDeleteCommit' => false,
		];

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = TableRegistry::getTableLocator()->get('Cars');
		// Disable soft delete behavior, otherwise beforeDelete will be stopped before the listener is called
		$table->getBehavior('SoftDelete')->setConfig('enabled', false);
		$entity = $table->newDefaultEntity(['title' => 'Test Car', 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'dark']]);
		$result = $table->save($entity);

		$this->assertNotEmpty($result);

		$eventManager = $table->getEventManager();
		foreach ($eventsSent as $eventName => $sent) {
			$eventManager->on('Model.Cars.' . $eventName, ['priority' => 0], function (EventInterface $event) use (&$eventsSent, $eventName) {
				$eventsSent[ $eventName ] = true;
				$event->stopPropagation();
				$event->setResult(false);
			});

			$data = unserialize(serialize($entity));
			$options = new ArrayObject();

			if (in_array($eventName, ['beforeMarshal', 'afterMarshal'])) {
				$data = new ArrayObject(['foo' => 'bar']);
			}
			elseif ($eventName === 'beforeFind') {
				$data = $table->find();
			}
			elseif ($eventName === 'buildValidator') {
				$data = $table->getValidator();
				$options = RulesChecker::CREATE;
			}
			elseif ($eventName === 'buildRules') {
				$data = $table->rulesChecker();
			}

			$event = new Event('Model.' . $eventName, null, [$data, $options, true]);
			try {
				$eventManager->dispatch($event);
			}
			catch (RuntimeException) {
				// Ignore exceptions, we just want to test if the event was dispatched
			}

			$this->assertTrue($eventsSent[ $eventName ], sprintf('Event `%s` was not dispatched', $eventName));
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\EventTriggerBehavior::dispatchCreateUpdateEvents()
	 */
	public function testBehaviorDispatchesCreateEvents(): void {
		/** @var \Customer\Model\Table\CarsTable $table */
		$table = TableRegistry::getTableLocator()->get('Cars');
		$entity = $table->newDefaultEntity(['title' => 'Test Car', 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'foobar']]);

		$eventManager = $table->getEventManager();

		$eventsSent = [
			'beforeCreate' => false,
			'afterCreate' => false,
			'afterCreateCommit' => false,
			'beforeUpdate' => false,
			'afterUpdate' => false,
			'afterUpdateCommit' => false,
		];

		foreach (
			[
				'beforeCreate' => 'beforeSave',
				'beforeUpdate' => 'beforeSave',
				'afterCreate' => 'afterSave',
				'afterUpdate' => 'afterSave',
				'afterCreateCommit' => 'afterSaveCommit',
				'afterUpdateCommit' => 'afterSaveCommit',
			] as $newEventName => $baseEventName
		) {
			$eventManager->on('Model.Cars.' . $newEventName, ['priority' => 0], function (EventInterface $event) use (&$eventsSent, $newEventName) {
				$eventsSent[ $newEventName ] = true;
				$event->stopPropagation();
				$event->setResult(false);
			});

			$data = unserialize(serialize($entity));
			$options = new ArrayObject();

			$event = new Event('Model.' . $baseEventName, null, [$data, $options]);
			$eventManager->dispatch($event);

			if (in_array($newEventName, ['beforeCreate', 'afterCreate', 'afterCreateCommit'])) {
				$this->assertTrue($eventsSent[ $newEventName ], sprintf('Event `%s` was not dispatched', $newEventName));
			}
			else {
				$this->assertFalse($eventsSent[ $newEventName ], sprintf('Event `%s` should not be dispatched', $newEventName));
			}
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\EventTriggerBehavior::dispatchCreateUpdateEvents()
	 */
	public function testBehaviorDispatchesUpdateEvents(): void {
		/** @var \Customer\Model\Table\CarsTable $table */
		$table = TableRegistry::getTableLocator()->get('Cars');
		$entity = $table->newDefaultEntity(['title' => 'Test Car', 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'dark']]);
		$result = $table->save($entity);

		$this->assertNotEmpty($result);

		$eventManager = $table->getEventManager();

		$eventsSent = [
			'beforeCreate' => false,
			'afterCreate' => false,
			'afterCreateCommit' => false,
			'beforeUpdate' => false,
			'afterUpdate' => false,
			'afterUpdateCommit' => false,
		];

		foreach (
			[
				'beforeCreate' => 'beforeSave',
				'beforeUpdate' => 'beforeSave',
				'afterCreate' => 'afterSave',
				'afterUpdate' => 'afterSave',
				'afterCreateCommit' => 'afterSaveCommit',
				'afterUpdateCommit' => 'afterSaveCommit',
			] as $newEventName => $baseEventName
		) {
			$eventManager->on('Model.Cars.' . $newEventName, ['priority' => 0], function (EventInterface $event) use (&$eventsSent, $newEventName) {
				$eventsSent[ $newEventName ] = true;
				$event->stopPropagation();
				$event->setResult(false);
			});

			$data = unserialize(serialize($entity));
			$options = new ArrayObject(['_primary' => false]);

			$event = new Event('Model.' . $baseEventName, null, [$data, $options]);
			$eventManager->dispatch($event);

			if (in_array($newEventName, ['beforeCreate', 'afterCreate', 'afterCreateCommit'])) {
				$this->assertFalse($eventsSent[ $newEventName ], sprintf('Event `%s` should not be dispatched', $newEventName));
			}
			else {
				$this->assertTrue($eventsSent[ $newEventName ], sprintf('Event `%s` was not dispatched', $newEventName));
			}
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\EventTriggerBehavior::dispatchCopyEvents()
	 */
	public function testBehaviorDispatchesCopyEventsForCopy(): void {
		/** @var \Customer\Model\Table\CarsTable $table */
		$table = TableRegistry::getTableLocator()->get('Cars');
		$entity = $table->newDefaultEntity(['title' => 'Test Car', 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'dark']]);
		$result = $table->save($entity);

		$this->assertNotEmpty($result);

		$entity->setNew(true);

		$eventManager = $table->getEventManager();

		$eventsSent = [
			'beforeCopy' => false,
			'afterCopy' => false,
			'afterCopyCommit' => false,
		];

		foreach (
			[
				'beforeCopy' => 'beforeSave',
				'afterCopy' => 'afterSave',
				'afterCopyCommit' => 'afterSaveCommit',
			] as $newEventName => $baseEventName
		) {
			$eventManager->on('Model.Cars.' . $newEventName, ['priority' => 0], function (EventInterface $event) use (&$eventsSent, $newEventName) {
				$eventsSent[ $newEventName ] = true;
				$event->stopPropagation();
				$event->setResult(false);
			});

			$data = unserialize(serialize($entity));
			$options = new ArrayObject(['_primary' => true, 'isCopy' => true]);

			$event = new Event('Model.' . $baseEventName, null, [$data, $options]);
			$eventManager->dispatch($event);

			$this->assertTrue($eventsSent[ $newEventName ], sprintf('Event `%s` was not dispatched', $newEventName));
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\EventTriggerBehavior::dispatchCopyEvents()
	 */
	public function testBehaviorNotDispatchesCopyEventsForNoCopy(): void {
		/** @var \Customer\Model\Table\CarsTable $table */
		$table = TableRegistry::getTableLocator()->get('Cars');
		$entity = $table->newDefaultEntity(['title' => 'Test Car', 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'dark']]);
		$result = $table->save($entity);

		$this->assertNotEmpty($result);

		$entity->setNew(true);

		$eventManager = $table->getEventManager();

		$eventsSent = [
			'beforeCopy' => false,
			'afterCopy' => false,
			'afterCopyCommit' => false,
		];

		foreach (
			[
				'beforeCopy' => 'beforeSave',
				'afterCopy' => 'afterSave',
				'afterCopyCommit' => 'afterSaveCommit',
			] as $newEventName => $baseEventName
		) {
			$eventManager->on('Model.Cars.' . $newEventName, ['priority' => 0], function (EventInterface $event) use (&$eventsSent, $newEventName) {
				$eventsSent[ $newEventName ] = true;
				$event->stopPropagation();
				$event->setResult(false);
			});

			$data = unserialize(serialize($entity));
			$options = new ArrayObject(['_primary' => true]);

			$event = new Event('Model.' . $baseEventName, null, [$data, $options]);
			$eventManager->dispatch($event);

			$this->assertFalse($eventsSent[ $newEventName ], sprintf('Event `%s` should not be dispatched', $newEventName));
		}
	}
}
