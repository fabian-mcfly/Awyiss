<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Behavior\NestBehavior;
use Awyiss\Model\Entity\GlobalContent;
use Awyiss\Model\Entity\MediaAssignment;
use Awyiss\Model\Table;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Customer\Model\Entity\Employer;
use Exception;


/**
 * NestBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\NestBehavior
 */
class NestBehaviorTest extends TestCase {
	/**
	 * @var \Customer\Model\Table\EmployersTable
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\NestBehavior
	 */
	protected NestBehavior $behavior;


	/**
	 * @return array<\Cake\Datasource\EntityInterface>|false
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	protected function saveTestData(): iterable|false {
		try {
			// The weird order of the entities is to ensure that the order
			// of db ids does not matter when finding children or parents.
			$result = $this->table->saveMany([
				'rootEs' => $this->table->newDefaultEntity([
					'title' => 'Root Employer (es)',
					'languageShortcode' => 'es',
					'parentId' => null,
					'systemOrder' => 2,
				]),
				'grandchild2' => $this->table->newDefaultEntity([
					'title' => 'Grandchild Employer 2',
					'languageShortcode' => 'de',
					'parentId' => null,
					'systemOrder' => 2,
				]),
				'child3' => $this->table->newDefaultEntity([
					'title' => 'Child Employer 3',
					'active' => false,
					'languageShortcode' => 'de',
					'parentId' => null,
					'systemOrder' => 3,
				]),
				'child4' => $this->table->newDefaultEntity([
					'title' => 'Child Employer 4',
					'active' => true,
					'languageShortcode' => 'es',
					'parentId' => null,
					'systemOrder' => 4,
				]),
				'child1' => $this->table->newDefaultEntity([
					'title' => 'Child Employer 1',
					'languageShortcode' => 'de',
					'parentId' => null,
					'systemOrder' => 1,
				]),
				'child2' => $this->table->newDefaultEntity([
					'title' => 'Child Employer 2',
					'languageShortcode' => 'de',
					'parentId' => null,
					'systemOrder' => 2,
				]),
				'grandchild3' => $this->table->newDefaultEntity([
					'title' => 'Grandchild Employer 3',
					'languageShortcode' => 'es',
					'parentId' => null,
					'systemOrder' => 3,
				]),
				'child5' => $this->table->newDefaultEntity([
					'title' => 'Child Employer 5',
					'active' => false,
					'languageShortcode' => 'es',
					'parentId' => null,
					'systemOrder' => 5,
				]),
				'grandchild1' => $this->table->newDefaultEntity([
					'title' => 'Grandchild Employer 1',
					'languageShortcode' => 'de',
					'parentId' => null,
					'systemOrder' => 1,
				]),
				'rootDe' => $this->table->newDefaultEntity([
					'title' => 'Root Employer (de)',
					'languageShortcode' => 'de',
					'parentId' => null,
					'systemOrder' => 1,
				]),
			], [
				'systemOrder' => ['skip' => true],
			]);

			$this->assertNotFalse($result);

			$result['child1']->parentId = $result['rootDe']->id;
			$result['child2']->parentId = $result['rootDe']->id;
			$result['child3']->parentId = $result['rootDe']->id;
			$result['child4']->parentId = $result['rootDe']->id;
			$result['child5']->parentId = $result['rootDe']->id;

			$result['grandchild1']->parentId = $result['child1']->id;
			$result['grandchild2']->parentId = $result['child1']->id;

			$result['grandchild3']->parentId = $result['child5']->id;

			$updateResult = $this->table->saveMany(
				[
					$result['child1'],
					$result['child2'],
					$result['child3'],
					$result['child4'],
					$result['child5'],
					$result['grandchild1'],
					$result['grandchild2'],
					$result['grandchild3'],
				],
				[
					'audit' => ['skip' => true],
					'systemOrder' => ['skip' => true],
				]
			);
			$this->assertNotFalse($updateResult);

			return $result;
		}
		catch (Exception $ex) {
			$this->fail('Failed to save test data: ' . $ex->getMessage());
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Configure::write('Awyiss.Employers.Backend.nest.enabled', true);
		Configure::write('Awyiss.Employers.Backend.splitIntoLanguages', true);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Employers');

		Configure::delete('Awyiss');

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('Nest');

		$this->table->deleteAll([]);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		$this->table->deleteAll([]);

		parent::tearDown();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::__construct()
	 * @see \Awyiss\Model\Behavior\NestBehavior::initialize()
	 */
	public function testInitializationWhenEnabled(): void {
		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame([
			'getNestedChildren' => 'getNestedChildren',
			'getChildren' => 'getChildren',
			'getParent' => 'getParent',
			'getParents' => 'getParents',
			'getPossibleParents' => 'getPossibleParents',
			'listNested' => 'listNested',
		], $config['implementedMethods']);
		$this->assertSame('Employers', $config['alias']);
		$this->assertSame(NestBehavior::STRATEGY_FETCH_ALL, $config['strategy']);

		$this->assertTrue($this->table->hasAssociation('ChildEmployers'));
		$this->assertTrue($this->table->hasAssociation('ParentEmployers'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::initialize()
	 */
	public function testInitializationWhenDisabled(): void {
		Configure::write('Awyiss.Employers.Backend.Nest.enabled', false);
		TableRegistry::getTableLocator()->clear();

		$table = TableRegistry::getTableLocator()->get('Employers');

		Configure::delete('Awyiss');
		TableRegistry::getTableLocator()->clear();

		$behavior = $table->getBehavior('Nest');

		$config = $behavior->getConfig();

		$this->assertFalse($config['enabled']);

		// Disabled behavior should still build the associations, in case forceEnable is used
		$this->assertTrue($table->hasAssociation('ChildEmployers'));
		$this->assertTrue($table->hasAssociation('ParentEmployers'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::initialize()
	 * @see \Awyiss\Model\Behavior\NestBehavior::buildAssociations()
	 */
	public function testAssociations(): void {
		$childAssociation = $this->table->getAssociation('ChildEmployers');
		$this->assertInstanceOf(HasMany::class, $childAssociation);
		$this->assertSame(['id'], $childAssociation->getBindingKey());
		$this->assertSame(['parentId'], $childAssociation->getForeignKey());
		$this->assertTrue($childAssociation->getCascadeCallbacks());
		$this->assertTrue($childAssociation->getDependent());

		$parentAssociation = $this->table->getAssociation('ParentEmployers');
		$this->assertInstanceOf(BelongsTo::class, $parentAssociation);
		$this->assertSame(['id', 'languageShortcode'], $parentAssociation->getBindingKey());
		$this->assertSame(['parentId', 'languageShortcode'], $parentAssociation->getForeignKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function testGetChildren(): void {
		$result = $this->saveTestData();

		$children = $this->table->getChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(5, $children);

		$childrenArray = $children->toList();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Child Employer 2', $childrenArray[1]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[2]);
		$this->assertSame('Child Employer 3', $childrenArray[2]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[3]);
		$this->assertSame('Child Employer 4', $childrenArray[3]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[4]);
		$this->assertSame('Child Employer 5', $childrenArray[4]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function testGetChildrenWhenDisabled(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('enabled', false);

		$children = $this->table->getChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertNull($children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function testGetChildrenWithForceEnabledWhenDisabled(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('enabled', false);

		$children = $this->table->getChildren($result['rootDe'], [
			'forceEnable' => true,
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(5, $children);

		$childrenArray = $children->toList();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Child Employer 2', $childrenArray[1]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[2]);
		$this->assertSame('Child Employer 3', $childrenArray[2]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[3]);
		$this->assertSame('Child Employer 4', $childrenArray[3]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[4]);
		$this->assertSame('Child Employer 5', $childrenArray[4]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function testGetChildrenWithFinderInConfig(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('children.finder', 'active');

		$children = $this->table->getChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(3, $children);

		$childrenArray = $children->toList();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Child Employer 2', $childrenArray[1]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[2]);
		$this->assertSame('Child Employer 4', $childrenArray[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function testGetChildrenWithFinderInOptions(): void {
		$result = $this->saveTestData();

		$children = $this->table->getChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
			'finder' => 'active',
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(3, $children);

		$childrenArray = $children->toList();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Child Employer 2', $childrenArray[1]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[2]);
		$this->assertSame('Child Employer 4', $childrenArray[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function testGetChildrenWithFindersInOptions(): void {
		$result = $this->saveTestData();

		$children = $this->table->getChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
			'finders' => ['active', 'forCurrentLanguage'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(2, $children);

		$childrenArray = $children->toList();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Child Employer 2', $childrenArray[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function testGetChildrenWithNoChildren(): void {
		$result = $this->saveTestData();

		$children = $this->table->getChildren($result['rootEs'], [
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertNotNull($children);
		$this->assertCount(0, $children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function testGetNestedChildren(): void {
		$result = $this->saveTestData();

		$children = $this->table->getNestedChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);

		$this->assertCount(8, $children);

		$childrenArray = $children->toArray();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);
		$this->assertSame(1, $childrenArray[0]->level);
		$this->assertTrue(in_array('level', $childrenArray[0]->getVirtual()));

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Grandchild Employer 1', $childrenArray[1]->title);
		$this->assertSame(2, $childrenArray[1]->level);

		$this->assertInstanceOf(Employer::class, $childrenArray[2]);
		$this->assertSame('Grandchild Employer 2', $childrenArray[2]->title);
		$this->assertSame(2, $childrenArray[2]->level);

		$this->assertInstanceOf(Employer::class, $childrenArray[3]);
		$this->assertSame('Child Employer 2', $childrenArray[3]->title);
		$this->assertSame(1, $childrenArray[3]->level);

		$this->assertInstanceOf(Employer::class, $childrenArray[4]);
		$this->assertSame('Child Employer 3', $childrenArray[4]->title);
		$this->assertSame(1, $childrenArray[4]->level);

		$this->assertInstanceOf(Employer::class, $childrenArray[5]);
		$this->assertSame('Child Employer 4', $childrenArray[5]->title);
		$this->assertSame(1, $childrenArray[5]->level);

		$this->assertInstanceOf(Employer::class, $childrenArray[6]);
		$this->assertSame('Child Employer 5', $childrenArray[6]->title);
		$this->assertSame(1, $childrenArray[6]->level);

		$this->assertInstanceOf(Employer::class, $childrenArray[7]);
		$this->assertSame('Grandchild Employer 3', $childrenArray[7]->title);
		$this->assertSame(2, $childrenArray[7]->level);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function testGetNestedChildrenWhenDisabled(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('enabled', false);

		$children = $this->table->getNestedChildren($result['rootDe']);
		$this->assertNull($children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function testGetNestedChildrenWithForceEnabledWhenDisabled(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('enabled', false);

		$children = $this->table->getNestedChildren($result['rootDe'], [
			'forceEnable' => true,
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(8, $children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function testGetNestedChildrenWithMaxLevel(): void {
		$result = $this->saveTestData();

		$children = $this->table->getNestedChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
			'maxLevel' => 1,
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(5, $children);

		$childrenArray = $children->toList();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);
		$this->assertSame(1, $childrenArray[0]->level);

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Child Employer 2', $childrenArray[1]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[2]);
		$this->assertSame('Child Employer 3', $childrenArray[2]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[3]);
		$this->assertSame('Child Employer 4', $childrenArray[3]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[4]);
		$this->assertSame('Child Employer 5', $childrenArray[4]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function testGetNestedChildrenWithFinderInConfig(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('children.finder', 'active');

		$children = $this->table->getNestedChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(5, $children);

		$childrenArray = $children->toList();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Grandchild Employer 1', $childrenArray[1]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[2]);
		$this->assertSame('Grandchild Employer 2', $childrenArray[2]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[3]);
		$this->assertSame('Child Employer 2', $childrenArray[3]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[4]);
		$this->assertSame('Child Employer 4', $childrenArray[4]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function testGetNestedChildrenWithFinderInOptions(): void {
		$result = $this->saveTestData();

		$children = $this->table->getNestedChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
			'finder' => 'active',
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(5, $children);

		$childrenArray = $children->toList();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Grandchild Employer 1', $childrenArray[1]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[2]);
		$this->assertSame('Grandchild Employer 2', $childrenArray[2]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[3]);
		$this->assertSame('Child Employer 2', $childrenArray[3]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[4]);
		$this->assertSame('Child Employer 4', $childrenArray[4]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function testGetNestedChildrenWithFindersInOptions(): void {
		$result = $this->saveTestData();

		$children = $this->table->getNestedChildren($result['rootDe'], [
			'orderBy' => ['title' => 'ASC'],
			'finders' => ['active', 'forCurrentLanguage'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(4, $children);

		$childrenArray = $children->toList();

		$this->assertInstanceOf(Employer::class, $childrenArray[0]);
		$this->assertSame('Child Employer 1', $childrenArray[0]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[1]);
		$this->assertSame('Grandchild Employer 1', $childrenArray[1]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[2]);
		$this->assertSame('Grandchild Employer 2', $childrenArray[2]->title);

		$this->assertInstanceOf(Employer::class, $childrenArray[3]);
		$this->assertSame('Child Employer 2', $childrenArray[3]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function testGetNestedChildrenWithNoChildren(): void {
		$result = $this->saveTestData();

		$children = $this->table->getNestedChildren($result['rootEs']);
		$this->assertNotNull($children);
		$this->assertCount(0, $children);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function testGetParent(): void {
		$result = $this->saveTestData();

		$parent = $this->table->getParent($result['grandchild3']);

		$this->assertInstanceOf(Employer::class, $parent);
		$this->assertSame('Child Employer 5', $parent->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function testGetParentWhenDisabled(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('enabled', false);

		$parent = $this->table->getParent($result['grandchild3']);

		$this->assertNull($parent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function testGetParentWithForceEnabledWhenDisabled(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('enabled', false);

		$parent = $this->table->getParent($result['grandchild3'], ['forceEnable' => true]);

		$this->assertInstanceOf(Employer::class, $parent);
		$this->assertSame('Child Employer 5', $parent->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function testGetParentWithFinderInConfig(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('parent.finder', 'active');

		$parent = $this->table->getParent($result['grandchild3']);

		$this->assertNull($parent); // Parent of grandchild3 is inactive, so it should not be returned
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function testGetParentWithFinderInOptions(): void {
		$result = $this->saveTestData();

		$parent = $this->table->getParent($result['grandchild3'], ['finder' => 'active']);

		$this->assertNull($parent); // Parent of grandchild3 is inactive, so it should not be returned
	}



	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function testGetParentWithFindersInOptions(): void {
		$result = $this->saveTestData();

		$parent = $this->table->getParent($result['grandchild3'], [
			'finders' => ['active'],
		]);

		$this->assertNull($parent); // Parent of grandchild3 is inactive, so it should not be returned
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function testGetParentWithNoParent(): void {
		$result = $this->saveTestData();

		$parent = $this->table->getParent($result['rootDe']);

		$this->assertNull($parent); // Root entity has no parent
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function testGetParents(): void {
		$result = $this->saveTestData();

		$parents = $this->table->getParents($result['grandchild3']);

		$this->assertInstanceOf(CollectionInterface::class, $parents);
		$this->assertCount(2, $parents);

		$parentsArray = $parents->toList();

		$this->assertInstanceOf(Employer::class, $parentsArray[0]);
		$this->assertSame('Child Employer 5', $parentsArray[0]->title);

		$this->assertInstanceOf(Employer::class, $parentsArray[1]);
		$this->assertSame('Root Employer (de)', $parentsArray[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function testGetParentsWhenDisabled(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('enabled', false);

		$parents = $this->table->getParents($result['grandchild3']);

		$this->assertNull($parents); // Should return null when behavior is disabled
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function testGetParentsWithForceEnabledWhenDisabled(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('enabled', false);

		$parents = $this->table->getParents($result['grandchild3'], ['forceEnable' => true]);

		$this->assertInstanceOf(CollectionInterface::class, $parents);
		$this->assertCount(2, $parents);

		$parentsArray = $parents->toList();

		$this->assertInstanceOf(Employer::class, $parentsArray[0]);
		$this->assertSame('Child Employer 5', $parentsArray[0]->title);
		$this->assertSame(1, $parentsArray[0]->level); // Level should be 1 for direct parent

		$this->assertInstanceOf(Employer::class, $parentsArray[1]);
		$this->assertSame('Root Employer (de)', $parentsArray[1]->title);
		$this->assertSame(0, $parentsArray[1]->level); // Level should be 0 for root parent
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function testGetParentsWithMaxLevel(): void {
		$result = $this->saveTestData();

		$parents = $this->table->getParents($result['grandchild3'], [
			'maxLevel' => 1,
		]);

		$this->assertInstanceOf(CollectionInterface::class, $parents);
		$this->assertCount(1, $parents); // Only direct parent should be returned

		$parentsArray = $parents->toList();

		$this->assertInstanceOf(Employer::class, $parentsArray[0]);
		$this->assertSame('Child Employer 5', $parentsArray[0]->title);
		$this->assertSame(1, $parentsArray[0]->level); // Level should be 1 for direct parent
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function testGetParentsWithFinderInConfig(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('parent.finder', 'active');

		$parents = $this->table->getParents($result['grandchild3']);

		$this->assertInstanceOf(CollectionInterface::class, $parents);
		$this->assertCount(0, $parents); // Direct parent is inactive, so all grandparents should be excluded
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function testGetParentsWithFinderInOptions(): void {
		$result = $this->saveTestData();

		$parents = $this->table->getParents($result['grandchild3'], ['finder' => 'active']);

		$this->assertInstanceOf(CollectionInterface::class, $parents);
		$this->assertCount(0, $parents); // Direct parent is inactive, so all grandparents should be excluded
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function testGetParentsWithFindersInOptions(): void {
		$result = $this->saveTestData();

		$parents = $this->table->getParents($result['grandchild3'], [
			'finders' => ['active'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $parents);
		$this->assertCount(0, $parents); // Direct parent is inactive, so all grandparents should be excluded
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function testGetParentsWithNoParents(): void {
		$result = $this->saveTestData();

		$parents = $this->table->getParents($result['rootEs']);

		$this->assertInstanceOf(CollectionInterface::class, $parents);
		$this->assertCount(0, $parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getPossibleParents()
	 */
	public function testGetPossibleParents(): void {
		$result = $this->saveTestData();

		$threadedEntities = $this->behavior->listNested($this->table->find());

		// Must not contain the entity itself
		$possibleParents = $this->table->getPossibleParents($result['grandchild3'], $threadedEntities);
		$this->assertCount(9, $possibleParents);
		$titles = array_column($possibleParents->toArray(false), 'title');
		$this->assertSame([
			'Root Employer (de)',
			'Child Employer 1',
			'Grandchild Employer 1',
			'Grandchild Employer 2',
			'Child Employer 2',
			'Child Employer 3',
			'Child Employer 4',
			'Child Employer 5',
			'Root Employer (es)',
		], $titles);


		// Must not contain the entity itself, nor its children
		$possibleParents = $this->table->getPossibleParents($result['child1'], $threadedEntities);
		$this->assertCount(7, $possibleParents);
		$titles = array_column($possibleParents->toArray(false), 'title');
		$this->assertSame([
			'Root Employer (de)',
			'Child Employer 2',
			'Child Employer 3',
			'Child Employer 4',
			'Child Employer 5',
			'Grandchild Employer 3',
			'Root Employer (es)',
		], $titles);


		// Must not contain the entity itself, nor its children
		$possibleParents = $this->table->getPossibleParents($result['rootDe'], $threadedEntities);
		$this->assertCount(1, $possibleParents);
		$titles = array_column($possibleParents->toArray(false), 'title');
		$this->assertSame([
			'Root Employer (es)',
		], $titles);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::getPossibleParents()
	 */
	public function testGetPossibleParentsForNewEntity(): void {
		$result = $this->saveTestData();

		$newEntity = $this->table->newDefaultEntity(['title' => 'New Entity']);
		$threadedEntities = new Collection($result);

		$possibleParents = $this->table->getPossibleParents($newEntity, $threadedEntities);
		$this->assertCount(10, $possibleParents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::listNested()
	 */
	public function testListNested(): void {
		$this->saveTestData();

		$query = $this->table->find();
		$nestedList = $this->table->listNested($query);

		$this->assertCount(10, $nestedList);

		$elements = $nestedList->toArray(false);

		$this->assertSame('Root Employer (de)', $elements[0]->title);
		$this->assertNotEmpty($elements[0]->children);

		$this->assertSame('Child Employer 1', $elements[1]->title);
		$this->assertSame('Grandchild Employer 1', $elements[2]->title);
		$this->assertSame('Grandchild Employer 2', $elements[3]->title);
		$this->assertSame('Child Employer 2', $elements[4]->title);
		$this->assertSame('Child Employer 3', $elements[5]->title);
		$this->assertSame('Child Employer 4', $elements[6]->title);
		$this->assertSame('Child Employer 5', $elements[7]->title);
		$this->assertSame('Grandchild Employer 3', $elements[8]->title);
		$this->assertSame('Root Employer (es)', $elements[9]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::listNested()
	 */
	public function testListNestedWithTreeIteratorAndCustomKey(): void {
		$this->saveTestData();

		$threaded = $this->table->find('threaded', nestingKey: 'dummy')->all()->listNested('desc', 'dummy');
		$nestedList = $this->table->listNested($threaded);

		$this->assertCount(10, $nestedList);

		$elements = $nestedList->toArray(false);

		$this->assertSame('Root Employer (de)', $elements[0]->title);
		$this->assertNotEmpty($elements[0]->dummy);
		$this->assertEmpty($elements[0]->children);

		$this->assertSame('Child Employer 1', $elements[1]->title);
		$this->assertSame('Grandchild Employer 1', $elements[2]->title);
		$this->assertSame('Grandchild Employer 2', $elements[3]->title);
		$this->assertSame('Child Employer 2', $elements[4]->title);
		$this->assertSame('Child Employer 3', $elements[5]->title);
		$this->assertSame('Child Employer 4', $elements[6]->title);
		$this->assertSame('Child Employer 5', $elements[7]->title);
		$this->assertSame('Grandchild Employer 3', $elements[8]->title);
		$this->assertSame('Root Employer (es)', $elements[9]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::listNested()
	 */
	public function testListNestedWithDirection(): void {
		$this->saveTestData();

		$query = $this->table->find();
		$nestedList = $this->table->listNested($query, 'children', 'asc');

		$this->assertCount(10, $nestedList);

		$elements = $nestedList->toArray(false);

		$this->assertSame('Grandchild Employer 1', $elements[0]->title);
		$this->assertSame('Grandchild Employer 2', $elements[1]->title);
		$this->assertSame('Child Employer 1', $elements[2]->title);
		$this->assertSame('Child Employer 2', $elements[3]->title);
		$this->assertSame('Child Employer 3', $elements[4]->title);
		$this->assertSame('Child Employer 4', $elements[5]->title);
		$this->assertSame('Grandchild Employer 3', $elements[6]->title);
		$this->assertSame('Child Employer 5', $elements[7]->title);
		$this->assertSame('Root Employer (de)', $elements[8]->title);
		$this->assertSame('Root Employer (es)', $elements[9]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::buildRules()
	 */
	public function testBuildRulesWithUnknownParent(): void {
		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['child1'];
		$entity->parentId = 9999; // Set to an ID that does not exist

		$result = $this->table->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('validParentId', $errors['parentId']);
		$this->assertSame('employers::error_valid_parent_id', $errors['parentId']['validParentId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::buildRules()
	 */
	public function testBuildRulesPreventingCircularReferences(): void {
		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->parentId = $result['grandchild3']->id; // Attempt to create a circular reference

		$result = $this->table->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('validParentId', $errors['parentId']);
		$this->assertSame('employers::error_valid_parent_id', $errors['parentId']['validParentId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::buildRules()
	 */
	public function testBuildRulesWithValidParent(): void {
		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['child1'];
		$entity->parentId = $result['child2']->id;

		$result = $this->table->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::buildRules()
	 */
	public function testBuildRulesWithNullParent(): void {
		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['child1'];
		$entity->parentId = null;

		$result = $this->table->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::buildRules()
	 */
	public function testBuildRulesWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->parentId = $result['grandchild3']->id; // Attempt to create a circular reference

		$result = $this->table->checkRules($entity);
		$this->assertTrue($result); // Should pass since behavior is disabled
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::buildRules()
	 */
	public function testBuildRulesWhenBuildRulesDisabled(): void {
		$this->behavior->setConfig('buildRules', false);

		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->parentId = $result['grandchild3']->id; // Attempt to create a circular reference

		$result = $this->table->checkRules($entity);
		$this->assertTrue($result); // Should pass since behavior is disabled
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::beforeCopy()
	 */
	public function testBeforeCopyLoadsChildren(): void {
		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->title = 'Copied Root Employer (de)';

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);
		$this->behavior->beforeCopy($event, $entity, $options);

		// Check if children are loaded
		$this->assertCount(5, $entity->get('childEmployers'));

		// Make sure nested children are loaded as well
		$this->assertIsArray($entity->get('childEmployers')[0]->get('childEmployers'));
		$grandChildren = $entity->get('childEmployers')[0]->get('childEmployers');
		$this->assertCount(2, $grandChildren);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::beforeCopy()
	 */
	public function testBeforeCopyWhenDisabled(): void {
		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->title = 'Copied Root Employer (de)';

		$this->behavior->setConfig('enabled', false);

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);
		$this->behavior->beforeCopy($event, $entity, $options);

		// Children should not be loaded when behavior is disabled
		$this->assertNull($entity->get('childEmployers'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::beforeCopy()
	 */
	public function testBeforeCopyWhenNotPrimary(): void {
		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->title = 'Copied Root Employer (de)';

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => false]);
		$this->behavior->beforeCopy($event, $entity, $options);

		// Children should not be loaded when not primary
		$this->assertNull($entity->get('childEmployers'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::beforeCopy()
	 */
	public function testBeforeCopyLoadsMediaAssignmentsOfChildren(): void {
		$table = $this->fetchTable('GlobalContents');
		$behavior = $table->getBehavior('Nest');
		$entity = $table->get(4);

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);
		$behavior->beforeCopy($event, $entity, $options);

		$this->assertNotNull($entity->get('childGlobalContents'));
		$this->assertCount(1, $entity->get('childGlobalContents'));

		$child = $entity->get('childGlobalContents')[0];
		$this->assertInstanceOf(GlobalContent::class, $child);
		$this->assertIsArray($child->mediaAssignments);
		$this->assertArrayHasKey(0, $child->mediaAssignments);
		$this->assertInstanceOf(MediaAssignment::class, $child->mediaAssignments[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::beforeCopy()
	 */
	public function testBeforeCopyLoadsTranslationsOfChildren(): void {
		$table = $this->fetchTable('GlobalContents');
		$behavior = $table->getBehavior('Nest');
		$entity = $table->get(4);

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);
		$behavior->beforeCopy($event, $entity, $options);

		$this->assertNotNull($entity->get('childGlobalContents'));
		$this->assertCount(1, $entity->get('childGlobalContents'));

		$child = $entity->get('childGlobalContents')[0];
		$this->assertInstanceOf(GlobalContent::class, $child);
		$this->assertIsArray($child->_translations);
		$this->assertArrayHasKey('de', $child->_translations);
		$this->assertInstanceOf(GlobalContent::class, $child->_translations['de']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::beforeCopy()
	 */
	public function testBeforeCopyUnsetsPrimaryKeysOfChildren(): void {
		$result = $this->saveTestData();

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->title = 'Copied Root Employer (de)';

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);
		$this->behavior->beforeCopy($event, $entity, $options);

		$this->assertInstanceOf(Employer::class, $entity);
		$this->assertSame('Copied Root Employer (de)', $entity->title);

		foreach ($entity->get('childEmployers') as $child) {
			$this->assertNull($child->id);
			$this->assertTrue($child->isNew());
		}

		$grandChildren = $entity->get('childEmployers')[0]->get('childEmployers');
		foreach ($grandChildren as $grandChild) {
			$this->assertNull($grandChild->id);
			$this->assertTrue($grandChild->isNew());
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::beforeCopy()
	 */
	public function testBeforeCopyTransfersRelatedColumnsToChildren(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('relatedColumns', ['languageShortcode', 'title'], false);

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->title = 'Copied Root Employer (de)';
		$entity->languageShortcode = 'es';

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);
		$this->behavior->beforeCopy($event, $entity, $options);

		/** @var \Customer\Model\Entity\Employer $child */
		foreach ($entity->get('childEmployers') as $child) {
			$this->assertSame('Copied Root Employer (de)', $child->title);
			$this->assertSame('es', $child->languageShortcode);
		}

		$grandChildren = $entity->get('childEmployers')[0]->get('childEmployers');
		foreach ($grandChildren as $grandChild) {
			$this->assertSame('Copied Root Employer (de)', $grandChild->title);
			$this->assertSame('es', $grandChild->languageShortcode);
		}

		$entity->languageShortcode = 'en';

		$this->behavior->beforeCopy($event, $entity, $options);

		$this->assertTrue($entity->has('childEmployers'));
		/** @var \Customer\Model\Entity\Employer $child */
		foreach ($entity->get('childEmployers') as $child) {
			$this->assertSame('Copied Root Employer (de)', $child->title);
			$this->assertSame('en', $child->languageShortcode);
		}

		$grandChildren = $entity->get('childEmployers')[0]->get('childEmployers');
		foreach ($grandChildren as $grandChild) {
			$this->assertSame('Copied Root Employer (de)', $grandChild->title);
			$this->assertSame('en', $grandChild->languageShortcode);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::beforeCopy()
	 */
	public function testBeforeCopyNotTransfersBlocklistedRelatedColumnsToChildren(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('children.blocklistedColumns', ['title'], false);
		$this->behavior->setConfig('relatedColumns', ['languageShortcode', 'title'], false);

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->title = 'Copied Root Employer (de)';
		$entity->languageShortcode = 'es';

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);
		$this->behavior->beforeCopy($event, $entity, $options);

		/** @var \Customer\Model\Entity\Employer $child */
		foreach ($entity->get('childEmployers') as $child) {
			$this->assertNotSame('Copied Root Employer (de)', $child->title);
			$this->assertSame('es', $child->languageShortcode);
		}

		$grandChildren = $entity->get('childEmployers')[0]->get('childEmployers');
		foreach ($grandChildren as $grandChild) {
			$this->assertNotSame('Copied Root Employer (de)', $grandChild->title);
			$this->assertSame('es', $grandChild->languageShortcode);
		}

		$entity->languageShortcode = 'en';

		$this->behavior->beforeCopy($event, $entity, $options);

		$this->assertTrue($entity->has('childEmployers'));
		/** @var \Customer\Model\Entity\Employer $child */
		foreach ($entity->get('childEmployers') as $child) {
			$this->assertNotSame('Copied Root Employer (de)', $child->title);
			$this->assertSame('en', $child->languageShortcode);
		}

		$grandChildren = $entity->get('childEmployers')[0]->get('childEmployers');
		foreach ($grandChildren as $grandChild) {
			$this->assertNotSame('Copied Root Employer (de)', $grandChild->title);
			$this->assertSame('en', $grandChild->languageShortcode);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::beforeCopy()
	 */
	public function testBeforeCopyDisabledRulesOnChildAssociation(): void {
		$table = $this->fetchTable('Contents');
		$behavior = $table->getBehavior('Nest');

		$this->assertTrue($table->getAssociation('ChildContents')->getBehavior('Nest')->getConfig('buildRules'));
		$this->assertTrue($table->getAssociation('ChildContents')->getBehavior('Categories')->getConfig('buildRules'));

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $table->get(2);

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);
		$behavior->beforeCopy($event, $entity, $options);

		$this->assertFalse($table->getAssociation('ChildContents')->getBehavior('Nest')->getConfig('buildRules'));
		$this->assertFalse($table->getAssociation('ChildContents')->getBehavior('Categories')->getConfig('buildRules'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveTransfersRelatedColumnsToChildren(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('relatedColumns', ['languageShortcode', 'title'], false);

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->title = 'Updated Root Employer (de)';
		$entity->languageShortcode = 'en';

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);
		$this->behavior->beforeSave($event, $entity, $options);

		$event = new Event('Model.afterSave');
		$this->behavior->afterSave($event, $entity, $options);

		// beforeSave/afterSave does not load children
		$this->assertFalse($entity->has('childEmployers'));

		$children = $this->table->getNestedChildren($entity, [
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(8, $children);

		/** @var \Customer\Model\Entity\Employer $child */
		foreach ($children as $child) {
			$this->assertSame('Updated Root Employer (de)', $child->title);
			$this->assertSame('en', $child->languageShortcode);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\NestBehavior::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveTransfersBlocklistedRelatedColumnsToChildren(): void {
		$result = $this->saveTestData();

		$this->behavior->setConfig('children.blocklistedColumns', ['title'], false);
		$this->behavior->setConfig('relatedColumns', ['languageShortcode', 'title'], false);

		/** @var \Customer\Model\Entity\Employer $entity */
		$entity = $result['rootDe'];
		$entity->title = 'Updated Root Employer (de)';
		$entity->languageShortcode = 'en';

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);
		$this->behavior->beforeSave($event, $entity, $options);

		$event = new Event('Model.afterSave');
		$this->behavior->afterSave($event, $entity, $options);

		// beforeSave/afterSave does not load children
		$this->assertFalse($entity->has('childEmployers'));

		$children = $this->table->getNestedChildren($entity, [
			'orderBy' => ['title' => 'ASC'],
		]);

		$this->assertInstanceOf(CollectionInterface::class, $children);
		$this->assertCount(8, $children);

		/** @var \Customer\Model\Entity\Employer $child */
		foreach ($children as $child) {
			$this->assertNotSame('Updated Root Employer (de)', $child->title);
			$this->assertSame('en', $child->languageShortcode);
		}
	}
}
