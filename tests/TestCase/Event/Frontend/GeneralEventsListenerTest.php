<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Frontend;


use ArrayObject;
use Awyiss\Event\Frontend\GeneralEventsListener;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;
use Cake\ORM\Entity as BaseEntity;


/**
 * GeneralEventsListener Test Case
 *
 * @see \Awyiss\Event\Frontend\GeneralEventsListener
 */
class GeneralEventsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Frontend\GeneralEventsListener
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
	 * @see \Awyiss\Event\Frontend\GeneralEventsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.beforeSave' => 'beforeSave',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\GeneralEventsListener::beforeSave()
	 */
	public function testBeforeSaveBlocksRegularEntitySave(): void {
		$entity = $this->createMock(Entity::class);
		$entity->expects($this->once())->method('setError')->with('_general', 'Saving inside the Frontend Realm is not allowed.');

		$options = new ArrayObject();
		$event = new Event('Model.beforeSave');

		$this->listener->beforeSave($event, $entity, $options);

		$this->assertTrue($event->isStopped());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\GeneralEventsListener::beforeSave()
	 */
	public function testBeforeSaveAllowsNonAwyissEntity(): void {
		$entity = $this->createMock(BaseEntity::class);

		$options = new ArrayObject();
		$event = new Event('Model.beforeSave');

		$this->listener->beforeSave($event, $entity, $options);

		$this->assertFalse($event->isStopped());
		$this->assertEmpty($entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\GeneralEventsListener::beforeSave()
	 */
	public function testBeforeSaveAllowsMediaResizedImageEntity(): void {
		$entity = $this->createMock(MediaResizedImage::class);
		$options = new ArrayObject();
		$event = new Event('Model.beforeSave');

		$this->listener->beforeSave($event, $entity, $options);

		$this->assertFalse($event->isStopped());
		$this->assertEmpty($entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\GeneralEventsListener::beforeSave()
	 */
	public function testBeforeSaveAllowsWithAllowFrontendSaveOption(): void {
		$entity = $this->createMock(Entity::class);
		$entity->expects($this->never())->method('setError');

		$options = new ArrayObject(['allowFrontendSave' => true]);
		$event = new Event('Model.beforeSave');

		$this->listener->beforeSave($event, $entity, $options);

		$this->assertFalse($event->isStopped());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\GeneralEventsListener::beforeSave()
	 */
	public function testBeforeSaveAllowsWithAllowFrontendSaveFalse(): void {
		$entity = $this->createMock(Entity::class);
		$entity->expects($this->once())->method('setError')->with('_general', 'Saving inside the Frontend Realm is not allowed.');

		$options = new ArrayObject(['allowFrontendSave' => false]);
		$event = new Event('Model.beforeSave');

		$this->listener->beforeSave($event, $entity, $options);

		$this->assertTrue($event->isStopped());
	}
}
