<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Event\Backend\ContentsListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
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
			'Configuration.Contents.Backend.columnSystem.className.afterSaveCommit' => 'recompileAfterClassNameSave',
			'Configuration.Contents.Backend.columnSystem.maxColumns.afterSaveCommit' => 'recompileAfterMaxColumnsSave',
			'Configuration.Contents.Backend.columnSystem.className.afterDeleteCommit' => 'recompileAfterClassNameDelete',
			'Configuration.Contents.Backend.columnSystem.maxColumns.afterDeleteCommit' => 'recompileAfterMaxColumnsDelete',
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


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::recompileAfterClassNameSave
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRecompilesFrontendScssWhenColumnClassNameChanged(): void {
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$callCount = 0;
		$designMiddlewareMock->expects($this->exactly(2))->method('compileScss')->with(
			true,
			$this->callback(function ($value) use (&$callCount) {
				$callCount++;

				if ($callCount === 1) {
					return $value === Awyiss::REALM_FRONTEND;
				}
				if ($callCount === 2) {
					return $value === Awyiss::REALM_BACKEND;
				}

				return false;
			})
		);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$configTable = $this->fetchTable('Configuration');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'contents',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'column_system.class_name',
			'value' => '\Awyiss\Utility\Content\BootstrapColumnSystem',
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');

		$this->listener->recompileAfterClassNameSave($event, $entity);

		$this->assertSame('\Awyiss\Utility\Content\BootstrapColumnSystem', Configure::read('Awyiss.Contents.Backend.columnSystem.className'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::recompileAfterMaxColumnsSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRecompilesFrontendScssWhenColumnMaxColumnsChanged(): void {
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$callCount = 0;
		$designMiddlewareMock->expects($this->exactly(2))->method('compileScss')->with(
			true,
			$this->callback(function ($value) use (&$callCount) {
				$callCount++;

				if ($callCount === 1) {
					return $value === Awyiss::REALM_FRONTEND;
				}
				if ($callCount === 2) {
					return $value === Awyiss::REALM_BACKEND;
				}

				return false;
			})
		);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$configTable = $this->fetchTable('Configuration');

		$entity = $configTable->newDefaultEntity([
			'scope' => 'contents',
			'realm' => Awyiss::REALM_BACKEND,
			'identifier' => 'column_system.max_columns',
			'value' => 10,
		]);

		$event = new Event('Model.Configuration.afterSaveCommit');

		$this->listener->recompileAfterMaxColumnsSave($event, $entity);

		$this->assertSame(10, Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::recompileAfterClassNameDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRecompilesFrontendScssWhenColumnClassNameDeleted(): void {
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$callCount = 0;
		$designMiddlewareMock->expects($this->exactly(2))->method('compileScss')->with(
			true,
			$this->callback(function ($value) use (&$callCount) {
				$callCount++;

				if ($callCount === 1) {
					return $value === Awyiss::REALM_FRONTEND;
				}
				if ($callCount === 2) {
					return $value === Awyiss::REALM_BACKEND;
				}

				return false;
			})
		);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$this->listener->recompileAfterClassNameDelete();

		$this->assertNull(Configure::read('Awyiss.Contents.Backend.columnSystem.className'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\ContentsListener::recompileAfterMaxColumnsDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRecompilesFrontendScssWhenColumnMaxColumnsDeleted(): void {
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$callCount = 0;
		$designMiddlewareMock->expects($this->exactly(2))->method('compileScss')->with(
			true,
			$this->callback(function ($value) use (&$callCount) {
				$callCount++;

				if ($callCount === 1) {
					return $value === Awyiss::REALM_FRONTEND;
				}
				if ($callCount === 2) {
					return $value === Awyiss::REALM_BACKEND;
				}

				return false;
			})
		);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		$this->listener->recompileAfterMaxColumnsDelete();

		$this->assertNull(Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns'));
	}
}
