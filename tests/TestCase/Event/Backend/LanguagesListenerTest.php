<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Event\Backend\LanguagesListener;
use Awyiss\Event\EventManager;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;


/**
 * LanguagesListener Test Case
 *
 * @see \Awyiss\Event\Backend\LanguagesListener
 */
class LanguagesListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\LanguagesListener
	 */
	protected LanguagesListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new LanguagesListener();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\LanguagesListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Languages.afterSaveCommit' => 'afterSaveCommit',
			'Model.Languages.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Languages.afterSoftDelete' => 'afterSoftDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\LanguagesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitDispatchesDeleteCustomConfigEventWhenNew(): void {
		$eventSent = false;
		$eventManager = EventManager::instance();

		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventSent) {
			$eventSent = true;
		});

		$languagesTable = $this->fetchTable('Languages');

		$language = $languagesTable->newDefaultEntity([
			'name' => 'Test Language',
			'shortcode' => 'tl',
			'realm' => 'backend',
		]);

		$event = new Event('Model.Languages.afterSaveCommit');

		$this->listener->afterSaveCommit($event, $language);

		$this->assertTrue($eventSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\LanguagesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitNotDispatchesDeleteCustomConfigEventWhenNotNew(): void {
		$eventSent = false;
		$eventManager = EventManager::instance();

		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventSent) {
			$eventSent = true;
		});

		$languagesTable = $this->fetchTable('Languages');

		$language = $languagesTable->newDefaultEntity([
			'name' => 'Test Language',
			'shortcode' => 'tl',
			'realm' => 'backend',
		]);
		$language->setNew(false);
		$language->clean();

		$event = new Event('Model.Languages.afterSaveCommit');

		$this->listener->afterSaveCommit($event, $language);

		$this->assertFalse($eventSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\LanguagesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitDispatchesDeleteCustomConfigEventWhenShortcodeChanged(): void {
		$eventSent = false;
		$eventManager = EventManager::instance();

		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventSent) {
			$eventSent = true;
		});

		$languagesTable = $this->fetchTable('Languages');

		$language = $languagesTable->newDefaultEntity([
			'name' => 'Test Language',
			'shortcode' => 'tl',
			'realm' => 'backend',
		]);
		$language->setNew(false);
		$language->clean();

		$language->shortcode = 'en';

		$event = new Event('Model.Languages.afterSaveCommit');

		$this->listener->afterSaveCommit($event, $language);

		$this->assertTrue($eventSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\LanguagesListener::afterSaveCommit()
	 */
	public function testAfterSaveCommitDispatchesDeleteCustomConfigEventWhenRealmChanged(): void {
		$eventSent = false;
		$eventManager = EventManager::instance();

		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventSent) {
			$eventSent = true;
		});

		$languagesTable = $this->fetchTable('Languages');

		$language = $languagesTable->newDefaultEntity([
			'name' => 'Test Language',
			'shortcode' => 'tl',
			'realm' => 'backend',
		]);
		$language->setNew(false);
		$language->clean();

		$language->realm = 'Frontend';

		$event = new Event('Model.Languages.afterSaveCommit');

		$this->listener->afterSaveCommit($event, $language);

		$this->assertTrue($eventSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\LanguagesListener::beforeSoftDelete()
	 */
	public function testBeforeSoftDeleteSetsMarksAssociationsDependentWhenRealmFrontend(): void {
		$languagesTable = $this->fetchTable('Languages');

		$language = $languagesTable->newDefaultEntity([
			'name' => 'Test Language',
			'shortcode' => 'tl',
			'realm' => 'Frontend',
		]);

		$event = new Event('Model.Languages.beforeSoftDelete', $languagesTable);

		$this->listener->beforeSoftDelete($event, $language);

		$this->assertTrue($languagesTable->MenuEntries->getDependent());
		$this->assertTrue($languagesTable->Pages->getDependent());
		$this->assertFalse($languagesTable->Pages->ChildPages->getCascadeCallbacks());
		$this->assertFalse($languagesTable->Pages->ChildPages->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\LanguagesListener::beforeSoftDelete()
	 */
	public function testBeforeSoftDeleteNotSetsMarksAssociationsDependentWhenRealmNotFrontend(): void {
		$languagesTable = $this->fetchTable('Languages');

		$language = $languagesTable->newDefaultEntity([
			'name' => 'Test Language',
			'shortcode' => 'tl',
			'realm' => 'Backend',
		]);

		$event = new Event('Model.Languages.beforeSoftDelete', $languagesTable);

		$this->listener->beforeSoftDelete($event, $language);

		$this->assertFalse($languagesTable->MenuEntries->getDependent());
		$this->assertFalse($languagesTable->Pages->getDependent());
		$this->assertTrue($languagesTable->Pages->ChildPages->getCascadeCallbacks());
		$this->assertTrue($languagesTable->Pages->ChildPages->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\LanguagesListener::afterSoftDelete()
	 */
	public function testAfterSoftDeleteSetsMarksAssociationsNotDependent(): void {
		$languagesTable = $this->fetchTable('Languages');

		$language = $languagesTable->newDefaultEntity([
			'name' => 'Test Language',
			'shortcode' => 'tl',
			'realm' => 'Frontend',
		]);

		$event = new Event('Model.Languages.beforeSoftDelete', $languagesTable);
		$this->listener->beforeSoftDelete($event, $language);

		$this->assertTrue($languagesTable->MenuEntries->getDependent());
		$this->assertTrue($languagesTable->Pages->getDependent());
		$this->assertFalse($languagesTable->Pages->ChildPages->getCascadeCallbacks());
		$this->assertFalse($languagesTable->Pages->ChildPages->getDependent());

		$event = new Event('Model.Languages.afterSoftDelete', $languagesTable);
		$this->listener->afterSoftDelete($event, $language);

		$this->assertFalse($languagesTable->MenuEntries->getDependent());
		$this->assertFalse($languagesTable->Pages->getDependent());
		$this->assertTrue($languagesTable->Pages->ChildPages->getCascadeCallbacks());
		$this->assertTrue($languagesTable->Pages->ChildPages->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\LanguagesListener::afterSoftDelete()
	 */
	public function testAfterSoftDeleteDispatchesDeleteCustomConfigEventWhenRealmChanged(): void {
		$eventSent = false;
		$eventManager = EventManager::instance();

		$eventManager->on('Awyiss.Configuration.deleteCustomConfiguration', function () use (&$eventSent) {
			$eventSent = true;
		});

		$languagesTable = $this->fetchTable('Languages');

		$language = $languagesTable->newDefaultEntity([
			'name' => 'Test Language',
			'shortcode' => 'tl',
			'realm' => 'backend',
		]);
		$language->setNew(false);
		$language->clean();

		$language->realm = 'Frontend';

		$event = new Event('Model.Languages.afterSaveCommit');

		$this->listener->afterSaveCommit($event, $language);

		$this->assertTrue($eventSent);
	}
}
