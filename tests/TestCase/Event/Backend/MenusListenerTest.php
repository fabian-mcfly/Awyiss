<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Event\Backend\MenusListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;


/**
 * MenusListener Test Case
 *
 * @see \Awyiss\Event\Backend\MenusListener
 */
class MenusListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\MenusListener
	 */
	protected MenusListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new MenusListener();
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
	 * @see \Awyiss\Event\Backend\MenusListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Menus.afterCopy' => 'afterCopy',
			'Model.Menus.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Menus.beforeDelete' => 'beforeDelete',
			'Model.Menus.afterSoftDelete' => 'afterSoftDelete',
			'Model.Menus.afterDelete' => 'afterDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MenusListener::afterCopy()
	 * @throws \Exception
	 */
	public function testAfterCopyCopiesMenuEntries(): void {
		$menusTable = $this->fetchTable('Menus');
		$menuEntriesTable = $this->fetchTable('MenuEntries');

		/** @var \Awyiss\Model\Entity\Menu $menu */
		$menu = $menusTable->get(1);
		/** @noinspection PhpUndefinedFieldInspection */
		$menu->originalEntity = unserialize(serialize($menu));
		$menu->unset('id');

		$menu->id = 369;

		$this->assertCount(35, $menuEntriesTable->find()->where(['menuId' => 1])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['menuId' => 369])->all());

		$options = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.Menus.afterCopy', $menusTable);

		$this->listener->afterCopy($event, $menu, $options);

		$this->assertCount(35, $menuEntriesTable->find()->where(['menuId' => 1])->all());
		$this->assertCount(35, $menuEntriesTable->find()->where(['menuId' => 369])->all());

		$menuEntriesTable->deleteAll(['menuId' => 369]);
		$menusTable->deleteAll(['id' => 369]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MenusListener::afterCopy()
	 * @throws \Exception
	 */
	public function testAfterCopySkipsWhenNotPrimary(): void {
		$menusTable = $this->fetchTable('Menus');
		$menuEntriesTable = $this->fetchTable('MenuEntries');

		/** @var \Awyiss\Model\Entity\Menu $menu */
		$menu = $menusTable->get(1);
		/** @noinspection PhpUndefinedFieldInspection */
		$menu->originalEntity = $menu;

		$options = new ArrayObject(['_primary' => false]);
		$event = new Event('Model.Menus.afterCopy', $menusTable);

		$initialCount = $menuEntriesTable->find()->count();

		$this->listener->afterCopy($event, $menu, $options);

		$finalCount = $menuEntriesTable->find()->count();
		$this->assertSame($initialCount, $finalCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MenusListener::beforeSoftDelete()
	 */
	public function testBeforeSoftDeleteSetsFinderToAll(): void {
		$menusTable = $this->fetchTable('Menus');

		$event = new Event('Model.Menus.beforeSoftDelete', $menusTable);

		$this->listener->beforeSoftDelete($event);

		$this->assertSame('all', $menusTable->MenuEntries->getFinder());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MenusListener::beforeDelete()
	 */
	public function testBeforeDeleteSetsFinderToAll(): void {
		$menusTable = $this->fetchTable('Menus');

		$event = new Event('Model.Menus.beforeDelete', $menusTable);

		$this->listener->beforeDelete($event);

		$this->assertSame('all', $menusTable->MenuEntries->getFinder());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MenusListener::afterSoftDelete()
	 */
	public function testAfterSoftDeleteRestoresOriginalFinder(): void {
		$menusTable = $this->fetchTable('Menus');

		$beforeEvent = new Event('Model.Menus.beforeSoftDelete', $menusTable);
		$this->listener->beforeSoftDelete($beforeEvent);

		$this->assertSame('all', $menusTable->MenuEntries->getFinder());

		$afterEvent = new Event('Model.Menus.afterSoftDelete', $menusTable);
		$this->listener->afterSoftDelete($afterEvent);

		$this->assertSame('forCurrentLanguage', $menusTable->MenuEntries->getFinder());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MenusListener::afterDelete()
	 */
	public function testAfterDeleteRestoresOriginalFinder(): void {
		$menusTable = $this->fetchTable('Menus');

		$beforeEvent = new Event('Model.Menus.beforeDelete', $menusTable);
		$this->listener->beforeDelete($beforeEvent);

		$this->assertSame('all', $menusTable->MenuEntries->getFinder());

		$afterEvent = new Event('Model.Menus.afterDelete', $menusTable);
		$this->listener->afterDelete($afterEvent);

		$this->assertSame('forCurrentLanguage', $menusTable->MenuEntries->getFinder());
	}
}
