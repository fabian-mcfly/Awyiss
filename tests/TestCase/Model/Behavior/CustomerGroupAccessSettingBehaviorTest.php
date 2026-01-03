<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior;
use Awyiss\Model\Entity\CustomerGroupAccessSetting;
use Awyiss\Model\Entity\CustomerGroupAssignment;
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
			'accessible' => 'findAccessible',
		], $config['implementedFinders']);

		$this->assertSame([
			'getCustomerGroupAccessSettings' => 'getCustomerGroupAccessSettings',
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
	 * @see \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::getCustomerGroupAccessSettings()
	 */
	public function testGetCustomerGroupAccessSettings(): void {
		$accessSetting = $this->behavior->getCustomerGroupAccessSettings(1);

		$this->assertInstanceOf(CustomerGroupAccessSetting::class, $accessSetting);
		$this->assertSame('pages', $accessSetting->scope);
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
