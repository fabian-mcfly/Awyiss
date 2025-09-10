<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Queue\Task\Attributes;


use Awyiss\Model\Table;
use Awyiss\Model\Table\AttributesTable;
use Awyiss\ORM\Locator\TableLocator;
use Awyiss\Queue\Task\Attributes\DeleteTask;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\Locator\LocatorInterface;
use Cake\I18n\DateTime;
use Queue\Model\Table\QueuedJobsTable;


/**
 * Test case for DeleteTask
 *
 * @see \Awyiss\Queue\Task\Attributes\DeleteTask
 */
class DeleteTaskTest extends TestCase {
	/**
	 * @var string
	 */
	protected string $dummyTableFile;
	/**
	 * @var string
	 */
	protected string $dummyEntityFile;
	/**
	 * @var \Cake\Datasource\Locator\LocatorInterface
	 */
	protected LocatorInterface $tableLocator;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create the files that will be deleted
		$this->dummyTableFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Table' . DS . 'AttributesUsergroupsTable.php';
		$this->dummyEntityFile = ROOT . DS . CUSTOM_DIR . DS . 'Model' . DS . 'Entity' . DS . 'AttributesUsergroup.php';
		file_put_contents($this->dummyTableFile, '<?php // Mock table file');
		file_put_contents($this->dummyEntityFile, '<?php // Mock entity file');

		// Set up the table locator
		$this->tableLocator = FactoryLocator::get('Table');
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		// Restore original table locator
		FactoryLocator::drop('Table');
		FactoryLocator::add('Table', $this->tableLocator);

		parent::tearDown();

		// Unlink the files to simulate deletion
		if (file_exists($this->dummyTableFile)) {
			unlink($this->dummyTableFile);
		}
		if (file_exists($this->dummyEntityFile)) {
			unlink($this->dummyEntityFile);
		}
	}


	/**
	 * @dataProvider scopeDataProvider
	 * @param string $identifier
	 * @param int $identityId
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\DeleteTask::run()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRun(string $identifier, int $identityId): void {
		$data = [
			'identifier' => $identifier,
			'identityId' => $identityId,
		];

		$mockAttributesTable = $this->createMock(AttributesTable::class);
		$mockAttributesTable->expects($this->once())->method('updateAll')->with(
			$this->callback(function (array $fields) use ($identityId): bool {
				$this->assertTrue($fields['deleted']);
				$this->assertSame($identityId, $fields['deleted_by']);
				$this->assertInstanceOf(DateTime::class, $fields['deleted_on']);

				return true;
			}),
			$this->callback(function (array $conditions) use ($identifier): bool {
				$this->assertSame($identifier, $conditions['scope']);

				return true;
			})
		);

		$mockI18nTable = $this->createMock(Table::class);
		$mockI18nTable->expects($this->once())->method('deleteAll')->with([
			'model' => 'attributes_' . $identifier,
		]);

		$mockTableLocator = $this->createMock(TableLocator::class);
		$mockTableLocator->expects($this->exactly(2))->method('get')->willReturnMap([
			['Attributes', [], $mockAttributesTable],
			['I18n', [], $mockI18nTable],
		]);

		$mockQueuedJobs = $this->createMock(QueuedJobsTable::class);
		$mockQueuedJobs->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function (array $jobData) use ($identifier): bool {
				$unlinkCommands = '';

				if ($identifier === 'news') {
					// Special case for news, we need to unlink the files
					$unlinkCommands = 'unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Table/AttributesNewsTable.php';
					$unlinkCommands .= ' && unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Entity/AttributesNews.php && ';
				}
				elseif ($identifier === 'pages') {
					// Special case for pages, we need to unlink the files
					$unlinkCommands = 'unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Table/AttributesPagesTable.php';
					$unlinkCommands .= ' && unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Entity/AttributesPage.php && ';
				}
				elseif ($identifier === 'usergroups') {
					// Special case for usergroups, we need to unlink the files
					$unlinkCommands = 'unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Table/AttributesUsergroupsTable.php';
					$unlinkCommands .= ' && unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Entity/AttributesUsergroup.php && ';
				}

				$this->assertSame(
					'(' . $unlinkCommands . 'bin/cake bake migration drop_attributes_' . $identifier . ' --folder tests/customer/config/Migrations' .
					' && bin/cake migrations migrate --source ../../tests/customer/config/Migrations --no-lock' .
					' && bin/cake schema_cache clear' .
					' && bin/cake bake seed --data Attributes --folder tests/customer/config/Seeds --force --truncate)',
					$jobData['command']
				);

				return true;
			}),
			$this->callback(function (array $options): bool {
				$this->assertSame([
					'group' => 'general',
					'priority' => 1,
					'reference' => 'attributes::table_changes',
				], $options);

				return true;
			})
		);

		$task = new DeleteTask();
		$task->QueuedJobs = $mockQueuedJobs;

		FactoryLocator::add('Table', $mockTableLocator);

		$task->run($data, 999);
	}


	/**
	 * Data provider for scope testing
	 *
	 * @return array
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function scopeDataProvider(): array {
		return [
			['content_templates', 123],
			['pages', 456],
			['users', 789],
			['news', 101],
			['product_categories', 202],
			['categories', 303],
			['usergroups', 404],
		];
	}
}
