<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Event\Backend\WidgetsListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;


/**
 * WidgetsListener Test Case
 *
 * @see \Awyiss\Event\Backend\WidgetsListener
 */
class WidgetsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\WidgetsListener
	 */
	protected WidgetsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new WidgetsListener();
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\WidgetsListener::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Widgets.beforeSave' => 'beforeSave',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\WidgetsListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveEmptiesTitleTagWhenEmptyTitle(): void {
		$entity = $this->fetchTable('Widgets')->newDefaultEntity([
			'title' => '',
			'titleTag' => 'h2',
		]);

		$event = new Event('Model.Widgets.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertNull($entity->titleTag);

		$entity = $this->fetchTable('Widgets')->newDefaultEntity([
			'title' => 'Foobar',
			'titleTag' => 'h2',
		]);

		$event = new Event('Model.Widgets.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('h2', $entity->titleTag);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\WidgetsListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveEmptiesSubtitleTagWhenEmptySubtitle(): void {
		$entity = $this->fetchTable('Widgets')->newDefaultEntity([
			'subtitle' => '',
			'subtitleTag' => 'h3',
		]);

		$event = new Event('Model.Widgets.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertNull($entity->subtitleTag);

		$entity = $this->fetchTable('Widgets')->newDefaultEntity([
			'subtitle' => 'Foobar',
			'subtitleTag' => 'h3',
		]);

		$event = new Event('Model.Widgets.beforeSave');

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('h3', $entity->subtitleTag);
	}
}
