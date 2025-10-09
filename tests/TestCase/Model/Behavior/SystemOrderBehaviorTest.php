<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Model\Behavior\SystemOrderBehavior;
use Awyiss\Model\Table;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use ReflectionClass;


/**
 * SystemOrderBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\SystemOrderBehavior
 */
class SystemOrderBehaviorTest extends TestCase {
	/**
	 * @var \Customer\Model\Table\EmployersTable
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\SystemOrderBehavior
	 */
	protected SystemOrderBehavior $behavior;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::loadConfiguration('de', 'de');

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Employers');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('SystemOrder');

		$this->table->deleteAll([]);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function tearDown(): void {
		$this->table->deleteAll([]);

		$attributesCarsTable = TableRegistry::getTableLocator()->get('AttributesCars');
		$attributesCarsTable->deleteAll([]);

		$i18nTable = TableRegistry::getTableLocator()->get('I18n');
		$i18nTable->deleteAll([
			'model IN' => ['cars', 'employers'],
		]);

		parent::tearDown();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitialization(): void {
		$config = $this->behavior->getConfig();

		$this->assertSame(SORT_ASC, $config['direction']);
		$this->assertTrue($config['enabled']);
		$this->assertSame('systemOrder', $config['field']);

		$this->assertSame([
			'beforeCopy',
			'beforeFind',
			'beforeMarshal',
			'beforeSave',
			'afterSave',
			'beforeDelete',
			'beforeSoftDelete',
			'afterDelete',
			'afterSoftDelete',
			'afterDeleteCommit',
		], $config['implementedEvents']);

		$this->assertSame([
			'addSystemOrderQueryConditions' => 'addQueryConditions',
			'getHighestSystemOrder' => 'getHighestSystemOrder',
			'getSystemOrderRelatedColumns' => 'getRelatedColumns',
			'hasDirtySystemOrderRelatedColumns' => 'hasDirtyRelatedColumns',
		], $config['implementedMethods']);

		$this->assertSame(['languageShortcode'], $config['relatedColumns']);
		$this->assertFalse($config['skip']);

		$this->assertTrue(Configure::read('Awyiss.Cars.Backend.splitIntoLanguages'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$events = $this->behavior->implementedEvents();
		$this->assertSame([
			'Model.beforeCopy' => 'beforeCopy',
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeMarshal' => 'beforeMarshal',
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
			'Model.beforeDelete' => 'beforeDelete',
			'Model.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.afterDelete' => 'afterDelete',
			'Model.afterSoftDelete' => 'afterSoftDelete',
			'Model.afterDeleteCommit' => 'afterDeleteCommit',
		], $events);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeFind()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindAddsOrdering(): void {
		$query = $this->table->find();
		$options = new ArrayObject();
		$event = new Event('Model.beforeFind');

		$this->behavior->beforeFind($event, $query, $options, true);

		$this->markBeforeFindFired($query);

		$sql = $query->sql();

		$this->assertStringContainsString('ORDER BY Employers.system_order ASC', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWhenDisabled(): void {
		$query = $this->table->find();
		$options = new ArrayObject();
		$event = new Event('Model.beforeFind');

		$this->behavior->setConfig('enabled', false);

		$this->behavior->beforeFind($event, $query, $options, true);

		$this->markBeforeFindFired($query);

		$sql = $query->sql();

		$this->assertStringNotContainsString('ORDER BY Employers.system_order ASC', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeFind()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeFindWhenSkipped(): void {
		$query = $this->table->find();
		$options = new ArrayObject([
			'systemOrder' => [
				'skip' => true,
			],
		]);
		$event = new Event('Model.beforeFind');

		$this->behavior->beforeFind($event, $query, $options, true);

		$this->markBeforeFindFired($query);

		$sql = $query->sql();

		$this->assertStringNotContainsString('ORDER BY Employers.system_order ASC', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeMarshal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalRemovesCurrentValuePlaceholder(): void {
		$data = new ArrayObject([
			'title' => 'Test',
			'systemOrder' => 5,
		]);
		$options = new ArrayObject();
		$event = new Event('Model.beforeMarshal');

		$this->behavior->beforeMarshal($event, $data, $options);

		$this->assertSame(5, $data['systemOrder']);
		$this->assertSame('Test', $data['title']);

		$data = new ArrayObject([
			'title' => 'Test',
			'systemOrder' => SystemOrderBehavior::CURRENT_VALUE_PLACEHOLDER,
		]);

		$this->assertTrue(isset($data['systemOrder']));

		$this->behavior->beforeMarshal($event, $data, $options);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertFalse(isset($data['systemOrder']));
		$this->assertSame('Test', $data['title']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeMarshal()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeMarshalRemovesSnakeCaseCurrentValuePlaceholder(): void {
		$data = new ArrayObject([
			'title' => 'Test',
			'system_order' => 5,
		]);
		$options = new ArrayObject();
		$event = new Event('Model.beforeMarshal');

		$this->assertTrue(isset($data['system_order']));

		$this->behavior->beforeMarshal($event, $data, $options);

		$this->assertSame(5, $data['system_order']);
		$this->assertSame('Test', $data['title']);

		$data = new ArrayObject([
			'title' => 'Test',
			'system_order' => SystemOrderBehavior::CURRENT_VALUE_PLACEHOLDER,
		]);

		$this->assertTrue(isset($data['system_order']));

		$this->behavior->beforeMarshal($event, $data, $options);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertFalse(isset($data['system_order']));
		$this->assertSame('Test', $data['title']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyDoesNothingWhenDisabled(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Copy',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$entity->clean();

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->setConfig('enabled', false);

		$this->behavior->beforeCopy($event, $entity, $options);

		// Must remain unchanged when not primary
		$this->assertSame(2, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyDoesNothingWhenNotPrimary(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Copy',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$entity->clean();

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => false]);

		$this->behavior->beforeCopy($event, $entity, $options);

		// Must remain unchanged when not primary
		$this->assertSame(2, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyDoesNothingWhenRelatedColumnsChanged(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Copy',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$entity->clean();
		$entity->languageShortcode = 'es';

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeCopy($event, $entity, $options);

		// Must remain unchanged when related columns changed
		$this->assertSame(2, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyIncrementsSystemOrderWhenCopyOrderHigherThanOriginal(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Copy',
			'systemOrder' => 3,
			'languageShortcode' => 'de',
		]);
		$entity->clean();

		$originalEntity = $this->table->newDefaultEntity([
			'title' => 'Original',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$originalEntity->clean();

		$entity->originalEntity = $originalEntity;

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeCopy($event, $entity, $options);

		// Must increment by 1 when copy order > original order
		$this->assertSame(4, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyIncrementsSystemOrderWhenCopyOrderEqualToOriginal(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Copy',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$entity->clean();

		$originalEntity = $this->table->newDefaultEntity([
			'title' => 'Original',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$originalEntity->clean();

		$entity->originalEntity = $originalEntity;

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeCopy($event, $entity, $options);

		// Must increment by 1 when copy order = original order
		$this->assertSame(3, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeCopy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyDoesNotIncrementWhenCopyOrderLowerThanOriginal(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Copy',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$entity->clean();

		$originalEntity = $this->table->newDefaultEntity([
			'title' => 'Original',
			'systemOrder' => 3,
			'languageShortcode' => 'de',
		]);
		$originalEntity->clean();

		$entity->originalEntity = $originalEntity;

		$event = new Event('Model.beforeCopy');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeCopy($event, $entity, $options);

		// Must increment by 1 when copy order > original order
		$this->assertSame(2, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveDoesNothingWhenDisabled(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Save',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$entity->clean();

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->setConfig('enabled', false);

		$this->behavior->beforeSave($event, $entity, $options);

		// Must remain unchanged when not primary
		$this->assertSame(2, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveDoesNothingWhenNotPrimary(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Save',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$entity->clean();

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => false]);

		$this->behavior->beforeSave($event, $entity, $options);

		// Must remain unchanged when not primary
		$this->assertSame(2, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveDoesNothingWhenSkipped(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Save',
			'systemOrder' => 2,
			'languageShortcode' => 'de',
		]);
		$entity->clean();

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject([
			'_primary' => true,
			'systemOrder' => [
				'skip' => true,
			],
		]);

		$this->behavior->beforeSave($event, $entity, $options);

		// Must remain unchanged when not primary
		$this->assertSame(2, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveAssignsHighestSystemOrderForNewEntity(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'First', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Second', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Third', 'languageShortcode' => 'de']);
		$entity->clean();

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertSame(3, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveAssignsHighestSystemOrderForNewEntityWhenTooLarge(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 99, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 999, 'languageShortcode' => 'de']);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertSame(3, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveAssignsHighestSystemOrderForNewEntityWhenZero(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 0, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 0, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 0, 'languageShortcode' => 'de']);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertSame(3, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveAssigns1SystemOrderForNewEntityWhenBelowZero(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => -1, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'New First', 'systemOrder' => -5, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'New New First', 'systemOrder' => -10, 'languageShortcode' => 'de']);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertSame(1, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveAssigns1SystemOrderForForExistingEntityWhenBelowOne(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity->systemOrder = 0;

		$this->assertSame(0, $entity->systemOrder);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertSame(1, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveAssignsHighestSystemOrderForExistingEntityWhenTooLarge(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'New First', 'systemOrder' => 1, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity->systemOrder = 99;

		$this->assertSame(99, $entity->systemOrder);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		// Must assign the highest system order when too large
		$this->assertSame(2, $entity->systemOrder);
		$this->assertTrue($entity->isDirty('systemOrder'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveHonorsRelatedFieldsWhenDeterminingHighestSystemOrder(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'es']);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		// Must assign the highest system order for the related language
		$this->assertSame(1, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveHonorsRelatedFieldsWhenDeterminingAutoOrder(): void {
		$this->behavior->setConfig('field', 'title');

		$entity = $this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'es']);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		// Must assign the highest system order for the related language
		$this->assertSame(1, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveAssignsHighestSystemOrderForExistingEntityWhenTooLargeAndScopeChanged(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'es']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);
		$entity = $this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 99, 'languageShortcode' => 'es']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 1, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity->systemOrder = 99;
		$entity->languageShortcode = 'es';

		$this->assertSame(99, $entity->systemOrder);
		$this->assertSame('es', $entity->languageShortcode);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertSame(3, $entity->systemOrder);
		$this->assertSame('es', $entity->languageShortcode);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveCleansSystemOrderForExistingEntityWhenSame(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity->systemOrder = 2;

		$this->assertSame(2, $entity->systemOrder);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		// Must clean the system order when same
		$this->assertSame(2, $entity->systemOrder);
		$this->assertFalse($entity->isDirty('systemOrder'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveSetsAutoOrderWhenFieldNotSystemOrder(): void {
		$this->behavior->setConfig('field', 'title');

		$entity = $this->table->newDefaultEntity(['title' => 'A First', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'The Third', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Some Second', 'languageShortcode' => 'de']);

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertSame(2, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveCleansSystemOrderForExistingEntityWhenSameWithAutoOrder(): void {
		$this->behavior->setConfig('field', 'title');

		$entity = $this->table->newDefaultEntity(['title' => 'A First', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'The Third', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity = $this->table->newDefaultEntity(['title' => 'Some Second', 'languageShortcode' => 'de']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(2, $entity->systemOrder);

		$entity->systemOrder = 1;

		$event = new Event('Model.beforeSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSave($event, $entity, $options);

		// Will revert to the original system order
		$this->assertSame(2, $entity->systemOrder);
		$this->assertFalse($entity->isDirty('systemOrder'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveDoesNothingWhenDisabled(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$newEntity = $this->table->newDefaultEntity(['title' => 'New', 'systemOrder' => 2, 'languageShortcode' => 'de']);

		$event = new Event('Model.afterSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->setConfig('enabled', false);

		$this->behavior->afterSave($event, $newEntity, $options);

		// Database entries should not be changed
		$results = $this->table->find('all')->toArray();
		$this->assertCount(3, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Second', $results[1]->title);
		$this->assertSame(2, $results[1]->systemOrder);

		$this->assertSame('Third', $results[2]->title);
		$this->assertSame(3, $results[2]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveDoesNothingWhenNotPrimary(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$newEntity = $this->table->newDefaultEntity(['title' => 'New', 'systemOrder' => 2, 'languageShortcode' => 'de']);

		$event = new Event('Model.afterSave');
		$options = new ArrayObject(['_primary' => false]);

		$this->behavior->afterSave($event, $newEntity, $options);

		// Database entries should not be changed
		$results = $this->table->find('all')->toArray();
		$this->assertCount(3, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Second', $results[1]->title);
		$this->assertSame(2, $results[1]->systemOrder);

		$this->assertSame('Third', $results[2]->title);
		$this->assertSame(3, $results[2]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveDoesNothingWhenSkipped(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$newEntity = $this->table->newDefaultEntity(['title' => 'New', 'systemOrder' => 2, 'languageShortcode' => 'de']);

		$event = new Event('Model.afterSave');
		$options = new ArrayObject([
			'_primary' => true,
			'systemOrder' => [
				'skip' => true,
			],
		]);

		$this->behavior->afterSave($event, $newEntity, $options);

		// Database entries should not be changed
		$results = $this->table->find('all')->toArray();
		$this->assertCount(3, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Second', $results[1]->title);
		$this->assertSame(2, $results[1]->systemOrder);

		$this->assertSame('Third', $results[2]->title);
		$this->assertSame(3, $results[2]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveReordersOnInsert(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$newEntity = $this->table->newDefaultEntity(['title' => 'New', 'systemOrder' => 2, 'languageShortcode' => 'de']);
		$newEntity->id = 1234; // Simulate an existing ID to satisfy the primary key negation in updateAfterInsert

		$event = new Event('Model.afterSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->afterSave($event, $newEntity, $options);

		// Database entries should be reordered
		$results = $this->table->find('all')->toArray();
		$this->assertCount(3, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Second', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);

		$this->assertSame('Third', $results[2]->title);
		$this->assertSame(4, $results[2]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveNotReordersOnInsertWhenDifferentScope(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$newEntity = $this->table->newDefaultEntity(['title' => 'New', 'systemOrder' => 2, 'languageShortcode' => 'es']);
		$newEntity->id = 1234; // Simulate an existing ID to satisfy the primary key negation in updateAfterInsert

		$event = new Event('Model.afterSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->afterSave($event, $newEntity, $options);

		// Database entries should be reordered
		$results = $this->table->find('all')->toArray();
		$this->assertCount(3, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Second', $results[1]->title);
		$this->assertSame(2, $results[1]->systemOrder);

		$this->assertSame('Third', $results[2]->title);
		$this->assertSame(3, $results[2]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveReordersOnMoveForward(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$third = $this->table->find('all')->where(['title' => 'Third'])->first();

		$this->assertSame('Third', $third->title);
		$this->assertSame(3, $third->systemOrder);

		$third->systemOrder = 1; // Move to the front

		$event = new Event('Model.afterSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->afterSave($event, $third, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Third'])->toArray();
		$this->assertCount(3, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(2, $results[0]->systemOrder);

		$this->assertSame('Second', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);

		$this->assertSame('Fourth', $results[2]->title);
		$this->assertSame(4, $results[2]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveReordersOnMoveBackward(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$second = $this->table->find('all')->where(['title' => 'Second'])->first();

		$this->assertSame('Second', $second->title);
		$this->assertSame(2, $second->systemOrder);

		$second->systemOrder = 4; // Move to the end

		$event = new Event('Model.afterSave');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->afterSave($event, $second, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(3, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(2, $results[1]->systemOrder);

		$this->assertSame('Fourth', $results[2]->title);
		$this->assertSame(3, $results[2]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveClosesGapOnScopeChange(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$second = $this->table->find('all')->where(['title' => 'Second'])->first();

		$this->assertSame('Second', $second->title);
		$this->assertSame(2, $second->systemOrder);

		$second->languageShortcode = 'es'; // Change scope

		$options = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.beforeSave');
		// beforeSave will remember the dirty related fields
		$this->behavior->beforeSave($event, $second, $options);
		$event = new Event('Model.afterSave');
		$this->behavior->afterSave($event, $second, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(2, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveMovesItemsOnScopeChange(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 1, 'languageShortcode' => 'es']),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 2, 'languageShortcode' => 'es']),
			$this->table->newDefaultEntity(['title' => 'Fifth', 'systemOrder' => 3, 'languageShortcode' => 'es']),
		]);

		$this->assertNotFalse($result);

		$first = $this->table->find('all')->where(['title' => 'First'])->first();

		$this->assertSame('First', $first->title);
		$this->assertSame(1, $first->systemOrder);

		$first->languageShortcode = 'es'; // Change scope

		$options = new ArrayObject(['_primary' => true]);
		$event = new Event('Model.beforeSave');
		// beforeSave will remember the dirty related fields
		$this->behavior->beforeSave($event, $first, $options);
		$event = new Event('Model.afterSave');
		$this->behavior->afterSave($event, $first, $options);

		$results = $this->table->find('all')->where(['title !=' => 'First', 'language_shortcode' => 'es'])->toArray();
		$this->assertCount(3, $results);

		$this->assertSame('Third', $results[0]->title);
		$this->assertSame(2, $results[0]->systemOrder);

		$this->assertSame('Fourth', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);

		$this->assertSame('Fifth', $results[2]->title);
		$this->assertSame(4, $results[2]->systemOrder);

		$results = $this->table->find('all')->where(['title !=' => 'First', 'language_shortcode' => 'de'])->toArray();
		$this->assertCount(1, $results);

		$this->assertSame('Second', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeDelete()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeDeleteSetsSystemOrderToMax(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'systemOrder' => 1, 'languageShortcode' => 'es']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$event = new Event('Model.beforeDelete');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeDelete($event, $entity, $options);

		$this->assertSame(999999, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeDelete()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeDeleteSetsSystemOrderToMaxWhenNotPrimary(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'systemOrder' => 1, 'languageShortcode' => 'es']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$event = new Event('Model.beforeDelete');
		$options = new ArrayObject(['_primary' => false]);

		$this->behavior->beforeDelete($event, $entity, $options);

		$this->assertSame(999999, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSoftDelete()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSoftDeleteSetsSystemOrderToMax(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'systemOrder' => 1, 'languageShortcode' => 'es']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$event = new Event('Model.beforeSoftDelete');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->beforeSoftDelete($event, $entity, $options);

		$this->assertSame(999999, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::beforeSoftDelete()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSoftDeleteSetsSystemOrderToMaxWhenNotPrimary(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'systemOrder' => 1, 'languageShortcode' => 'es']);
		$result = $this->table->save($entity);

		$this->assertNotFalse($result);
		$this->assertSame(1, $entity->systemOrder);

		$event = new Event('Model.beforeSoftDelete');
		$options = new ArrayObject(['_primary' => false]);

		$this->behavior->beforeSoftDelete($event, $entity, $options);

		$this->assertSame(999999, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteReordersRemainingEntities(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterDelete');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->afterDelete($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(2, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteNotReordersRemainingEntitiesWhenDisabled(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterDelete');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->setConfig('enabled', false);

		$this->behavior->afterDelete($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteNotReordersRemainingEntitiesWhenNotPrimary(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterDelete');
		$options = new ArrayObject(['_primary' => false]);

		$this->behavior->afterDelete($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteNotReordersRemainingEntitiesWhenSkipped(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterDelete');
		$options = new ArrayObject([
			'_primary' => true,
			'systemOrder' => [
				'skip' => true,
			],
		]);

		$this->behavior->afterDelete($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSoftDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSoftDeleteReordersRemainingEntities(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterSoftDelete');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->afterSoftDelete($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(2, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSoftDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSoftDeleteNotReordersRemainingEntitiesWhenDisabled(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterSoftDelete');
		$options = new ArrayObject(['_primary' => true]);

		$this->behavior->setConfig('enabled', false);

		$this->behavior->afterSoftDelete($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSoftDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSoftDeleteNotReordersRemainingEntitiesWhenNotPrimary(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterSoftDelete');
		$options = new ArrayObject(['_primary' => false]);

		$this->behavior->afterSoftDelete($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterSoftDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSoftDeleteNotReordersRemainingEntitiesWhenSkipped(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterSoftDelete');
		$options = new ArrayObject([
			'_primary' => true,
			'systemOrder' => [
				'skip' => true,
			],
		]);

		$this->behavior->afterSoftDelete($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterDeleteCommit()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteCommitDoesNothingWhenDisabled(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterSoftDelete');
		$options = new ArrayObject([
			'_primary' => true,
			'systemOrder' => [
				'skip' => true,
			],
		]);

		$this->behavior->afterDelete($event, $entity, $options);

		$this->behavior->setConfig('enabled', false);

		$this->behavior->afterDeleteCommit($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterDeleteCommit()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteCommitDoesNothingWhenNoRelatedData(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterSoftDelete');
		$options = new ArrayObject(['_primary' => true]);
		$this->behavior->setConfig('enabled', false);

		$this->behavior->afterDeleteCommit($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::afterDeleteCommit()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterDeleteCommitReordersRemainingEntities(): void {
		$this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$entity = $this->table->find('all')->where(['title' => 'Second'])->first();

		$event = new Event('Model.afterDelete');
		$options = new ArrayObject([
			'_primary' => true,
			'systemOrder' => [
				'skip' => true, // Only when skipped the afterDelete, the reordering is done in afterDeleteCommit
			],
		]);

		$this->behavior->afterDelete($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(3, $results[1]->systemOrder);

		$this->behavior->afterDeleteCommit($event, $entity, $options);

		$results = $this->table->find('all')->where(['title !=' => 'Second'])->toArray();
		$this->assertCount(2, $results);

		$this->assertSame('First', $results[0]->title);
		$this->assertSame(1, $results[0]->systemOrder);

		$this->assertSame('Third', $results[1]->title);
		$this->assertSame(2, $results[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsReturnsFalseWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$query = $this->table->find();

		$result = $this->behavior->addQueryConditions($query, $entity);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsCreatesNewQueryWhenNullProvided(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);

		$result = $this->behavior->addQueryConditions(null, $entity);

		$this->assertInstanceOf(SelectQuery::class, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsAddsWhereClausesForRelatedColumns(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$query = $this->table->find();

		$result = $this->behavior->addQueryConditions($query, $entity);

		$this->assertInstanceOf(SelectQuery::class, $result);

		$sql = $result->sql();
		$this->assertStringContainsString('language_shortcode = :c0', $sql);
		$values = $result->getValueBinder()->bindings();
		$this->assertArrayHasKey(':c0', $values);
		$this->assertSame('de', $values[':c0']['value']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsSkipsIdSystemOrderColumns(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$query = $this->table->find();

		$this->behavior->setConfig('relatedColumns', ['id', 'systemOrder', 'system_order', 'languageShortcode'], false);

		$result = $this->behavior->addQueryConditions($query, $entity);

		$sql = $result->sql();

		$this->assertStringNotContainsString('id =', $sql);
		$this->assertStringNotContainsString('system_order =', $sql);
		$this->assertStringContainsString('language_shortcode = :c0', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsHandlesNullValues(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => null]);
		$query = $this->table->find();

		$result = $this->behavior->addQueryConditions($query, $entity);

		$sql = $result->sql();

		// Must use IS NULL for null values
		$this->assertStringContainsString('(Employers.language_shortcode) IS NULL', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsUsesCurrentValuesWhenPreferOriginalFalse(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$entity->clean();
		$entity->languageShortcode = 'es'; // Change the value

		$query = $this->table->find();

		$result = $this->behavior->addQueryConditions($query, $entity);

		$sql = $result->sql();

		// Must use the current value 'es'
		$this->assertStringContainsString('language_shortcode = :c0', $sql);
		$values = $result->getValueBinder()->bindings();
		$this->assertArrayHasKey(':c0', $values);
		$this->assertSame('es', $values[':c0']['value']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsUsesOriginalValuesWhenPreferOriginalTrue(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$entity->clean();
		$entity->languageShortcode = 'es'; // Change the value

		$query = $this->table->find();

		$result = $this->behavior->addQueryConditions($query, $entity, true);

		$sql = $result->sql();

		// Must use the original value 'de'
		$this->assertStringContainsString('language_shortcode = :c0', $sql);
		$values = $result->getValueBinder()->bindings();
		$this->assertArrayHasKey(':c0', $values);
		$this->assertSame('de', $values[':c0']['value']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function _testAddQueryConditionsWithMultipleRelatedColumns(): void {
		// Set up multiple related columns
		$this->behavior->setConfig('relatedColumns', ['languageShortcode', 'title']);

		$entity = $this->table->newDefaultEntity(['title' => 'Test Title', 'languageShortcode' => 'de']);
		$query = $this->table->find();

		$result = $this->behavior->addQueryConditions($query, $entity);

		$sql = $result->sql();

		// Must contain conditions for both columns
		$this->assertStringContainsString('language_shortcode', $sql);
		$this->assertStringContainsString('title', $sql);
		$this->assertStringContainsString('de', $sql);
		$this->assertStringContainsString('Test Title', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsReturnsOriginalQuery(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$query = $this->table->find();

		$result = $this->behavior->addQueryConditions($query, $entity);

		// Must return the same query object that was passed in
		$this->assertSame($query, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsKeepsPreExistingConditions(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$query = $this->table->find()->where(['id >' => 100]);

		$result = $this->behavior->addQueryConditions($query, $entity);

		$sql = $result->sql();

		// Must preserve existing conditions and add new ones
		$this->assertStringContainsString('id > :c0', $sql);
		$this->assertStringContainsString('language_shortcode = :c1', $sql);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionsWithAttributes(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Cars');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('SystemOrder');

		/** @var \Customer\Model\Entity\Car $entity */
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'es', 'attributes' => ['dropdownSelect' => 'dummy']]);
		/** @noinspection PhpUndefinedFieldInspection */
		$this->assertSame('dummy', $entity->dropdownSelect);
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->assertSame('dummy', $entity->attributes->dropdownSelect);

		// Ensure the behavior is configured to handle attributes
		$this->behavior->setConfig('relatedColumns', ['languageShortcode', 'attributes.dropdownSelect'], false);

		$query = $this->table->find();

		$result = $this->behavior->addQueryConditions($query, $entity);

		$this->assertInstanceOf(SelectQuery::class, $result);

		$sql = $result->sql();

		$this->assertStringContainsString('language_shortcode = :c8', $sql);
		$this->assertStringContainsString('AttributesCars.dropdown_select = :c9', $sql);

		$values = $result->getValueBinder()->bindings();

		$this->assertArrayHasKey(':c8', $values);
		$this->assertSame('es', $values[':c8']['value']);

		$this->assertArrayHasKey(':c9', $values);
		$this->assertSame('dummy', $values[':c9']['value']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddQueryConditionWithResult(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 1, 'languageShortcode' => 'es']),
			$this->table->newDefaultEntity(['title' => 'Fifth', 'systemOrder' => 2, 'languageShortcode' => 'es']),
		]);

		$this->assertCount(5, $result);

		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'es']);

		$query = $this->table->find();
		$query = $this->behavior->addQueryConditions($query, $entity);

		$this->assertInstanceOf(SelectQuery::class, $query);

		$result = $query->all()->toArray();

		$this->assertCount(2, $result);

		$this->assertSame('Fourth', $result[0]->title);
		$this->assertSame(1, $result[0]->systemOrder);

		$this->assertSame('Fifth', $result[1]->title);
		$this->assertSame(2, $result[1]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::getHighestSystemOrder()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHighestSystemOrderWithEmptyTable(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test']);

		$result = $this->behavior->getHighestSystemOrder($entity);

		$this->assertSame(0, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::getHighestSystemOrder()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHighestSystemOrderWithExistingEntities(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);

		$result = $this->behavior->getHighestSystemOrder($entity);

		$this->assertSame(3, $result);

		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'es']);

		$result = $this->behavior->getHighestSystemOrder($entity);

		$this->assertSame(0, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::getDirtyRelatedColumns()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetDirtyRelatedColumns(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$entity->clean();
		$entity->languageShortcode = 'es';

		$result = $this->behavior->getDirtyRelatedColumns($entity);

		$this->assertSame(['languageShortcode'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::getDirtyRelatedColumns()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetDirtyRelatedColumnsWithNoDirtyFields(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$entity->clean();

		$result = $this->behavior->getDirtyRelatedColumns($entity);

		$this->assertSame([], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::getDirtyRelatedColumns()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetDirtyRelatedColumnsWithAttributes(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Cars');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('SystemOrder');

		/** @var \Customer\Model\Entity\Car $entity */
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'es', 'attributes' => ['dropdownSelect' => 'dummy']]);
		/** @noinspection PhpUndefinedFieldInspection */
		$this->assertSame('dummy', $entity->dropdownSelect);
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->assertSame('dummy', $entity->attributes->dropdownSelect);

		// Ensure the behavior is configured to handle attributes
		$this->behavior->setConfig('relatedColumns', ['languageShortcode', 'attributes.dropdownSelect'], false);

		$result = $this->behavior->getDirtyRelatedColumns($entity);

		$this->assertSame(['languageShortcode', 'dropdownSelect'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::hasDirtyRelatedColumns()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testHasDirtyRelatedColumns(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'de']);
		$entity->clean();
		$entity->languageShortcode = 'es';

		$result = $this->behavior->hasDirtyRelatedColumns($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::hasDirtyRelatedColumns()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testHasDirtyRelatedColumnsWithNoDirtyFields(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test']);

		$result = $this->behavior->hasDirtyRelatedColumns($entity);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::hasDirtyRelatedColumns()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testHasDirtyRelatedColumnsWithAttributes(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Cars');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('SystemOrder');

		/** @var \Customer\Model\Entity\Car $entity */
		$entity = $this->table->newDefaultEntity(['title' => 'Test', 'languageShortcode' => 'es']);
		$entity->attributes->clean();

		/** @noinspection PhpDynamicFieldDeclarationInspection, PhpPossiblePolymorphicInvocationInspection */
		$entity->attributes->dropdownSelect = 'dummy'; // Ensure the attribute is set

		/** @noinspection PhpUndefinedFieldInspection */
		$this->assertSame('dummy', $entity->dropdownSelect);
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->assertSame('dummy', $entity->attributes->dropdownSelect);

		// Ensure the behavior is configured to handle attributes
		$this->behavior->setConfig('relatedColumns', ['languageShortcode', 'attributes.dropdownSelect'], false);

		$result = $this->behavior->hasDirtyRelatedColumns($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::rebuildSystemOrder()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRebuildSystemOrderForFieldSystemOrder(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 20, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 4, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 16, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 5, 'languageShortcode' => 'es']),
			$this->table->newDefaultEntity(['title' => 'Fifth', 'systemOrder' => 50, 'languageShortcode' => 'es']),
		], ['atomic' => false, 'checkRules' => false, 'systemOrder' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(5, $result);

		$this->assertSame('Second', $result[0]->title);
		$this->assertSame(4, $result[0]->systemOrder);

		$this->assertSame('Fourth', $result[1]->title);
		$this->assertSame(5, $result[1]->systemOrder);

		$this->assertSame('Third', $result[2]->title);
		$this->assertSame(16, $result[2]->systemOrder);

		$this->assertSame('First', $result[3]->title);
		$this->assertSame(20, $result[3]->systemOrder);

		$this->assertSame('Fifth', $result[4]->title);
		$this->assertSame(50, $result[4]->systemOrder);

		// Rebuild system order
		$result = $this->behavior->rebuildSystemOrder('systemOrder');

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(5, $result);

		$this->assertSame('Second', $result[0]->title);
		$this->assertSame(1, $result[0]->systemOrder);

		$this->assertSame('Fourth', $result[1]->title);
		$this->assertSame(1, $result[1]->systemOrder);

		$this->assertSame('Third', $result[2]->title);
		$this->assertSame(2, $result[2]->systemOrder);

		$this->assertSame('Fifth', $result[3]->title);
		$this->assertSame(2, $result[3]->systemOrder);

		$this->assertSame('First', $result[4]->title);
		$this->assertSame(3, $result[4]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::rebuildSystemOrder()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRebuildSystemOrderForFieldSystemOrderDescending(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 20, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 4, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 16, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 5, 'languageShortcode' => 'es']),
			$this->table->newDefaultEntity(['title' => 'Fifth', 'systemOrder' => 50, 'languageShortcode' => 'es']),
		], ['atomic' => false, 'checkRules' => false, 'systemOrder' => ['skip' => true]]);

		$this->assertNotFalse($result);

		// Rebuild system order
		$result = $this->behavior->rebuildSystemOrder('systemOrder', SORT_DESC);

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(5, $result);

		$this->assertSame('First', $result[0]->title);
		$this->assertSame(1, $result[0]->systemOrder);

		$this->assertSame('Fifth', $result[1]->title);
		$this->assertSame(1, $result[1]->systemOrder);

		$this->assertSame('Third', $result[2]->title);
		$this->assertSame(2, $result[2]->systemOrder);

		$this->assertSame('Fourth', $result[3]->title);
		$this->assertSame(2, $result[3]->systemOrder);

		$this->assertSame('Second', $result[4]->title);
		$this->assertSame(3, $result[4]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::rebuildSystemOrder()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRebuildSystemOrderForFieldSystemOrderWithAdditionalWhere(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 20, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 4, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 16, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 5, 'languageShortcode' => 'es']),
			$this->table->newDefaultEntity(['title' => 'Fifth', 'systemOrder' => 50, 'languageShortcode' => 'es']),
		], ['atomic' => false, 'checkRules' => false, 'systemOrder' => ['skip' => true]]);

		$this->assertNotFalse($result);

		// Rebuild system order
		$result = $this->behavior->rebuildSystemOrder('systemOrder', SORT_ASC, null, ['language_shortcode' => 'es']);

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(5, $result);

		$this->assertSame('Fourth', $result[0]->title);
		$this->assertSame(1, $result[0]->systemOrder);

		$this->assertSame('Fifth', $result[1]->title);
		$this->assertSame(2, $result[1]->systemOrder);

		$this->assertSame('Second', $result[2]->title);
		$this->assertSame(4, $result[2]->systemOrder);

		$this->assertSame('Third', $result[3]->title);
		$this->assertSame(16, $result[3]->systemOrder);

		$this->assertSame('First', $result[4]->title);
		$this->assertSame(20, $result[4]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::rebuildSystemOrder()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRebuildSystemOrderForFieldSystemOrderWithoutRelatedFields(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 20, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 4, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 16, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 5, 'languageShortcode' => 'es']),
			$this->table->newDefaultEntity(['title' => 'Fifth', 'systemOrder' => 50, 'languageShortcode' => 'es']),
		], ['atomic' => false, 'checkRules' => false, 'systemOrder' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(5, $result);

		$this->assertSame('Second', $result[0]->title);
		$this->assertSame(4, $result[0]->systemOrder);

		$this->assertSame('Fourth', $result[1]->title);
		$this->assertSame(5, $result[1]->systemOrder);

		$this->assertSame('Third', $result[2]->title);
		$this->assertSame(16, $result[2]->systemOrder);

		$this->assertSame('First', $result[3]->title);
		$this->assertSame(20, $result[3]->systemOrder);

		$this->assertSame('Fifth', $result[4]->title);
		$this->assertSame(50, $result[4]->systemOrder);

		$this->behavior->setConfig('relatedColumns', [], false);

		// Rebuild system order
		$result = $this->behavior->rebuildSystemOrder('systemOrder');

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(5, $result);

		$this->assertSame('Second', $result[0]->title);
		$this->assertSame(1, $result[0]->systemOrder);

		$this->assertSame('Fourth', $result[1]->title);
		$this->assertSame(2, $result[1]->systemOrder);

		$this->assertSame('Third', $result[2]->title);
		$this->assertSame(3, $result[2]->systemOrder);

		$this->assertSame('First', $result[3]->title);
		$this->assertSame(4, $result[3]->systemOrder);

		$this->assertSame('Fifth', $result[4]->title);
		$this->assertSame(5, $result[4]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::rebuildSystemOrder()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRebuildSystemOrderForTextField(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Äthiopien', 'systemOrder' => 20, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Australien', 'systemOrder' => 4, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Deutschland', 'systemOrder' => 16, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Oman', 'systemOrder' => 5, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Österreich', 'systemOrder' => 50, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Schweiz', 'systemOrder' => 10, 'languageShortcode' => 'de']),
		], ['atomic' => false, 'checkRules' => false, 'systemOrder' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(6, $result);

		$this->assertSame('Australien', $result[0]->title);
		$this->assertSame(4, $result[0]->systemOrder);

		$this->assertSame('Oman', $result[1]->title);
		$this->assertSame(5, $result[1]->systemOrder);

		$this->assertSame('Schweiz', $result[2]->title);
		$this->assertSame(10, $result[2]->systemOrder);

		$this->assertSame('Deutschland', $result[3]->title);
		$this->assertSame(16, $result[3]->systemOrder);

		$this->assertSame('Äthiopien', $result[4]->title);
		$this->assertSame(20, $result[4]->systemOrder);

		$this->assertSame('Österreich', $result[5]->title);
		$this->assertSame(50, $result[5]->systemOrder);

		// Rebuild system order
		$result = $this->behavior->rebuildSystemOrder('title');

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(6, $result);

		$this->assertSame('Äthiopien', $result[0]->title);
		$this->assertSame(1, $result[0]->systemOrder);

		$this->assertSame('Australien', $result[1]->title);
		$this->assertSame(2, $result[1]->systemOrder);

		$this->assertSame('Deutschland', $result[2]->title);
		$this->assertSame(3, $result[2]->systemOrder);

		$this->assertSame('Oman', $result[3]->title);
		$this->assertSame(4, $result[3]->systemOrder);

		$this->assertSame('Österreich', $result[4]->title);
		$this->assertSame(5, $result[4]->systemOrder);

		$this->assertSame('Schweiz', $result[5]->title);
		$this->assertSame(6, $result[5]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::rebuildSystemOrder()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRebuildSystemOrderForTextAttribute(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Cars');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('SystemOrder');

		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Oman']]),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Australien']]),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Österreich']]),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 4, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Äthiopien']]),
			$this->table->newDefaultEntity(['title' => 'Fifth', 'systemOrder' => 5, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Schweiz']]),
			$this->table->newDefaultEntity(['title' => 'Sixth', 'systemOrder' => 6, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Deutschland']]),
		], ['atomic' => false, 'checkRules' => false, 'systemOrder' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(6, $result);

		// Rebuild system order
		$result = $this->behavior->rebuildSystemOrder('dropdown_select');

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(6, $result);

		$this->assertSame('Fourth', $result[0]->title);
		$this->assertSame('Äthiopien', $result[0]->dropdownSelect);
		$this->assertSame(1, $result[0]->systemOrder);

		$this->assertSame('Second', $result[1]->title);
		$this->assertSame('Australien', $result[1]->dropdownSelect);
		$this->assertSame(2, $result[1]->systemOrder);

		$this->assertSame('Sixth', $result[2]->title);
		$this->assertSame('Deutschland', $result[2]->dropdownSelect);
		$this->assertSame(3, $result[2]->systemOrder);

		$this->assertSame('First', $result[3]->title);
		$this->assertSame('Oman', $result[3]->dropdownSelect);
		$this->assertSame(4, $result[3]->systemOrder);

		$this->assertSame('Third', $result[4]->title);
		$this->assertSame('Österreich', $result[4]->dropdownSelect);
		$this->assertSame(5, $result[4]->systemOrder);

		$this->assertSame('Fifth', $result[5]->title);
		$this->assertSame('Schweiz', $result[5]->dropdownSelect);
		$this->assertSame(6, $result[5]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::rebuildSystemOrder()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRebuildSystemOrderForTextAttributeWithPrefix(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Cars');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('SystemOrder');

		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'First', 'systemOrder' => 1, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Oman']]),
			$this->table->newDefaultEntity(['title' => 'Second', 'systemOrder' => 2, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Australien']]),
			$this->table->newDefaultEntity(['title' => 'Third', 'systemOrder' => 3, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Österreich']]),
			$this->table->newDefaultEntity(['title' => 'Fourth', 'systemOrder' => 4, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Äthiopien']]),
			$this->table->newDefaultEntity(['title' => 'Fifth', 'systemOrder' => 5, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Schweiz']]),
			$this->table->newDefaultEntity(['title' => 'Sixth', 'systemOrder' => 6, 'languageShortcode' => 'de', 'attributes' => ['dropdownSelect' => 'Deutschland']]),
		], ['atomic' => false, 'checkRules' => false, 'systemOrder' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(6, $result);

		// Rebuild system order
		$result = $this->behavior->rebuildSystemOrder('attributes.dropdown_select');

		$this->assertNotFalse($result);

		$result = $this->table->find('all')->orderBy(['system_order' => 'ASC'])->toArray();

		$this->assertCount(6, $result);

		$this->assertSame('Fourth', $result[0]->title);
		$this->assertSame('Äthiopien', $result[0]->dropdownSelect);
		$this->assertSame(1, $result[0]->systemOrder);

		$this->assertSame('Second', $result[1]->title);
		$this->assertSame('Australien', $result[1]->dropdownSelect);
		$this->assertSame(2, $result[1]->systemOrder);

		$this->assertSame('Sixth', $result[2]->title);
		$this->assertSame('Deutschland', $result[2]->dropdownSelect);
		$this->assertSame(3, $result[2]->systemOrder);

		$this->assertSame('First', $result[3]->title);
		$this->assertSame('Oman', $result[3]->dropdownSelect);
		$this->assertSame(4, $result[3]->systemOrder);

		$this->assertSame('Third', $result[4]->title);
		$this->assertSame('Österreich', $result[4]->dropdownSelect);
		$this->assertSame(5, $result[4]->systemOrder);

		$this->assertSame('Fifth', $result[5]->title);
		$this->assertSame('Schweiz', $result[5]->dropdownSelect);
		$this->assertSame(6, $result[5]->systemOrder);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function markBeforeFindFired(SelectQuery $query): void {
		$reflection = new ReflectionClass($query);
		$property = $reflection->getProperty('_beforeFindFired');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($query, true);
	}
}
