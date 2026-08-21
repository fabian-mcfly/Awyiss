<?php

/**
 * @noinspection PhpComplexClassInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Model;


use Awyiss\Awyiss;
use Awyiss\Event\EventManager;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Behavior\AttributesBehavior;
use Awyiss\Model\Behavior\TranslateBehavior;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Model\Table;
use Awyiss\Model\Table\FormsTable;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Model\Table\UsersTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\Marshaller;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\HtmlCleaner;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\AssociationCollection;
use Cake\ORM\Behavior as CakeBehavior;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Query\UnhydratedSelectQuery;
use Closure;
use Customer\Model\Entity\DummyUser;
use Customer\Model\Enum\PageRole;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionClass;
use RuntimeException;


/**
 * General tests for the Table class
 *
 * @see \Awyiss\Model\Table
 */
class TableTest extends TestCase {
	/**
	 * @noinspection HtmlDeprecatedAttribute
	 */
	protected string $exampleHtml = <<<'HTML'
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp; &nbsp; &nbsp;&nbsp;    </p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>After starting empty p-tags</p>
<p><br>Starting &lt;br&gt;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>After two empty p-tags</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Before ending empty p-tags</p>
<p>&nbsp;</p>
<p>&nbsp; &nbsp; &nbsp;&nbsp;    </p>
<p>&nbsp;</p>
<p><br> <br>&nbsp;<br><br> &nbsp;Lorem&nbsp;&nbsp;<br> <br> <br>&nbsp;ipsum with spaces before &lt;br&gt;.</p>
<p>&nbsp;</p>
<p>Spaces  &nbsp;  between </p>
<p>&nbsp;</p>
<p> Spaces around &nbsp; &nbsp; </p>
<p>&nbsp;</p>
<p>Spaces after &nbsp;</p>
<p>&nbsp;</p>
<p>Duis autem</p>
<p>&nbsp;</p>
<ul><li></li></ul>
<ul><li>&lt;br&gt; at the end<br></li>
<li><br>&lt;br&gt; at the start</li></ul>
<p>&nbsp;</p>
	<ul>
		<li>Many ending &lt;br&gt;<br><br><br><br><br><br></li>
		<li><br>Another starting &lt;br&gt;</li>
	</ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<ul><li>&nbsp; <br> &nbsp;</li></ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Space after!&nbsp;asdf</p>
<p>Space after?&nbsp;asdf</p>
<p>Space after.&nbsp;asdf</p>
<p>&nbsp;</p>
<p>At vero</p>
<p>&nbsp;<br> <br></p>
<p>&nbsp;<br> <br><span>Spaces before span</span></p>
<p>&nbsp;<br> <br><span><br><span>&nbsp;</span></span></p>
<p>&nbsp;</p>
<p style="text-align:center;" align="right">&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp; &nbsp; &nbsp;&nbsp;    </p>
<p>&nbsp; <br>&nbsp; &nbsp;&nbsp;  <br> &nbsp;<br><br>  </p>
<p>&nbsp;</p>
HTML;


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::__construct()
	 * @see \Awyiss\Model\Table::getEventManager()
	 */
	public function testConstructSetsAwyissEventManager(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => '']);

		$this->assertInstanceOf(EventManager::class, $table->getEventManager());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Table::TABLE
	 */
	public function testInitializeSetsTableFromConstant(): void {
		$table = new class extends Table {
			/** @var string */
			public const string TABLE = 'attributes_contents';
		};

		$this->assertSame('attributes_contents', $table->getTable());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initializeAssociations()
	 */
	public function testInitializeCallsInitializeAssociations(): void {
		$table = $this->getMockBuilder(Table::class)->disableOriginalConstructor()->onlyMethods(['initializeAssociations'])->getMock();

		$table->expects($this->once())->method('initializeAssociations');

		$table->__construct(['alias' => 'TestTable', 'table' => 'attributes_contents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initializeAssociations()
	 */
	public function testUsesAwyissAssociationCollection(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'contents']) extends Table {
		};

		$this->assertInstanceOf(AssociationCollection::class, $table->associations());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 */
	public function testInitializeMergesConfigPropertiesFromConfiguration(): void {
		Configure::write([
			'Awyiss' => [
				'Pages' => [
					'Backend' => [
						'categories' => ['key1' => 'value1', 'key2' => 'value2'],
						'eventTrigger' => ['key3' => 'value3', 'key4' => 'value4'],
						'nest' => ['key5' => 'value5', 'key6' => 'value6'],
						'publicationData' => ['key7' => 'value7', 'key8' => 'value8'],
						'systemOrder' => ['key9' => 'value9', 'key10' => 'value10'],
					],
				],
			],
		]);

		$table = new class ([
			'alias' => 'TestTable',
			'table' => 'pages',
		]) extends Table {
			/**
			 * @return array
			 */
			public function getCategories(bool $returnRaw = false): ResultSetInterface|TreeIterator|array|null {
				return $this->categories;
			}


			/**
			 * @return array
			 */
			public function getEventTrigger(): array {
				return $this->eventTrigger;
			}


			/**
			 * @return array
			 */
			public function getNest(): array {
				return $this->nest;
			}


			/**
			 * @return array
			 */
			public function getPublicationData(): array {
				return $this->publicationData;
			}


			/**
			 * @return array
			 */
			public function getSystemOrder(): array {
				return $this->systemOrder;
			}
		};

		$this->assertSame([
			'key1' => 'value1',
			'key2' => 'value2',
		], $table->getCategories());

		$this->assertSame([
			'key3' => 'value3',
			'key4' => 'value4',
		], $table->getEventTrigger());

		$this->assertSame([
			'key5' => 'value5',
			'key6' => 'value6',
		], $table->getNest());

		$this->assertSame([
			'key7' => 'value7',
			'key8' => 'value8',
		], $table->getPublicationData());

		$this->assertSame([
			'key9' => 'value9',
			'key10' => 'value10',
		], $table->getSystemOrder());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 */
	public function testInitializeMergesConfigPropertiesForPageRoleFromConfiguration(): void {
		Configure::write([
			'Awyiss' => [
				'Pages' => [
					'Backend' => [
						'categories' => ['key1' => 'value1', 'key2' => 'value2'],
						'eventTrigger' => ['key3' => 'value3', 'key4' => 'value4'],
						'nest' => ['key5' => 'value5', 'key6' => 'value6'],
						'publicationData' => ['key7' => 'value7', 'key8' => 'value8'],
						'systemOrder' => ['key9' => 'value9', 'key10' => 'value10'],
					],
				],
				'News' => [
					'Backend' => [
						'categories' => ['key1' => 'newsvalue1', 'key2' => 'newsvalue2'],
						'eventTrigger' => ['key3' => 'newsvalue3', 'key4' => 'newsvalue4'],
						'nest' => ['key5' => 'newsvalue5', 'key6' => 'newsvalue6'],
						'publicationData' => ['key7' => 'newsvalue7', 'key8' => 'newsvalue8'],
						'systemOrder' => ['key9' => 'newsvalue9', 'key10' => 'newsvalue10'],
					],
				],
			],
		]);

		$table = new class ([
			'alias' => 'TestTable',
			'table' => 'pages',
		]) extends Table {
			/**
			 * @var \Awyiss\Model\Enum\PageRoleEnumInterface
			 */
			protected PageRoleEnumInterface $pageRole = PageRole::News;


			/**
			 * @return array
			 */
			public function getCategories(bool $returnRaw = false): ResultSetInterface|TreeIterator|array|null {
				return $this->categories;
			}


			/**
			 * @return array
			 */
			public function getEventTrigger(): array {
				return $this->eventTrigger;
			}


			/**
			 * @return array
			 */
			public function getNest(): array {
				return $this->nest;
			}


			/**
			 * @return array
			 */
			public function getPublicationData(): array {
				return $this->publicationData;
			}


			/**
			 * @return array
			 */
			public function getSystemOrder(): array {
				return $this->systemOrder;
			}
		};

		$this->assertSame([
			'key1' => 'newsvalue1',
			'key2' => 'newsvalue2',
		], $table->getCategories());

		$this->assertSame([
			'key3' => 'newsvalue3',
			'key4' => 'newsvalue4',
		], $table->getEventTrigger());

		$this->assertSame([
			'key5' => 'newsvalue5',
			'key6' => 'newsvalue6',
		], $table->getNest());

		$this->assertSame([
			'key7' => 'newsvalue7',
			'key8' => 'newsvalue8',
		], $table->getPublicationData());

		$this->assertSame([
			'key9' => 'newsvalue9',
			'key10' => 'newsvalue10',
		], $table->getSystemOrder());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initializeSchema()
	 */
	public function testInitializeCallsInitializeSchema(): void {
		$table = $this->getMockBuilder(Table::class)->disableOriginalConstructor()->onlyMethods(['initializeSchema'])->getMock();

		$table->expects($this->once())->method('initializeSchema');

		$table->__construct(['alias' => 'TestTable', 'table' => 'attributes_contents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\SearchBehavior
	 */
	public function testInitializeAddsSearchBehaviorWithCorrectProperties(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $search = [
				'enabled' => true,
				'fields' => ['title', 'content'],
			];
		};

		$this->assertTrue($table->hasBehavior('Search'));
		$behavior = $table->getBehavior('Search');
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertSame(['title', 'content'], $behavior->getConfig('fields'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\AuditBehavior
	 */
	public function testInitializeAddsAuditBehaviorForNonAttributesTables(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $audit = ['enabled' => true, 'key' => 'value'];
		};

		$this->assertTrue($table->hasBehavior('Audit'));
		$behavior = $table->getBehavior('Audit');
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertSame(999999, $behavior->getConfig('priority'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 */
	public function testInitializeDoesNotAddAuditBehaviorForAttributesTables(): void {
		$table = new class (['alias' => 'AttributesTest', 'table' => 'attributes_contents']) extends Table {
		};

		$this->assertFalse($table->hasBehavior('Audit'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior
	 * @throws \Exception
	 */
	public function testInitializeAddsSoftDeleteBehaviorWhenDeletedColumnExists(): void {
		$schema = new TableSchema('pages');
		$schema->addColumn('id', ['type' => 'integer'])->addColumn('deleted', ['type' => 'boolean']);

		$table = $this->getStubBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['getSchema'])->getStub();

		$table->method('getSchema')->willReturn($schema);

		// Set custom config for SoftDelete behavior
		$reflection = new ReflectionClass(Table::class);
		$property = $reflection->getProperty('softDelete');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($table, [
			'key' => 'value',
		]);

		$table->initialize([]);

		$this->assertTrue($table->hasBehavior('SoftDelete'));
		$behavior = $table->getBehavior('SoftDelete');
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @throws \Exception
	 */
	public function testInitializeDoesNotAddSoftDeleteBehaviorWhenDeletedColumnMissing(): void {
		$schema = new TableSchema('pages');
		$schema->addColumn('id', ['type' => 'integer']);

		$table = $this->getStubBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['getSchema'])->getStub();

		$table->method('getSchema')->willReturn($schema);

		$table->initialize([]);

		$this->assertFalse($table->hasBehavior('SoftDelete'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior
	 * @throws \Exception
	 */
	public function testInitializeAddsSystemOrderBehaviorWhenSystemOrderColumnExists(): void {
		$schema = new TableSchema('pages');
		$schema->addColumn('id', ['type' => 'integer'])->addColumn('systemOrder', ['type' => 'integer']);

		$table = $this->getStubBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['getSchema'])->getStub();

		$table->method('getSchema')->willReturn($schema);

		// Set custom config for SystemOrder behavior
		$reflection = new ReflectionClass(Table::class);
		$property = $reflection->getProperty('systemOrder');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue($table, [
			'key' => 'value',
		]);

		$table->initialize([]);

		$this->assertTrue($table->hasBehavior('SystemOrder'));
		$behavior = $table->getBehavior('SystemOrder');
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @throws \Exception
	 */
	public function testInitializeDoesNotAddSystemOrderBehaviorWhenSystemOrderColumnMissing(): void {
		$schema = new TableSchema('pages');
		$schema->addColumn('id', ['type' => 'integer']);

		$table = $this->getStubBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['getSchema'])->getStub();

		$table->method('getSchema')->willReturn($schema);

		$table->initialize([]);

		$this->assertFalse($table->hasBehavior('SystemOrder'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior
	 * @see \Awyiss\Model\Behavior\MediaElementAssignmentBehavior
	 */
	public function testInitializeAddsMediaBehaviorsForNonMediaTables(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		$this->assertTrue($table->hasBehavior('MediaAssignment'));
		$this->assertTrue($table->hasBehavior('MediaElementAssignment'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 */
	public function testInitializeDoesNotAddMediaBehaviorsForMediaTables(): void {
		$table = new class (['alias' => 'MediaTest', 'table' => 'media']) extends Table {
		};

		$this->assertFalse($table->hasBehavior('MediaAssignment'));
		$this->assertFalse($table->hasBehavior('MediaElementAssignment'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 */
	public function testInitializeDoesNotAddMediaBehaviorsForPublicationDataTable(): void {
		$table = new class (['alias' => 'PublicationData', 'table' => 'publication_data']) extends Table {
		};

		$this->assertFalse($table->hasBehavior('MediaAssignment'));
		$this->assertFalse($table->hasBehavior('MediaElementAssignment'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\NestBehav9ior
	 */
	public function testInitializeAddsNestBehaviorWhenNestConfigExists(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $nest = ['enabled' => true, 'key' => 'value'];
		};

		$this->assertTrue($table->hasBehavior('Nest'));
		$behavior = $table->getBehavior('Nest');
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertIsArray($behavior->getConfig('parent'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 */
	public function testInitializeDoesNotAddNestBehaviorWhenNestConfigEmpty(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $nest = [];
		};

		$this->assertFalse($table->hasBehavior('Nest'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\PublicationDataBehavior
	 */
	public function testInitializeAddsPublicationDataBehaviorForNonPublicationDataTables(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $publicationData = ['enabled' => true, 'key' => 'value'];
		};

		$this->assertTrue($table->hasBehavior('PublicationData'));
		$behavior = $table->getBehavior('PublicationData');
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 */
	public function testInitializeDoesNotAddPublicationDataBehaviorForPublicationDataTable(): void {
		$table = new class (['alias' => 'PublicationData', 'table' => 'publication_data']) extends Table {
		};

		$this->assertFalse($table->hasBehavior('PublicationData'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\TranslateBehavior
	 */
	public function testInitializeAddsTranslateBehaviorWhenTranslateFieldsExist(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $translate = [
				'enabled' => true,
				'fields' => ['title', 'content'],
				'key' => 'value',
			];
		};

		$this->assertTrue($table->hasBehavior('Translate'));
		$behavior = $table->getBehavior('Translate');
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertSame(['title', 'content'], $behavior->getConfig('fields'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 */
	public function testInitializeDoesNotAddTranslateBehaviorWhenTranslateFieldsEmpty(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $translate = [];
		};

		$this->assertFalse($table->hasBehavior('Translate'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\AutoPrefixBehavior
	 */
	public function testInitializeAlwaysAddsAutoPrefixBehavior(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $autoPrefix = ['enabled' => true, 'key' => 'value'];
		};

		$this->assertTrue($table->hasBehavior('AutoPrefix'));
		$behavior = $table->getBehavior('AutoPrefix');
		$this->assertSame(999999, $behavior->getConfig('priority'));
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\DefaultValuesBehavior
	 */
	public function testInitializeAlwaysAddsDefaultValuesBehavior(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $defaultValues = ['field1' => 'value1', 'field2' => 'value2'];
		};

		$this->assertTrue($table->hasBehavior('DefaultValues'));
		$behavior = $table->getBehavior('DefaultValues');
		$this->assertSame('value1', $behavior->getConfig('field1'));
		$this->assertSame('value2', $behavior->getConfig('field2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\EventTriggerBehavior
	 */
	public function testInitializeAddsEventTriggerBehaviorForNonAttributesTables(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $eventTrigger = ['enabled' => true, 'key' => 'value'];
		};

		$this->assertTrue($table->hasBehavior('EventTrigger'));
		$behavior = $table->getBehavior('EventTrigger');
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @noinspection PhpUnused
	 */
	public function testInitializeDoesNotAddEventTriggerBehaviorForAttributesTables(): void {
		$table = new class (['alias' => 'AttributesTest', 'table' => 'attributes_contents']) extends Table {
		};

		$this->assertFalse($table->hasBehavior('EventTrigger'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Table::addAttributesBehavior()
	 * @see \Awyiss\Model\Behavior\AttributesBehavior
	 */
	public function testAddAttributesBehaviorForAttributesTables(): void {
		$table = new class (['alias' => 'AttributesTest', 'table' => 'attributes_contents']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $attributes = [
				'isAttributesTable' => 'dummy',
				'key' => 'value',
			];
		};

		$this->assertFalse($table->hasBehavior('Translate'));

		$this->assertTrue($table->hasBehavior('Attributes'));
		$behavior = $table->getBehavior('Attributes');
		$this->assertTrue($behavior->getConfig('isAttributesTable'));
		$this->assertSame('contents', $behavior->getConfig('sourceTable'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Table::addAttributesBehavior()
	 * @see \Awyiss\Model\Behavior\AttributesBehavior
	 */
	public function testAddAttributesBehaviorWithSourceTableForNonAttributesTables(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $attributes = [
				'isAttributesTable' => 'dummy',
				'key' => 'value',
			];
		};

		$this->assertTrue($table->hasBehavior('Attributes'));
		$behavior = $table->getBehavior('Attributes');
		$this->assertFalse($behavior->getConfig('isAttributesTable'));
		$this->assertSame('pages', $behavior->getConfig('sourceTable'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Table::addAttributesBehavior()
	 * @see \Awyiss\Model\Behavior\AttributesBehavior
	 */
	public function testAddAttributesBehaviorAddsTranslatableFieldsToTranslateProperty(): void {
		$attributes = [
			new Attribute()->patch([
				'identifier' => 'title',
				'translatable' => true,
			]),
			new Attribute()->patch([
				'identifier' => 'description',
				'translatable' => true,
			]),
			new Attribute()->patch([
				'identifier' => 'nonTranslatable',
				'translatable' => false,
			]),
		];
		$attributesBehavior = $this->getMockBuilder(AttributesBehavior::class)->disableOriginalConstructor()->onlyMethods(['getAttributes'])->getMock();

		$attributesBehavior->expects($this->exactly(2))->method('getAttributes')->willReturn($attributes);

		$table = new class (['alias' => 'AttributesTest', 'table' => 'attributes_contents', 'attributesBehavior' => $attributesBehavior]) extends Table {
			/**
			 * @var \Awyiss\Model\Behavior\AttributesBehavior|mixed
			 */
			private AttributesBehavior $attributesBehavior;


			/**
			 * @inheritDoc
			 */
			public function __construct(array $config = []) {
				$this->attributesBehavior = $config['attributesBehavior'];

				parent::__construct($config);
			}


			/**
			 * @inheritDoc
			 */
			public function getBehavior(string $name): CakeBehavior {
				if ($name === 'Attributes') {
					return $this->attributesBehavior;
				}

				return parent::getBehavior($name);
			}
		};

		$this->assertTrue($table->hasBehavior('Translate'));
		$behavior = $table->getBehavior('Translate');
		$this->assertSame(['title', 'description'], $behavior->getConfig('fields'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior
	 */
	public function testInitializeAddsCategoriesBehaviorForNonAttributesTables(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $categories = ['enabled' => true, 'key' => 'value'];
		};

		$this->assertTrue($table->hasBehavior('Categories'));
		$behavior = $table->getBehavior('Categories');
		$this->assertTrue($behavior->getConfig('enabled'));
		$this->assertSame('value', $behavior->getConfig('key'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initialize()
	 */
	public function testInitializeDoesNotAddCategoriesBehaviorForAttributesTables(): void {
		$table = new class (['alias' => 'AttributesTest', 'table' => 'attributes_contents']) extends Table {
		};

		$this->assertFalse($table->hasBehavior('Categories'));
	}


	/**
	 * @param bool $translateBehaviorExists
	 * @return void
	 * @see \Awyiss\Model\Table::findTranslations()
	 */
	#[TestWith([true])]
	#[TestWith([false])]
	public function testFindTranslationsCallsBehaviorMethodWhenTranslateBehaviorExists(bool $translateBehaviorExists): void {
		/** @var \Cake\ORM\Query\SelectQuery $query */
		$query = $this->getStubBuilder(SelectQuery::class)->disableOriginalConstructor()->getStub();

		$modifiedQuery = $this->getStubBuilder(SelectQuery::class)->disableOriginalConstructor()->getStub();

		$translateBehavior = $this->getMockBuilder(TranslateBehavior::class)->disableOriginalConstructor()->onlyMethods(['findTranslations'])->getMock();

		$translateBehavior->expects($translateBehaviorExists ? $this->once() : $this->never())->method('findTranslations')->with($query)->willReturn($modifiedQuery);

		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['addCategoriesBehavior', 'hasBehavior', 'getBehavior'])->getMock();

		$table->expects($this->once())->method('hasBehavior')->with('Translate')->willReturn($translateBehaviorExists);

		$table->expects($translateBehaviorExists ? $this->once() : $this->never())->method('getBehavior')->with('Translate')->willReturn($translateBehavior);

		$result = $table->findTranslations($query);

		if ($translateBehaviorExists) {
			$this->assertSame($modifiedQuery, $result);
		}
		else {
			$this->assertSame($query, $result);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::find()
	 * @see \Awyiss\Model\Table::findTranslations()
	 */
	public function testFindTranslationsCanBeUsedAsFinder(): void {
		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['findTranslations'])->getMock();

		$table->expects($this->once())->method('findTranslations')->with($this->isInstanceOf(SelectQuery::class));

		$table->find('translations');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::findActive()
	 */
	public function testFindActiveAddsWhereConditionWhenActiveColumnExists(): void {
		$query = $this->getMockBuilder(SelectQuery::class)->disableOriginalConstructor()->onlyMethods(['where'])->getMock();

		$query->expects($this->once())->method('where')->with(['active' => true])->willReturnSelf();

		$schema = new TableSchema('pages');
		$schema->addColumn('id', ['type' => 'integer'])->addColumn('active', ['type' => 'boolean']);

		$table = $this->getStubBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['getSchema'])->getStub();

		$table->method('getSchema')->willReturn($schema);

		$result = $table->findActive($query);

		$this->assertSame($query, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::findActive()
	 */
	public function testFindActiveThrowsExceptionWhenActiveColumnDoesNotExist(): void {
		$query = $this->getStubBuilder(SelectQuery::class)->disableOriginalConstructor()->getStub();

		$schema = new TableSchema('pages');
		$schema->addColumn('id', ['type' => 'integer']);

		$table = $this->getStubBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['getSchema'])->getStub();

		$table->method('getSchema')->willReturn($schema);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot use `findActive` on table `TestTable`');

		$table->findActive($query);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::find()
	 * @see \Awyiss\Model\Table::findActive()
	 */
	public function testFindActiveCanBeUsedAsFinder(): void {
		$schema = new TableSchema('pages');
		$schema->addColumn('id', ['type' => 'integer'])->addColumn('active', ['type' => 'boolean']);

		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods([
			'findActive',
			'getSchema',
		])->getMock();

		$table->method('getSchema')->willReturn($schema);

		$table->expects($this->once())->method('findActive')->with($this->isInstanceOf(SelectQuery::class));

		$table->find('active');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::findForCurrentLanguage()
	 * @throws \Exception
	 */
	public function testFindForCurrentLanguageUsesCurrentLanguageWhenNoParameterProvided(): void {
		$query = $this->getMockBuilder(SelectQuery::class)->disableOriginalConstructor()->onlyMethods(['where'])->getMock();

		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		$request = new ServerRequest([
			'url' => 'es/dummy',
			'params' => [
				'lang' => 'es',
				'slug' => 'dummy',
			],
		]);
		Router::setRequest($request);

		$query->expects($this->once())->method('where')->with([
			'OR' => [
				'languageShortcode' => 'es',
				'languageShortcode IS' => null,
			],
		])->willReturnSelf();

		// We need to mock the static method call, but since we can't mock static methods directly in PHPUnit,
		// we'll use a workaround by testing with explicit language parameter
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$result = $table->findForCurrentLanguage($query);

		$this->assertSame($query, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::findForCurrentLanguage()
	 * @throws \Exception
	 */
	public function testFindForCurrentLanguageWithSpecificLanguageAndIncludeGlobal(): void {
		$query = $this->getMockBuilder(SelectQuery::class)->disableOriginalConstructor()->onlyMethods(['where'])->getMock();

		$query->expects($this->once())->method('where')->with([
			'OR' => [
				'languageShortcode' => 'en',
				'languageShortcode IS' => null,
			],
		])->willReturnSelf();

		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$result = $table->findForCurrentLanguage($query, 'en');

		$this->assertSame($query, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::findForCurrentLanguage()
	 * @throws \Exception
	 */
	public function testFindForCurrentLanguageWithSpecificLanguageAndExcludeGlobal(): void {
		$query = $this->getMockBuilder(SelectQuery::class)->disableOriginalConstructor()->onlyMethods(['where'])->getMock();

		$query->expects($this->once())->method('where')->with(['languageShortcode' => 'fr'])->willReturnSelf();

		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$result = $table->findForCurrentLanguage($query, 'fr', null, false);

		$this->assertSame($query, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::findForCurrentLanguage()
	 * @throws \Exception
	 */
	public function testFindForCurrentLanguageWithFalseLanguageShortcode(): void {
		$query = $this->getMockBuilder(SelectQuery::class)->disableOriginalConstructor()->onlyMethods(['where'])->getMock();

		$query->expects($this->once())->method('where')->with(['languageShortcode IS' => null])->willReturnSelf();

		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$result = $table->findForCurrentLanguage($query, false);

		$this->assertSame($query, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::findForCurrentLanguage()
	 * @throws \Exception
	 */
	public function testFindForCurrentLanguageWithEntityOverridesLanguageShortcode(): void {
		$query = $this->getMockBuilder(SelectQuery::class)->disableOriginalConstructor()->onlyMethods(['where'])->getMock();

		$query->expects($this->once())->method('where')->with([
			'OR' => [
				'languageShortcode' => 'es',
				'languageShortcode IS' => null,
			],
		])->willReturnSelf();

		$entity = new Entity(['languageShortcode' => 'es']);

		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		// The entity's languageShortcode should override the provided parameter
		$result = $table->findForCurrentLanguage($query, 'de', $entity);

		$this->assertSame($query, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::findForCurrentLanguage()
	 * @throws \Exception
	 */
	public function testFindForCurrentLanguageWithEntityAndExcludeGlobal(): void {
		$query = $this->getMockBuilder(SelectQuery::class)->disableOriginalConstructor()->onlyMethods(['where'])->getMock();

		$query->expects($this->once())->method('where')->with(['languageShortcode' => 'it'])->willReturnSelf();

		$entity = new Entity(['languageShortcode' => 'it']);

		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$result = $table->findForCurrentLanguage($query, null, $entity, false);

		$this->assertSame($query, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::find()
	 * @see \Awyiss\Model\Table::findForCurrentLanguage()
	 */
	public function testFindForCurrentLanguageCanBeUsedAsFinder(): void {
		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['findForCurrentLanguage'])->getMock();

		$table->expects($this->once())->method('findForCurrentLanguage')->with(
			$this->isInstanceOf(SelectQuery::class),
			'pt',
			null,
			true
		);

		$table->find('forCurrentLanguage', languageShortcode: 'pt', includeGlobal: true);
	}


	/**
	 * @return void
	 */
	public function testGetEntityClassBuildsEntityClassFromAlias(): void {
		$table = new UsersTable();

		$this->assertSame('\Awyiss\Model\Entity\User', $table->getEntityClass());
	}


	/**
	 * @return void
	 */
	public function testGetEntityClassPrefersCustomerClass(): void {
		$table = new FormsTable([]);

		$this->assertSame('\Customer\Model\Entity\Form', $table->getEntityClass());
	}


	/**
	 * @return void
	 */
	public function testGetEntityClassReturnsSetProperty(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected ?string $_entityClass = DummyUser::class; // phpcs:ignore
		};

		$this->assertSame(DummyUser::class, $table->getEntityClass());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::belongsTo()
	 */
	public function testBelongsToUsesCorrectClassAndSetsOptions(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$association = $table->belongsTo('Users', ['foreignKey' => 'user_id']);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(BelongsTo::class, $association);
		$this->assertSame($table, $association->getSource());
		$this->assertSame('user_id', $association->getForeignKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::belongsToMany()
	 */
	public function testBelongsToManyUsesCorrectClassAndSetsOptions(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$association = $table->belongsToMany('Tags', [
			'through' => 'PagesTags',
			'foreignKey' => 'page_id',
			'targetForeignKey' => 'tag_id',
		]);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(BelongsToMany::class, $association);
		$this->assertSame($table, $association->getSource());
		$this->assertSame('page_id', $association->getForeignKey());
		$this->assertSame('tag_id', $association->getTargetForeignKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::hasOne()
	 */
	public function testHasOneUsesCorrectClassAndSetsOptions(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$association = $table->hasOne('Profile', ['foreignKey' => 'page_id']);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(HasOne::class, $association);
		$this->assertSame($table, $association->getSource());
		$this->assertSame('page_id', $association->getForeignKey());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::hasMany()
	 */
	public function testHasManyUsesCorrectClassAndSetsOptions(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$association = $table->hasMany('Comments', [
			'foreignKey' => 'page_id',
			'dependent' => true,
		]);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(HasMany::class, $association);
		$this->assertSame($table, $association->getSource());
		$this->assertSame('page_id', $association->getForeignKey());
		$this->assertTrue($association->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::exists()
	 */
	public function testExistsPassesOptionsToQuery(): void {
		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['unhydratedFind'])->getMock();

		$query = $this->getMockBuilder(UnhydratedSelectQuery::class)
			->disableOriginalConstructor()
			->onlyMethods(['applyOptions', 'select', 'where', 'limit', 'toArray'])
			->getMock();

		$options = [
			'contain' => ['Users'],
			'conditions' => ['status' => 'published'],
			'order' => ['created' => 'DESC'],
			'group' => ['categoryId'],
		];

		$table->expects($this->once())->method('unhydratedFind')->with('all')->willReturn($query);

		$query->expects($this->once())->method('applyOptions')->with($options)->willReturnSelf();

		$query->expects($this->once())->method('select')->with(['existing' => 1])->willReturnSelf();

		$query->expects($this->once())->method('where')->with(['id' => 1])->willReturnSelf();

		$query->expects($this->once())->method('limit')->with(1)->willReturnSelf();

		$query->expects($this->once())->method('toArray')->willReturn([['existing' => 1]]);

		$result = $table->exists(['id' => 1], $options);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::exists()
	 */
	public function testExistsPassesFinderOptionsToQuery(): void {
		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['unhydratedFind', '_extractFinder'])->getMock();

		$query = $this->getMockBuilder(UnhydratedSelectQuery::class)
			->disableOriginalConstructor()
			->onlyMethods(['applyOptions', 'select', 'where', 'limit', 'toArray'])
			->getMock();

		$options = [
			'finder' => 'active',
			'contain' => ['Categories'],
			'order' => ['title' => 'ASC'],
		];

		$extractedOptions = [
			'contain' => ['Categories'],
			'order' => ['title' => 'ASC'],
		];

		$table->expects($this->once())->method('_extractFinder')->with('active')->willReturn(['active', []]);

		$table->expects($this->once())->method('unhydratedFind')->with('active')->willReturn($query);

		$query->expects($this->once())->method('applyOptions')->with($extractedOptions)->willReturnSelf();

		$query->expects($this->once())->method('select')->with(['existing' => 1])->willReturnSelf();

		$query->expects($this->once())->method('where')->with(['active' => true])->willReturnSelf();

		$query->expects($this->once())->method('limit')->with(1)->willReturnSelf();

		$query->expects($this->once())->method('toArray')->willReturn([]);

		$result = $table->exists(['active' => true], $options);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::exists()
	 */
	public function testExistsReturnsTrueWhenRecordExists(): void {
		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['unhydratedFind'])->getMock();

		$query = $this->getStubBuilder(UnhydratedSelectQuery::class)
			->disableOriginalConstructor()
			->onlyMethods(['applyOptions', 'select', 'where', 'limit', 'toArray'])
			->getStub();

		$table->expects($this->once())->method('unhydratedFind')->with('all')->willReturn($query);

		$query->method('applyOptions')->willReturnSelf();
		$query->method('select')->willReturnSelf();
		$query->method('where')->willReturnSelf();
		$query->method('limit')->willReturnSelf();
		$query->method('toArray')->willReturn([['existing' => 1]]);

		$result = $table->exists(['id' => 1]);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::exists()
	 */
	public function testExistsReturnsFalseWhenNoRecordExists(): void {
		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['unhydratedFind'])->getMock();

		$query = $this->getStubBuilder(UnhydratedSelectQuery::class)
			->disableOriginalConstructor()
			->onlyMethods(['applyOptions', 'select', 'where', 'limit', 'toArray'])
			->getStub();

		$table->expects($this->once())->method('unhydratedFind')->with('all')->willReturn($query);

		$query->method('applyOptions')->willReturnSelf();
		$query->method('select')->willReturnSelf();
		$query->method('where')->willReturnSelf();
		$query->method('limit')->willReturnSelf();
		$query->method('toArray')->willReturn([]);

		$result = $table->exists(['id' => 999]);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::validationDefault()
	 */
	public function testValidationDefaultSetsI18nDomain(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		$validator = $table->getValidator('default');
		$this->assertSame('TestTable', $table->validationDefault($validator)->getI18nDomain());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::validationDefault()
	 */
	public function testValidationDefaultSetsStopOnFailure(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		$validator = $table->getValidator('default');
		$validator = $table->validationDefault($validator);
		$this->assertTrue($validator->__debugInfo()['_stopOnFailure']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		$events = $table->implementedEvents();
		$this->assertCount(2, $events);
		$this->assertArrayHasKey('Model.beforeRules', $events);
		$this->assertArrayHasKey('Model.beforeSave', $events);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::implementedEvents()
	 */
	public function testImplementedEventsWithCustomEvents(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @var array
			 */
			protected array $defaultEvents = [
				'dummy1' => 'customDummy1',
				'dummy2',
			];


			/**
			 * @param \Cake\Event\EventInterface $event
			 * @param array $data
			 * @return void
			 * @noinspection PhpUnused
			 */
			public function customDummy1(EventInterface $event, array $data): void {
			}


			/**
			 * @param \Cake\Event\EventInterface $event
			 * @param array $data
			 * @return void
			 * @noinspection PhpUnused
			 */
			public function dummy2(EventInterface $event, array $data): void {
			}
		};

		$events = $table->implementedEvents();
		$this->assertCount(2, $events);
		$this->assertArrayHasKey('dummy1', $events);
		$this->assertSame('customDummy1', $events['dummy1']);
		$this->assertArrayHasKey('Model.dummy2', $events);
		$this->assertSame('dummy2', $events['Model.dummy2']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::implementedEvents()
	 */
	public function testImplementedEventsWithFirstClassEvents(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			public function __construct(array $config = []) {
				$this->defaultEvents = [
					'dummy1' => $this->dummyFunction(...),
				];

				parent::__construct($config);
			}


			/**
			 * @param \Cake\Event\EventInterface $event
			 * @param array $data
			 * @return void
			 */
			public function dummyFunction(EventInterface $event, array $data): void {
			}
		};

		$events = $table->implementedEvents();
		$this->assertCount(1, $events);
		$this->assertArrayHasKey('dummy1', $events);
		$this->assertInstanceOf(Closure::class, $events['dummy1']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::implementedEvents()
	 */
	public function testImplementedEventsWithCustomEventsSkipsEventsWithoutMethod(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @var array
			 */
			protected array $defaultEvents = [
				'dummy1' => 'customDummy1',
				'dummy2' => 'customDummy2',
			];
		};

		$events = $table->implementedEvents();
		$this->assertCount(0, $events);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::implementedEvents()
	 */
	public function testImplementedEventsWithArray(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @var array
			 */
			protected array $defaultEvents = [
				'dummy1' => [
					'callable' => 'customDummy1',
					'priority' => 10,
				],
				'dummy2' => [
					'callable' => 'customDummy2',
					'priority' => 20,
				],
			];

			/**
			 * @param \Cake\Event\EventInterface $event
			 * @param array $data
			 * @return void
			 * @noinspection PhpUnused
			 */
			public function customDummy1(EventInterface $event, array $data): void {
			}

			/**
			 * @param \Cake\Event\EventInterface $event
			 * @param array $data
			 * @return void
			 * @noinspection PhpUnused
			 */
			public function customDummy2(EventInterface $event, array $data): void {
			}
		};

		$events = $table->implementedEvents();
		$this->assertCount(2, $events);
		$this->assertArrayHasKey('dummy1', $events);
		$this->assertSame([
			'callable' => 'customDummy1',
			'priority' => 10,
		], $events['dummy1']);
		$this->assertArrayHasKey('dummy2', $events);
		$this->assertSame([
			'callable' => 'customDummy2',
			'priority' => 20,
		], $events['dummy2']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::implementedEvents()
	 */
	public function testImplementedEventsWithArrayWithoutCallableThrows(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('When provided an array, the key `dummy1` must contain a `callable` key');

		new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @var array
			 */
			protected array $defaultEvents = [
				'dummy1' => [
					'nonExistentMethod',
				],
			];
		};
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::implementedEvents()
	 */
	public function testImplementedEventsWithArrayAndNumericKeys(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			public function __construct(array $config = []) {
				$this->defaultEvents = [
					0 => 'customDummy1',
				];

				parent::__construct($config);
			}


			/**
			 * @param \Cake\Event\EventInterface $event
			 * @param array $data
			 * @return void
			 * @noinspection PhpUnused
			 */
			public function customDummy1(EventInterface $event, array $data): void {
			}
		};

		$events = $table->implementedEvents();
		$this->assertCount(1, $events);
		$this->assertArrayHasKey('Model.customDummy1', $events);
		$this->assertSame('customDummy1', $events['Model.customDummy1']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::implementedEvents()
	 */
	public function testImplementedEventsWithArrayAndNumericKeyThrowsWhenCallableNotString(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('When provided a callable, the key must be a string. `integer` given');

		new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			public function __construct(array $config = []) {
				$this->defaultEvents = [
					0 => $this->dummyFunction(...),
				];

				parent::__construct($config);
			}


			/**
			 * @param \Cake\Event\EventInterface $event
			 * @param array $data
			 * @return void
			 * @noinspection PhpUnused
			 */
			public function dummyFunction(EventInterface $event, array $data): void {
			}
		};
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapWithStringCallables(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @noinspection PhpUnused
			 */
			public function customMethod(): void {
			}
		};

		$eventMap = [
			'Model.beforeSave' => 'customMethod',
			'Model.afterSave' => 'customMethod',
		];

		$result = $table->buildEventMap($table, $eventMap);

		$expected = [
			'Model.beforeSave' => 'customMethod',
			'Model.afterSave' => 'customMethod',
		];

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapWithArrayCallablesAndPriority(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @noinspection PhpUnused
			 */
			public function customMethod(): void {
			}
		};

		$eventMap = [
			'Model.beforeSave' => [
				'callable' => 'customMethod',
				'priority' => 10,
			],
		];

		$result = $table->buildEventMap($table, $eventMap);

		$expected = [
			'Model.beforeSave' => [
				'callable' => 'customMethod',
				'priority' => 10,
			],
		];

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapWithGlobalPriority(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @noinspection PhpUnused
			 */
			public function customMethod(): void {
			}
		};

		$eventMap = [
			'Model.beforeSave' => 'customMethod',
			'Model.afterSave' => 'customMethod',
		];

		$result = $table->buildEventMap($table, $eventMap, 5);

		$expected = [
			'Model.beforeSave' => [
				'callable' => 'customMethod',
				'priority' => 5,
			],
			'Model.afterSave' => [
				'callable' => 'customMethod',
				'priority' => 5,
			],
		];

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapWithNumericKeysCreatesModelEvents(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @noinspection PhpUnused
			 */
			public function customMethod(): void {
			}
		};

		$eventMap = [
			0 => 'customMethod',
			1 => 'customMethod',
		];

		$result = $table->buildEventMap($table, $eventMap);

		$expected = [
			'Model.customMethod' => 'customMethod',
		];

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapSkipsNonExistentMethods(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$eventMap = [
			'Model.beforeSave' => 'nonExistentMethod',
			'Model.afterSave' => 'anotherNonExistentMethod',
		];

		$result = $table->buildEventMap($table, $eventMap);

		$this->assertEmpty($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapWithClosureCallables(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$closure = function () {
		};
		$eventMap = [
			'Model.beforeSave' => $closure,
		];

		$result = $table->buildEventMap($table, $eventMap);

		$expected = [
			'Model.beforeSave' => $closure,
		];

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapThrowsExceptionWhenArrayMissingCallable(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$eventMap = [
			'Model.beforeSave' => [
				'priority' => 10,
				// missing 'callable' key
			],
		];

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('When provided an array, the key `Model.beforeSave` must contain a `callable` key');

		$table->buildEventMap($table, $eventMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapThrowsExceptionWhenNumericKeyWithNonStringCallable(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$eventMap = [
			0 => function () {
			}, // numeric key with non-string callable
		];

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('When provided a callable, the key must be a string. `integer` given');

		$table->buildEventMap($table, $eventMap);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapArrayPriorityOverridesGlobalPriority(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @noinspection PhpUnused
			 */
			public function customMethod(): void {
			}
		};

		$eventMap = [
			'Model.beforeSave' => [
				'callable' => 'customMethod',
				'priority' => 15, // should override global priority
			],
			'Model.afterSave' => 'customMethod', // should use global priority
		];

		$result = $table->buildEventMap($table, $eventMap, 5);

		$expected = [
			'Model.beforeSave' => [
				'callable' => 'customMethod',
				'priority' => 15,
			],
			'Model.afterSave' => [
				'callable' => 'customMethod',
				'priority' => 5,
			],
		];

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::buildEventMap()
	 */
	public function testBuildEventMapWithBehaviorInstance(): void {
		$table = new Table(['alias' => 'TestTable', 'table' => 'pages']);

		$behavior = new class ($table) extends Behavior {
			/**
			 * @param \Cake\Event\EventInterface $event
			 * @param array $data
			 * @return void
			 * @noinspection PhpUnused
			 */
			public function customMethod(EventInterface $event, array $data): void {
			}
		};

		$eventMap = [
			'Model.beforeSave' => 'customMethod',
		];

		$result = $table->buildEventMap($behavior, $eventMap);

		$expected = [
			'Model.beforeSave' => 'customMethod',
		];

		$this->assertSame($expected, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::fieldIsAttribute()
	 */
	public function testFieldIsAttributeReturnsFalseWhenFieldInSchema(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $attributes = ['enabled' => true];


			/**
			 * @inheritDoc
			 */
			public function getSchema(): TableSchema {
				$schema = new TableSchema('pages');
				$schema->addColumn('id', ['type' => 'integer']);
				$schema->addColumn('title', ['type' => 'string']);

				return $schema;
			}
		};

		// Test with a field that exists in schema - should return false
		$result = $table->fieldIsAttribute('title');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::fieldIsAttribute()
	 */
	public function testFieldIsAttributeReturnsFalseWhenFieldNotInSchemaAndNoAttributes(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $attributes = [];


			/**
			 * @inheritDoc
			 */
			public function getSchema(): TableSchema {
				$schema = new TableSchema('pages');
				$schema->addColumn('id', ['type' => 'integer'])
					->addColumn('title', ['type' => 'string']);
				return $schema;
			}


			/**
			 * @inheritDoc
			 */
			public function hasAttributes(): bool {
				return false;
			}
		};

		// Test with a field that doesn't exist in schema and no attributes - should return false
		$result = $table->fieldIsAttribute('custom_attribute');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::fieldIsAttribute()
	 */
	public function testFieldIsAttributeReturnsTrueWhenFieldNotInSchemaAndHasAttributes(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			protected array $attributes = ['customAttribute' => true];


			/**
			 * @inheritDoc
			 */
			public function getSchema(): TableSchema {
				$schema = new TableSchema('pages');
				$schema->addColumn('id', ['type' => 'integer'])
					->addColumn('title', ['type' => 'string']);
				return $schema;
			}

			/**
			 * @inheritDoc
			 */
			public function hasAttributes(): bool {
				return true;
			}
		};

		// Test with a field that doesn't exist in schema - should return true since table has attributes
		$result = $table->fieldIsAttribute('custom_attribute');

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::getI18nDomain()
	 */
	public function testGetI18nDomainReturnsTableAlias(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		// Test that getI18nDomain returns the table alias
		$result = $table->getI18nDomain();

		$this->assertSame('TestTable', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::getI18nDomain()
	 */
	public function testGetI18nDomainReturnsMainTableForAttributes(): void {
		$table = new class (['alias' => 'AttributesContents', 'table' => 'attributes_contents']) extends Table {
		};

		// Test that getI18nDomain returns the main table alias for attributes
		$result = $table->getI18nDomain();

		$this->assertSame('Contents', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::addCategoriesBehavior()
	 */
	public function testAddCategoriesBehaviorDisablesBuildRulesOnNestPropertyWhenNestForeignKeyEqualsCategoriesFieldName(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			protected array $categories = [
				'field' => 'parentId',
				'enabled' => true,
			];


			/**
			 * @inheritDoc
			 */
			protected array $nest = [
				'enabled' => true,
				'parent' => [
					'foreignKey' => 'parentId',
				],
			];
		};

		$behaviors = $table->behaviors();
		$this->assertTrue($behaviors->has('Categories'));
		$this->assertTrue($behaviors->get('Categories')->getConfig('buildRules'));
		$this->assertTrue($behaviors->has('Nest'));
		$this->assertFalse($behaviors->get('Nest')->getConfig('buildRules'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::addCategoriesBehavior()
	 */
	public function testAddCategoriesBehaviorNotDisablesBuildRulesOnNestPropertyWhenNestForeignKeyEqualsCategoriesFieldName(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			protected array $categories = [
				'field' => 'parentId',
				'enabled' => true,
			];


			/**
			 * @inheritDoc
			 */
			protected array $nest = [
				'enabled' => true,
				'parent' => [
					'foreignKey' => 'other_column',
				],
			];
		};

		$behaviors = $table->behaviors();
		$this->assertTrue($behaviors->has('Categories'));
		$this->assertTrue($behaviors->get('Categories')->getConfig('buildRules'));
		$this->assertTrue($behaviors->has('Nest'));
		$this->assertTrue($behaviors->get('Nest')->getConfig('buildRules'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::addCategoriesBehavior()
	 */
	public function testAddCategoriesBehaviorSetsRelatedColumnOnNestProperty(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			protected array $categories = [
				'field' => 'title',
				'enabled' => true,
			];
		};

		$behaviors = $table->behaviors();
		$this->assertTrue($behaviors->has('Categories'));
		$this->assertTrue($behaviors->has('Nest'));
		$this->assertTrue($behaviors->get('Nest')->getConfig('buildRules'));
		$this->assertSame(['title'], $behaviors->get('Nest')->getConfig('relatedColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::addCategoriesBehavior()
	 */
	public function testAddCategoriesBehaviorNotSetsRelatedColumnOnNestPropertyWhenAlreadySet(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			protected array $categories = [
				'field' => 'title',
				'enabled' => true,
			];

			/**
			 * @inheritDoc
			 */
			protected array $nest = [
				'enabled' => true,
				'relatedColumns' => ['title'],
			];
		};

		$behaviors = $table->behaviors();
		$this->assertTrue($behaviors->has('Categories'));
		$this->assertTrue($behaviors->has('Nest'));
		$this->assertTrue($behaviors->get('Nest')->getConfig('buildRules'));
		$this->assertSame(['title'], $behaviors->get('Nest')->getConfig('relatedColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::addCategoriesBehavior()
	 */
	public function testAddCategoriesBehaviorSetsRelatedColumnOnNestPropertyWhenNotNestParentForeignKey(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			protected array $categories = [
				'field' => 'title',
				'enabled' => true,
			];

			/**
			 * @inheritDoc
			 */
			protected array $nest = [
				'enabled' => true,
				'parent' => [
					'foreignKey' => 'title',
				],
			];
		};

		$behaviors = $table->behaviors();
		$this->assertTrue($behaviors->has('Categories'));
		$this->assertTrue($behaviors->has('Nest'));
		$this->assertFalse($behaviors->get('Nest')->getConfig('buildRules'));
		$this->assertSame([], $behaviors->get('Nest')->getConfig('relatedColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::addCategoriesBehavior()
	 */
	public function testAddCategoriesBehaviorSetsRelatedColumnOnNestPropertyWithPrefixWhenAttribute(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			protected array $categories = [
				'field' => 'category_id',
				'enabled' => true,
			];

			/**
			 * @inheritDoc
			 */
			public function hasAttributes(): bool {
				return true;
			}
		};

		$behaviors = $table->behaviors();
		$this->assertTrue($behaviors->has('Categories'));
		$this->assertTrue($behaviors->has('Nest'));
		$this->assertTrue($behaviors->get('Nest')->getConfig('buildRules'));
		$this->assertSame(['attributes.category_id'], $behaviors->get('Nest')->getConfig('relatedColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::addCategoriesBehavior()
	 */
	public function testAddCategoriesBehaviorSetsRelatedColumnOnSystemOrderProperty(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			protected array $categories = [
				'field' => 'languageShortcode',
				'enabled' => true,
			];
		};

		$behaviors = $table->behaviors();
		$this->assertTrue($behaviors->has('Categories'));
		$this->assertTrue($behaviors->has('SystemOrder'));
		$this->assertSame(['languageShortcode'], $behaviors->get('SystemOrder')->getConfig('relatedColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::addCategoriesBehavior()
	 */
	public function testAddCategoriesBehaviorSetsRelatedColumnOnSystemOrderPropertyWithPrefixWhenAttribute(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			protected array $categories = [
				'field' => 'category_id',
				'enabled' => true,
			];

			/**
			 * @inheritDoc
			 */
			public function hasAttributes(): bool {
				return true;
			}
		};

		$behaviors = $table->behaviors();
		$this->assertTrue($behaviors->has('Categories'));
		$this->assertTrue($behaviors->has('SystemOrder'));
		$this->assertSame(['attributes.category_id'], $behaviors->get('SystemOrder')->getConfig('relatedColumns'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::marshaller()
	 */
	public function testMarshallerReturnsInstanceOfAwyissMarshaller(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		$marshaller = $table->marshaller();
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(Marshaller::class, $marshaller);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::save()
	 * @noinspection PhpUndefinedFieldInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testSaveWithIsCopyOptionSetsOriginalEntity(): void {
		$entity = new Entity(['id' => 1, 'title' => 'Test', 'subtitle' => 'Subtitle']);
		$entity->setNew(false);

		$entity->set('id', 35);
		$entity->set('subtitle', 'New Subtitle');

		$this->assertTrue($entity->isDirty());

		// Mock the parent save to just return the entity
		$table = $this->getStubBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['_processSave'])->getStub();

		$table->method('_processSave')->willReturn(false);

		$table->save($entity, ['isCopy' => true]);

		$this->assertSame(['label', 'originalEntity'], $entity->getVirtual());
		$this->assertInstanceOf(Entity::class, $entity->originalEntity);
		$this->assertSame('Test', $entity->originalEntity->title);
		$this->assertSame('Subtitle', $entity->originalEntity->subtitle);
		$this->assertSame(1, $entity->originalEntity->id);
		$this->assertFalse($entity->originalEntity->isDirty());

		// Test that the main entity still has its data
		$this->assertSame('Test', $entity->title);
		$this->assertSame('New Subtitle', $entity->subtitle);
		// Primary key should not be set
		$this->assertNull($entity->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::save()
	 * @noinspection PhpUndefinedFieldInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testSaveWithAsCopyOptionSetsOriginalEntity(): void {
		$entity = new Entity(['id' => 1, 'title' => 'Test', 'subtitle' => 'Subtitle']);
		$entity->setNew(false);

		$entity->set('id', 35);
		$entity->set('subtitle', 'New Subtitle');

		$this->assertTrue($entity->isDirty());

		// Mock the parent save to just return the entity
		$table = $this->getStubBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'pages']])->onlyMethods(['_processSave'])->getStub();

		$table->method('_processSave')->willReturn(false);

		$table->save($entity, ['asCopy' => true]);

		$this->assertSame(['label', 'originalEntity'], $entity->getVirtual());
		$this->assertInstanceOf(Entity::class, $entity->originalEntity);
		$this->assertSame('Test', $entity->originalEntity->title);
		$this->assertSame('Subtitle', $entity->originalEntity->subtitle);
		$this->assertSame(1, $entity->originalEntity->id);
		$this->assertFalse($entity->originalEntity->isDirty());

		// Test that the main entity still has its data
		$this->assertSame('Test', $entity->title);
		$this->assertSame('New Subtitle', $entity->subtitle);
		// Primary key should not be set
		$this->assertNull($entity->id);
		$this->assertTrue($entity->isNew());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::save()
	 * @noinspection PhpUndefinedFieldInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testSaveWithAsCopyMarksHasManyAssociationsAsNew(): void {
		$entity = new Entity(['id' => 1, 'title' => 'Test', 'subtitle' => 'Subtitle']);
		$entity->setNew(false);

		$entity->testAssociations = [
			new Entity(['id' => 2, 'title' => 'Associated Test 1', 'subtitle' => 'Associated Subtitle 1']),
			new Entity(['id' => 3, 'title' => 'Associated Test 2', 'subtitle' => 'Associated Subtitle 2']),
		];
		foreach ($entity->testAssociations as $association) {
			$association->setNew(false);
		}

		// Create a concrete table class to avoid mock issues with behaviors
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			public function initializeAssociations(): void {
				$this->hasMany('TestAssociations', [
					'className' => PagesTable::class,
					'foreignKey' => 'testId',
					'propertyName' => 'testAssociations',
				]);
			}


			/**
			 * @inheritDoc
			 */
			protected function _processSave($entity, $options): EntityInterface|false {
				return false; // Simulate save without actual database operations
			}
		};

		$table->save($entity, ['asCopy' => true]);

		// Test parent entity
		$this->assertFalse($entity->has('id'));
		$this->assertTrue($entity->isNew());

		// Test associated entities
		foreach ($entity->testAssociations as $association) {
			$this->assertFalse($association->has('id'));
			$this->assertTrue($association->isNew());
		}

		$this->assertSame('Test', $entity->title);

		$this->assertSame(['label', 'originalEntity'], $entity->getVirtual());
		$this->assertSame(1, $entity->originalEntity->id);

		$this->assertCount(2, $entity->originalEntity->testAssociations);
		$this->assertSame(2, $entity->originalEntity->testAssociations[0]->id);
		$this->assertSame(3, $entity->originalEntity->testAssociations[1]->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::save()
	 * @noinspection PhpUndefinedFieldInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testSaveWithAsCopyMarksHasOneAssociationAsNew(): void {
		$entity = new Entity(['id' => 1, 'title' => 'Test', 'subtitle' => 'Subtitle']);
		$entity->setNew(false);
		$entity->testAssociation = new Entity(['id' => 2, 'title' => 'Associated Test', 'subtitle' => 'Associated Subtitle']);
		$entity->testAssociation->setNew(false);

		// Create a concrete table class to avoid mock issues with behaviors
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @inheritDoc
			 */
			public function initializeAssociations(): void {
				$this->hasOne('TestAssociation', [
					'className' => PagesTable::class,
					'foreignKey' => 'testId',
					'propertyName' => 'testAssociation',
				]);
			}


			/**
			 * @inheritDoc
			 */
			protected function _processSave($entity, $options): EntityInterface|false {
				return false; // Simulate save without actual database operations
			}
		};

		$table->save($entity, ['asCopy' => true]);

		// Test parent entity
		$this->assertFalse($entity->has('id'));
		$this->assertTrue($entity->isNew());

		// Test associated entity
		$this->assertFalse($entity->testAssociation->has('id'));
		$this->assertTrue($entity->testAssociation->isNew());

		// Test that other data is preserved
		$this->assertSame('Test', $entity->title);
		$this->assertSame('Associated Test', $entity->testAssociation->title);

		$this->assertSame(['label', 'originalEntity'], $entity->getVirtual());
		$this->assertSame(1, $entity->originalEntity->id);
		$this->assertSame(2, $entity->originalEntity->testAssociation->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::saveMany()
	 * @throws \Exception
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testSaveManyWithTransactionFalseThrowsExceptionOnSaveFailure(): void {
		$mockConnection = $this->createMock(Connection::class);

		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'test_entities']])->onlyMethods(['save', 'getConnection'])->getMock();

		// Mock save to return false (failure)
		$table->expects($this->once())->method('save')->willReturn(false);

		// Connection methods should never be called when transaction=false
		$mockConnection->expects($this->never())->method('transactional');
		$mockConnection->expects($this->never())->method('begin');
		$mockConnection->expects($this->never())->method('commit');
		$mockConnection->expects($this->never())->method('rollback');

		$entities = [new Entity(['name' => 'Test Entity'])];

		// Expect PersistenceFailedException to be thrown directly when transaction=false
		$this->expectException(PersistenceFailedException::class);

		$table->saveMany($entities, ['transaction' => false]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::saveMany()
	 * @throws \Exception
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testSaveManyWithTransactionTrueReturnsFalseOnSaveFailure(): void {
		$mockConnection = $this->createMock(Connection::class);

		$table = $this->getMockBuilder(Table::class)->setConstructorArgs([['alias' => 'TestTable', 'table' => 'test_entities']])->onlyMethods(['save', 'getConnection'])->getMock();

		$table->expects($this->atLeastOnce())->method('getConnection')->willReturn($mockConnection);

		// Mock save to return false (failure)
		$table->expects($this->once())->method('save')->willReturn(false);

		// Connection transactional should be called when transaction=true
		$mockConnection->expects($this->once())->method('transactional')->willReturnCallback(function ($callback) {
			return $callback(); // Execute the callback which will throw PersistenceFailedException
		});

		$entities = [new Entity(['name' => 'Test Entity'])];

		// When transaction=true, should return false instead of throwing exception
		$result = $table->saveMany($entities, ['transaction' => true]);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::delete()
	 * @throws \Exception
	 */
	public function testDeleteDispatchesAfterDeleteCommitWitoutSoftDeleteBehavior(): void {
		/** @var \Customer\Model\Table\EmployersTable $table */
		$table = $this->fetchTable('Employers');
		$entity = $table->newDefaultEntity([
			'name' => 'Test Employer',
			'languageShortcode' => 'de',
		]);

		$result = $table->save($entity);

		$this->assertNotFalse($result);

		$beforeDeleteSent = false;
		$table->getEventManager()->on('Model.afterDeleteCommit', function () use (&$beforeDeleteSent) {
			$beforeDeleteSent = true;
		});

		$table->getBehavior('SoftDelete')->setConfig('enabled', false);

		$result = $table->delete($entity, ['audit' => ['skip' => true]]);

		$this->assertTrue($result);
		$this->assertTrue($beforeDeleteSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::delete()
	 * @throws \Exception
	 */
	public function testDeleteNotDispatchesAfterDeleteCommitWithSoftDeleteBehavior(): void {
		/** @var \Customer\Model\Table\EmployersTable $table */
		$table = $this->fetchTable('Employers');
		$entity = $table->newDefaultEntity([
			'name' => 'Test Employer',
			'languageShortcode' => 'de',
		]);

		$result = $table->save($entity);

		$this->assertNotFalse($result);

		$beforeDeleteSent = false;
		$table->getEventManager()->on('Model.afterDeleteCommit', function () use (&$beforeDeleteSent) {
			$beforeDeleteSent = true;
		});

		$result = $table->delete($entity, ['audit' => ['skip' => true]]);

		$this->assertTrue($result);
		$this->assertFalse($beforeDeleteSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::save()
	 * @throws \Exception
	 */
	public function testSaveWithAssociationsDispatchesAssociationEvents(): void {
		/** @var \Awyiss\Model\Table $table */
		$table = $this->fetchTable('Cars');
		/** @var \Customer\Model\Entity\Car $car */
		$car = $table->newDefaultEntity();
		$car->set('name', 'Test Car');
		$car->set('freeText', 'This is a test car with attributes');
		$car->set('dropdownSelect', 'dark');
		$car->set('languageShortcode', 'de');

		$beforeSaveAssociationsSent = false;
		$table->getEventManager()->on('Model.beforeSaveAssociations', function () use (&$beforeSaveAssociationsSent) {
			$beforeSaveAssociationsSent = true;
		});

		$afterSaveAssociationsSent = false;
		$table->getEventManager()->on('Model.afterSaveAssociations', function () use (&$afterSaveAssociationsSent) {
			$afterSaveAssociationsSent = true;
		});

		$result = $table->save($car, [
			'associated' => [
				'AttributesCars',
			],
		]);

		$this->assertNotFalse($result);
		$this->assertTrue($beforeSaveAssociationsSent);
		$this->assertTrue($afterSaveAssociationsSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::save()
	 * @throws \Exception
	 */
	public function testSaveWithoutAssociationsDispatchesAssociationEvents(): void {
		/** @var \Awyiss\Model\Table $table */
		$table = $this->fetchTable('Cars');
		/** @var \Customer\Model\Entity\Car $car */
		$car = $table->newDefaultEntity();
		$car->set('name', 'Test Car');
		$car->set('freeText', 'This is a test car with attributes');
		$car->set('dropdownSelect', 'Mitsubishi');
		$car->set('languageShortcode', 'de');

		$beforeSaveAssociationsSent = false;
		$table->getEventManager()->on('Model.beforeSaveAssociations', function () use (&$beforeSaveAssociationsSent) {
			$beforeSaveAssociationsSent = true;
		});

		$afterSaveAssociationsSent = false;
		$table->getEventManager()->on('Model.afterSaveAssociations', function () use (&$afterSaveAssociationsSent) {
			$afterSaveAssociationsSent = true;
		});

		$result = $table->save($car, [
			'associated' => [],
		]);

		$this->assertNotFalse($result);
		$this->assertFalse($beforeSaveAssociationsSent);
		$this->assertFalse($afterSaveAssociationsSent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initializeSchema()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testInitializeSchemaDoesNothingForNonAttributesTables(): void {
		$schema = $this->createMock(TableSchemaInterface::class);

		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
			/**
			 * @param \Cake\Database\Schema\TableSchemaInterface $schema
			 * @return void
			 */
			public function testInitializeSchema(TableSchemaInterface $schema): void {
				$this->initializeSchema($schema);
			}
		};

		// Schema should not be modified for non-attributes tables
		$schema->expects($this->never())->method('getColumn');
		$schema->expects($this->never())->method('setColumnType');
		$schema->expects($this->never())->method('addColumn');

		$table->testInitializeSchema($schema);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table::initializeSchema()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testInitializeSchemaProcessesAttributesCorrectly(): void {
		$schema = $this->createMock(TableSchemaInterface::class);
		$attribute = new Attribute();

		$table = new class (['alias' => 'AttributesPages', 'table' => 'attributes_cars']) extends Table {
			/**
			 * @var array
			 */
			private array $testAttributes = [];


			/**
			 * @param array $attributes
			 * @return void
			 */
			public function setTestAttributes(array $attributes): void {
				$this->testAttributes = $attributes;
			}


			/**
			 * @return array
			 */
			public function getAttributes(): array {
				return $this->testAttributes;
			}


			/**
			 * @param \Cake\Database\Schema\TableSchemaInterface $schema
			 * @return void
			 */
			public function testInitializeSchema(TableSchemaInterface $schema): void {
				$this->initializeSchema($schema);
			}
		};

		$attribute->identifier = 'testField';
		$attribute->type = 'json';
		$attribute->defaultValue = '{"test": "value"}';

		$table->setTestAttributes([$attribute]);

		$schema->expects($this->once())->method('getColumn')->with('testField')->willReturn(['type' => 'text', 'default' => null]);

		$schema->expects($this->once())->method('setColumnType')->with('testField', 'json');

		$schema->expects($this->once())->method('addColumn')->with('testField', ['type' => 'text', 'default' => '{"test": "value"}']);

		$table->testInitializeSchema($schema);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::beforeRules()
	 */
	public function testBeforeRulesNotCleansHtmlWhenConfigNone(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		Configure::write('Awyiss.System.Backend.htmlCleaning', null);

		$entity = new Entity();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity->text = $this->exampleHtml;

		$table->checkRules($entity);

		$this->assertSame($this->exampleHtml, $entity->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::beforeRules()
	 * @see \Awyiss\Utility\Content\HtmlCleaner::cleanModerare()
	 */
	public function testBeforeRulesCleansHtmlModerate(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		Configure::write('Awyiss.System.Backend.htmlCleaning', HtmlCleaner::CLEAN_MODERATE);

		$entity = new Entity();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity->text = $this->exampleHtml;

		$table->checkRules($entity);

		/** @noinspection HtmlDeprecatedAttribute */
		$this->assertSame(
			<<<'HTML'
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>After starting empty p-tags</p>
<p>Starting &lt;br&gt;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>After two empty p-tags</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Before ending empty p-tags</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Lorem&nbsp;<br> <br> <br>&nbsp;ipsum with spaces before &lt;br&gt;.</p>
<p>&nbsp;</p>
<p>Spaces&nbsp;between</p>
<p>&nbsp;</p>
<p>Spaces around</p>
<p>&nbsp;</p>
<p>Spaces after</p>
<p>&nbsp;</p>
<p>Duis autem</p>
<p>&nbsp;</p>
<ul><li></li></ul>
<ul><li>&lt;br&gt; at the end</li>
<li>&lt;br&gt; at the start</li></ul>
<p>&nbsp;</p>
	<ul>
		<li>Many ending &lt;br&gt;</li>
		<li>Another starting &lt;br&gt;</li>
	</ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<ul><li>&nbsp;</li></ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Space after! asdf</p>
<p>Space after? asdf</p>
<p>Space after. asdf</p>
<p>&nbsp;</p>
<p>At vero</p>
<p>&nbsp;</p>
<p><span>Spaces before span</span></p>
<p><span><span>&nbsp;</span></span></p>
<p>&nbsp;</p>
<p style="text-align:center;" align="right">&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
HTML,
			$entity->text,
			'Text should be cleaned with CLEAN_MODERATE'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::beforeRules()
	 * @see \Awyiss\Utility\Content\HtmlCleaner::cleanModerare()
	 * @see \Awyiss\Utility\Content\HtmlCleaner::cleanStrict()
	 */
	public function testBeforeRulesCleansHtmlStrict(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		Configure::write('Awyiss.System.Backend.htmlCleaning', HtmlCleaner::CLEAN_STRICT);

		$entity = new Entity();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity->text = $this->exampleHtml;

		$table->checkRules($entity);

		$this->assertSame(
			<<<'HTML'
<p>After starting empty p-tags</p>
<p>Starting &lt;br&gt;</p>
<p>&nbsp;</p>
<p>After two empty p-tags</p>
<p>&nbsp;</p>
<p>Before ending empty p-tags</p>
<p>&nbsp;</p>
<p>Lorem<br>ipsum with spaces before &lt;br&gt;.</p>
<p>&nbsp;</p>
<p>Spaces&nbsp;between</p>
<p>&nbsp;</p>
<p>Spaces around</p>
<p>&nbsp;</p>
<p>Spaces after</p>
<p>&nbsp;</p>
<p>Duis autem</p>
<p>&nbsp;</p>
<ul><li>&lt;br&gt; at the end</li>
<li>&lt;br&gt; at the start</li></ul>
<p>&nbsp;</p>
	<ul>
		<li>Many ending &lt;br&gt;</li>
		<li>Another starting &lt;br&gt;</li>
	</ul>
<p>&nbsp;</p>
<p>Space after! asdf</p>
<p>Space after? asdf</p>
<p>Space after. asdf</p>
<p>&nbsp;</p>
<p>At vero</p>
<p>&nbsp;</p>
<p><span>Spaces before span</span></p>
HTML,
			$entity->text,
			'Text should be cleaned with CLEAN_STRICT'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::beforeRules()
	 */
	public function testBeforeRulesNotCleansHtmlWhenDeleted(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		Configure::write('Awyiss.System.Backend.htmlCleaning', HtmlCleaner::CLEAN_STRICT);

		$entity = new Entity();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity->text = $this->exampleHtml;
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity->deleted = true; // Simulate deleted entity

		$table->checkRules($entity);

		$this->assertSame($this->exampleHtml, $entity->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::beforeSave()
	 */
	public function testBeforeSaveNotHandleImagesInHtmlConfigFalsish(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		Configure::write('Awyiss.Media.Backend.handleImagesInHtml', null);

		$cancelEvent = function ($event) {
			// Cancel the event to prevent saving
			$event->stopPropagation();
		};
		$table->getEventManager()->on('Model.beforeSave', ['priority' => 100], $cancelEvent);

		/** @noinspection HtmlUnknownTarget */
		$text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image" width="100" height="100"></p>';

		$entity = new Page();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity->text = $text;

		$table->save($entity, ['checkRules' => false]);

		$table->getEventManager()->off('Model.beforeSave', $cancelEvent);

		$this->assertSame($text, $entity->text);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table::beforeSave()
	 * @see \Awyiss\Utility\Content\ImageHandler::replaceImageTags()
	 */
	public function testBeforeSaveHandlesImagesInHtmlConfigTrueish(): void {
		$table = new class (['alias' => 'TestTable', 'table' => 'pages']) extends Table {
		};

		Configure::write('Awyiss.Media.Backend.handleImagesInHtml', true);

		$cancelEvent = function ($event) {
			// Cancel the event to prevent saving
			$event->stopPropagation();
		};
		$table->getEventManager()->on('Model.beforeSave', ['priority' => 100], $cancelEvent);

		$entity = new Page();
		/** @noinspection HtmlUnknownTarget, PhpPossiblePolymorphicInvocationInspection */
		$entity->text = '<p>Test image: <img src="../awyiss/Command/Media/TestFiles/logo-awyiss.jpg" alt="Test Image" width="100" height="100"></p>';
		$entity->setSource('pages');

		$table->save($entity, ['checkRules' => false]);

		$table->getEventManager()->off('Model.beforeSave', $cancelEvent);

		$this->assertSame('<p>Test image: <awyiss-responsive-image>{"alt":"Test Image","width":"100","height":"100","mediaId":"2"}</awyiss-responsive-image></p>', $entity->text);

		$this->assertCount(1, $entity->mediaAssignments);
		$this->assertSame(2, $entity->mediaAssignments[0]->mediaId);
		$this->assertSame('pages', $entity->mediaAssignments[0]->scope);
	}
}
