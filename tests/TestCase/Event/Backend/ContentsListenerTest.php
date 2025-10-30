<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Event\Backend\ContentsListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;


/**
 * ContentsListener Test Case
 *
 * @see \Awyiss\Event\Backend\ContentsListener
 */
class ContentsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\ContentsListener
	 */
	protected ContentsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new ContentsListener();
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
	 * @see \Awyiss\Event\Backend\ContentsListener::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Contents.beforeSave' => 'beforeSave',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveEmptiesTitleTagWhenEmptyTitle(): void {
		$entity = $this->fetchTable('Contents')->newDefaultEntity([
			'title' => '',
			'titleTag' => 'h2',
		]);

		$event = new Event('Model.Contents.beforeSave', $this->fetchTable('Contents'));

		$this->listener->beforeSave($event, $entity);

		$this->assertNull($entity->titleTag);

		$entity = $this->fetchTable('Contents')->newDefaultEntity([
			'title' => 'Foobar',
			'titleTag' => 'h2',
		]);

		$event = new Event('Model.Contents.beforeSave', $this->fetchTable('Contents'));

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('h2', $entity->titleTag);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveEmptiesSubtitleTagWhenEmptySubtitle(): void {
		$entity = $this->fetchTable('Contents')->newDefaultEntity([
			'subtitle' => '',
			'subtitleTag' => 'h3',
		]);

		$event = new Event('Model.Contents.beforeSave', $this->fetchTable('Contents'));

		$this->listener->beforeSave($event, $entity);

		$this->assertNull($entity->subtitleTag);

		$entity = $this->fetchTable('Contents')->newDefaultEntity([
			'subtitle' => 'Foobar',
			'subtitleTag' => 'h3',
		]);

		$event = new Event('Model.Contents.beforeSave', $this->fetchTable('Contents'));

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('h3', $entity->subtitleTag);
	}
}
