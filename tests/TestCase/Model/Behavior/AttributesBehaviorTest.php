<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Behavior\AttributesBehavior;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Model\Table;
use Awyiss\Model\Table\PagesTable;
use Awyiss\ORM\Association\HasOne;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Customer\Model\Entity\AttributesCar;
use Customer\Model\Enum\PageRole;
use Customer\Model\Table\AttributesNewsTable;


/**
 * AttributesBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\AttributesBehavior
 */
class AttributesBehaviorTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Customer\Model\Table\NewsTable
	 */
	protected Table $table;
	/**
	 * @var AttributesBehavior
	 */
	protected AttributesBehavior $behavior;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::loadConfiguration('de', 'de');
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		Configure::delete('Awyiss.News.Backend.mediaFolders.autoCreate');

		TableRegistry::getTableLocator()->clear();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('News');
		$this->behavior = $this->table->getBehavior('Attributes');
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$this->fetchTable('News')->deleteAll(['id >=' => 2000]);
		$this->fetchTable('AttributesNews')->deleteAll(['page_id >=' => 2000]);

		$this->fetchTable('Cars')->deleteAll([]);
		$this->fetchTable('AttributesCars')->deleteAll([]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::initialize()
	 */
	public function testInitialize(): void {
		$config = $this->behavior->getConfig();

		$this->assertSame([
			'attributeOptionsProviderClass' => 'Awyiss\Attribute\AttributeOptionsProvider',
			'foreignKey' => 'page_id',
			'implementedFinders' => [
				'withMatchingAttributes' => 'findWithMatchingAttributes',
				'futureDate' => 'futureDate',
				'pastDate' => 'pastDate',
			],
			'implementedEvents' => [
				'beforeMarshal',
				'buildRules',
				'beforeFind',
				'beforeCopy',
				'beforeSave',
				'afterSave',
			],
			'implementedMethods' => [
				'extractAttributeFields' => 'extractAttributeFields',
				'getAttributes' => 'getAttributes',
				'getAttributesTable' => 'getAttributesTable',
				'getAttributesTableName' => 'getAttributesTableName',
				'hasAttributes' => 'hasAttributes',
			],
			'isAttributesTable' => false,
			'skip' => false,
			'sourceTable' => 'news',
		], $config);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::initialize()
	 */
	public function testInitializeForNotAttributableClass(): void {
		$this->table = new class (['table' => 'pages', 'alias' => 'DummyNews']) extends PagesTable {
			/**
			 * @inheritDoc
			 */
			public const bool ATTRIBUTABLE = false;
			/**
			 * @inheritDoc
			 */
			protected PageRoleEnumInterface $pageRole = PageRole::News;
		};
		$this->behavior = $this->table->getBehavior('Attributes');

		$config = $this->behavior->getConfig();

		$this->assertSame([
			'attributeOptionsProviderClass' => 'Awyiss\Attribute\AttributeOptionsProvider',
			'foreignKey' => 'page_id',
			'implementedFinders' => [
				'withMatchingAttributes' => 'findWithMatchingAttributes',
			],
			'implementedEvents' => [
				'beforeMarshal',
				'buildRules',
				'beforeFind',
				'beforeCopy',
				'beforeSave',
				'afterSave',
			],
			'implementedMethods' => [
				'extractAttributeFields' => 'extractAttributeFields',
				'getAttributes' => 'getAttributes',
				'getAttributesTable' => 'getAttributesTable',
				'getAttributesTableName' => 'getAttributesTableName',
				'hasAttributes' => 'hasAttributes',
			],
			'isAttributesTable' => false,
			'skip' => false,
			'sourceTable' => 'news',
		], $config);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::initialize()
	 */
	public function testInitializeCreatesAssociationWithAttributesTable(): void {
		$this->assertTrue($this->table->hasAssociation('AttributesNews'));

		$association = $this->table->getAssociation('AttributesNews');

		$this->assertInstanceOf(HasOne::class, $association);

		$this->assertSame('attributes_news', $association->getTarget()->getTable());
		$this->assertTrue($association->getCascadeCallbacks());
		$this->assertTrue($association->getDependent());
		$this->assertSame('page_id', $association->getForeignKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::initialize()
	 */
	public function testInitializeNotCreatesAssociationWithAttributesTableForAttributesTable(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesNews');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->assertFalse($this->table->hasAssociation('AttributesNews'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::initialize()
	 */
	public function testInitializeNotCreatesAssociationWithAttributesTableForForNotAttributableClass(): void {
		$this->table = new class (['table' => 'pages', 'alias' => 'DummyNews']) extends PagesTable {
			/**
			 * @inheritDoc
			 */
			public const bool ATTRIBUTABLE = false;
			/**
			 * @inheritDoc
			 */
			protected PageRoleEnumInterface $pageRole = PageRole::News;
		};
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->assertFalse($this->table->hasAssociation('AttributesNews'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::initialize()
	 */
	public function testInitializeForAttributsTable(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesNews');
		$this->behavior = $this->table->getBehavior('Attributes');

		$config = $this->behavior->getConfig();

		$this->assertSame([
			'attributeOptionsProviderClass' => 'Awyiss\Attribute\AttributeOptionsProvider',
			'foreignKey' => null,
			'implementedFinders' => [
				'withMatchingAttributes' => 'findWithMatchingAttributes',
			],
			'implementedEvents' => [
				'beforeMarshal',
				'buildRules',
				'beforeFind',
				'beforeCopy',
				'beforeSave',
				'afterSave',
			],
			'implementedMethods' => [
				'extractAttributeFields' => 'extractAttributeFields',
				'getAttributes' => 'getAttributes',
				'getAttributesTable' => 'getAttributesTable',
				'getAttributesTableName' => 'getAttributesTableName',
				'hasAttributes' => 'hasAttributes',
			],
			'isAttributesTable' => true,
			'skip' => false,
			'sourceTable' => 'news',
		], $config);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$events = $this->behavior->implementedEvents();

		$this->assertSame([
			'Model.beforeMarshal' => 'beforeMarshal',
			'Model.buildRules' => 'buildRules',
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeCopy' => 'beforeCopy',
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
		], $events);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::implementedEvents()
	 */
	public function testImplementedEventsForAttributesTable(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesNews');
		$this->behavior = $this->table->getBehavior('Attributes');

		$events = $this->behavior->implementedEvents();

		$this->assertSame([
			'Model.beforeMarshal' => 'beforeMarshal',
			'Model.buildRules' => 'buildRules',
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeCopy' => 'beforeCopy',
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
		], $events);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::implementedFinders()
	 * @throws \Exception
	 */
	public function testImplementedFinders(): void {
		$finders = $this->behavior->implementedFinders();

		$this->assertSame([
			'withMatchingAttributes' => 'findWithMatchingAttributes',
			'futureDate' => 'futureDate',
			'pastDate' => 'pastDate',
		], $finders);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::implementedFinders()
	 * @throws \Exception
	 */
	public function testImplementedFindersForAttributesTable(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesNews');
		$this->behavior = $this->table->getBehavior('Attributes');

		$finders = $this->behavior->implementedFinders();

		$this->assertSame([
			'withMatchingAttributes' => 'findWithMatchingAttributes',
		], $finders);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::implementedFinders()
	 * @throws \Exception
	 */
	public function testImplementedFindersForNotAttributableClass(): void {
		$this->table = new class (['table' => 'pages', 'alias' => 'DummyNews']) extends PagesTable {
			/**
			 * @inheritDoc
			 */
			public const bool ATTRIBUTABLE = false;
			/**
			 * @inheritDoc
			 */
			protected PageRoleEnumInterface $pageRole = PageRole::News;
		};
		$this->behavior = $this->table->getBehavior('Attributes');

		$finders = $this->behavior->implementedFinders();

		$this->assertSame([
			'withMatchingAttributes' => 'findWithMatchingAttributes',
		], $finders);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::implementedMethods()
	 * @throws \Exception
	 */
	public function testImplementedMethods(): void {
		$methods = $this->behavior->implementedMethods();

		$this->assertSame([
			'extractAttributeFields' => 'extractAttributeFields',
			'getAttributes' => 'getAttributes',
			'getAttributesTable' => 'getAttributesTable',
			'getAttributesTableName' => 'getAttributesTableName',
			'hasAttributes' => 'hasAttributes',
		], $methods);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::implementedMethods()
	 * @throws \Exception
	 */
	public function testImplementedMethodsForAttributesTable(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesNews');
		$this->behavior = $this->table->getBehavior('Attributes');

		$methods = $this->behavior->implementedMethods();

		$this->assertSame([
			'extractAttributeFields' => 'extractAttributeFields',
			'getAttributes' => 'getAttributes',
			'getAttributesTable' => 'getAttributesTable',
			'getAttributesTableName' => 'getAttributesTableName',
			'hasAttributes' => 'hasAttributes',
		], $methods);
	}



	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::extractAttributeFields()
	 */
	public function testExtractAttributeFields(): void {
		$fields = ['attributes.name', 'attributes.age', 'other_field'];
		$result = $this->behavior->extractAttributeFields($fields);

		$this->assertSame(['name', 'age'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::extractAttributeFields()
	 */
	public function testExtractAttributeFieldsWithBaseFields(): void {
		$fields = ['attributes.name', 'attributes.age', 'other_field', 'Dummytable.other_field2'];
		$result = $this->behavior->extractAttributeFields($fields, true);

		$this->assertSame(['name', 'age', 'other_field'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::findWithMatchingAttributes()
	 * @throws \Exception
	 */
	public function testFindWithMatchingAttributes(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car1 = $this->table->newDefaultEntity(['title' => 'Car 1', 'languageShortcode' => 'de', 'dropdownSelect' => 'main']);
		$car2 = $this->table->newDefaultEntity(['title' => 'Car 2', 'languageShortcode' => 'de', 'dropdownSelect' => 'dark']);
		$car3 = $this->table->newDefaultEntity(['title' => 'Car 3', 'languageShortcode' => 'de', 'dropdownSelect' => 'main']);

		$result = $this->table->saveMany([$car1, $car2, $car3], ['audit' => ['skip' => true]]);
		$this->assertNotFalse($result);

		$query = $this->table->find('withMatchingAttributes', $car1, ['dropdownSelect']);
		$results = $query->all()->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Car 1', $results[0]->title);
		$this->assertSame('Car 3', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::findWithMatchingAttributes()
	 * @throws \Exception
	 */
	public function testFindWithMatchingAttributesWithNullValues(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car1 = $this->table->newDefaultEntity(['title' => 'Car 1', 'languageShortcode' => 'de', 'dropdownSelect' => 'main', 'freeText' => null]);
		$car2 = $this->table->newDefaultEntity(['title' => 'Car 2', 'languageShortcode' => 'de', 'dropdownSelect' => 'main', 'freeText' => 'Some text']);
		$car3 = $this->table->newDefaultEntity(['title' => 'Car 3', 'languageShortcode' => 'de', 'dropdownSelect' => 'main', 'freeText' => null]);

		$result = $this->table->saveMany([$car1, $car2, $car3], ['audit' => ['skip' => true]]);
		$this->assertNotFalse($result);

		$query = $this->table->find('withMatchingAttributes', $car1, ['freeText']);
		$results = $query->all()->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Car 1', $results[0]->title);
		$this->assertSame('Car 3', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::getAttributesTable()
	 */
	public function testGetAttributesTable(): void {
		$result = $this->behavior->getAttributesTable();
		$this->assertInstanceOf(AttributesNewsTable::class, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::getAttributesTable()
	 */
	public function testGetAttributesTableForAttributesTable(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesNews');
		$this->behavior = $this->table->getBehavior('Attributes');

		$result = $this->behavior->getAttributesTable();
		$this->assertInstanceOf(AttributesNewsTable::class, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::getAttributesTableName()
	 */
	public function testGetAttributesTableName(): void {
		$result = $this->behavior->getAttributesTableName();
		$this->assertSame('attributes_news', $result);

		$resultCamelized = $this->behavior->getAttributesTableName(true);
		$this->assertSame('AttributesNews', $resultCamelized);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::getAttributesTableName()
	 */
	public function testGetAttributesTableNameForAttributesTable(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesNews');
		$this->behavior = $this->table->getBehavior('Attributes');

		$result = $this->behavior->getAttributesTableName();
		$this->assertSame('attributes_news', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::hasAttributes()
	 */
	public function testHasAttributes(): void {
		$this->assertTrue($this->behavior->hasAttributes());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::hasAttributes()
	 */
	public function testHasAttributesForTableWithoutAttributes(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Employers');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->assertFalse($this->behavior->hasAttributes());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::hasAttributes()
	 */
	public function testHasAttributesForAttributesTable(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesNews');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->assertFalse($this->behavior->hasAttributes());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::hasAttributes()
	 */
	public function testHasAttributesForAttributesTableNotAttributableClass(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = new class (['table' => 'pages', 'alias' => 'DummyNews']) extends PagesTable {
			/**
			 * @inheritDoc
			 */
			public const bool ATTRIBUTABLE = false;
			/**
			 * @inheritDoc
			 */
			protected PageRoleEnumInterface $pageRole = PageRole::News;
		};
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->assertFalse($this->behavior->hasAttributes());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::getAttributes()
	 */
	public function testGetAttributes(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$result = $this->behavior->getAttributes();

		$this->assertCount(5, $result);

		foreach ($result as $attribute) {
			$this->assertInstanceOf(Attribute::class, $attribute);
		}

		$this->assertSame([
			'input_list',
			'input_key_value_list',
			'free_text',
			'dropdown_select',
			'dummy_pw',
		], array_column($result, 'identifier'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::getAttributes()
	 */
	public function testGetAttributesForAttributesTable(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesCars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$result = $this->behavior->getAttributes();

		$this->assertCount(5, $result);

		foreach ($result as $attribute) {
			$this->assertInstanceOf(Attribute::class, $attribute);
		}

		$this->assertSame([
			'input_list',
			'input_key_value_list',
			'free_text',
			'dropdown_select',
			'dummy_pw',
		], array_column($result, 'identifier'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeMarshal()
	 */
	public function testBeforeMarshalRemovesEmptyValuesFromInputList(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$entity = $this->table->newDefaultEntity();

		$this->table->patchEntity($entity, [
			'attributes' => [
				'inputList' => [
					'value1',
					'',
					'value2',
					'',
					'value3',
					null,
					'0',
					'value4',
					0,
				],
			],
		], ['associated' => ['AttributesCars']]);

		$result = json_encode($entity->get('inputList'));
		$this->assertSame('["value1","value2","value3","0","value4",0]', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeMarshal()
	 */
	public function testBeforeMarshalRemovesEmptyElementsFromInputKeyValueList(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$entity = $this->table->newDefaultEntity();

		$this->table->patchEntity($entity, [
			'attributes' => [
				'inputKeyValueList' => [
					['key' => 'key1', 'value' => 'value1'],
					['key' => '', 'value' => 'value2'],
					['key' => '', 'value' => ''],
					['key' => 'key3', 'value' => ''],
					['key' => null, 'value' => 'value4'],
					['key' => 'key5', 'value' => null],
					['key' => null, 'value' => null],
					['key' => '0', 'value' => 'value6'],
					['key' => 0, 'value' => 'value7'],
				],
			],
		], ['associated' => ['AttributesCars']]);

		$result = json_encode($entity->get('inputKeyValueList'));
		$this->assertSame('{"key1":"value1","":"value4","key3":"","key5":null,"0":"value7"}', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::buildRules()
	 */
	public function testBuildRulesRequiresRequiredFieldsToNotBeEmpty(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesNews');
		$this->behavior = $this->table->getBehavior('Attributes');

		/** @var \Customer\Model\Entity\AttributesNews $entity */
		$entity = $this->table->newDefaultEntity();

		$result = $this->table->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('date', $errors);
		$this->assertArrayHasKey('validValueDate', $errors['date']);
		$this->assertSame('news::error_valid_value', $errors['date']['validValueDate']);

		$entity->date = new DateTime();

		$result = $this->table->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('date', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::buildRules()
	 */
	public function testBuildRulesRequiresValidValue(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesCars');
		$this->behavior = $this->table->getBehavior('Attributes');

		/** @var \Customer\Model\Entity\AttributesCar $entity */
		$entity = $this->table->newDefaultEntity();
		$entity->dropdownSelect = 'foobar';

		$result = $this->table->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('dropdownSelect', $errors);
		$this->assertArrayHasKey('validValueDropdownSelect', $errors['dropdownSelect']);
		$this->assertSame('cars::error_valid_value', $errors['dropdownSelect']['validValueDropdownSelect']);

		$entity->dropdownSelect = 'main';

		$result = $this->table->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('dropdownSelect', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeFind()
	 * @throws \Exception
	 */
	public function testBeforeFindSetsAttributeEntity(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car1 = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);
		$car2 = $this->table->newDefaultEntity([
			'title' => 'Car 2',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'dark',
		]);
		$car3 = $this->table->newDefaultEntity([
			'title' => 'Car 3',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);

		$result = $this->table->saveMany([$car1, $car2, $car3], [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$query = $this->table->find();
		$result = $query->all();

		$this->assertCount(3, $result);
		foreach ($result as $key => $entity) {
			$this->assertTrue($entity->has('attributes'));
			$this->assertInstanceOf(AttributesCar::class, $entity->get('attributes'));

			$this->assertFalse($entity->has('_translations'));
			$this->assertFalse($entity->get('attributes')->has('_translations'));

			$value = $key % 2 === 0 ? 'main' : 'dark';

			$this->assertSame($value, $entity->dropdownSelect);
			$this->assertSame($value, $entity->get('attributes')->dropdownSelect);

			$this->assertTrue(method_exists($entity->get('attributes'), 'getEntity'));
			$this->assertSame($entity, $entity->get('attributes')->getEntity());
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeFind()
	 * @throws \Exception
	 */
	public function testBeforeFindNotSetsAttributeEntityWhenSkipped(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car1 = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);
		$car2 = $this->table->newDefaultEntity([
			'title' => 'Car 2',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'dark',
		]);
		$car3 = $this->table->newDefaultEntity([
			'title' => 'Car 3',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);

		$result = $this->table->saveMany([$car1, $car2, $car3], [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$query = $this->table->find('all', attributes: ['skip' => true]);
		$result = $query->all();

		$this->assertCount(3, $result);
		foreach ($result as $entity) {
			$this->assertFalse($entity->has('attributes'));
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeFind()
	 * @throws \Exception
	 */
	public function testBeforeFindNotSetsAttributeEntityOnAttribute(): void {
		$table = $this->fetchTable('AttributesCars');
		$table->deleteAll([]);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car1 = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);
		$car2 = $this->table->newDefaultEntity([
			'title' => 'Car 2',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);
		$car3 = $this->table->newDefaultEntity([
			'title' => 'Car 3',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);

		$result = $this->table->saveMany([$car1, $car2, $car3], [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$table = $this->fetchTable('AttributesCars');
		$this->behavior = $table->getBehavior('Attributes');

		$query = $table->find();
		$result = $query->all();

		$this->assertCount(3, $result);
		foreach ($result as $entity) {
			$this->assertFalse($entity->has('attributes'));
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeFind()
	 * @throws \Exception
	 */
	public function testBeforeFindLoadsI18nWhenContainI18n(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car1 = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);
		$car2 = $this->table->newDefaultEntity([
			'title' => 'Car 2',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'dark',
		]);
		$car3 = $this->table->newDefaultEntity([
			'title' => 'Car 3',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);

		$result = $this->table->saveMany([$car1, $car2, $car3], [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$query = $this->table->find('translations');
		$result = $query->all();

		$this->assertCount(3, $result);

		foreach ($result as $entity) {
			$this->assertTrue($entity->has('attributes'));
			$this->assertInstanceOf(AttributesCar::class, $entity->get('attributes'));

			$this->assertTrue($entity->has('_translations'));
			$this->assertTrue($entity->get('attributes')->has('_translations'));
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeCopy()
	 */
	public function testBeforeCopyMarksAttributeEntityNew(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);

		$result = $this->table->save($car, [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$this->assertNotEmpty($car->get('attributes')->get('id'));
		$this->assertFalse($car->get('attributes')->isNew());

		$options = new ArrayObject();
		$event = new Event('Model.beforeCopy', $this->table, ['entity' => $car, 'options' => $options]);

		$this->behavior->beforeCopy($event, $car, $options);

		$this->assertEmpty($car->get('attributes')->get('id'));
		$this->assertTrue($car->get('attributes')->isNew());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeSave()
	 */
	public function testBeforeSaveMarksAttributesPropertyDirtyWhenAttributesDirty(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
		]);

		$result = $this->table->save($car, [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$this->assertNotEmpty($car->get('attributes')->get('id'));
		$this->assertFalse($car->get('attributes')->isNew());
		$this->assertFalse($car->isDirty('attributes'));
		$this->assertFalse($car->get('attributes')->isDirty());

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$car->attributes->dropdownSelect = 'dark';

		$this->assertFalse($car->isDirty('attributes'));
		$this->assertTrue($car->get('attributes')->isDirty());

		$options = new ArrayObject();
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $car, 'options' => $options]);

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$this->behavior->beforeSave($event, $car, $options);

		$this->assertTrue($car->isDirty('attributes'));
		$this->assertTrue($car->get('attributes')->isDirty());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeSave()
	 */
	public function testBeforeSaveMarksPasswordFieldsAsNotEmptyWhenEmpty(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
			'dummyPw' => 'dummy',
		]);

		$result = $this->table->save($car, [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesCars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$car->get('attributes')->dummyPw = '';

		$options = new ArrayObject();
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $car->get('attributes'), 'options' => $options]);

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$this->behavior->beforeSave($event, $car->get('attributes'), $options);

		$this->assertFalse($car->get('attributes')->isDirty('dummyPw'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeSave()
	 */
	public function testBeforeSaveHashesPasswordFields(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$this->table->deleteAll([]);

		$car = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
			'dummyPw' => 'dummy',
		]);

		$result = $this->table->save($car, [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('AttributesCars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$car->get('attributes')->dummyPw = 'updated_password';

		$options = new ArrayObject();
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $car->get('attributes'), 'options' => $options]);

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$this->behavior->beforeSave($event, $car->get('attributes'), $options);

		$this->assertTrue($car->get('attributes')->isDirty('dummyPw'));

		$this->assertStringStartsWith('$2y$12$', $car->get('attributes')->dummyPw);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeSave()
	 */
	public function testAfterSaveDeletesEntityRowWhenAttributeSetToFalse(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$attributesTable = $this->fetchTable('AttributesCars');

		$this->table->deleteAll([]);
		$attributesTable->deleteAll([]);

		$car = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
			'dummyPw' => 'dummy',
		]);

		$result = $this->table->save($car, [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$query = $attributesTable->find()->where(['id' => $car->get('id')]);
		$result = $query->all();

		$this->assertCount(1, $result);

		$car->attributes = false;

		$options = new ArrayObject();
		$event = new Event('Model.afterSave', $this->table, ['entity' => $car, 'options' => $options]);

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$this->behavior->afterSave($event, $car, $options);

		$query = $attributesTable->find()->where(['id' => $car->get('id')]);
		$result = $query->all();

		$this->assertCount(0, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::beforeSave()
	 */
	public function testAfterSaveNotDeletesEntityRowWhenAttributeSet(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Cars');
		$this->behavior = $this->table->getBehavior('Attributes');

		$attributesTable = $this->fetchTable('AttributesCars');

		$this->table->deleteAll([]);
		$attributesTable->deleteAll([]);

		$car = $this->table->newDefaultEntity([
			'title' => 'Car 1',
			'languageShortcode' => 'de',
			'dropdownSelect' => 'main',
			'dummyPw' => 'dummy',
		]);

		$result = $this->table->save($car, [
			'audit' => ['skip' => true],
		]);
		$this->assertNotFalse($result);

		$query = $attributesTable->find()->where(['id' => $car->get('id')]);
		$result = $query->all()->toList();

		$this->assertCount(1, $result);

		$attributesId = $result[0]->get('id');

		$options = new ArrayObject();
		$event = new Event('Model.afterSave', $this->table, ['entity' => $car, 'options' => $options]);

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$this->behavior->afterSave($event, $car, $options);

		$query = $attributesTable->find()->where(['id' => $car->get('id')]);
		$result = $query->all()->toList();

		$this->assertCount(1, $result);

		$this->assertSame($attributesId, $result[0]->get('id'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::_dynamicFinder()
	 * @throws \Exception
	 */
	public function testDynamicPastFinder(): void {
		$news1 = $this->table->newDefaultEntity([
			'title' => 'News 1',
			'slug' => 'news_1',
			'pageTemplateId' => 2,
			'parentId' => 34,
			'languageShortcode' => 'de',
			'date' => new DateTime('-1 year'),
		]);
		$news1->id = 2001;

		$news2 = $this->table->newDefaultEntity([
			'title' => 'News 2',
			'slug' => 'news_2',
			'pageTemplateId' => 2,
			'parentId' => 34,
			'languageShortcode' => 'de',
			'date' => new DateTime('+2 days'),
		]);
		$news2->id = 2002;

		$news3 = $this->table->newDefaultEntity([
			'title' => 'News 3',
			'slug' => 'news_3',
			'pageTemplateId' => 2,
			'parentId' => 34,
			'languageShortcode' => 'de',
			'date' => new DateTime('+1 year'),
		]);
		$news3->id = 2003;

		$news4 = $this->table->newDefaultEntity([
			'title' => 'News 4',
			'slug' => 'news_4',
			'pageTemplateId' => 2,
			'parentId' => 34,
			'languageShortcode' => 'de',
			'date' => new DateTime('-2 years'),
		]);
		$news4->id = 2004;

		$result = $this->table->saveMany([$news1, $news2, $news3, $news4], [
			'audit' => ['skip' => true],
		]);

		$this->assertNotFalse($result);

		$query = $this->table->find('pastDate', new DateTime())->where(['id >' => 2000])->orderByAsc('News.id');
		$results = $query->all()->toArray();

		$this->assertCount(2, $results);

		$this->assertSame(2001, $results[0]->id);
		$this->assertSame(2004, $results[1]->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AttributesBehavior::_dynamicFinder()
	 * @throws \Exception
	 */
	public function testDynamicFutureFinder(): void {
		$news1 = $this->table->newDefaultEntity([
			'title' => 'News 1',
			'slug' => 'news_1',
			'pageTemplateId' => 2,
			'parentId' => 34,
			'languageShortcode' => 'de',
			'date' => new DateTime('-1 year'),
		]);
		$news1->id = 2001;

		$news2 = $this->table->newDefaultEntity([
			'title' => 'News 2',
			'slug' => 'news_2',
			'pageTemplateId' => 2,
			'parentId' => 34,
			'languageShortcode' => 'de',
			'date' => new DateTime('+2 days'),
		]);
		$news2->id = 2002;

		$news3 = $this->table->newDefaultEntity([
			'title' => 'News 3',
			'slug' => 'news_3',
			'pageTemplateId' => 2,
			'parentId' => 34,
			'languageShortcode' => 'de',
			'date' => new DateTime('+1 year'),
		]);
		$news3->id = 2003;

		$news4 = $this->table->newDefaultEntity([
			'title' => 'News 4',
			'slug' => 'news_4',
			'pageTemplateId' => 2,
			'parentId' => 34,
			'languageShortcode' => 'de',
			'date' => new DateTime('-2 years'),
		]);
		$news4->id = 2004;

		$result = $this->table->saveMany([$news1, $news2, $news3, $news4], [
			'audit' => ['skip' => true],
		]);

		$this->assertNotFalse($result);

		$query = $this->table->find('futureDate', new DateTime())->where(['id >' => 2000])->orderByAsc('News.id');
		$results = $query->all()->toArray();

		$this->assertCount(2, $results);

		$this->assertSame(2002, $results[0]->id);
		$this->assertSame(2003, $results[1]->id);
	}
}
