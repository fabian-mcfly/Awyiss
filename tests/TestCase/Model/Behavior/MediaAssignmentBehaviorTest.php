<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Model\Behavior\MediaAssignmentBehavior;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaAssignment;
use Awyiss\Model\Entity\MediaElementSelector;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Table;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;


/**
 * MediaAssignmentBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior
 */
class MediaAssignmentBehaviorTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\WidgetsTable
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\MediaAssignmentBehavior
	 */
	protected MediaAssignmentBehavior $behavior;
	/**
	 * @var \Awyiss\Model\Table\MediaAssignmentsTable
	 */
	protected Table $mediaAssignmentsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->table = TableRegistry::getTableLocator()->get('Widgets');

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('MediaAssignment');

		$this->mediaAssignmentsTable = TableRegistry::getTableLocator()->get('MediaAssignments');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::__construct()
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::initialize()
	 */
	public function testInitialization(): void {
		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);

		$this->assertSame([
			'mediaAssignments' => 'findMediaAssignments',
		], $config['implementedFinders']);

		$this->assertSame([
			'rebuildMediaAssignments' => 'rebuildMediaAssignments',
		], $config['implementedMethods']);

		$this->assertSame('widgets', $config['referenceName']);
		$this->assertSame('select', $config['strategy']);

		$this->assertTrue($this->table->hasAssociation('MediaAssignments'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::initialize()
	 */
	public function testAssociations(): void {
		$association = $this->table->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $association);

		$this->assertSame('MediaAssignments', $association->getName());
		$this->assertSame('foreign_key', $association->getForeignKey());
		$this->assertSame('mediaAssignments', $association->getProperty());
		$this->assertTrue($association->getCascadeCallbacks());
		$this->assertTrue($association->getDependent());
		$this->assertSame('replace', $association->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
	 */
	public function testFindMediaAssignments(): void {
		$query = $this->table->find('mediaAssignments')->where(['identifier' => 'dummy_nested']);
		$result = $query->all()->toArray();

		$this->assertNotEmpty($result);
		$this->assertCount(4, $result);

		$this->assertIsArray($result[0]->mediaAssignments);
		$this->assertCount(1, $result[0]->mediaAssignments);
		$this->assertArrayHasKey('standard', $result[0]->mediaAssignments);
		$this->assertCount(2, $result[0]->mediaAssignments['standard']);

		$this->assertArrayHasKey('media', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments['standard']['media']);
		$this->assertNull($result[0]->mediaAssignments['standard']['media']->mediaElementSelector);
		$this->assertArrayNotHasKey('_media', $result[0]->mediaAssignments['standard']);

		$this->assertArrayHasKey('lightboxMedia', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments['standard']['lightboxMedia']);
		$this->assertNull($result[0]->mediaAssignments['standard']['lightboxMedia']->mediaElementSelector);
		$this->assertArrayNotHasKey('_lightboxMedia', $result[0]->mediaAssignments['standard']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
	 */
	public function testFindMediaAssignmentsWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$query = $this->table->find('mediaAssignments')->where(['identifier' => 'dummy_nested']);
		$result = $query->all()->toArray();

		$this->assertNotEmpty($result);
		$this->assertCount(4, $result);

		$this->assertNull($result[0]->mediaAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
	 */
	public function testFindMediaAssignmentsWithIncludeElementSelector(): void {
		$query = $this->table->find('mediaAssignments', includeElementSelector: true)->where(['identifier' => 'dummy_nested']);
		$result = $query->all()->toArray();

		$this->assertNotEmpty($result);
		$this->assertCount(4, $result);

		$this->assertIsArray($result[0]->mediaAssignments);
		$this->assertCount(1, $result[0]->mediaAssignments);
		$this->assertArrayHasKey('standard', $result[0]->mediaAssignments);
		$this->assertCount(2, $result[0]->mediaAssignments['standard']);

		$this->assertArrayHasKey('media', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments['standard']['media']);
		$this->assertInstanceOf(MediaElementSelector::class, $result[0]->mediaAssignments['standard']['media']->mediaElementSelector);
		$this->assertArrayNotHasKey('_media', $result[0]->mediaAssignments['standard']);

		$this->assertArrayHasKey('lightboxMedia', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments['standard']['lightboxMedia']);
		$this->assertInstanceOf(MediaElementSelector::class, $result[0]->mediaAssignments['standard']['lightboxMedia']->mediaElementSelector);
		$this->assertArrayNotHasKey('_lightboxMedia', $result[0]->mediaAssignments['standard']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
	 */
	public function testFindMediaAssignmentsWithUseMediaEntity(): void {
		$query = $this->table->find('mediaAssignments', useMediaEntity: true)->where(['identifier' => 'dummy_nested']);
		$result = $query->all()->toArray();

		$this->assertNotEmpty($result);
		$this->assertCount(4, $result);

		$this->assertIsArray($result[0]->mediaAssignments);
		$this->assertCount(1, $result[0]->mediaAssignments);
		$this->assertArrayHasKey('standard', $result[0]->mediaAssignments);
		$this->assertCount(4, $result[0]->mediaAssignments['standard']);

		$this->assertArrayHasKey('media', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(Media::class, $result[0]->mediaAssignments['standard']['media']);

		$this->assertArrayHasKey('_media', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments['standard']['_media']);
		$this->assertNull($result[0]->mediaAssignments['standard']['_media']->mediaElementSelector);

		$this->assertArrayHasKey('lightboxMedia', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(Media::class, $result[0]->mediaAssignments['standard']['lightboxMedia']);

		$this->assertArrayHasKey('_lightboxMedia', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments['standard']['_lightboxMedia']);
		$this->assertNull($result[0]->mediaAssignments['standard']['_lightboxMedia']->mediaElementSelector);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
	 */
	public function testFindMediaAssignmentsWithUseMediaEntityForGallery(): void {
		$query = $this->fetchTable('Contents')->find('mediaAssignments')->where(['id' => 57]);
		$result = $query->first();

		$this->assertNotEmpty($result);
		$this->assertInstanceOf(Content::class, $result);

		$this->assertIsArray($result->mediaAssignments);
		$this->assertCount(2, $result->mediaAssignments);

		$this->assertArrayHasKey('standard', $result->mediaAssignments);
		$this->assertCount(1, $result->mediaAssignments['standard']);
		$this->assertArrayHasKey('media', $result->mediaAssignments['standard']);
		$this->assertInstanceOf(MediaAssignment::class, $result->mediaAssignments['standard']['media']);

		$this->assertArrayHasKey('gallery', $result->mediaAssignments);
		$this->assertCount(1, $result->mediaAssignments['gallery']);
		$this->assertArrayHasKey('media', $result->mediaAssignments['gallery']);
		$this->assertIsArray($result->mediaAssignments['gallery']['media']);

		$this->assertCount(3, $result->mediaAssignments['gallery']['media']);
		$this->assertInstanceOf(MediaAssignment::class, $result->mediaAssignments['gallery']['media'][0]);
		$this->assertInstanceOf(MediaAssignment::class, $result->mediaAssignments['gallery']['media'][1]);
		$this->assertInstanceOf(MediaAssignment::class, $result->mediaAssignments['gallery']['media'][2]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
	 */
	public function testFindMediaAssignmentsWithoutFormatResult(): void {
		$query = $this->table->find('mediaAssignments', formatResult: false)->where(['identifier' => 'dummy_nested']);
		$result = $query->all()->toArray();

		$this->assertNotEmpty($result);
		$this->assertCount(4, $result);

		$this->assertIsArray($result[0]->mediaAssignments);
		$this->assertCount(2, $result[0]->mediaAssignments);

		$this->assertArrayNotHasKey('standard', $result[0]->mediaAssignments);

		$this->assertArrayHasKey(0, $result[0]->mediaAssignments);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments[0]);
		$this->assertArrayHasKey(1, $result[0]->mediaAssignments);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments[1]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::rebuildMediaAssignments()
	 */
	public function testRebuildMediaAssignments(): void {
		$query = $this->table->find('mediaAssignments', formatResult: false)->where(['identifier' => 'dummy_nested']);
		$result = $query->all()->toArray();

		$this->assertNotEmpty($result);
		$this->assertCount(4, $result);

		$this->assertIsArray($result[0]->mediaAssignments);
		$this->assertCount(2, $result[0]->mediaAssignments);

		$this->assertArrayNotHasKey('standard', $result[0]->mediaAssignments);
		$this->assertArrayHasKey(0, $result[0]->mediaAssignments);
		$this->assertArrayHasKey(1, $result[0]->mediaAssignments);

		$this->table->rebuildMediaAssignments($result[0]);

		$this->assertArrayHasKey('standard', $result[0]->mediaAssignments);
		$this->assertCount(2, $result[0]->mediaAssignments['standard']);
		$this->assertArrayHasKey('media', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments['standard']['media']);
		$this->assertArrayNotHasKey('_media', $result[0]->mediaAssignments['standard']);

		$this->assertArrayNotHasKey(0, $result[0]->mediaAssignments);
		$this->assertArrayNotHasKey(1, $result[0]->mediaAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::rebuildMediaAssignments()
	 */
	public function testRebuildMediaAssignmentsWithUseMediaEntity(): void {
		$query = $this->table->find('mediaAssignments', formatResult: false)->where(['identifier' => 'dummy_nested']);
		$result = $query->all()->toArray();

		$this->assertNotEmpty($result);
		$this->assertCount(4, $result);

		$this->assertIsArray($result[0]->mediaAssignments);
		$this->assertCount(2, $result[0]->mediaAssignments);

		$this->assertArrayNotHasKey('standard', $result[0]->mediaAssignments);
		$this->assertArrayHasKey(0, $result[0]->mediaAssignments);
		$this->assertArrayHasKey(1, $result[0]->mediaAssignments);

		$this->table->rebuildMediaAssignments($result[0], useMediaEntity: true);

		$this->assertArrayHasKey('standard', $result[0]->mediaAssignments);
		$this->assertCount(4, $result[0]->mediaAssignments['standard']);

		$this->assertArrayHasKey('media', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(Media::class, $result[0]->mediaAssignments['standard']['media']);

		$this->assertArrayHasKey('_media', $result[0]->mediaAssignments['standard']);
		$this->assertInstanceOf(MediaAssignment::class, $result[0]->mediaAssignments['standard']['_media']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::rebuildMediaAssignments()
	 */
	public function testRebuildMediaAssignmentsWithAlreadyRebuiltAssignments(): void {
		$widget = $this->table->newDefaultEntity([
			'title' => 'Test Widget',
			'mediaAssignments' => [
				'testElement' => [
					'media' => 'already_rebuilt',
				],
			],
		]);

		/** @var \Awyiss\Model\Entity\Widget $result */
		$result = $this->table->rebuildMediaAssignments($widget);

		$this->assertSame($widget, $result);
		$this->assertSame('already_rebuilt', $result->mediaAssignments['testElement']['media']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::buildMarshalMap()
	 */
	public function testBuildMarshalMap(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->newDefaultEntity();

		$data = [
			'title' => 'Test Template',
			'media_assignments' => [
				2 => [
					'media' => [
						'media_id' => '10',
					],
					'lightbox_media' => [
						'media_id' => 10,
					],
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->mediaAssignments);
		$this->assertCount(2, $entity->mediaAssignments);

		$this->assertArrayHasKey(0, $entity->mediaAssignments);

		$this->assertInstanceOf(MediaAssignment::class, $entity->mediaAssignments[0]);
		$this->assertSame(10, $entity->mediaAssignments[0]->mediaId);
		$this->assertSame(2, $entity->mediaAssignments[0]->mediaElementId);
		$this->assertSame('media', $entity->mediaAssignments[0]->mediaElementSelectorIdentifier);
		$this->assertNull($entity->mediaAssignments[0]->mediaFolderId);
		$this->assertSame('widgets', $entity->mediaAssignments[0]->scope);
		$this->assertSame(1, $entity->mediaAssignments[0]->systemOrder);

		$this->assertInstanceOf(MediaAssignment::class, $entity->mediaAssignments[1]);
		$this->assertSame(2, $entity->mediaAssignments[1]->mediaElementId);
		$this->assertSame(10, $entity->mediaAssignments[1]->mediaId);
		$this->assertSame('lightbox_media', $entity->mediaAssignments[1]->mediaElementSelectorIdentifier);
		$this->assertNull($entity->mediaAssignments[1]->mediaFolderId);
		$this->assertSame('widgets', $entity->mediaAssignments[1]->scope);
		$this->assertSame(1, $entity->mediaAssignments[0]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::buildMarshalMap()
	 */
	public function testBuildMarshalMapForGallery(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->newDefaultEntity();

		$data = [
			'title' => 'Test Template',
			'media_assignments' => [
				4 => [
					'media' => [
						'media_id' => [2, 4, 0, 10],
					],
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->mediaAssignments);
		$this->assertCount(3, $entity->mediaAssignments);

		$this->assertArrayHasKey(0, $entity->mediaAssignments);
		$this->assertInstanceOf(MediaAssignment::class, $entity->mediaAssignments[0]);
		$this->assertSame(4, $entity->mediaAssignments[0]->mediaElementId);
		$this->assertSame(2, $entity->mediaAssignments[0]->mediaId);
		$this->assertSame('media', $entity->mediaAssignments[0]->mediaElementSelectorIdentifier);
		$this->assertNull($entity->mediaAssignments[0]->mediaFolderId);
		$this->assertSame('widgets', $entity->mediaAssignments[0]->scope);
		$this->assertSame(1, $entity->mediaAssignments[0]->systemOrder);

		$this->assertArrayHasKey(1, $entity->mediaAssignments);
		$this->assertInstanceOf(MediaAssignment::class, $entity->mediaAssignments[1]);
		$this->assertSame(4, $entity->mediaAssignments[1]->mediaElementId);
		$this->assertSame(4, $entity->mediaAssignments[1]->mediaId);
		$this->assertSame('media', $entity->mediaAssignments[1]->mediaElementSelectorIdentifier);
		$this->assertNull($entity->mediaAssignments[1]->mediaFolderId);
		$this->assertSame('widgets', $entity->mediaAssignments[1]->scope);
		$this->assertSame(2, $entity->mediaAssignments[1]->systemOrder);
		$this->assertArrayNotHasKey('lightbox_media', $entity->mediaAssignments[1]);

		$this->assertArrayHasKey(2, $entity->mediaAssignments);
		$this->assertInstanceOf(MediaAssignment::class, $entity->mediaAssignments[2]);
		$this->assertSame(4, $entity->mediaAssignments[2]->mediaElementId);
		$this->assertSame(10, $entity->mediaAssignments[2]->mediaId);
		$this->assertSame('media', $entity->mediaAssignments[2]->mediaElementSelectorIdentifier);
		$this->assertNull($entity->mediaAssignments[2]->mediaFolderId);
		$this->assertSame('widgets', $entity->mediaAssignments[2]->scope);
		$this->assertSame(3, $entity->mediaAssignments[2]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::buildMarshalMap()
	 */
	public function testBuildMarshalMapForFolder(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->newDefaultEntity();

		$data = [
			'title' => 'Test Template',
			'media_assignments' => [
				1 => [
					'hidden_folder' => [
						'media_folder_id' => 2,
					],
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->mediaAssignments);
		$this->assertCount(1, $entity->mediaAssignments);

		$this->assertArrayHasKey(0, $entity->mediaAssignments);
		$this->assertInstanceOf(MediaAssignment::class, $entity->mediaAssignments[0]);
		$this->assertSame(1, $entity->mediaAssignments[0]->mediaElementId);
		$this->assertNull($entity->mediaAssignments[0]->mediaId);
		$this->assertSame('hidden_folder', $entity->mediaAssignments[0]->mediaElementSelectorIdentifier);
		$this->assertSame(2, $entity->mediaAssignments[0]->mediaFolderId);
		$this->assertSame('widgets', $entity->mediaAssignments[0]->scope);
		$this->assertSame(1, $entity->mediaAssignments[0]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::buildMarshalMap()
	 */
	public function testBuildMarshalMapWithoutLogin(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->newDefaultEntity();

		$data = [
			'title' => 'Test Template',
			'media_assignments' => [
				2 => [
					'media' => [
						'media_id' => '10',
					],
					'lightbox_media' => [
						'media_id' => 10,
					],
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->mediaAssignments);
		$this->assertCount(1, $entity->mediaAssignments);

		$this->assertArrayHasKey(2, $entity->mediaAssignments);

		$this->assertArrayHasKey('media', $entity->mediaAssignments[2]);
		$this->assertIsArray($entity->mediaAssignments[2]['media']);

		$this->assertArrayHasKey('lightbox_media', $entity->mediaAssignments[2]);
		$this->assertIsArray($entity->mediaAssignments[2]['lightbox_media']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::buildMarshalMap()
	 */
	public function testBuildMarshalMapWhenDisabled(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		$this->behavior->setConfig('enabled', false);

		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->newDefaultEntity();

		$data = [
			'title' => 'Test Template',
			'media_assignments' => [
				2 => [
					'media' => [
						'media_id' => '10',
					],
					'lightbox_media' => [
						'media_id' => 10,
					],
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->mediaAssignments);
		$this->assertCount(1, $entity->mediaAssignments);

		$this->assertArrayHasKey(2, $entity->mediaAssignments);

		$this->assertArrayHasKey('media', $entity->mediaAssignments[2]);
		$this->assertIsArray($entity->mediaAssignments[2]['media']);

		$this->assertArrayHasKey('lightbox_media', $entity->mediaAssignments[2]);
		$this->assertIsArray($entity->mediaAssignments[2]['lightbox_media']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::buildMarshalMap()
	 */
	public function testBuildMarshalMapWhenMediaAssignmentsDisabled(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->newDefaultEntity();

		$data = [
			'title' => 'Test Template',
			'media_assignments' => [
				2 => [
					'media' => [
						'media_id' => '10',
					],
					'lightbox_media' => [
						'media_id' => 10,
					],
				],
			],
		];

		$this->table->patchEntity($entity, $data, ['mediaAssignments' => false]);

		$this->assertNotEmpty($entity->mediaAssignments);
		$this->assertCount(1, $entity->mediaAssignments);

		$this->assertArrayHasKey(2, $entity->mediaAssignments);

		$this->assertArrayHasKey('media', $entity->mediaAssignments[2]);
		$this->assertIsArray($entity->mediaAssignments[2]['media']);

		$this->assertArrayHasKey('lightbox_media', $entity->mediaAssignments[2]);
		$this->assertIsArray($entity->mediaAssignments[2]['lightbox_media']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::buildMarshalMap()
	 */
	public function testMarshalMediaAssignmentsSkipsEmptyMediaId(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Entity\Widget $entity */
		$entity = $this->table->newEmptyEntity();

		$data = [
			'title' => 'Test Template',
			'media_assignments' => [
				2 => [
					'media' => [
						'media_id' => null,
					],
					'lightbox_media' => [
						'media_id' => 0,
					],
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertEmpty($entity->mediaAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::buildMarshalMap()
	 */
	public function testMarshalMediaAssignmentsSetsErrors(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		$entity = $this->table->newEmptyEntity();
		$data = [
			'title' => 'Test Template',
			'media_assignments' => [
				2 => [
					'media' => [
						'media_id' => 'invalid',
					],
					'lightbox_media' => [
						'media_id' => 10,
					],
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->getErrors());

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaAssignments', $errors);
		$this->assertArrayHasKey(0, $errors['mediaAssignments']);
		$this->assertArrayHasKey('mediaFolderId', $errors['mediaAssignments'][0]);
		$this->assertSame(['_empty' => 'media_assignments::error_not_empty'], $errors['mediaAssignments'][0]['mediaFolderId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::beforeSave()
	 * @throws \ReflectionException
	 */
	public function testBeforeSaveSkipsWhenNoMediaAssignments(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		$entity = $this->table->newDefaultEntity(['title' => 'Test Widget']);
		$event = new Event('Model.beforeSave');
		$options = new ArrayObject();

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertFalse($entity->has('mediaAssignments'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::beforeSave()
	 * @throws \ReflectionException
	 */
	public function testBeforeSaveSkipsWhenExplicitlySkipped(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Widget',
		]);
		$entity->set('mediaAssignments', ['test']);

		$this->assertTrue($entity->has('mediaAssignments'));
		$this->assertSame(['test'], $entity->mediaAssignments);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['mediaAssignments' => ['skip' => true]]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertTrue($entity->has('mediaAssignments'));
		$this->assertSame(['test'], $entity->mediaAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::beforeSave()
	 * @throws \ReflectionException
	 */
	public function testBeforeSaveUnsetsMediaAssignmentsWhenNoAccess(): void {
		$this->login(4);

		$validAssignment = $this->mediaAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 2,
			'mediaId' => 4,
			'scope' => 'widgets',
		]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Widget',
		]);

		$entity->set('mediaAssignments', [$validAssignment]);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject();

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertFalse($entity->has('mediaAssignments'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::beforeSave()
	 * @throws \ReflectionException
	 */
	public function testBeforeSaveRemovesInvalidAssignments(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		$validAssignment = $this->mediaAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 2,
			'mediaId' => 4,
			'scope' => 'widgets',
		]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Widget',
		]);

		$entity->set('mediaAssignments', [
			$validAssignment,
			'invalid_key' => 'invalid_value',
			'not_an_assignment',
		]);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject();

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertCount(1, $entity->mediaAssignments);
		$this->assertSame($validAssignment, $entity->mediaAssignments[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::beforeSave()
	 * @throws \ReflectionException
	 */
	public function testBeforeSaveMarksAssignmentsAsNew(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		$assignment = $this->mediaAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 2,
			'mediaId' => 4,
			'scope' => 'widgets',
		]);
		$assignment->set('id', 123);
		$assignment->setNew(false);

		$this->assertSame(123, $assignment->id);
		$this->assertFalse($assignment->isNew());

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Widget',
		]);

		$entity->set('mediaAssignments', [
			$assignment,
		]);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['isCopy' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertCount(1, $entity->mediaAssignments);
		$this->assertSame($assignment, $entity->mediaAssignments[0]);

		$this->assertNull($entity->mediaAssignments[0]->id);
		$this->assertTrue($entity->mediaAssignments[0]->isNew());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveWithoutAutoCreateConfig(): void {
		$entity = $this->table->get(23);

		$event = new Event('Model.afterSave');
		$options = new ArrayObject();

		$this->behavior->afterSave($event, $entity, $options);

		$existingAssignments = $this->mediaAssignmentsTable->find()->where([
			'media_element_id' => 1,
			'media_element_selector_identifier' => 'hidden_folder',
			'foreign_key' => 23,
			'scope' => 'widgets',
		])->count();

		$this->assertSame(0, $existingAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveWithAutoCreateConfig(): void {
		/** @var \Awyiss\Model\Entity\Widget $entity */
		$entity = $this->table->get(23);
		$entity->title = 'Dummy Title';

		Configure::write('Awyiss.Widgets.Backend.mediaFolders.autoCreate', true);

		$event = new Event('Model.afterSave');
		$options = new ArrayObject();

		$this->behavior->afterSave($event, $entity, $options);

		$existingAssignments = $this->mediaAssignmentsTable->find()->where([
			'media_element_id' => 1,
			'media_element_selector_identifier' => 'hidden_folder',
			'foreign_key' => 23,
			'scope' => 'widgets',
		]);

		$this->assertCount(1, $existingAssignments);

		$folderId = $existingAssignments->first()->mediaFolderId;
		$this->assertNotEmpty($folderId);

		$folder = $this->fetchTable('MediaFolders')->get($folderId);
		$this->assertInstanceOf(MediaFolder::class, $folder);
		$this->assertSame('Dummy Title', $folder->title);
		$this->assertSame('media/dummy-title', $folder->path);
		$this->assertTrue($folder->hidden);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveWithAutoCreateConfigUsesExistingHiddenFolderAssignment(): void {
		/** @var \Awyiss\Model\Entity\Widget $entity */
		$entity = $this->table->get(23);
		$entity->title = 'Testfolder1';

		$this->mediaAssignmentsTable->deleteAll([
			'media_element_id' => 1,
			'media_element_selector_identifier' => 'hidden_folder',
			'foreign_key' => 23,
			'scope' => 'widgets',
		]);

		$assignment = $this->mediaAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 1,
			'mediaElementSelectorIdentifier' => 'hidden_folder',
			'foreignKey' => 23,
			'scope' => 'widgets',
			'mediaFolderId' => 2,
		]);
		$result = $this->mediaAssignmentsTable->save($assignment);

		$this->assertNotFalse($result);
		$existingAssignment = $result;

		Configure::write('Awyiss.Widgets.Backend.mediaFolders.autoCreate', true);

		$event = new Event('Model.afterSave');
		$options = new ArrayObject();

		$this->behavior->afterSave($event, $entity, $options);

		$existingAssignments = $this->mediaAssignmentsTable->find()->where([
			'media_element_id' => 1,
			'media_element_selector_identifier' => 'hidden_folder',
			'foreign_key' => 23,
			'scope' => 'widgets',
		]);
		$this->assertCount(1, $existingAssignments);

		$this->assertSame($existingAssignment->id, $existingAssignments->first()->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::afterDelete()
	 * @throws \Exception
	 */
	public function testAfterDeleteDeletesHiddenFolder(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Entity\Widget $entity */
		$entity = $this->table->get(23);
		$entity->title = 'Testfolder1';

		$this->mediaAssignmentsTable->deleteAll([
			'media_element_id' => 1,
			'media_element_selector_identifier' => 'hidden_folder',
			'foreign_key' => 23,
			'scope' => 'widgets',
		]);

		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$mediaFolder = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Testfolder1',
			'path' => 'media/testfolder1',
			'hidden' => true,
		]);
		$result = $mediaFoldersTable->save($mediaFolder);
		$this->assertNotFalse($result);
		$mediaFolderId = $result->id;

		$assignment = $this->mediaAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 1,
			'mediaElementSelectorIdentifier' => 'hidden_folder',
			'foreignKey' => 23,
			'scope' => 'widgets',
			'mediaFolderId' => $mediaFolderId,
		]);
		$result = $this->mediaAssignmentsTable->save($assignment);

		$this->assertNotFalse($result);

		$event = new Event('Model.afterDelete');
		$options = new ArrayObject();

		$this->behavior->afterDelete($event, $entity, $options);

		$mediaFolder = $mediaFoldersTable->find()->where(['id' => $mediaFolderId])->first();
		$this->assertNull($mediaFolder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::afterSoftDelete()
	 * @throws \Exception
	 */
	public function testAfterSoftDeleteDeletesHiddenFolder(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Entity\Widget $entity */
		$entity = $this->table->get(23);
		$entity->title = 'Testfolder1';

		$this->mediaAssignmentsTable->deleteAll([
			'media_element_id' => 1,
			'media_element_selector_identifier' => 'hidden_folder',
			'foreign_key' => 23,
			'scope' => 'widgets',
		]);

		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$mediaFolder = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Testfolder1',
			'path' => 'media/testfolder1',
			'hidden' => true,
		]);
		$result = $mediaFoldersTable->save($mediaFolder);
		$this->assertNotFalse($result);
		$mediaFolderId = $result->id;

		$assignment = $this->mediaAssignmentsTable->newDefaultEntity([
			'mediaElementId' => 1,
			'mediaElementSelectorIdentifier' => 'hidden_folder',
			'foreignKey' => 23,
			'scope' => 'widgets',
			'mediaFolderId' => $mediaFolderId,
		]);
		$result = $this->mediaAssignmentsTable->save($assignment);

		$this->assertNotFalse($result);

		$event = new Event('Model.afterSoftDelete');
		$options = new ArrayObject();

		$this->behavior->afterSoftDelete($event, $entity, $options);

		$mediaFolder = $mediaFoldersTable->find()->where(['id' => $mediaFolderId])->first();
		$this->assertNull($mediaFolder);
	}


	/**
	 * @param int $userId The user ID to log in as.
	 * @return \Awyiss\Model\Entity\User
	 */
	protected function login(int $userId = 1): User {
		$request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'dashboard',
				'action' => 'overview',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));

		$user = parent::login($userId);
		$request = $request->withAttribute('identity', $user);

		Router::setRequest($request);

		return $user;
	}
}
