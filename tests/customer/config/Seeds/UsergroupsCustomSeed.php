<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * Usergroups seed.
 */
class UsergroupsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'title' => 'all access',
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
				'title' => 'no permissions set',
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
				'title' => 'no access',
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => '2001-11-11 11:11:11',
				'changed_by' => 2,
				'changed_on' => '2002-02-22 22:22:22',
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$lo_table = $this->table('usergroups');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
