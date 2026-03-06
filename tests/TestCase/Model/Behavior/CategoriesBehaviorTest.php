<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Behavior\CategoriesBehavior;
use Awyiss\Model\Table;
use Awyiss\Model\Table\ContentsTable;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Collection\CollectionInterface;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Customer\Model\Entity\News;
use Customer\Model\Entity\Newscategory;
use InvalidArgumentException;
use RuntimeException;


/**
 * CategoriesBehavior Test Case
 * Tests are mostly based on the NewsTable
 *
 * @see \Awyiss\Model\Behavior\CategoriesBehavior
 */
class CategoriesBehaviorTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Customer\Model\Table\NewsTable
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\CategoriesBehavior
	 */
	protected CategoriesBehavior $behavior;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
		Awyiss::loadConfiguration('de', 'de');

		$request = new ServerRequest([
			'url' => '/de/backend/news/overview',
			'params' => [
				'lang' => 'xy',
				'controller' => 'News',
				'action' => 'overview',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		Router::setRequest($request);

		// Start with a clean slate
		$this->getTableLocator()->remove('News');
		$this->getTableLocator()->remove('Newscategories');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->getTableLocator()->get('News');

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('Categories');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::initialize()
	 */
	public function testInitialization(): void {
		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);

		$this->assertSame('all', $config['aggregationKey']);
		$this->assertTrue($config['allowAggregation']);
		$this->assertTrue($config['allowUnassigned']);
		$this->assertSame('Newscategories', $config['associationName']);
		$this->assertSame('id', $config['bindingKey']);
		$this->assertTrue($config['buildRules']);
		$this->assertNull($config['categories']);
		$this->assertSame(['id', 'label', null], $config['combinator']);
		$this->assertNull($config['defaultVal']);
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertTrue($config['enabled']);
		$this->assertSame('parentId', $config['field']);
		$this->assertSame('forCurrentLanguage', $config['finder']);
		$this->assertSame('parentId', $config['foreignKey']);
		$this->assertTrue($config['includeParentCategories']);
		$this->assertSame('category', $config['identifier']);
		$this->assertSame([], $config['queryConditions']);
		$this->assertNull($config['selectedCategory']);
		$this->assertTrue($config['threaded']);
		$this->assertSame('unassigned', $config['unassignedKey']);
		$this->assertTrue($config['useDatasource']);

		$this->table->hasAssociation('Newscategories');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$events = $this->behavior->implementedEvents();

		$this->assertSame([
			'Model.buildRules' => 'buildRules',
		], $events);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::implementedMethods()
	 * @throws \ReflectionException
	 */
	public function testImplementedMethods(): void {
		$methods = $this->behavior->implementedMethods();

		$this->assertSame([
			'filterQuery' => 'filterQuery',
			'getCategories' => 'getCategories',
			'getQueryConditions' => 'getQueryConditions',
			'groupResult' => 'groupResult',
			'sortQuery' => 'sortQuery',
		], $methods);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getCategories()
	 */
	public function testGetCategories(): void {
		$result = $this->behavior->getCategories();
		$this->assertSame([
			36 => 'Branchennews',
			34 => 'Fachartikel',
			35 => 'Unternehmensnews',
			59 => 'Jobnews',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getCategories()
	 */
	public function testGetCategoriesRaw(): void {
		$result = $this->behavior->getCategories(true);

		$this->assertInstanceOf(CollectionInterface::class, $result);

		$result = $result->listNested()->toList();

		$this->assertCount(4, $result);

		$this->assertInstanceOf(Newscategory::class, $result[0]);
		$this->assertSame(36, $result[0]->get('id'));

		$this->assertInstanceOf(Newscategory::class, $result[1]);
		$this->assertSame(34, $result[1]->get('id'));

		$this->assertInstanceOf(Newscategory::class, $result[2]);
		$this->assertSame(35, $result[2]->get('id'));

		$this->assertInstanceOf(Newscategory::class, $result[3]);
		$this->assertSame(59, $result[3]->get('id'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getCategories()
	 */
	public function testGetCategoriesWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$result = $this->behavior->getCategories();
		$this->assertSame([], $result);

		$resultRaw = $this->behavior->getCategories(true);
		$this->assertEmpty($resultRaw);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getCategories()
	 */
	public function testGetCategoriesWithoutDatasourceUsesBuildCategoriesOfTable(): void {
		$table = new class extends ContentsTable {
			/**
			 * @inheritDoc
			 */
			protected array $nest = ['alias' => 'Contents'];


			/**
			 * @return array<string>
			 * @noinspection PhpMemberCanBePulledUpInspection
			 */
			public function buildCategories(): array {
				return [
					'tech' => 'Technology',
					'finance' => 'Finance',
					'healthcare' => 'Healthcare',
				];
			}
		};
		$behavior = $table->getBehavior('Categories');
		$behavior->setConfig('enabled', true);
		$behavior->setConfig('useDatasource', false);

		$categories = $behavior->getCategories();

		$this->assertSame([
			'tech' => 'Technology',
			'finance' => 'Finance',
			'healthcare' => 'Healthcare',
		], $categories);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getCategories()
	 */
	public function testGetCategoriesWithoutDatasource(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
			'healthcare' => 'Healthcare',
		]);

		$result = $this->behavior->getCategories();

		$this->assertSame([
			'tech' => 'Technology',
			'finance' => 'Finance',
			'healthcare' => 'Healthcare',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getCategories()
	 */
	public function testGetCategoriesThrowsExceptionWithoutCategories(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('You need to provide categories or a `buildCategories`-method when using `useDatasource = false`');

		$this->behavior->getCategories();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::resetCategories()
	 */
	public function testResetCategories(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
		]);

		$this->behavior->getCategories();
		$this->behavior->resetCategories();

		$result = $this->behavior->getCategories();
		$this->assertIsArray($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::fieldIsAttribute()
	 */
	public function testFieldIsAttribute(): void {
		$this->behavior->setConfig('field', 'date');

		$result = $this->behavior->fieldIsAttribute();
		$this->assertTrue($result);

		$this->behavior->setConfig('field', 'title');

		$result = $this->behavior->fieldIsAttribute();
		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::fieldIsAttribute()
	 */
	public function testFieldIsAttributeWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);
		$this->behavior->setConfig('field', 'date');

		$result = $this->behavior->fieldIsAttribute();
		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::filterQuery()
	 */
	public function testFilterQuery(): void {
		$this->behavior->setConfig('selectedCategory', 36);
		$this->assertSame(36, $this->behavior->getSelectedCategory());

		$query = $this->table->find();
		$result = $this->behavior->filterQuery($query);

		$this->assertSame($query, $result);

		$where = $query->clause('where');
		$this->assertNotEmpty($where);

		$result = $query->all();
		$this->assertCount(5, $result);

		/** @var \Customer\Model\Entity\News $entity */
		foreach ($result as $entity) {
			$this->assertInstanceOf(News::class, $entity);
			$this->assertSame(36, $entity->parentId);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::filterQuery()
	 */
	public function testFilterQueryWithProvidedCategory(): void {
		$this->behavior->setConfig('selectedCategory', 36);
		$this->assertSame(36, $this->behavior->getSelectedCategory());

		$query = $this->table->find();
		$result = $this->behavior->filterQuery($query, 19);

		$this->assertSame($query, $result);

		$where = $query->clause('where');
		$this->assertNotEmpty($where);

		$result = $query->all();
		$this->assertCount(1, $result);

		/** @var \Customer\Model\Entity\News $entity */
		foreach ($result as $entity) {
			$this->assertInstanceOf(News::class, $entity);
			$this->assertSame(19, $entity->parentId);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::filterQuery()
	 */
	public function testFilterQueryWithoutCategoryUsesFirstAvailable(): void {
		$this->assertNull($this->behavior->getSelectedCategory());
		$this->behavior->setConfig('allowAggregation', false);
		$this->behavior->setConfig('allowUnassigned', false);

		$query = $this->table->find();
		$this->behavior->filterQuery($query);
		$result = $query->all();

		$this->assertCount(5, $result);
		/** @var \Customer\Model\Entity\News $entity */
		foreach ($result as $entity) {
			$this->assertInstanceOf(News::class, $entity);
			$this->assertSame(36, $entity->parentId);
		}
		$this->assertStringContainsString('News.parentId = :c6', $query->sql());

		$this->behavior->setConfig('allowUnassigned', true);

		$query = $this->table->find();
		// Will use unassigned key as no category is selected
		$this->behavior->filterQuery($query);
		$result = $query->all();

		$this->assertCount(1, $result);
		/** @var \Customer\Model\Entity\News $entity */
		foreach ($result as $entity) {
			$this->assertInstanceOf(News::class, $entity);
			$this->assertSame(19, $entity->parentId);
		}
		$this->assertStringContainsString('((News.parentId) IS NULL OR News.parentId NOT IN (:c6,:c7,:c8,:c9)', $query->sql());

		$this->behavior->setConfig('allowAggregation', true);

		$query = $this->table->find();
		// Will use aggregation key 'all' as no category is selected
		$this->behavior->filterQuery($query);
		$result = $query->all();

		$this->assertCount(6, $result);
		$this->assertStringNotContainsString('News.parentId = :c6', $query->sql());
		$this->assertStringNotContainsString('((News.parentId) IS NULL OR News.parentId NOT IN', $query->sql());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::filterQuery()
	 */
	public function testFilterQueryWhenDisabled(): void {
		$this->behavior->setConfig('selectedCategory', 35);
		$this->assertSame(35, $this->behavior->getSelectedCategory());

		$this->behavior->setConfig('enabled', false);

		$query = $this->table->find();
		$result = $this->behavior->filterQuery($query);

		$this->assertSame($query, $result);

		$where = $query->clause('where');
		$this->assertEmpty($where);

		$result = $query->all();
		$this->assertCount(6, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::filterQuery()
	 */
	public function testFilterQueryWithoutDatasource(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('field', 'title');
		$this->behavior->setConfig('selectedCategory', 'Dummynews #2');

		$query = $this->table->find();
		$result = $this->behavior->filterQuery($query);

		$this->assertCount(1, $result);

		$entity = $result->first();
		$this->assertInstanceOf(News::class, $entity);
		$this->assertSame('Dummynews #2', $entity->title);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getQueryConditions()
	 */
	public function testGetQueryConditions(): void {
		$this->behavior->setConfig('selectedCategory', 34);
		$this->assertSame(34, $this->behavior->getSelectedCategory());

		$result = $this->behavior->getQueryConditions();
		$this->assertSame(['parentId' => 34], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getQueryConditions()
	 */
	public function testGetQueryConditionsWithoutCategoryUsesFirstAvailable(): void {
		$this->assertNull($this->behavior->getSelectedCategory());
		$this->behavior->setConfig('allowAggregation', false);
		$this->behavior->setConfig('allowUnassigned', false);

		$result = $this->behavior->getQueryConditions();
		$this->assertSame(['parentId' => 36], $result);

		$this->behavior->setConfig('allowUnassigned', true);

		$result = $this->behavior->getQueryConditions('unassigned');
		$this->assertIsArray($result);
		$this->assertSame([
			'OR' => [
				'parentId IS' => null,
				'parentId NOT IN' => [36, 34, 35, 59],
			],
		], $result);

		$this->behavior->setConfig('allowAggregation', true);

		$result = $this->behavior->getQueryConditions('all');
		$this->assertSame([], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getQueryConditions()
	 */
	public function testGetQueryConditionsWithAggregationKey(): void {
		$this->behavior->setConfig('selectedCategory', 'dummy');

		$result = $this->behavior->getQueryConditions('dummy');
		$this->assertSame(['parentId' => 'dummy'], $result);

		$this->behavior->setConfig('aggregationKey', 'dummy');

		$result = $this->behavior->getQueryConditions('dummy');
		$this->assertSame([], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getQueryConditions()
	 */
	public function testGetQueryConditionsWithUnassignedKeyKey(): void {
		$this->behavior->setConfig('selectedCategory', 'dummy');

		$result = $this->behavior->getQueryConditions('dummy');
		$this->assertSame(['parentId' => 'dummy'], $result);

		$this->behavior->setConfig('unassignedKey', 'dummy');

		$result = $this->behavior->getQueryConditions('dummy');
		$this->assertSame([
			'OR' => [
				'parentId IS' => null,
				'parentId NOT IN' => [36, 34, 35, 59],
			],
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getQueryConditions()
	 */
	public function testGetQueryConditionsWithUnassignedKeyKeyAndNoCategories(): void {
		$table = new class extends ContentsTable {
			/**
			 * @inheritDoc
			 */
			protected array $nest = ['alias' => 'Contents'];


			/**
			 * @return array<string>
			 * @noinspection PhpMemberCanBePulledUpInspection
			 */
			public function buildCategories(): array {
				return [];
			}
		};

		$behavior = $table->getBehavior('Categories');

		$behavior->setConfig('allowUnassigned', true);
		$behavior->setConfig('enabled', true);
		$behavior->setConfig('useDatasource', false);

		$result = $behavior->getQueryConditions('unassigned');

		$this->assertSame(['pageId IS' => null], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getQueryConditions()
	 */
	public function testGetQueryConditionsWhenDisabled(): void {
		$this->behavior->setConfig('allowAggregation', false);
		$this->behavior->setConfig('allowUnassigned', false);
		$this->behavior->setConfig('categories', [
			36 => 'Branchennews',
			34 => 'Fachartikel',
			35 => 'Unternehmensnews',
		]);

		$this->behavior->setConfig('enabled', false);

		$result = $this->behavior->getQueryConditions();
		$this->assertSame([], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getSelectedCategory()
	 */
	public function testGetSelectedCategory(): void {
		$this->behavior->setConfig('selectedCategory', 'tech');

		$result = $this->behavior->getSelectedCategory();
		$this->assertSame('tech', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getSelectedCategory()
	 */
	public function testGetSelectedCategoryWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$result = $this->behavior->getSelectedCategory();
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getSelectedCategory()
	 */
	public function testGetSelectedCategoryFromEntity(): void {
		$this->behavior->setConfig('field', 'teaser');

		$entity = $this->table->newDefaultEntity(['title' => 'foo', 'teaser' => 'finance']);
		$result = $this->behavior->getSelectedCategory($entity);

		$this->assertSame('finance', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::groupResult()
	 */
	public function testGroupResult(): void {
		$query = $this->table->find();
		$this->behavior->groupResult($query);
		$result = $query->all()->toList();

		$this->assertCount(2, $result);
		$this->assertIsArray($result[0]);
		$this->assertIsArray($result[1]);

		$group1 = $result[0];

		$this->assertCount(5, $group1);
		/** @var \Customer\Model\Entity\News $entity */
		foreach ($group1 as $entity) {
			$this->assertInstanceOf(News::class, $entity);
			$this->assertSame(36, $entity->parentId);
		}

		$group2 = $result[1];
		$this->assertCount(1, $group2);

		$entity = $group2[0];
		$this->assertInstanceOf(News::class, $entity);
		$this->assertSame(19, $entity->parentId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::groupResult()
	 */
	public function testGroupResultWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$query = $this->table->find();
		$this->behavior->groupResult($query);
		$result = $query->all()->toList();

		$this->assertCount(6, $result);

		foreach ($result as $entity) {
			$this->assertInstanceOf(News::class, $entity);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::groupResult()
	 */
	public function testGroupResultWithCustomColumn(): void {
		$query = $this->table->find();
		$this->behavior->groupResult($query, 'changedBy');
		$result = $query->all()->toList();

		$this->assertCount(1, $result);
		$this->assertIsArray($result[0]);
		$this->assertCount(6, $result[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::groupResult()
	 */
	public function testGroupResultWithEmptyAssociationName(): void {
		$query = $this->table->find();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot filter query without an association in `Awyiss\Model\Behavior\CategoriesBehavior` for table `News`.');

		$this->behavior->groupResult($query, null, '');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::groupResult()
	 */
	public function testGroupResultWithUnknownAssociationName(): void {
		$query = $this->table->find();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The `Dummy` association is not defined on `News`');

		$this->behavior->groupResult($query, null, 'Dummy');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::groupResult()
	 */
	public function testGroupResultNonDatasourceMode(): void {
		$this->behavior->setConfig('identifier', 'parentId');
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			1 => 'Category 1',
			2 => 'Category 2',
		]);

		$query = $this->table->find();
		$this->behavior->groupResult($query);
		$result = $query->all()->toList();

		$this->assertCount(2, $result);

		$group1 = $result[0];

		$this->assertCount(5, $group1);
		/** @var \Customer\Model\Entity\News $entity */
		foreach ($group1 as $entity) {
			$this->assertInstanceOf(News::class, $entity);
			$this->assertSame(36, $entity->parentId);
		}

		$group2 = $result[1];
		$this->assertCount(1, $group2);

		$entity = $group2[0];
		$this->assertInstanceOf(News::class, $entity);
		$this->assertSame(19, $entity->parentId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::sortQuery()
	 */
	public function testSortQuery(): void {
		$query = $this->table->find();
		$result = $this->behavior->sortQuery($query);
		$keys = array_keys($result->all()->indexBy('id')->toArray());

		$this->assertSame([
			38,
			40,
			37,
			41,
			39,
			21,
		], $keys);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::sortQuery()
	 */
	public function testSortQueryWithEmptyCategories(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', []);

		$query = $this->table->find();
		$result = $this->behavior->sortQuery($query);

		$keys = array_keys($result->all()->indexBy('id')->toArray());

		$this->assertSame([
			40,
			21,
			37,
			41,
			39,
			38,
		], $keys);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::sortQuery()
	 */
	public function testSortQueryWhenDisabled(): void {
		$this->behavior->setConfig('enabled', false);

		$query = $this->table->find();
		$result = $this->behavior->sortQuery($query);

		$keys = array_keys($result->all()->indexBy('id')->toArray());

		$this->assertSame([
			40,
			21,
			37,
			41,
			39,
			38,
		], $keys);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getValidSelectionValues()
	 */
	public function testGetValidSelectionValues(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
		]);
		$this->behavior->setConfig('allowAggregation', true);
		$this->behavior->setConfig('allowUnassigned', true);

		$result = $this->behavior->getValidSelectionValues();

		$this->assertSame([
			'all',
			'unassigned',
			'tech',
			'finance',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getValidSelectionValues()
	 */
	public function testGetValidSelectionValuesWithoutAggregation(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
		]);
		$this->behavior->setConfig('allowAggregation', false);

		$result = $this->behavior->getValidSelectionValues();

		$this->assertSame([
			'unassigned',
			'tech',
			'finance',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getValidSelectionValues()
	 */
	public function testGetValidSelectionValuesWithCustomAggregation(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
		]);
		$this->behavior->setConfig('allowAggregation', true);
		$this->behavior->setConfig('aggregationKey', 'custom_aggregation');

		$result = $this->behavior->getValidSelectionValues();

		$this->assertSame([
			'custom_aggregation',
			'unassigned',
			'tech',
			'finance',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getValidSelectionValues()
	 */
	public function testGetValidSelectionValuesWithoutUnassigned(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
		]);
		$this->behavior->setConfig('allowUnassigned', false);

		$result = $this->behavior->getValidSelectionValues();

		$this->assertSame([
			'all',
			'tech',
			'finance',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getValidSelectionValues()
	 */
	public function testGetValidSelectionValuesWithCustomUnassigned(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
		]);
		$this->behavior->setConfig('allowUnassigned', true);
		$this->behavior->setConfig('unassignedKey', 'custom_unassigned');

		$result = $this->behavior->getValidSelectionValues();

		$this->assertSame([
			'all',
			'custom_unassigned',
			'tech',
			'finance',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::verifySelection()
	 */
	public function testVerifySelection(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
			'healthcare' => 'Healthcare',
		]);

		$result = $this->behavior->verifySelection('finance');
		$this->assertSame('finance', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::verifySelection()
	 */
	public function testVerifySelectionWithInvalidCategory(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
		]);

		$result = $this->behavior->verifySelection('finance');
		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::verifySelection()
	 */
	public function testVerifySelectionWithStringConversion(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'techCategory' => 'Technology',
		]);

		$result = $this->behavior->verifySelection('TechCategory');
		$this->assertSame('techCategory', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::verifySelection()
	 */
	public function testVerifySelectionAggregationKey(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('aggregationKey', 'overview');
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
		]);

		$result = $this->behavior->verifySelection('overview');
		$this->assertSame('overview', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::verifySelection()
	 */
	public function testVerifySelectionUnassignedKey(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('unassignedKey', 'noCategory');
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
		]);

		$result = $this->behavior->verifySelection('noCategory');
		$this->assertSame('noCategory', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getParentCategories()
	 */
	public function testGetParentCategories(): void {
		$result = $this->behavior->getParentCategories();
		$this->assertInstanceOf(CollectionInterface::class, $result);
		$result = $result->toList();
		$result = array_column($result, 'title', 'id');

		$this->assertCount(32, $result);

		$this->assertSame([
			1 => 'Startseite',
			2 => 'Über uns',
			3 => 'Unternehmensgeschichte',
			4 => 'Mission und Vision',
			5 => 'Teamvorstellung',
			6 => 'Zertifikate und Auszeichnungen',
			7 => 'Aktuelles',
			8 => 'Dienstleistungen',
			9 => 'Seefracht',
			10 => 'Luftfracht',
			11 => 'Landtransport',
			12 => 'Lagerung und Logistik',
			13 => 'Zollabwicklung',
			14 => 'Flotte',
			15 => 'Übersicht der Schiffe',
			16 => 'Technische Daten',
			17 => 'Sicherheitsstandards',
			18 => 'Umweltfreundlichkeit',
			19 => 'Kundenbereich',
			20 => 'Anmeldung/Registrierung',
			22 => 'Dokumentenverwaltung',
			23 => 'Rechnungsübersicht',
			24 => 'Karriere',
			25 => 'Offene Stellen',
			26 => 'Ausbildungsprogramme',
			27 => 'Mitarbeiterbenefits',
			28 => 'Bewerbungsprozess',
			29 => 'Kontakt',
			30 => 'Impressum',
			31 => 'Datenschutzrichtlinien',
			32 => 'Fehler 404',
			33 => 'Fehler 410',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getParentCategories()
	 */
	public function testGetParentCategoriesIncludingCurrentCategories(): void {
		$result = $this->behavior->getParentCategories(true);
		$this->assertInstanceOf(CollectionInterface::class, $result);
		$result = $result->toList();
		$result = array_column($result, 'title', 'id');

		$this->assertCount(36, $result);

		$this->assertSame([
			1 => 'Startseite',
			2 => 'Über uns',
			3 => 'Unternehmensgeschichte',
			4 => 'Mission und Vision',
			5 => 'Teamvorstellung',
			6 => 'Zertifikate und Auszeichnungen',
			7 => 'Aktuelles',
			8 => 'Dienstleistungen',
			9 => 'Seefracht',
			10 => 'Luftfracht',
			11 => 'Landtransport',
			12 => 'Lagerung und Logistik',
			13 => 'Zollabwicklung',
			14 => 'Flotte',
			15 => 'Übersicht der Schiffe',
			16 => 'Technische Daten',
			17 => 'Sicherheitsstandards',
			18 => 'Umweltfreundlichkeit',
			19 => 'Kundenbereich',
			20 => 'Anmeldung/Registrierung',
			22 => 'Dokumentenverwaltung',
			23 => 'Rechnungsübersicht',
			24 => 'Karriere',
			25 => 'Offene Stellen',
			26 => 'Ausbildungsprogramme',
			27 => 'Mitarbeiterbenefits',
			28 => 'Bewerbungsprozess',
			29 => 'Kontakt',
			30 => 'Impressum',
			31 => 'Datenschutzrichtlinien',
			32 => 'Fehler 404',
			33 => 'Fehler 410',
			36 => 'Branchennews',
			34 => 'Fachartikel',
			35 => 'Unternehmensnews',
			59 => 'Jobnews',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::assignParentCategories()
	 */
	public function testAssignParentCategories(): void {
		$this->behavior->assignParentCategories();
		$categories = $this->behavior->getCategories(true)->toList();

		$this->assertIsArray($categories);
		$this->assertCount(4, $categories);

		/** @var \Customer\Model\Entity\Newscategory $category */
		foreach ($categories as $category) {
			$this->assertTrue(in_array('_parents', $category->getVirtual()));
			/** @noinspection PhpUndefinedFieldInspection */
			$this->assertIsArray($category->_parents);
		}

		$parents = array_column($categories[0]->_parents, 'title', 'id');
		$this->assertSame([
			2 => 'Über uns',
			7 => 'Aktuelles',
		], $parents);

		$parents = array_column($categories[1]->_parents, 'title', 'id');
		$this->assertSame([
			2 => 'Über uns',
			7 => 'Aktuelles',
		], $parents);

		$parents = array_column($categories[2]->_parents, 'title', 'id');
		$this->assertSame([
			2 => 'Über uns',
			7 => 'Aktuelles',
		], $parents);

		$parents = array_column($categories[3]->_parents, 'title', 'id');
		$this->assertSame([
			24 => 'Karriere',
			25 => 'Offene Stellen',
		], $parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::assignParentCategories()
	 */
	public function testAssignParentCategoriesWithMaxLEvel(): void {
		$this->behavior->assignParentCategories(1);
		$categories = $this->behavior->getCategories(true)->toList();

		$this->assertIsArray($categories);
		$this->assertCount(4, $categories);
		$parents = array_column($categories[0]->_parents, 'title', 'id');
		$this->assertSame([
			7 => 'Aktuelles',
		], $parents);

		$parents = array_column($categories[1]->_parents, 'title', 'id');
		$this->assertSame([
			7 => 'Aktuelles',
		], $parents);

		$parents = array_column($categories[2]->_parents, 'title', 'id');
		$this->assertSame([
			7 => 'Aktuelles',
		], $parents);

		$parents = array_column($categories[3]->_parents, 'title', 'id');
		$this->assertSame([
			25 => 'Offene Stellen',
		], $parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::buildRules()
	 */
	public function testBuildRulesValid(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Test News',
			'languageShortcode' => 'de',
			'pageTemplateId' => 3,
			'parentId' => 36,
		]);

		$result = $this->table->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::buildRules()
	 */
	public function testBuildRulesValidWithoutDatasource(): void {
		$this->behavior->setConfig('useDatasource', false);
		$this->behavior->setConfig('categories', [
			'tech' => 'Technology',
			'finance' => 'Finance',
			'healthcare' => 'Healthcare',
		]);
		$this->behavior->setConfig('identifier', 'slug');

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test News',
			'languageShortcode' => 'de',
			'pageTemplateId' => 3,
			'slug' => 'healthcare',
		]);

		$result = $this->table->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::buildRules()
	 */
	public function testBuildRulesMissingCategory(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Test News',
			'languageShortcode' => 'de',
			'pageTemplateId' => 3,
		]);

		$result = $this->table->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('validParentId', $errors['parentId']);
		$this->assertSame('news::error_valid_parent_id', $errors['parentId']['validParentId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::buildRules()
	 */
	public function testBuildRulesInvalidCategory(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Test News',
			'languageShortcode' => 'de',
			'pageTemplateId' => 3,
			'parentId' => 999, // Invalid category ID
		]);

		$result = $this->table->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('validParentId', $errors['parentId']);
		$this->assertSame('news::error_valid_parent_id', $errors['parentId']['validParentId']);
	}
}
