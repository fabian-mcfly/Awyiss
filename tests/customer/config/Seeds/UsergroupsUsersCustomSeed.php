<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * UsergroupsUsers seed.
 */
class UsergroupsUsersCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'usergroup_id' => 1,
				'user_id' => 1,
			],
			[
				'id' => 2,
				'usergroup_id' => 2,
				'user_id' => 1,
			],
			[
				'id' => 3,
				'usergroup_id' => 2,
				'user_id' => 2,
			],
			[
				'id' => 4,
				'usergroup_id' => 2,
				'user_id' => 3,
			],
			[
				'id' => 5,
				'usergroup_id' => 3,
				'user_id' => 3,
			],
		];

		$lo_table = $this->table('usergroups_users');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
