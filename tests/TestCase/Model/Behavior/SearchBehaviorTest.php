<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use Awyiss\Awyiss;
use Awyiss\Model\Behavior\SearchBehavior;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\ORM\TableRegistry;
use Customer\Model\Table\EmployersTable;


/**
 * SearchBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\SearchBehavior
 */
class SearchBehaviorTest extends TestCase {
	/**
	 * @var \Customer\Model\Table\EmployersTable
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\SearchBehavior
	 */
	protected SearchBehavior $behavior;
	/**
	 * @var \Cake\Http\Session
	 */
	protected Session $session;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::loadConfiguration('de', 'de');

		// Set up a mock session
		$this->session = new Session();
		$request = new ServerRequest([
			'url' => '/test',
			'session' => $this->session,
		]);
		Router::setRequest($request);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Employers');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('Search');

		$this->table->deleteAll([]);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		$this->table->deleteAll([]);

		$attributesCarsTable = TableRegistry::getTableLocator()->get('AttributesCars');
		$attributesCarsTable->deleteAll([]);

		$i18nTable = TableRegistry::getTableLocator()->get('I18n');
		$i18nTable->deleteAll([
			'model IN' => ['cars', 'employers'],
		]);

		// Clear session
		$this->session->clear();

		parent::tearDown();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::initialize()
	 */
	public function testInitialization(): void {
		$config = $this->behavior->getConfig();

		$this->assertSame([], $config['blocklistedColumns']);
		$this->assertSame([
			'getPossibleFieldValues' => 'getPossibleFieldValues',
			'getFilterColumns' => 'getFilterColumns',
			'searchFilterQuery' => 'filterQuery',
			'normalizeColumnType' => 'normalizeColumnType',
			'searchIsActive' => 'isActive',
		], $config['implementedMethods']);
		$this->assertSame('_filter.Employers', $config['sessionIdentifier']);
		$this->assertSame([], $config['operators']);
		$this->assertSame([], $config['values']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::initialize()
	 */
	public function testInitializationWithSessionData(): void {
		// Set session data first
		$sessionData = [
			'operators' => ['title' => ComparisonOperator::Equal->value, 'id' => '>'],
			'values' => ['title' => 'test', 'id' => '5'],
		];
		$this->session->write('_filter.TestTable', $sessionData);

		// Create a new table with SearchBehavior
		$table = $this->getMockBuilder(EmployersTable::class)->setConstructorArgs([
			[
				'table' => 'employers',
				'registryAlias' => 'TestTable',
				'alias' => 'TestTable',
			],
		])->onlyMethods([])->getMock();

		$table->addBehavior('Search');
		$behavior = $table->getBehavior('Search');

		$config = $behavior->getConfig();

		$this->assertSame('_filter.TestTable', $config['sessionIdentifier']);
		$this->assertSame(['title' => ComparisonOperator::Equal->value, 'id' => '>'], $config['operators']);
		$this->assertSame(['title' => 'test', 'id' => '5'], $config['values']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::initialize()
	 */
	public function testInitializationWithoutSession(): void {
		$request = new ServerRequest([
			'url' => '/test',
		]);
		Router::setRequest($request);

		$table = new EmployersTable([
			'table' => 'employers',
			'registryAlias' => 'TestTable2',
			'alias' => 'TestTable2',
		]);
		$table->addBehavior('Search');
		$behavior = $table->getBehavior('Search');

		$config = $behavior->getConfig();

		$this->assertSame('_filter.TestTable2', $config['sessionIdentifier']);
		$this->assertSame([], $config['operators']);
		$this->assertSame([], $config['values']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getFilterColumns()
	 */
	public function testGetFilterColumns(): void {
		$columns = $this->table->getFilterColumns();

		// Keys are db field names
		$this->assertSame([
			'id',
			'parentId',
			'languageShortcode',
			'title',
			'systemOrder',
			'active',
			'createdBy',
			'createdOn',
			'changedBy',
			'changedOn',
		], array_keys($columns));

		// Test the contents table
		$table = $this->fetchTable('Contents');
		$columns = $table->getFilterColumns();

		// Fields include attributes
		$keys = [
			'id',
			'contentAreaId',
			'contentTemplateId',
			'parentId',
			'title',
			'titleTag',
			'subtitle',
			'subtitleTag',
			'text',
			'link',
			'columnWidth',
			'columnIndent',
			'columnLast',
			'columnRtl',
			'cssClass',
			'css',
			'duplicateOf',
			'data',
			'formId',
			'surveyId',
			'systemOrder',
			'active',
			'createdBy',
			'createdOn',
			'changedBy',
			'changedOn',
			'attributes__backgroundColor',
			'attributes__teaser',
		];
		$this->assertEquals([], array_diff(array_keys($columns), $keys));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getFilterColumns()
	 */
	public function testGetFilterColumnsWithBlocklistedColumns(): void {
		$columns = $this->table->getFilterColumns(['title', 'systemOrder']);

		$this->assertSame([
			'id',
			'parentId',
			'languageShortcode',
			'active',
			'createdBy',
			'createdOn',
			'changedBy',
			'changedOn',
		], array_keys($columns));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getFilterColumns()
	 */
	public function testGetFilterColumnsWithSelectedOperatorsAndValues(): void {
		// Set session data with operators and values
		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::Equal->value, 'id' => ComparisonOperator::GreaterThan->value]);
		$this->behavior->setConfig('values', ['title' => 'test', 'id' => '5']);

		$columns = $this->table->getFilterColumns();

		$this->assertSame(ComparisonOperator::Equal->value, $columns['title']->operator);
		$this->assertSame('test', $columns['title']->value);
		$this->assertSame('>', $columns['id']->operator);
		$this->assertSame('5', $columns['id']->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getFilterColumns()
	 */
	public function testGetFilterColumnsWithSelectedOperatorsAndValuesForAttributes(): void {
		$table = TableRegistry::getTableLocator()->get('Contents');
		$behavior = $table->getBehavior('Search');

		// Set session data with operators and values
		$behavior->setConfig('operators', ['attributes__teaser' => ComparisonOperator::Equal->value, 'attributes__backgroundColor' => ComparisonOperator::NotEqual->value]);
		$behavior->setConfig('values', ['attributes__teaser' => 'test', 'attributes__backgroundColor' => '#fff']);

		$columns = $table->getFilterColumns();

		$this->assertSame(ComparisonOperator::Equal->value, $columns['attributes__teaser']->operator);
		$this->assertSame('test', $columns['attributes__teaser']->value);
		$this->assertSame(ComparisonOperator::NotEqual->value, $columns['attributes__backgroundColor']->operator);
		$this->assertSame('#fff', $columns['attributes__backgroundColor']->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::isActive()
	 */
	public function testIsActiveWithoutValues(): void {
		$this->assertFalse($this->table->searchIsActive());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::isActive()
	 */
	public function testIsActiveWithValues(): void {
		$this->session->write('_filter.Employers', [
			'values' => ['title' => 'test'],
		]);

		$this->assertTrue($this->table->searchIsActive());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::isActive()
	 */
	public function testIsActiveWithEmptyValues(): void {
		$this->session->write('_filter.Employers', [
			'values' => [],
		]);

		$this->assertFalse($this->table->searchIsActive());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesForLanguageShortcode(): void {
		$values = $this->table->getPossibleFieldValues('languageShortcode');

		// Will return the frontend languages
		$this->assertSame([
			'de' => 'Deutsch',
			'zu' => 'Klingon',
			'es' => 'Esperanto',
		], $values);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesForCreatedBy(): void {
		$values = $this->table->getPossibleFieldValues('createdBy');

		$this->assertSame([
			1 => 'awyiss',
			2 => 'awyiss-undecided-access',
			3 => 'awyiss-no-access',
			4 => 'Users::inactive awyiss-inactive',
		], $values);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesForChangedBy(): void {
		$values = $this->table->getPossibleFieldValues('changedBy');

		$this->assertSame([
			1 => 'awyiss',
			2 => 'awyiss-undecided-access',
			3 => 'awyiss-no-access',
			4 => 'Users::inactive awyiss-inactive',
		], $values);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesForUnknownColumn(): void {
		$values = $this->table->getPossibleFieldValues('unknown_column');

		$this->assertNull($values);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getPossibleFieldValues()
	 */
	public function testGetPossibleFieldValuesForEnum(): void {
		$table = TableRegistry::getTableLocator()->get('SurveyQuestions');

		$values = $table->getPossibleFieldValues('type');

		$this->assertSame([
			'singleChoice' => 'SurveyQuestions::question_type_single_choice',
			'multipleChoice' => 'SurveyQuestions::question_type_multiple_choice',
			'freeText' => 'SurveyQuestions::question_type_free_text',
			'infoText' => 'SurveyQuestions::question_type_info_text',
		], $values);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::normalizeColumnType()
	 */
	public function testNormalizeColumnType(): void {
		$this->assertSame('text', $this->table->normalizeColumnType('char'));
		$this->assertSame('text', $this->table->normalizeColumnType('json'));
		$this->assertSame('text', $this->table->normalizeColumnType('string'));
		$this->assertSame('text', $this->table->normalizeColumnType('text'));
		$this->assertSame('enum', $this->table->normalizeColumnType('enum-Status'));
		$this->assertSame('enum', $this->table->normalizeColumnType('enum-ComparisonOperator'));
		$this->assertSame('integer', $this->table->normalizeColumnType('integer'));
		$this->assertSame('boolean', $this->table->normalizeColumnType('boolean'));
		$this->assertSame('date', $this->table->normalizeColumnType('date'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addEqualsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithEqualsOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Different Entity', 'languageShortcode' => 'es']),
		]);

		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::Equal->value]);
		$this->behavior->setConfig('values', ['title' => 'Test Entity']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Test Entity', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addEqualsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithEqualsOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'es']),
			$this->table->newDefaultEntity(['title' => 'Different Entity', 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::Equal->value]);
		$this->behavior->setConfig('values', ['title' => null]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame(null, $results[0]->title);
		$this->assertSame('es', $results[0]->languageShortcode);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addEqualsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotEqualsOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Different Entity', 'languageShortcode' => 'es']),
		]);

		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::NotEqual->value]);
		$this->behavior->setConfig('values', ['title' => 'Test Entity']);

		$query = $this->table->find()->orderBy(['id' => 'ASC']);
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame(null, $results[0]->title);
		$this->assertSame('Different Entity', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addEqualsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotEqualsOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Test Entity', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'es']),
			$this->table->newDefaultEntity(['title' => 'Different Entity', 'languageShortcode' => 'de']),
		]);

		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::NotEqual->value]);
		$this->behavior->setConfig('values', ['title' => null]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Test Entity', $results[0]->title);
		$this->assertSame('Different Entity', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addGreaterThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithGreaterThanOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::GreaterThan->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '2']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Entity 3', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addGreaterThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithGreaterThanOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'parentId' => null, 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'parentId' => 1, 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'parentId' => 2, 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['parentId' => ComparisonOperator::GreaterThan->value]);
		$this->behavior->setConfig('values', ['parentId' => '1']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Entity 3', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addGreaterThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithGreaterThanOrEqualOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::GreaterThanOrEqual->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '2']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 2', $results[0]->title);
		$this->assertSame('Entity 3', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addGreaterThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithGreaterThanOrEqualOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'parentId' => null, 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'parentId' => 1, 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'parentId' => 2, 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['parentId' => ComparisonOperator::GreaterThanOrEqual->value]);
		$this->behavior->setConfig('values', ['parentId' => '1']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 2', $results[0]->title);
		$this->assertSame(1, $results[0]->parentId);
		$this->assertSame('Entity 3', $results[1]->title);
		$this->assertSame(2, $results[1]->parentId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addGreaterThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLessThanOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::LessThan->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '2']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Entity 1', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addGreaterThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLessThanOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'parentId' => null, 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'parentId' => 1, 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'parentId' => 2, 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['parentId' => ComparisonOperator::LessThan->value]);
		$this->behavior->setConfig('values', ['parentId' => '2']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertNull($results[0]->parentId);
		$this->assertSame('Entity 2', $results[1]->title);
		$this->assertSame(1, $results[1]->parentId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addGreaterThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLessThanOrEqualOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::LessThanOrEqual->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '2']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertSame('Entity 2', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addGreaterThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLessThanOrEqualOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'parentId' => null, 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'parentId' => 1, 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'parentId' => 2, 'systemOrder' => 3, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['parentId' => ComparisonOperator::LessThanOrEqual->value]);
		$this->behavior->setConfig('values', ['parentId' => '1']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertNull($results[0]->parentId);
		$this->assertSame('Entity 2', $results[1]->title);
		$this->assertSame(1, $results[1]->parentId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addBetweenCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithBetweenOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::Between->value]);
		$this->behavior->setConfig('values', ['systemOrder' => ['2', '3']]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);

		$this->assertSame('Entity 2', $results[0]->title);
		$this->assertSame('Entity 3', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addBetweenCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithBetweenOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'parentId' => null, 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'parentId' => 1, 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'parentId' => 2, 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'parentId' => 3, 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['parentId' => ComparisonOperator::Between->value]);
		$this->behavior->setConfig('values', ['parentId' => ['1', '2']]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 2', $results[0]->title);
		$this->assertSame(1, $results[0]->parentId);
		$this->assertSame('Entity 3', $results[1]->title);
		$this->assertSame(2, $results[1]->parentId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addBetweenCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithBetweenOperatorStringValue(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::Between->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '2,3']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);

		$this->assertSame('Entity 2', $results[0]->title);
		$this->assertSame('Entity 3', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addBetweenCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithBetweenOperatorInvalidValue(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::Between->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '2,invalid']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(4, $results);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addBetweenCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotBetweenOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::NotBetween->value]);
		$this->behavior->setConfig('values', ['systemOrder' => ['2', '3']]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);

		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertSame('Entity 4', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addBetweenCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotBetweenOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'parentId' => null, 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'parentId' => 1, 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'parentId' => 2, 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'parentId' => 3, 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['parentId' => ComparisonOperator::NotBetween->value]);
		$this->behavior->setConfig('values', ['parentId' => ['1', '2']]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);

		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertNull($results[0]->parentId);

		$this->assertSame('Entity 4', $results[1]->title);
		$this->assertSame(3, $results[1]->parentId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addBetweenCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotBetweenOperatorStringValue(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::NotBetween->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '2,3']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);

		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertSame('Entity 4', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addBetweenCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotBetweenOperatorInvalidValue(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::NotBetween->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '2,invalid']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(4, $results);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLengthEqualToCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLengthEqualToOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::LengthEqual->value]);
		$this->behavior->setConfig('values', ['title' => 5]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Short', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLengthEqualToCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLengthEqualToOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::LengthEqual->value]);
		$this->behavior->setConfig('values', ['title' => 0]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame(null, $results[0]->title);
		$this->assertSame('', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLengthEqualToCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLengthNotEqualToOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::LengthNotEqual->value]);
		$this->behavior->setConfig('values', ['title' => 5]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame(null, $results[0]->title);
		$this->assertSame('', $results[1]->title);
		$this->assertSame('A Very Long Title Indeed', $results[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLengthEqualToCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLengthNotEqualToOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::LengthNotEqual->value]);
		$this->behavior->setConfig('values', ['title' => 0]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Short', $results[0]->title);
		$this->assertSame('A Very Long Title Indeed', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLongerThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithShorterThanOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::ShorterThan->value]);
		$this->behavior->setConfig('values', ['title' => 10]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame('Short', $results[0]->title);
		$this->assertSame(null, $results[1]->title);
		$this->assertSame('', $results[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLongerThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithShorterThanOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::ShorterThan->value]);
		$this->behavior->setConfig('values', ['title' => 0]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(0, $results);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLongerThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithShorterThanOrEqualOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::ShorterThanOrEqual->value]);
		$this->behavior->setConfig('values', ['title' => 5]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame('Short', $results[0]->title);
		$this->assertSame(null, $results[1]->title);
		$this->assertSame('', $results[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLongerThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithShorterThanOrEqualOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::ShorterThanOrEqual->value]);
		$this->behavior->setConfig('values', ['title' => 0]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame(null, $results[0]->title);
		$this->assertSame('', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLongerThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLongerThanOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::LongerThan->value]);
		$this->behavior->setConfig('values', ['title' => 10]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('A Very Long Title Indeed', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLongerThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLongerThanOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::LongerThan->value]);
		$this->behavior->setConfig('values', ['title' => 0]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Short', $results[0]->title);
		$this->assertSame('A Very Long Title Indeed', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLongerThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLongerThanOrEqualOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::LongerThanOrEqual->value]);
		$this->behavior->setConfig('values', ['title' => 5]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Short', $results[0]->title);
		$this->assertSame('A Very Long Title Indeed', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addLongerThanCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithLongerThanOrEqualOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Short', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'A Very Long Title Indeed', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::LongerThanOrEqual->value]);
		$this->behavior->setConfig('values', ['title' => 0]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(4, $results);
		$this->assertSame('Short', $results[0]->title);
		$this->assertSame(null, $results[1]->title);
		$this->assertSame('', $results[2]->title);
		$this->assertSame('A Very Long Title Indeed', $results[3]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addInCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithInOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::In->value]);
		$this->behavior->setConfig('values', ['systemOrder' => [1, 3]]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertSame('Entity 3', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addInCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithInOperatorStringValue(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::In->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '1,3']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertSame('Entity 3', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addInCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithInOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::In->value]);
		$this->behavior->setConfig('values', ['title' => [null, '']]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame(null, $results[0]->title);
		$this->assertSame('', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addInCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotInOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::NotIn->value]);
		$this->behavior->setConfig('values', ['systemOrder' => [1, 3]]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 2', $results[0]->title);
		$this->assertSame('Entity 4', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addInCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotInOperatorStringValue(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'systemOrder' => 1, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 2', 'systemOrder' => 2, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 3', 'systemOrder' => 3, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'systemOrder' => 4, 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['systemOrder' => ComparisonOperator::NotIn->value]);
		$this->behavior->setConfig('values', ['systemOrder' => '1,3']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 2', $results[0]->title);
		$this->assertSame('Entity 4', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addInCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotInOperatorAndNullValues(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Entity 4', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::NotIn->value]);
		$this->behavior->setConfig('values', ['title' => [null, '']]);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(2, $results);
		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertSame('Entity 4', $results[1]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addContainsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithContainsOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Another Entity', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::Contains->value]);
		$this->behavior->setConfig('values', ['title' => 'Another']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Another Entity', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addContainsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotContainsOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Another Entity', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::NotContains->value]);
		$this->behavior->setConfig('values', ['title' => 'Another']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertSame(null, $results[1]->title);
		$this->assertSame('', $results[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addContainsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithStartsWithOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Another Entity', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::StartsWith->value]);
		$this->behavior->setConfig('values', ['title' => 'Another']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Another Entity', $results[0]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addContainsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotStartsWithOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Another Entity', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::NotStartsWith->value]);
		$this->behavior->setConfig('values', ['title' => 'Another']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertSame(null, $results[1]->title);
		$this->assertSame('', $results[2]->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addContainsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithEndsWithOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Another Entity', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::EndsWith->value]);
		$this->behavior->setConfig('values', ['title' => 'Entity']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(1, $results);
		$this->assertSame('Another Entity', $results[0]->title);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 * @see \Awyiss\Model\Behavior\SearchBehavior::addContainsCondition()
	 * @throws \Exception
	 */
	public function testFilterQueryWithNotEndsWithOperator(): void {
		$result = $this->table->saveMany([
			$this->table->newDefaultEntity(['title' => 'Entity 1', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => null, 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => '', 'languageShortcode' => 'de']),
			$this->table->newDefaultEntity(['title' => 'Another Entity', 'languageShortcode' => 'de']),
		]);
		$this->assertNotFalse($result);

		$this->behavior->setConfig('operators', ['title' => ComparisonOperator::NotEndsWith->value]);
		$this->behavior->setConfig('values', ['title' => 'Entity']);

		$query = $this->table->find();
		$query = $this->behavior->filterQuery($query);

		$results = $query->toArray();

		$this->assertCount(3, $results);
		$this->assertSame('Entity 1', $results[0]->title);
		$this->assertSame(null, $results[1]->title);
		$this->assertSame('', $results[2]->title);
	}
}
