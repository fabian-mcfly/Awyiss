<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Event\Backend\GlobalContentsListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;


/**
 * GlobalContentsListener Test Case
 *
 * @see \Awyiss\Event\Backend\GlobalContentsListener
 */
class GlobalContentsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\GlobalContentsListener
	 */
	protected GlobalContentsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new GlobalContentsListener();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.GlobalContents.beforeSave' => 'beforeSave',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentsListener::beforeSave()
	 */
	public function testBeforeSaveEmptiesTitleTagWhenEmptyTitle(): void {
		$entity = $this->fetchTable('GlobalContents')->newDefaultEntity([
			'title' => '',
			'titleTag' => 'h2',
		]);

		$event = new Event('Model.GlobalContents.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertNull($entity->titleTag);

		$entity = $this->fetchTable('GlobalContents')->newDefaultEntity([
			'title' => 'Foobar',
			'titleTag' => 'h2',
		]);

		$event = new Event('Model.GlobalContents.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('h2', $entity->titleTag);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\GlobalContentsListener::beforeSave()
	 */
	public function testBeforeSaveEmptiesSubtitleTagWhenEmptySubtitle(): void {
		$entity = $this->fetchTable('GlobalContents')->newDefaultEntity([
			'subtitle' => '',
			'subtitleTag' => 'h3',
		]);

		$event = new Event('Model.GlobalContents.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertNull($entity->subtitleTag);

		$entity = $this->fetchTable('GlobalContents')->newDefaultEntity([
			'subtitle' => 'Foobar',
			'subtitleTag' => 'h3',
		]);

		$event = new Event('Model.GlobalContents.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('h3', $entity->subtitleTag);
	}
}
