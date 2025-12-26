<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior;
use Awyiss\Model\Entity\CustomerGroup;
use Awyiss\Model\Entity\CustomerGroupAccessSetting;
use Awyiss\Model\Entity\CustomerGroupAssignment;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\CustomerGroupAccessType;
use Awyiss\Model\Table;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;


/**
 * CustomerGroupAccessSettingBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior
 */
class CustomerGroupAccessSettingBehaviorTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior
	 */
	protected CustomerGroupAccessSettingBehavior $behavior;
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $accessSettingsTable;
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $assignmentsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->table = TableRegistry::getTableLocator()->get('Pages');

		$this->behavior = $this->table->getBehavior('CustomerGroupAccessSetting');

		$this->accessSettingsTable = TableRegistry::getTableLocator()->get('CustomerGroupAccessSettings');
		$this->assignmentsTable = TableRegistry::getTableLocator()->get('CustomerGroupAssignments');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::__construct()
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::initialize()
	 */
	public function testInitialization(): void {
		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);

		$this->assertSame([
			'customerGroupAccessSettings' => 'findCustomerGroupAccessSettings',
			'customerGroupAssignments' => 'findCustomerGroupAssignments',
		], $config['implementedFinders']);

		$this->assertSame([
			'getCustomerGroupAccessSettings' => 'getCustomerGroupAccessSettings',
			'rebuildCustomerGroupAssignments' => 'rebuildCustomerGroupAssignments',
		], $config['implementedMethods']);

		$this->assertSame('pages', $config['referenceName']);
		$this->assertSame('select', $config['strategy']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::initialize()
	 */
	public function testAssociations(): void {
		// Test access settings association
		$accessSettingsAssociation = $this->table->getAssociation('CustomerGroupAccessSettings');
		$this->assertInstanceOf(HasOne::class, $accessSettingsAssociation);
		$this->assertSame('CustomerGroupAccessSettings', $accessSettingsAssociation->getName());
		$this->assertSame('foreign_key', $accessSettingsAssociation->getForeignKey());
		$this->assertSame('customerGroupAccessSettings', $accessSettingsAssociation->getProperty());
		$this->assertTrue($accessSettingsAssociation->getCascadeCallbacks());
		$this->assertTrue($accessSettingsAssociation->getDependent());

		// Test assignments association
		$assignmentsAssociation = $this->table->getAssociation('CustomerGroupAssignments');
		$this->assertInstanceOf(HasMany::class, $assignmentsAssociation);
		$this->assertSame('CustomerGroupAssignments', $assignmentsAssociation->getName());
		$this->assertSame('foreign_key', $assignmentsAssociation->getForeignKey());
		$this->assertSame('customerGroupAssignments', $assignmentsAssociation->getProperty());
		$this->assertTrue($assignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($assignmentsAssociation->getDependent());
		$this->assertSame('replace', $assignmentsAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findCustomerGroupAccessSettings()
	 */
	public function testFindCustomerGroupAccessSettings(): void {
		$query = $this->table->find('customerGroupAccessSettings')->where(['id' => 1]);
		$result = $query->first();

		$this->assertNotEmpty($result);
		$this->assertInstanceOf(Page::class, $result);
		$this->assertInstanceOf(CustomerGroupAccessSetting::class, $result->customerGroupAccessSettings);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findCustomerGroupAccessSettings()
	 */
	public function testFindCustomerGroupAccessSettingsWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$query = $this->table->find('customerGroupAccessSettings')->where(['id' => 1]);
		$result = $query->first();

		$this->assertNotEmpty($result);
		$this->assertInstanceOf(Page::class, $result);
		// When disabled, access settings should not be included
		$this->assertNull($result->customerGroupAccessSettings);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findCustomerGroupAssignments()
	 */
	public function testFindCustomerGroupAssignments(): void {
		$query = $this->table->find('customerGroupAssignments')->where(['id' => 1]);
		$result = $query->first();

		$this->assertNotEmpty($result);
		$this->assertInstanceOf(Page::class, $result);
		$this->assertIsArray($result->customerGroupAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findCustomerGroupAssignments()
	 */
	public function testFindCustomerGroupAssignmentsWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$query = $this->table->find('customerGroupAssignments')->where(['id' => 1]);
		$result = $query->first();

		$this->assertNotEmpty($result);
		$this->assertInstanceOf(Page::class, $result);
		// When disabled, assignments should not be included
		$this->assertEmpty($result->customerGroupAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findCustomerGroupAssignments()
	 */
	public function testFindCustomerGroupAssignmentsWithoutFormatResult(): void {
		$query = $this->table->find('customerGroupAssignments', formatResult: false)->where(['id' => 1]);
		$result = $query->first();

		$this->assertNotEmpty($result);
		$this->assertInstanceOf(Page::class, $result);
		$this->assertIsArray($result->customerGroupAssignments);
		foreach ($result->customerGroupAssignments as $assignment) {
			$this->assertInstanceOf(CustomerGroupAssignment::class, $assignment);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::getCustomerGroupAccessSettings()
	 */
	public function testGetCustomerGroupAccessSettings(): void {
		$accessSetting = $this->behavior->getCustomerGroupAccessSettings(1);

		$this->assertInstanceOf(CustomerGroupAccessSetting::class, $accessSetting);
		$this->assertSame('pages', $accessSetting->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::rebuildCustomerGroupAssignments()
	 */
	public function testRebuildCustomerGroupAssignments(): void {
		$query = $this->table->find('customerGroupAssignments', formatResult: false)->where(['id' => 1]);
		$entity = $query->first();

		$this->assertNotEmpty($entity);
		$this->assertNotEmpty($entity->customerGroupAssignments);

		// Before rebuild: should have assignment entities with nested customerGroup
		$this->assertIsArray($entity->customerGroupAssignments);
		$originalAssignmentCount = count($entity->customerGroupAssignments);

		foreach ($entity->customerGroupAssignments as $item) {
			$this->assertInstanceOf(CustomerGroupAssignment::class, $item);
			// Assignments should have customerGroup data
			$this->assertNotEmpty($item->customerGroup);
		}

		$this->behavior->rebuildCustomerGroupAssignments($entity);

		// After rebuild: customerGroupAssignments should now contain just the customer groups
		$this->assertIsArray($entity->customerGroupAssignments);

		// Should have same count as original assignments
		$this->assertSame($originalAssignmentCount, count($entity->customerGroupAssignments));

		// Each item should now be a customer group (extracted from the assignment)
		foreach ($entity->customerGroupAssignments as $group) {
			// The extracted group should have id and title
			$this->assertInstanceOf(CustomerGroup::class, $group);
			$this->assertNotNull($group->id);
			$this->assertNotNull($group->title);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::rebuildCustomerGroupAssignments()
	 */
	public function testRebuildCustomerGroupAssignmentsExtractsGroups(): void {
		// Create a mock entity with assignments containing customer groups
		$entity = [
			'id' => 1,
			'title' => 'Test Page',
			'customerGroupAssignments' => [
				new CustomerGroupAssignment([
					'id' => 1,
					'customerGroupId' => 1,
					'scope' => 'pages',
					'customerGroup' => [
						'id' => 1,
						'title' => 'Premium',
						'active' => true,
					],
				]),
				new CustomerGroupAssignment([
					'id' => 2,
					'customerGroupId' => 2,
					'scope' => 'pages',
					'customerGroup' => [
						'id' => 2,
						'title' => 'Standard',
						'active' => true,
					],
				]),
			],
		];

		$result = $this->behavior->rebuildCustomerGroupAssignments($entity);

		// After rebuild, customerGroupAssignments should contain just the groups (not assignments)
		$this->assertCount(2, $result['customerGroupAssignments']);

		// Verify the groups are extracted
		foreach ($result['customerGroupAssignments'] as $group) {
			// Should have id and title from the customer groups
			if (is_array($group)) {
				$this->assertArrayHasKey('id', $group);
				$this->assertArrayHasKey('title', $group);
			}
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::beforeSave()
	 */
	public function testBeforeSaveSkipsWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $this->table->get(1, contain: ['CustomerGroupAssignments']);

		$initialAssignments = $entity->customerGroupAssignments;

		$entity->set('title', 'Updated Title');

		$event = new Event('Model.beforeSave', $this->table);
		$options = new ArrayObject(['customerGroupAssignments' => []]);

		$this->behavior->beforeSave($event, $entity, $options);

		// When disabled, assignments should not be modified
		$this->assertEquals($initialAssignments, $entity->customerGroupAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::beforeSave()
	 */
	public function testBeforeSaveSkipsWhenSkipOptionSet(): void {
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $this->table->get(1, contain: ['CustomerGroupAssignments']);

		$initialAssignments = $entity->customerGroupAssignments;

		$event = new Event('Model.beforeSave', $this->table);
		$options = new ArrayObject(['customerGroupAssignments' => ['skip' => true]]);

		$this->behavior->beforeSave($event, $entity, $options);

		// When skip is set, assignments should not be modified
		$this->assertEquals($initialAssignments, $entity->customerGroupAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::beforeSave()
	 */
	public function testBeforeSaveClearsAssignmentsWhenNotSpecificGroups(): void {
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $this->table->newDefaultEntity([
			'id' => 999,
			'title' => 'Test Page',
		]);

		// Set access setting to all_groups (not specific_groups)
		$entity->set('customerGroupAccessSettings', $this->accessSettingsTable->newDefaultEntity([
			'accessType' => CustomerGroupAccessType::AllGroups,
		]));

		// Add some assignments
		$entity->set('customerGroupAssignments', [
			$this->assignmentsTable->newDefaultEntity(['customerGroupId' => 1]),
			$this->assignmentsTable->newDefaultEntity(['customerGroupId' => 2]),
		]);

		$event = new Event('Model.beforeSave', $this->table);
		$options = new ArrayObject(['customerGroupAssignments' => []]);

		$this->behavior->beforeSave($event, $entity, $options);

		// Assignments should be cleared when access type is not specific_groups
		$this->assertEmpty($entity->customerGroupAssignments);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::buildMarshalMap()
	 */
	public function testBuildMarshalMapAccessSettings(): void {
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $this->table->get(1);

		$data = [
			'title' => 'Updated Title',
			'customer_group_access_settings' => [
				'access_type' => 'specific_groups',
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->customerGroupAccessSettings);
		$this->assertInstanceOf(CustomerGroupAccessSetting::class, $entity->customerGroupAccessSettings);
		$this->assertSame(CustomerGroupAccessType::SpecificGroups, $entity->customerGroupAccessSettings->accessType);
		$this->assertSame('pages', $entity->customerGroupAccessSettings->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::buildMarshalMap()
	 */
	public function testBuildMarshalMapAssignments(): void {
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $this->table->get(1);

		$data = [
			'title' => 'Updated Title',
			'customer_group_assignments' => [
				1,
				2,
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->customerGroupAssignments);
		$this->assertCount(2, $entity->customerGroupAssignments);

		foreach ($entity->customerGroupAssignments as $assignment) {
			$this->assertInstanceOf(CustomerGroupAssignment::class, $assignment);
			$this->assertSame('pages', $assignment->scope);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::buildMarshalMap()
	 */
	public function testBuildMarshalMapAssignmentsWithExistingId(): void {
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $this->table->get(1, contain: ['CustomerGroupAssignments']);

		$data = [
			'title' => 'Updated Title',
			'customer_group_assignments' => [
				['customer_group_id' => 1, 'id' => 1],
				['customer_group_id' => 2],
			],
		];

		$this->table->patchEntity($entity, $data);

		$this->assertNotEmpty($entity->customerGroupAssignments);

		foreach ($entity->customerGroupAssignments as $assignment) {
			$this->assertInstanceOf(CustomerGroupAssignment::class, $assignment);
			$this->assertSame('pages', $assignment->scope);
		}
	}
}
