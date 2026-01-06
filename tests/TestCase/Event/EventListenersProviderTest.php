<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event;


use Awyiss\Event\EventListenersProvider;
use Awyiss\Test\TestSuite\TestCase;


/**
 * EventListenersProvider Test Case
 *
 * @see \Awyiss\Event\EventListenersProvider
 */
class EventListenersProviderTest extends TestCase {
	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		EventListenersProvider::reset();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::sanitizeScope()
	 */
	public function testSanitizeScope(): void {
		$result = EventListenersProvider::sanitizeScope('user_management');
		$this->assertSame('UserManagement', $result);

		$result = EventListenersProvider::sanitizeScope('UserManagement');
		$this->assertSame('UserManagement', $result);

		$result = EventListenersProvider::sanitizeScope('user-management');
		$this->assertSame('UserManagement', $result);

		$result = EventListenersProvider::sanitizeScope('user management');
		$this->assertSame('UserManagement', $result);

		$result = EventListenersProvider::sanitizeScope('user-management_system');
		$this->assertSame('UserManagementSystem', $result);

		$result = EventListenersProvider::sanitizeScope('user2_management');
		$this->assertSame('User2Management', $result);

		$result = EventListenersProvider::sanitizeScope('user@management#system');
		$this->assertSame('UserManagementSystem', $result);

		$result = EventListenersProvider::sanitizeScope('');
		$this->assertSame('', $result);

		$result = EventListenersProvider::sanitizeScope('user');
		$this->assertSame('User', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::getListener()
	 */
	public function testGetListener(): void {
		$this->assertEmpty($this->_eventManager->listeners('Model.Designs.afterSave'));

		$result1 = EventListenersProvider::getListener('Designs', 'Backend');

		$result2 = EventListenersProvider::getListener('Designs', 'Frontend');

		$result3 = EventListenersProvider::getListener('Designs', 'Customer');

		$this->assertEmpty($this->_eventManager->listeners('Model.Designs.afterSave'));

		$this->assertSame('\Awyiss\Event\Backend\DesignsListener', $result1);
		$this->assertNull($result2);
		$this->assertNull($result3);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::getListener()
	 */
	public function testGetListenerSanitizesScope(): void {
		$result1 = EventListenersProvider::getListener('media__folders', 'Backend');
		$result2 = EventListenersProvider::getListener('MediaFolders', 'Backend');
		$result3 = EventListenersProvider::getListener('MediáFolders', 'Backend');
		$result4 = EventListenersProvider::getListener('media folders', 'Backend');

		$this->assertSame('\Awyiss\Event\Backend\MediaFoldersListener', $result1);
		$this->assertSame('\Awyiss\Event\Backend\MediaFoldersListener', $result2);
		$this->assertSame('\Awyiss\Event\Backend\MediaFoldersListener', $result3);
		$this->assertSame('\Awyiss\Event\Backend\MediaFoldersListener', $result4);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::getListener()
	 */
	public function testGetListenerPrefersCustomClasses(): void {
		$result = EventListenersProvider::getListener('Forms', 'Backend');

		$this->assertSame('\Customer\Event\Backend\FormsListener', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::getListener()
	 */
	public function testGetListenerWithNonExistentScope(): void {
		$result1 = EventListenersProvider::getListener('TestScope', 'Backend');

		$result2 = EventListenersProvider::getListener('TestScope', 'Frontend');

		$result3 = EventListenersProvider::getListener('TestScope', 'Customer');

		$this->assertNull($result1);
		$this->assertNull($result2);
		$this->assertNull($result3);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::loadListener()
	 */
	public function testLoadListener(): void {
		$this->assertEmpty($this->_eventManager->listeners('Model.Designs.afterSave'));

		$result1 = EventListenersProvider::loadListener('Designs', 'Backend');

		$result2 = EventListenersProvider::loadListener('Designs', 'Frontend');

		$result3 = EventListenersProvider::loadListener('Designs', 'Customer');

		$this->assertNotEmpty($this->_eventManager->listeners('Model.Designs.afterSave'));

		$this->assertTrue($result1);
		$this->assertFalse($result2);
		$this->assertFalse($result3);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::loadListener()
	 */
	public function testLoadListenerSanitizesScope(): void {
		$this->assertEmpty($this->_eventManager->listeners('Model.MediaFolders.afterSave'));

		$result1 = EventListenersProvider::loadListener('media__folders', 'Backend');
		$result2 = EventListenersProvider::loadListener('MediaFolders', 'Backend');
		$result3 = EventListenersProvider::loadListener('MediáFolders', 'Backend');
		$result4 = EventListenersProvider::loadListener('media folders', 'Backend');

		$this->assertNotEmpty($this->_eventManager->listeners('Model.MediaFolders.afterSave'));

		$this->assertTrue($result1);
		$this->assertTrue($result2);
		$this->assertTrue($result3);
		$this->assertTrue($result4);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::loadListener()
	 */
	public function testLoadListenerPrefersCustomClasses(): void {
		$this->assertEmpty($this->_eventManager->listeners('Model.Forms.weirdEvent'));

		$result = EventListenersProvider::loadListener('Forms', 'Backend');

		$this->assertNotEmpty($this->_eventManager->listeners('Model.Forms.weirdEvent'));

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::loadListener()
	 */
	public function testLoadListenerWithNonExistentScope(): void {
		$result = EventListenersProvider::loadListener('NonExistentScope', 'Backend');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::getListeners()
	 */
	public function testGetListenersForGlobal(): void {
		$result = EventListenersProvider::getListeners('Global');

		$this->assertSame([
			'Authentication' => '\Customer\Event\Global\AuthenticationListener',
			'Cars' => '\Customer\Event\Global\CarsListener',
			'GeneralEvents' => '\Customer\Event\Global\GeneralEventsListener',
			'Customers' => '\Awyiss\Event\Global\CustomersListener',
			'Pages' => '\Awyiss\Event\Global\PagesListener',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::getListeners()
	 */
	public function testGetListenersForFrontend(): void {
		$result = EventListenersProvider::getListeners('Frontend');

		$this->assertSame([
			'Authentication' => '\Awyiss\Event\Frontend\AuthenticationListener',
			'GeneralEvents' => '\Awyiss\Event\Frontend\GeneralEventsListener',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\EventListenersProvider::getListeners()
	 */
	public function testGetListenersForBackend(): void {
		$result = EventListenersProvider::getListeners('Backend');

		$this->assertSame([
			'Forms' => '\Customer\Event\Backend\FormsListener',
			'Attributes' => '\Awyiss\Event\Backend\AttributesListener',
			'Authentication' => '\Awyiss\Event\Backend\AuthenticationListener',
			'Configuration' => '\Awyiss\Event\Backend\ConfigurationListener',
			'ContentTemplates' => '\Awyiss\Event\Backend\ContentTemplatesListener',
			'Contents' => '\Awyiss\Event\Backend\ContentsListener',
			'Datatables' => '\Awyiss\Event\Backend\DatatablesListener',
			'Designs' => '\Awyiss\Event\Backend\DesignsListener',
			'EmailTemplates' => '\Awyiss\Event\Backend\EmailTemplatesListener',
			'FormElements' => '\Awyiss\Event\Backend\FormElementsListener',
			'GeneralEvents' => '\Awyiss\Event\Backend\GeneralEventsListener',
			'GlobalContentTemplates' => '\Awyiss\Event\Backend\GlobalContentTemplatesListener',
			'GlobalContents' => '\Awyiss\Event\Backend\GlobalContentsListener',
			'Languages' => '\Awyiss\Event\Backend\LanguagesListener',
			'MediaElementAssignments' => '\Awyiss\Event\Backend\MediaElementAssignmentsListener',
			'MediaElementSelectors' => '\Awyiss\Event\Backend\MediaElementSelectorsListener',
			'MediaFolders' => '\Awyiss\Event\Backend\MediaFoldersListener',
			'Media' => '\Awyiss\Event\Backend\MediaListener',
			'Menus' => '\Awyiss\Event\Backend\MenusListener',
			'PageRoles' => '\Awyiss\Event\Backend\PageRolesListener',
			'PageTemplates' => '\Awyiss\Event\Backend\PageTemplatesListener',
			'Pages' => '\Awyiss\Event\Backend\PagesListener',
			'UrlHistory' => '\Awyiss\Event\Backend\UrlHistoryListener',
			'UserConfiguration' => '\Awyiss\Event\Backend\UserConfigurationListener',
			'Usergroups' => '\Awyiss\Event\Backend\UsergroupsListener',
		], $result);
	}
}
