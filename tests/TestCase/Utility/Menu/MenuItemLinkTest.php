<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Menu;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\MenuItemLink;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use stdClass;


/**
 * Test case for MenuItemLink class.
 *
 * @see \Awyiss\Utility\Menu\MenuItemLink
 */
class MenuItemLinkTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected ServerRequest $request;


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
		Awyiss::loadConfiguration('xy', 'yx');
		$this->loadRoutes();

		$this->request = new ServerRequest([
			'url' => '/xy/dummy/view/',
			'params' => [
				'lang' => 'xy',
				'controller' => 'Dummy',
				'action' => 'view',
				'_name' => Awyiss::REALM_BACKEND,
				'prefix' => Awyiss::REALM_BACKEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($this->request);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::__construct()
	 */
	public function testConstructWithStringLink(): void {
		$link = new MenuItemLink('https://example.com');

		// Test both raw and compiled URL
		$this->assertEquals('https://example.com', $link->getUrl(false));
		$this->assertEquals('https://example.com', $link->getUrl());

		$this->assertNull($link->getTarget());
		$this->assertNull($link->getRel());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::__construct()
	 */
	public function testConstructWithExternalLink(): void {
		$link = new MenuItemLink('https://example.com', true);

		// Test both raw and compiled URL
		$this->assertEquals('https://example.com', $link->getUrl(false));
		$this->assertEquals('https://example.com', $link->getUrl());

		$this->assertEquals('_blank', $link->getTarget());
		$this->assertEquals('noopener noreferrer', $link->getRel());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::__construct()
	 */
	public function testConstructWithControllerAction(): void {
		$link = new MenuItemLink('Users::view');

		// Test raw URL (array format)
		$this->assertEquals(['controller' => 'Users', 'action' => 'view'], $link->getUrl(false));
		$this->assertEquals('/backend/xy/users/view/', $link->getUrl());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::__construct()
	 */
	public function testConstructWithControllerActionAndParams(): void {
		$link = new MenuItemLink('Users::view::id:123');

		// Test raw URL (array format)
		$this->assertEquals(['controller' => 'Users', 'action' => 'view', 'id' => '123'], $link->getUrl(false));
		$this->assertEquals('/backend/xy/users/view/id:123/', $link->getUrl());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::__construct()
	 */
	public function testConstructWithObjectLink(): void {
		$linkObject = new stdClass();
		$linkObject->url = 'https://example.com';
		$linkObject->target = '_self';
		$linkObject->rel = 'bookmark';

		$link = new MenuItemLink($linkObject);

		// Test both raw and compiled URL
		$this->assertEquals('https://example.com', $link->getUrl(false));
		$this->assertEquals('https://example.com', $link->getUrl());

		$this->assertEquals('_self', $link->getTarget());
		$this->assertEquals('bookmark', $link->getRel());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::__construct()
	 */
	public function testConstructWithNestedObjectUrl(): void {
		$linkObject = new stdClass();
		$linkObject->url = new stdClass();
		$linkObject->url->controller = 'Users';
		$linkObject->url->action = 'view';

		$link = new MenuItemLink($linkObject);

		$this->assertEquals(['controller' => 'Users', 'action' => 'view'], $link->getUrl(false));
		$this->assertEquals('/backend/xy/users/view/', $link->getUrl());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::__construct()
	 */
	public function testConstructForFrontend(): void {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		$request = new ServerRequest([
			'url' => '/xy/dummy-slug',
			'params' => [
				'lang' => 'xy',
				'controller' => 'TheController',
				'action' => 'theAction',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$link = new MenuItemLink('de/dummy');

		$this->assertEquals('/de/dummy/', $link->getUrl());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::__construct()
	 */
	public function testConstructForFrontendWithParams(): void {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		$request = new ServerRequest([
			'url' => '/xy/dummy-slug',
			'params' => [
				'lang' => 'xy',
				'controller' => 'TheController',
				'action' => 'theAction',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$link = new MenuItemLink('de/dummy/id:123');

		$this->assertEquals('/de/dummy/id:123/', $link->getUrl());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::getAttributes()
	 */
	public function testGetAttributes(): void {
		$link = new MenuItemLink('https://example.com', true);
		$expected = [
			'target' => '_blank',
			'rel' => 'noopener noreferrer',
		];
		$this->assertEquals($expected, $link->getAttributes());

		// Test with no attributes
		$link = new MenuItemLink('https://example.com');
		$this->assertEquals([], $link->getAttributes());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::setRel()
	 * @see \Awyiss\Utility\Menu\MenuItemLink::getRel()
	 */
	public function testSetRel(): void {
		$link = new MenuItemLink('https://example.com');
		$this->assertNull($link->getRel());

		// Test append mode
		$link->setRel('nofollow');
		$this->assertEquals('nofollow', $link->getRel());

		$link->setRel('noopener');
		$this->assertEquals('nofollow noopener', $link->getRel());

		// Test replace mode
		$link->setRel('noreferrer', true);
		$this->assertEquals('noreferrer', $link->getRel());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Menu\MenuItemLink::setTarget()
	 * @see \Awyiss\Utility\Menu\MenuItemLink::getTarget()
	 */
	public function testSetTarget(): void {
		$link = new MenuItemLink('https://example.com');
		$this->assertNull($link->getTarget());

		$link->setTarget('_self');
		$this->assertEquals('_self', $link->getTarget());

		$link->setTarget('_blank');
		$this->assertEquals('_blank', $link->getTarget());
	}
}
