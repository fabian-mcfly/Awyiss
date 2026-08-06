<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * Customers seed.
 */
class CustomersCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'email' => 'customer1@example.com',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
				'firstname' => 'Test',
				'lastname' => 'Customer',
				'verified' => 1,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => '2001-11-11 11:11:11',
				'changedBy' => 2,
				'changedOn' => '2002-02-22 22:22:22',
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 2,
				'email' => 'customer2@example.com',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
				'firstname' => 'Another',
				'lastname' => 'Customer',
				'verified' => 1,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => '2001-11-11 11:11:11',
				'changedBy' => 2,
				'changedOn' => '2002-02-22 22:22:22',
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 3,
				'email' => 'customer3@example.com',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
				'firstname' => 'Inactive',
				'lastname' => 'Customer',
				'verified' => 0,
				'active' => 0,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => '2001-11-11 11:11:11',
				'changedBy' => 2,
				'changedOn' => '2002-02-22 22:22:22',
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('customers');
		$table->truncate();
		$table->insert($data)->save();
	}
}
