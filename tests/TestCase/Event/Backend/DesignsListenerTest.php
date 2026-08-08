<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Event\Backend\DesignsListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Model\Entity\Design;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Queue\Model\Table\QueuedJobsTable;


/**
 * DesignsListener Test Case
 *
 * @see \Awyiss\Event\Backend\DesignsListener
 */
class DesignsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\DesignsListener
	 */
	protected DesignsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new DesignsListener();

		$designMiddleware = $this->createStub(DesignMiddleware::class);
		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddleware);
		Router::setRequest($request);
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
	 * @see \Awyiss\Event\Backend\DesignsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Designs.afterSave' => 'afterSave',
			'Model.Designs.afterSaveCommit' => 'afterSaveCommit',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DesignsListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveWithDesignNotInUse(): void {
		$designsTable = $this->fetchTable('Designs');

		$this->createDummyDesigns();

		$entity = new Design([
			'id' => 3,
			'inUse' => true,
		]);
		$entity->clean();

		$entity->inUse = false;

		$event = new Event('Model.Designs.afterSave');

		$this->listener->afterSave($event, $entity);

		$inUseDesign = $designsTable->find()->where(['inUse' => true])->first();
		$this->assertNotNull($inUseDesign);
		$this->assertSame('design-2', $inUseDesign->identifier);

		$this->deleteDummyDesigns();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DesignsListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveWithDesignInUseChanged(): void {
		$designsTable = $this->fetchTable('Designs');

		$this->createDummyDesigns();

		$entity = new Design([
			'id' => 3,
			'inUse' => false,
		]);
		$entity->clean();

		$entity->inUse = true;

		$event = new Event('Model.Designs.afterSave');

		$this->listener->afterSave($event, $entity);

		$inUseDesign = $designsTable->find()->where(['inUse' => true])->first();
		$this->assertNull($inUseDesign);

		$this->deleteDummyDesigns();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DesignsListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveWithDesignInUseUnchanged(): void {
		$designsTable = $this->fetchTable('Designs');

		$this->createDummyDesigns();

		$entity = new Design([
			'inUse' => true,
		]);
		$entity->clean();

		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$entity->inUse = false;
		$entity->inUse = true;

		$event = new Event('Model.Designs.afterSave');

		$this->listener->afterSave($event, $entity);

		$inUseDesign = $designsTable->find()->where(['inUse' => true])->first();
		$this->assertNotNull($inUseDesign);
		$this->assertSame('design-2', $inUseDesign->identifier);

		$this->deleteDummyDesigns();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DesignsListener::afterSaveCommit()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testAfterSaveCommitWithDesignNotInUse(): void {
		$designMiddleware = $this->getMockBuilder(DesignMiddleware::class)->onlyMethods([
			'resetDesignVariables',
			'compileScss',
		])->getMock();

		$designMiddleware->expects($this->never())->method('resetDesignVariables');
		$designMiddleware->expects($this->never())->method('compileScss');

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddleware);
		Router::setRequest($request);

		$entity = new Design([
			'id' => 1,
			'inUse' => false,
		]);

		$event = new Event('Model.Designs.afterSaveCommit');

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DesignsListener::afterSaveCommit()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testAfterSaveCommitWithDesignInUse(): void {
		$designMiddleware = $this->getMockBuilder(DesignMiddleware::class)->onlyMethods([
			'resetDesignVariables',
			'compileScss',
		])->getMock();

		$designMiddleware->expects($this->once())->method('resetDesignVariables');
		$designMiddleware->expects($this->once())->method('compileScss');

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddleware);
		Router::setRequest($request);

		$entity = new Design([
			'id' => 1,
			'inUse' => true,
		]);

		$event = new Event('Model.Designs.afterSaveCommit');

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\DesignsListener::afterSaveCommit()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testAfterSaveCommitWithDesignInUseWithFonts(): void {
		$entity = new Design([
			'id' => 1,
			'inUse' => true,
			'settings' => [
				[
					'font' => [
						'id' => 'font-1',
						'name' => 'Arial',
						'version' => '1.0',
					],
					'variants' => ['regular', 'bold'],
				],
				[
					'font' => [
						'id' => 'font-2',
						'name' => 'Helvetica',
						'version' => '2.0',
					],
					'variants' => ['italic'],
				],
				[
					// Entry without font info should be skipped
					'other' => 'data',
				],
				'string_entry', // Non-array entry should be skipped
			],
		]);

		$expectedFonts = [
			[
				'id' => 'font-1',
				'name' => 'Arial',
				'variants' => ['regular', 'bold'],
				'version' => '1.0',
			],
			[
				'id' => 'font-2',
				'name' => 'Helvetica',
				'variants' => ['italic'],
				'version' => '2.0',
			],
		];

		$designMiddleware = $this->createMock(DesignMiddleware::class);
		$designMiddleware->expects($this->once())->method('resetDesignVariables');
		$designMiddleware->expects($this->once())->method('compileScss')->with(true, Awyiss::REALM_FRONTEND);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddleware);
		Router::setRequest($request);

		$queueTable = $this->getMockBuilder(QueuedJobsTable::class)->disableOriginalConstructor()->onlyMethods(['createJob'])->getMock();

		$queueTable->expects($this->once())->method('createJob')->with(
			'Design/WebfontDownload',
			[
				'fonts' => $expectedFonts,
			],
			[
				'group' => 'general',
				'priority' => 1,
				'reference' => 'Designs::webfontDownload',
			]
		);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->remove('Queue.QueuedJobs');
		$tableLocator->set('Queue.QueuedJobs', $queueTable);

		$event = new Event('Model.Designs.afterSaveCommit');

		$this->listener->afterSaveCommit($event, $entity);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function createDummyDesigns(): void {
		$designsTable = $this->fetchTable('Designs');

		$designs = [
			$designsTable->newDefaultEntity([
				'identifier' => 'design-1',
				'title' => 'Design 1',
				'settings' => [],
				'css' => '',
				'inUse' => 0,
				'isPreview' => 1,
			]),
			$designsTable->newDefaultEntity([
				'identifier' => 'design-2',
				'title' => 'Design 2',
				'settings' => [],
				'css' => '',
				'inUse' => 1,
				'isPreview' => 0,
			]),
		];

		$this->assertNotFalse($designsTable->saveMany($designs, ['audit' => ['skip' => true]]));
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function deleteDummyDesigns(): void {
		$designsTable = $this->fetchTable('Designs');
		$designsTable->deleteAll(['identifier IN' => ['design-1', 'design-2', 'design-3']]);
	}
}
