<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * Users seed.
 */
class UsersCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'username' => 'awyiss',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
				'email' => 'hello@2f.media',
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
				'username' => 'awyiss-undecided-access',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
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
				'username' => 'awyiss-no-access',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
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
				'id' => 4,
				'username' => 'awyiss-inactive',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
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

		$table = $this->table('users');
		$table->truncate();
		$table->insert($data)->save();
	}
}
