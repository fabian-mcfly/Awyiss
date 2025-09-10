<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use Awyiss\Awyiss;
use Awyiss\Model\Behavior\MediaElementAssignmentBehavior;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\MediaElementAssignment;
use Awyiss\Model\Table;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Test\TestSuite\TestCase;
use Cake\ORM\TableRegistry;


/**
 * MediaElementAssignmentBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior
 */
class MediaElementAssignmentBehaviorTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\MediaElementAssignmentBehavior
	 */
	protected MediaElementAssignmentBehavior $behavior;
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $mediaElementAssignmentsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);

		$this->table = TableRegistry::getTableLocator()->get('ContentTemplates');

		$this->behavior = $this->table->getBehavior('MediaElementAssignment');

		$this->mediaElementAssignmentsTable = TableRegistry::getTableLocator()->get('MediaElementAssignments');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializationForEntityLevel(): void {
		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame([
			'mediaElementAssignments' => 'findMediaElementAssignments',
		], $config['implementedFinders']);
		$this->assertSame('content_templates', $config['referenceName']);
		$this->assertSame('subquery', $config['strategy']);

		$this->assertTrue($this->table->hasAssociation('MediaElementAssignments'));

		$this->assertTrue($config['assignable']['entityLevel']);
		$this->assertFalse($config['assignable']['modelLevel']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializationForModelLevel(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('MediaElementAssignment');

		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame([
			'mediaElementAssignments' => 'findMediaElementAssignments',
		], $config['implementedFinders']);
		$this->assertSame('cars', $config['referenceName']);
		$this->assertSame('subquery', $config['strategy']);

		$this->assertTrue($this->table->hasAssociation('MediaElementAssignments'));

		$this->assertFalse($config['assignable']['entityLevel']);
		$this->assertTrue($config['assignable']['modelLevel']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializationDisabledInFrontend(): void {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		TableRegistry::getTableLocator()->clear();

		$table = TableRegistry::getTableLocator()->get('ContentTemplates');
		$behavior = $table->getBehavior('MediaElementAssignment');

		$config = $behavior->getConfig();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);

		$this->assertFalse($config['enabled']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializationWithDatatablesTable(): void {
		$table = TableRegistry::getTableLocator()->get('Datatables');
		$behavior = $table->getBehavior('MediaElementAssignment');

		$config = $behavior->getConfig();
		$this->assertTrue($config['assignable']['entityLevel']);
		$this->assertFalse($config['assignable']['modelLevel']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSkipsInitializationForMediaElementsTables(): void {
		$mediaElementsTable = TableRegistry::getTableLocator()->get('MediaElements');
		$mediaElementsTable->addBehavior('MediaElementAssignment');
		$behavior = $mediaElementsTable->getBehavior('MediaElementAssignment');

		$this->assertFalse($behavior->getConfig('enabled'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSkipsInitializationForMediaElementAssignmentsTables(): void {
		$this->mediaElementAssignmentsTable->addBehavior('MediaElementAssignment');
		$behavior = $this->mediaElementAssignmentsTable->getBehavior('MediaElementAssignment');

		$this->assertFalse($behavior->getConfig('enabled'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAssociations(): void {
		$association = $this->table->getAssociation('MediaElementAssignments');
		$this->assertInstanceOf(HasMany::class, $association);

		$this->assertSame('MediaElementAssignments', $association->getName());
		$this->assertSame('id', $association->getBindingKey());
		$this->assertSame('foreign_key', $association->getForeignKey());
		$this->assertTrue($association->getCascadeCallbacks());
		$this->assertTrue($association->getDependent());
		$this->assertSame('replace', $association->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindMediaElementAssignments(): void {
		$query = $this->table->find('mediaElementAssignments');
		$result = $query->all()->toArray();

		$this->assertCount(2, $result);

		foreach ($result as $entity) {
			$this->assertInstanceOf(ContentTemplate::class, $entity);
			$this->assertNotEmpty($entity->mediaElementAssignments);
			$this->assertIsArray($entity->mediaElementAssignments);

			foreach ($entity->mediaElementAssignments as $assignment) {
				$this->assertInstanceOf(MediaElementAssignment::class, $assignment);
				$this->assertSame('content_templates', $assignment->scope);
			}
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindMediaElementAssignmentsWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$query = $this->table->find('mediaElementAssignments');
		$result = $query->all()->toArray();

		$this->assertCount(2, $result);

		foreach ($result as $entity) {
			$this->assertInstanceOf(ContentTemplate::class, $entity);
			$this->assertEmpty($entity->mediaElementAssignments);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindMediaElementAssignmentsWhenNotEntityLevel(): void {
		$this->behavior->setConfig('assignable.entityLevel', false);

		$query = $this->table->find('mediaElementAssignments');
		$result = $query->all()->toArray();

		$this->assertCount(2, $result);

		foreach ($result as $entity) {
			$this->assertInstanceOf(ContentTemplate::class, $entity);
			$this->assertEmpty($entity->mediaElementAssignments);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::getScope()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetScope(): void {
		$scope = $this->behavior->getConfig('referenceName');
		$this->assertSame('content_templates', $scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::buildMarshalMap()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildMarshalMap(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->get(2);

		$data = [
			'title' => 'Test Template',
			'media_element_assignments' => [
				[
					'media_element_id' => 4,
				],
				[
					'media_element_id' => 2,
					'id' => 4,
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->mediaElementAssignments);
		$this->assertCount(2, $entity->mediaElementAssignments);

		$this->assertInstanceOf(MediaElementAssignment::class, $entity->mediaElementAssignments[0]);
		$this->assertSame(4, $entity->mediaElementAssignments[0]->mediaElementId);
		$this->assertSame('content_templates', $entity->mediaElementAssignments[0]->scope);
		$this->assertTrue($entity->mediaElementAssignments[0]->isNew());
		$this->assertNull($entity->mediaElementAssignments[0]->id);

		$this->assertInstanceOf(MediaElementAssignment::class, $entity->mediaElementAssignments[1]);
		$this->assertSame(2, $entity->mediaElementAssignments[1]->mediaElementId);
		$this->assertSame('content_templates', $entity->mediaElementAssignments[1]->scope);
		$this->assertTrue($entity->mediaElementAssignments[1]->isNew());
		$this->assertNull($entity->mediaElementAssignments[1]->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::buildMarshalMap()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildMarshalMapWithExistingAssignments(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->get(2, contain: ['MediaElementAssignments']);

		$data = [
			'title' => 'Test Template',
			'media_element_assignments' => [
				[
					'media_element_id' => 4,
				],
				[
					'media_element_id' => 2,
					'id' => 4,
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->mediaElementAssignments);
		$this->assertCount(2, $entity->mediaElementAssignments);

		$this->assertInstanceOf(MediaElementAssignment::class, $entity->mediaElementAssignments[0]);
		$this->assertSame(4, $entity->mediaElementAssignments[0]->mediaElementId);
		$this->assertSame('content_templates', $entity->mediaElementAssignments[0]->scope);
		$this->assertTrue($entity->mediaElementAssignments[0]->isNew());
		$this->assertNull($entity->mediaElementAssignments[0]->id);

		$this->assertInstanceOf(MediaElementAssignment::class, $entity->mediaElementAssignments[1]);
		$this->assertSame(2, $entity->mediaElementAssignments[1]->mediaElementId);
		$this->assertSame('content_templates', $entity->mediaElementAssignments[1]->scope);
		$this->assertFalse($entity->mediaElementAssignments[1]->isNew());
		$this->assertSame(4, $entity->mediaElementAssignments[1]->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::buildMarshalMap()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildMarshalMapWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->get(2, contain: ['MediaElementAssignments']);

		$data = [
			'title' => 'Test Template',
			'media_element_assignments' => [
				[
					'media_element_id' => 4,
				],
				[
					'media_element_id' => 2,
					'id' => 4,
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->mediaElementAssignments);
		$this->assertCount(2, $entity->mediaElementAssignments);
		$this->assertNotInstanceOf(MediaElementAssignment::class, $entity->mediaElementAssignments[0]);
		$this->assertNotInstanceOf(MediaElementAssignment::class, $entity->mediaElementAssignments[1]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::buildMarshalMap()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildMarshalMapWhenMediaElementAssignmentsDisabled(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->get(2, contain: ['MediaElementAssignments']);

		$data = [
			'title' => 'Test Template',
			'media_element_assignments' => [
				[
					'media_element_id' => 4,
				],
				[
					'media_element_id' => 2,
					'id' => 4,
				],
			],
		];

		$this->table->patchEntity($entity, $data, [
			'mediaElementAssignments' => false,
		]);

		$this->assertNotEmpty($entity->mediaElementAssignments);
		$this->assertCount(2, $entity->mediaElementAssignments);
		$this->assertNotInstanceOf(MediaElementAssignment::class, $entity->mediaElementAssignments[0]);
		$this->assertNotInstanceOf(MediaElementAssignment::class, $entity->mediaElementAssignments[1]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::buildMarshalMap()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMarshalMediaElementAssignmentsSkipsZeroMediaElementId(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->get(2, contain: ['MediaElementAssignments']);

		$data = [
			'title' => 'Test Template',
			'media_element_assignments' => [
				[
					'media_element_id' => 0,
				],
				[
					'media_element_id' => 2,
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->mediaElementAssignments);
		$this->assertCount(1, $entity->mediaElementAssignments);

		$this->assertInstanceOf(MediaElementAssignment::class, $entity->mediaElementAssignments[0]);
		$this->assertSame(2, $entity->mediaElementAssignments[0]->mediaElementId);
		$this->assertSame('content_templates', $entity->mediaElementAssignments[0]->scope);
		$this->assertTrue($entity->mediaElementAssignments[0]->isNew());
		$this->assertNull($entity->mediaElementAssignments[0]->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::buildMarshalMap()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMarshalMediaElementAssignmentsSetsErrors(): void {
		$entity = $this->table->get(2);

		$data = [
			'title' => 'Test Template',
			'media_element_assignments' => [
				[
					'media_element_id' => 'foobar',
				],
			],
		];

		$this->table->patchEntity($entity, $data);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaElementAssignments', $errors);
		$this->assertArrayHasKey('mediaElementId', $errors['mediaElementAssignments'][0]);
		$this->assertSame([
			'isInteger' => 'media_element_assignments::error_is_integer',
		], $errors['mediaElementAssignments'][0]['mediaElementId']);
	}
}
