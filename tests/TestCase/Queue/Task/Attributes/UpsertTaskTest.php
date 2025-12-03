<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Queue\Task\Attributes;


use Awyiss\Queue\Task\Attributes\UpsertTask;
use Awyiss\Test\TestSuite\TestCase;
use Queue\Model\Table\QueuedJobsTable;


/**
 * Test case for UpsertTask
 *
 * @see \Awyiss\Queue\Task\Attributes\UpsertTask
 */
class UpsertTaskTest extends TestCase {
	/**
	 * @var string
	 */
	protected string $dummyTableFile;
	/**
	 * @var string
	 */
	protected string $dummyEntityFile;


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
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
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
	 * Test getTypeAndLength with no type provided returns default string and length 255
	 *
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::getTypeAndLength();
	 * @throws \ReflectionException
	 */
	public function testGetTypeAndLengthDefault(): void {
		$task = new UpsertTask();
		[$type, $length] = $this->callProtectedMethod($task, 'getTypeAndLength', null);

		$this->assertSame('string', $type);
		$this->assertSame('255', $length);
	}


	/**
	 * Test getTypeAndLength with integer type and decimal length
	 *
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::getTypeAndLength();
	 * @throws \ReflectionException
	 */
	public function testGetTypeAndLengthIntegerDecimal(): void {
		$task = new UpsertTask();
		[$type, $length] = $this->callProtectedMethod($task, 'getTypeAndLength', 'int(10,4)');

		$this->assertSame('integer', $type);
		$this->assertSame('10,4', $length);
	}


	/**
	 * Test getTypeAndLength with tinyint without length
	 *
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::getTypeAndLength();
	 * @throws \ReflectionException
	 */
	public function testGetTypeAndLengthTinyint(): void {
		$task = new UpsertTask();
		[$type, $length] = $this->callProtectedMethod($task, 'getTypeAndLength', 'tinyint');

		$this->assertSame('tinyinteger', $type);
		$this->assertNull($length);
	}


	/**
	 * Test getTypeAndLength with unknown type falls back to string
	 *
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::getTypeAndLength();
	 * @throws \ReflectionException
	 */
	public function testGetTypeAndLengthUnknown(): void {
		$task = new UpsertTask();
		[$type, $length] = $this->callProtectedMethod($task, 'getTypeAndLength', 'foo(123)');

		$this->assertSame('string', $type);
		$this->assertSame('123', $length);
	}


	/**
	 * @return array[string, bool, ?string, bool, string]
	 */
	public static function newDataProvider(): array {
		return [
			['float(10,2)', true, null, false, 'float[10,2]'],
			['varchar(1000)', false, null, true, 'string?[1000]:index'],
			['char(10)', true, 'default_val', false, 'char[10](default_val)'],
			['text', false, '', true, 'text?:index'],
			['int(11)', false, '(123)', false, 'integer?[11]((123))'],
		];
	}


	/**
	 * @dataProvider newDataProvider
	 * @param string $type
	 * @param bool $required
	 * @param ?string $defaultValue
	 * @param bool $index
	 * @param string $colString
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::run : bool()
	 */
	public function testRunWithNewAttributeOnNewAttributesTable(string $type, bool $required, ?string $defaultValue, bool $index, string $colString): void {
		$data = [
			'old' => [
				'scope' => null,
				'identifier' => null,
				'type' => null,
				'required' => null,
				'defaultValue' => null,
				'hasIndex' => null,
				'deleted' => null,
			],
			'new' => [
				'scope' => 'users',
				'identifier' => 'test_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
		];

		$task = $this->getMockBuilder(UpsertTask::class)->onlyMethods(['createJob'])->getMock();

		$task->expects($this->once())->method('createJob')->with(
			$this->callback(function (array $commands) use ($colString): bool {
				$this->assertCount(1, $commands);

				$this->assertEquals(
					sprintf(
						'bin/cake bake migration create_attributes_users user_id:integer[11]:index test_col:%s --folder tests/customer/config/Migrations',
						$colString
					),
					$commands[0]
				);

				return true;
			}),
			$this->callback(function (array $dataArg) use ($data): bool {
				$this->assertSame($data, $dataArg);

				return true;
			}),
			false,
			false
		);

		$task->run($data, 1);
	}


	/**
	 * @dataProvider newDataProvider
	 * @param string $type
	 * @param bool $required
	 * @param ?string $defaultValue
	 * @param bool $index
	 * @param string $colString
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::run : bool()
	 */
	public function testRunWithNewAttributeOnExistingAttributesTable(string $type, bool $required, ?string $defaultValue, bool $index, string $colString): void {
		$data = [
			'old' => [
				'scope' => null,
				'identifier' => null,
				'type' => null,
				'required' => null,
				'defaultValue' => null,
				'hasIndex' => null,
				'deleted' => null,
			],
			'new' => [
				'scope' => 'contents',
				'identifier' => 'test_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
		];

		$task = $this->getMockBuilder(UpsertTask::class)->onlyMethods(['createJob'])->getMock();

		$task->expects($this->once())->method('createJob')->with(
			$this->callback(function (array $commands) use ($colString): bool {
				$this->assertCount(1, $commands);

				$this->assertEquals(
					sprintf(
						'bin/cake bake migration add_test_col_to_attributes_contents test_col:%s --folder tests/customer/config/Migrations',
						$colString
					),
					$commands[0]
				);

				return true;
			}),
			$this->callback(function (array $dataArg) use ($data): bool {
				$this->assertSame($data, $dataArg);

				return true;
			}),
			false,
			false
		);

		$task->run($data, 1);
	}


	/**
	 * @dataProvider newDataProvider
	 * @param string $type
	 * @param bool $required
	 * @param ?string $defaultValue
	 * @param bool $index
	 * @param string $colString
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::run : bool()
	 */
	public function testRunWithRenamedAttribute(string $type, bool $required, ?string $defaultValue, bool $index, string $colString): void {
		$data = [
			'old' => [
				'scope' => 'contents',
				'identifier' => 'old_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
			'new' => [
				'scope' => 'contents',
				'identifier' => 'new_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
		];

		$task = $this->getMockBuilder(UpsertTask::class)->onlyMethods(['createJob'])->getMock();

		$task->expects($this->exactly(1))->method('createJob')->with(
			$this->callback(function (array $commands) use ($colString): bool {
				$this->assertCount(1, $commands);

				$this->assertEquals(
					sprintf(
						'bin/cake bake migration alter_old_col_on_attributes_contents new_col:%s --folder tests/customer/config/Migrations',
						$colString
					),
					$commands[0]
				);

				return true;
			}),
			$this->callback(function (array $dataArg) use ($data): bool {
				$this->assertSame($data, $dataArg);

				return true;
			}),
			false,
			false
		);

		$task->run($data, 1);
	}


	/**
	 * @dataProvider newDataProvider
	 * @param string $type
	 * @param bool $required
	 * @param ?string $defaultValue
	 * @param bool $index
	 * @param string $colString
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::run : bool()
	 */
	public function testRunWithChangedScopeAndNewAttributesTable(string $type, bool $required, ?string $defaultValue, bool $index, string $colString): void {
		$data = [
			'old' => [
				'scope' => 'contents',
				'identifier' => 'test_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
			'new' => [
				'scope' => 'users',
				'identifier' => 'renamed_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
		];

		$task = $this->getMockBuilder(UpsertTask::class)->onlyMethods(['createJob'])->getMock();

		$task->expects($this->once())->method('createJob')->with(
			$this->callback(function (array $commands) use ($colString): bool {
				$this->assertCount(3, $commands);

				$this->assertEquals(
					'bin/cake bake migration remove_test_col_from_attributes_contents test_col --folder tests/customer/config/Migrations',
					$commands[0]
				);

				$this->assertEquals('sleep 1', $commands[1]);

				$this->assertEquals(
					sprintf(
						'bin/cake bake migration create_attributes_users user_id:integer[11]:index renamed_col:%s --folder tests/customer/config/Migrations',
						$colString
					),
					$commands[2]
				);

				return true;
			}),
			$this->callback(function (array $dataArg) use ($data): bool {
				$this->assertSame($data, $dataArg);

				return true;
			}),
			false,
			true
		);

		$task->run($data, 1);
	}


	/**
	 * @dataProvider newDataProvider
	 * @param string $type
	 * @param bool $required
	 * @param ?string $defaultValue
	 * @param bool $index
	 * @param string $colString
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::run : bool()
	 */
	public function testRunWithChangedScopeAndExistingAttributesTable(string $type, bool $required, ?string $defaultValue, bool $index, string $colString): void {
		$data = [
			'old' => [
				'scope' => 'news',
				'identifier' => 'test_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
			'new' => [
				'scope' => 'contents',
				'identifier' => 'renamed_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
		];

		$task = $this->getMockBuilder(UpsertTask::class)->onlyMethods(['createJob'])->getMock();

		$task->expects($this->once())->method('createJob')->with(
			$this->callback(function (array $commands) use ($colString): bool {
				$this->assertCount(3, $commands);

				$this->assertEquals(
					'bin/cake bake migration remove_test_col_from_attributes_news test_col --folder tests/customer/config/Migrations',
					$commands[0]
				);

				$this->assertEquals('sleep 1', $commands[1]);

				$this->assertEquals(
					sprintf(
						'bin/cake bake migration add_renamed_col_to_attributes_contents renamed_col:%s --folder tests/customer/config/Migrations',
						$colString
					),
					$commands[2]
				);

				return true;
			}),
			$this->callback(function (array $dataArg) use ($data): bool {
				$this->assertSame($data, $dataArg);

				return true;
			}),
			false,
			true
		);

		$task->run($data, 1);
	}


	/**
	 * @dataProvider newDataProvider
	 * @param string $type
	 * @param bool $required
	 * @param ?string $defaultValue
	 * @param bool $index
	 * @param string $colString
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::run : bool()
	 */
	public function testRunWithChangedScopeAndExistingAttributesTableForLastColumnInTable(
		string $type,
		bool $required,
		?string $defaultValue,
		bool $index,
		string $colString
	): void {
		$data = [
			'old' => [
				'scope' => 'usergroups',
				'identifier' => 'test_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
			'new' => [
				'scope' => 'contents',
				'identifier' => 'renamed_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
		];

		$task = $this->getMockBuilder(UpsertTask::class)->onlyMethods(['createJob'])->getMock();

		$task->expects($this->once())->method('createJob')->with(
			$this->callback(function (array $commands) use ($colString): bool {
				$this->assertCount(5, $commands);

				$this->assertEquals(
					'bin/cake bake migration drop_attributes_usergroups --folder tests/customer/config/Migrations',
					$commands[0]
				);

				$this->assertEquals(
					'unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Table/AttributesUsergroupsTable.php',
					$commands[1]
				);

				$this->assertEquals(
					'unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Entity/AttributesUsergroup.php',
					$commands[2]
				);

				$this->assertEquals('sleep 1', $commands[3]);

				$this->assertEquals(
					sprintf(
						'bin/cake bake migration add_renamed_col_to_attributes_contents renamed_col:%s --folder tests/customer/config/Migrations',
						$colString
					),
					$commands[4]
				);

				return true;
			}),
			$this->callback(function (array $dataArg) use ($data): bool {
				$this->assertSame($data, $dataArg);

				return true;
			}),
			false,
			false
		);

		// Ensure the table and entity files are deleted
		$this->assertFileExists($this->dummyTableFile);
		$this->assertFileExists($this->dummyEntityFile);

		$task->run($data, 1);
	}


	/**
	 * @dataProvider newDataProvider
	 * @param string $type
	 * @param bool $required
	 * @param ?string $defaultValue
	 * @param bool $index
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::run : bool()
	 */
	public function testRunWithDeletedAttribute(string $type, bool $required, ?string $defaultValue, bool $index): void {
		$data = [
			'old' => [
				'scope' => 'contents',
				'identifier' => 'test_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
			'new' => [
				'scope' => 'contents',
				'identifier' => 'test_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => true, // Marked as deleted
			],
		];

		$task = $this->getMockBuilder(UpsertTask::class)->onlyMethods(['createJob'])->getMock();

		$task->expects($this->once())->method('createJob')->with(
			$this->callback(function (array $commands): bool {
				$this->assertCount(1, $commands);

				$this->assertEquals(
					'bin/cake bake migration remove_test_col_from_attributes_contents test_col --folder tests/customer/config/Migrations',
					$commands[0]
				);

				return true;
			}),
			$this->callback(function (array $dataArg) use ($data): bool {
				$this->assertSame($data, $dataArg);

				return true;
			}),
			false,
			true
		);

		$task->run($data, 1);
	}


	/**
	 * @dataProvider newDataProvider
	 * @param string $type
	 * @param bool $required
	 * @param ?string $defaultValue
	 * @param bool $index
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::run : bool()
	 */
	public function testRunWithDeletedAttributeForLastColumnInTable(string $type, bool $required, ?string $defaultValue, bool $index): void {
		$data = [
			'old' => [
				'scope' => 'usergroups',
				'identifier' => 'test_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => false,
			],
			'new' => [
				'scope' => 'usergroups',
				'identifier' => 'test_col',
				'type' => $type,
				'required' => $required,
				'defaultValue' => $defaultValue,
				'hasIndex' => $index,
				'deleted' => true, // Marked as deleted
			],
		];

		$task = $this->getMockBuilder(UpsertTask::class)->onlyMethods(['createJob'])->getMock();

		$task->expects($this->once())->method('createJob')->with(
			$this->callback(function (array $commands): bool {
				$this->assertCount(3, $commands);

				$this->assertEquals(
					'bin/cake bake migration drop_attributes_usergroups --folder tests/customer/config/Migrations',
					$commands[0]
				);

				$this->assertEquals(
					'unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Table/AttributesUsergroupsTable.php',
					$commands[1]
				);

				$this->assertEquals(
					'unlink ' . ROOT . DS . CUSTOM_DIR . DS . 'Model/Entity/AttributesUsergroup.php',
					$commands[2]
				);

				return true;
			}),
			$this->callback(function (array $dataArg) use ($data): bool {
				$this->assertSame($data, $dataArg);

				return true;
			}),
			false,
			false,
		);

		// Ensure the table and entity files are deleted
		$this->assertFileExists($this->dummyTableFile);
		$this->assertFileExists($this->dummyEntityFile);

		$task->run($data, 1);
	}



	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::createJob()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testCreateJobWithEmptyCommands(): void {
		$task = $this->getMockBuilder(UpsertTask::class)->onlyMethods(['createJob'])->getMock();

		// Mock QueuedJobs to ensure it's never called
		$mockQueuedJobs = $this->createMock(QueuedJobsTable::class);
		$mockQueuedJobs->expects($this->never())->method('createJob');
		$task->QueuedJobs = $mockQueuedJobs;

		$data = ['new' => ['scope' => 'contents', 'deleted' => false], 'old' => ['scope' => 'pages']];

		$this->callProtectedMethod($task, 'createJob', [], $data, false, false);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::createJob()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testCreateJobWithNewAttribute(): void {
		$task = new UpsertTask();

		// Mock QueuedJobs to capture the job creation
		$mockQueuedJobs = $this->createMock(QueuedJobsTable::class);
		$mockQueuedJobs->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function (array $jobData): bool {
				$this->assertSame(
					'(bin/cake bake migration create_attributes_contents' .
					' && bin/cake migrations migrate --source ../../tests/customer/config/Migrations --no-lock' .
					' && bin/cake schema_cache clear' .
					' && bin/cake bake model attributes_contents --namespace Customer --no-fixture --no-test --update --force' .
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

		$task->QueuedJobs = $mockQueuedJobs;

		$data = [
			'new' => ['scope' => 'contents', 'deleted' => false],
			'old' => ['scope' => 'pages'],
		];

		$this->callProtectedMethod($task, 'createJob', ['bin/cake bake migration create_attributes_contents'], $data, false, false);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::createJob()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testCreateJobWithDeletedAttribute(): void {
		$task = new UpsertTask();

		$mockQueuedJobs = $this->createMock(QueuedJobsTable::class);
		$mockQueuedJobs->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function (array $jobData): bool {
				$this->assertSame(
					'(bin/cake bake migration remove_test_col_from_attributes_contents' .
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

		$task->QueuedJobs = $mockQueuedJobs;

		$data = [
			'new' => ['scope' => 'contents', 'deleted' => true],
			'old' => ['scope' => 'contents'],
		];

		$this->callProtectedMethod($task, 'createJob', ['bin/cake bake migration remove_test_col_from_attributes_contents'], $data, false, false);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::createJob()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testCreateJobWithPageRoleScope(): void {
		$task = new UpsertTask();

		$mockQueuedJobs = $this->createMock(QueuedJobsTable::class);
		$mockQueuedJobs->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function (array $jobData): bool {
				$this->assertSame(
					'(bin/cake bake migration add_test_to_attributes_news' .
					' && bin/cake migrations migrate --source ../../tests/customer/config/Migrations --no-lock' .
					' && bin/cake schema_cache clear' .
					' && bin/cake bake model attributes_news --namespace Customer --no-fixture --no-test --update --force --for-pagerole news' .
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

		$task->QueuedJobs = $mockQueuedJobs;

		$data = [
			'new' => ['scope' => 'news', 'deleted' => false],
			'old' => ['scope' => 'news'],
		];

		$this->callProtectedMethod($task, 'createJob', ['bin/cake bake migration add_test_to_attributes_news'], $data, true, false);
	}

	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::createJob()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testCreateJobWithBakeOldModel(): void {
		$task = new UpsertTask();

		$mockQueuedJobs = $this->createMock(QueuedJobsTable::class);
		$mockQueuedJobs->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function (array $jobData): bool {
				$this->assertSame(
					'(bin/cake bake migration add_test_to_attributes_contents' .
					' && bin/cake migrations migrate --source ../../tests/customer/config/Migrations --no-lock' .
					' && bin/cake schema_cache clear' .
					' && bin/cake bake model attributes_contents --namespace Customer --no-fixture --no-test --update --force' .
					' && bin/cake bake model attributes_pages --namespace Customer --no-fixture --no-test --update --force --for-pagerole pages' .
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

		$task->QueuedJobs = $mockQueuedJobs;

		$data = [
			'new' => ['scope' => 'contents', 'deleted' => false],
			'old' => ['scope' => 'pages'],
		];

		$this->callProtectedMethod($task, 'createJob', ['bin/cake bake migration add_test_to_attributes_contents'], $data, false, true);
	}

	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Attributes\UpsertTask::createJob()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testCreateJobWithOldPageRoleScopeAndBakeOldModel(): void {
		$task = new UpsertTask();

		$mockQueuedJobs = $this->createMock(QueuedJobsTable::class);
		$mockQueuedJobs->expects($this->once())->method('createJob')->with(
			'Queue.Execute',
			$this->callback(function (array $jobData): bool {
				$this->assertSame(
					'(bin/cake bake migration add_test_to_attributes_products' .
					' && bin/cake migrations migrate --source ../../tests/customer/config/Migrations --no-lock' .
					' && bin/cake schema_cache clear' .
					' && bin/cake bake model attributes_products --namespace Customer --no-fixture --no-test --update --force --for-pagerole products' .
					' && bin/cake bake model attributes_news --namespace Customer --no-fixture --no-test --update --force --for-pagerole news' .
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

		$task->QueuedJobs = $mockQueuedJobs;

		$data = [
			'new' => ['scope' => 'products', 'deleted' => false],
			'old' => ['scope' => 'news'],
		];

		$this->callProtectedMethod($task, 'createJob', ['bin/cake bake migration add_test_to_attributes_products'], $data, true, true);
	}
}
