<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * Users seed.
 */
class UsersCustomSeed extends AbstractSeed {
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
				'created_by' => 1,
				'created_on' => '2001-11-11 11:11:11',
				'changed_by' => 2,
				'changed_on' => '2002-02-22 22:22:22',
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 2,
				'username' => 'awyiss-undecided-access',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => '2001-11-11 11:11:11',
				'changed_by' => 2,
				'changed_on' => '2002-02-22 22:22:22',
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 3,
				'username' => 'awyiss-no-access',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => '2001-11-11 11:11:11',
				'changed_by' => 2,
				'changed_on' => '2002-02-22 22:22:22',
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 4,
				'username' => 'awyiss-inactive',
				'password' => '$2y$10$ocnuM5.0YcGuKPCzwSCVneEAJFM5kH5r9o/1Vn/MaIVUmkPFt1zca',
				'active' => 0,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => '2001-11-11 11:11:11',
				'changed_by' => 2,
				'changed_on' => '2002-02-22 22:22:22',
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$table = $this->table('users');
		$table->truncate();
		$table->insert($data)->save();
	}
}
