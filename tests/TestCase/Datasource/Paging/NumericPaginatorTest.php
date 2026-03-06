<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Datasource\Paging;


use Awyiss\Datasource\Paging\NumericPaginator;
use Awyiss\Test\TestSuite\TestCase;


/**
 * NumericPaginator Test Case
 *
 * @see \Awyiss\Datasource\Paging\NumericPaginator
 */
class NumericPaginatorTest extends TestCase {
	/**
	 * @var \Awyiss\Datasource\Paging\NumericPaginator
	 */
	protected NumericPaginator $paginator;
	/**
	 * @var array
	 */
	protected array $createdUserIds = [];


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->paginator = new NumericPaginator();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		if (!empty($this->createdUserIds)) {
			$table = $this->fetchTable('Users');
			$table->deleteAll(['id IN' => $this->createdUserIds]);
			$this->createdUserIds = [];
		}

		parent::tearDown();
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testPaginateWithStringSortFieldSortsCorrectly(): void {
		$table = $this->fetchTable('Users');
		$this->createTestUsers();

		$result = $this->paginator->paginate($table->find()->where(['id >' => 4]), [
			'limit' => 20,
			'page' => 1,
			'sort' => 'firstname',
			'direction' => 'asc',
		], [
			'sortableFields' => ['firstname', 'lastname', 'email'],
		]);

		$this->assertCount(8, $result);

		$firstnames = array_column($result->toArray(), 'firstname');

		$this->assertSame([
			'Alice',
			'Alice',
			'Bob',
			'Bob',
			'Charlie',
			'Charlie',
			'Diana',
			'Edward',
		], $firstnames);

		$result = $this->paginator->paginate($table->find()->where(['id >' => 4]), [
			'limit' => 20,
			'page' => 1,
			'sort' => 'lastname',
			'direction' => 'desc',
		], [
			'sortableFields' => ['firstname', 'lastname', 'email'],
		]);

		$lastnames = array_column($result->toArray(), 'lastname');
		$this->assertSame([
			'Wilson',
			'White',
			'Smith',
			'Miller',
			'Johnson',
			'Green',
			'Davis',
			'Brown',
		], $lastnames);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testPaginateWithArraySortFieldUsesFirstFieldForSorting(): void {
		$table = $this->fetchTable('Users');
		$this->createTestUsers();

		$result = $this->paginator->paginate($table->find()->where(['id >' => 4]), [
			'limit' => 20,
			'page' => 1,
			'sort' => ['firstname', 'lastname'],
			'direction' => 'asc',
		], [
			'sortableFields' => ['firstname', 'lastname', 'email'],
		]);

		$this->assertCount(8, $result);

		$firstnames = array_column($result->toArray(), 'firstname');

		$this->assertSame([
			'Alice',
			'Alice',
			'Bob',
			'Bob',
			'Charlie',
			'Charlie',
			'Diana',
			'Edward',
		], $firstnames);

		$users = $result->toArray();
		$aliceUsers = array_filter($users, fn ($user) => $user['firstname'] === 'Alice');
		$aliceLastnames = array_column($aliceUsers, 'lastname');
		$this->assertSame(['Johnson', 'Smith'], $aliceLastnames);

		$bobUsers = array_filter($users, fn ($user) => $user['firstname'] === 'Bob');
		$bobLastnames = array_column($bobUsers, 'lastname');
		$this->assertSame(['Brown', 'Wilson'], $bobLastnames);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testPaginateWithCoalesceOrderStructure(): void {
		$table = $this->fetchTable('Users');

		$testUsers = [
			$table->newDefaultEntity([
				'firstname' => null,
				'lastname' => 'Zulu',
				'email' => 'zulu@example.com',
				'username' => 'zulu',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Alice',
				'lastname' => 'Johnson',
				'email' => 'alice.johnson@example.com',
				'username' => 'alice.johnson',
			]),
			$table->newDefaultEntity([
				'firstname' => null,
				'lastname' => 'Alpha',
				'email' => 'alpha@example.com',
				'username' => 'alpha',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Bob',
				'lastname' => 'Smith',
				'email' => 'bob.smith@example.com',
				'username' => 'bob.smith',
			]),
			$table->newDefaultEntity([
				'firstname' => null,
				'lastname' => 'Beta',
				'email' => 'beta@example.com',
				'username' => 'beta',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Charlie',
				'lastname' => 'Brown',
				'email' => 'charlie.brown@example.com',
				'username' => 'charlie.brown',
			]),
		];

		$result = $table->saveMany($testUsers);

		$this->assertNotFalse($result);

		foreach ($testUsers as $entity) {
			$this->createdUserIds[] = $entity->id;
		}

		$result = $this->paginator->paginate($table->find()->where(['id >' => 4]), [
			'limit' => 20,
			'page' => 1,
			'sort' => ['firstname', 'lastname'],
			'direction' => 'asc',
		], [
			'sortableFields' => ['firstname', 'lastname', 'email'],
		]);

		$values = [];
		foreach ($result as $row) {
			$values[] = $row->firstname . ' ' . $row->lastname;
		}

		$this->assertSame([
			'Alice Johnson',
			' Alpha',
			' Beta',
			'Bob Smith',
			'Charlie Brown',
			' Zulu',
		], $values);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testPaginateWithSortableFieldsValidation(): void {
		$table = $this->fetchTable('Users');
		$this->createTestUsers();

		$result = $this->paginator->paginate($table->find()->where(['id >' => 4]), [
			'limit' => 20,
			'page' => 1,
			'sort' => 'firstname',
			'direction' => 'desc',
		], [
			'sortableFields' => ['firstname', 'lastname', 'email'],
		]);

		$this->assertCount(8, $result);

		$firstnames = array_column($result->toArray(), 'firstname');
		$this->assertSame([
			'Edward',
			'Diana',
			'Charlie',
			'Charlie',
			'Bob',
			'Bob',
			'Alice',
			'Alice',
		], $firstnames);

		$result = $this->paginator->paginate($table->find()->where(['id >' => 4]), [
			'limit' => 20,
			'page' => 1,
			'sort' => 'invalid_field',
			'direction' => 'desc',
		], [
			'sortableFields' => ['firstname', 'lastname', 'email'],
		]);

		$this->assertCount(8, $result->toArray());

		$firstnames = array_column($result->toArray(), 'firstname');
		$this->assertSame([
			'Alice',
			'Alice',
			'Bob',
			'Bob',
			'Charlie',
			'Charlie',
			'Diana',
			'Edward',
		], $firstnames);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testPaginateWithArraySortFieldAndSortableFieldsValidation(): void {
		$table = $this->fetchTable('Users');
		$this->createTestUsers();

		$result = $this->paginator->paginate($table->find()->where(['id >' => 4]), [
			'limit' => 20,
			'page' => 1,
			'sort' => ['lastname', 'firstname'],
			'direction' => 'asc',
		], [
			'sortableFields' => ['firstname', 'lastname', 'email'],
		]);

		$this->assertCount(8, $result);

		$firstnames = array_column($result->toArray(), 'firstname', 'lastname');
		$this->assertSame([
			'Brown' => 'Bob',
			'Davis' => 'Charlie',
			'Green' => 'Diana',
			'Johnson' => 'Alice',
			'Miller' => 'Charlie',
			'Smith' => 'Alice',
			'White' => 'Edward',
			'Wilson' => 'Bob',
		], $firstnames);

		$users = $result->toArray();
		$aliceUsers = array_filter($users, fn ($user) => $user['firstname'] === 'Alice');
		$aliceLastnames = array_column($aliceUsers, 'lastname');
		$this->assertSame(['Johnson', 'Smith'], $aliceLastnames);

		$result = $this->paginator->paginate($table->find()->where(['id >' => 4]), [
			'limit' => 20,
			'page' => 1,
			'sort' => ['invalidField', 'firstname'],
			'direction' => 'asc',
		], [
			'sortableFields' => ['firstname', 'lastname', 'email'],
		]);

		$this->assertCount(8, $result->toArray());

		$firstnames = array_column($result->toArray(), 'firstname', 'lastname');
		$this->assertSame([
			'Johnson' => 'Alice',
			'Smith' => 'Alice',
			'Brown' => 'Bob',
			'Wilson' => 'Bob',
			'Davis' => 'Charlie',
			'Miller' => 'Charlie',
			'Green' => 'Diana',
			'White' => 'Edward',
		], $firstnames);
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function createTestUsers(): void {
		$table = $this->fetchTable('Users');

		$testUsers = [
			$table->newDefaultEntity([
				'firstname' => 'Alice',
				'lastname' => 'Johnson',
				'email' => 'alice.johnson@example.com',
				'username' => 'alice.johnson',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Alice',
				'lastname' => 'Smith',
				'email' => 'alice.smith@example.com',
				'username' => 'alice.smith',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Bob',
				'lastname' => 'Brown',
				'email' => 'bob.brown@example.com',
				'username' => 'bob.brown',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Bob',
				'lastname' => 'Wilson',
				'email' => 'bob.wilson@example.com',
				'username' => 'bob.wilson',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Charlie',
				'lastname' => 'Davis',
				'email' => 'charlie.davis@example.com',
				'username' => 'charlie.davis',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Charlie',
				'lastname' => 'Miller',
				'email' => 'charlie.miller@example.com',
				'username' => 'charlie.miller',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Diana',
				'lastname' => 'Green',
				'email' => 'diana.green@example.com',
				'username' => 'diana.green',
			]),
			$table->newDefaultEntity([
				'firstname' => 'Edward',
				'lastname' => 'White',
				'email' => 'edward.white@example.com',
				'username' => 'edward.white',
			]),
		];

		$result = $table->saveMany($testUsers);

		$this->assertNotFalse($result);

		foreach ($result as $entity) {
			$this->createdUserIds[] = $entity->id;
		}
	}
}
