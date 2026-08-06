<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * UsergroupsUsers seed.
 */
class UsergroupsUsersCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'usergroupId' => 1,
				'userId' => 1,
			],
			[
				'id' => 2,
				'usergroupId' => 2,
				'userId' => 1,
			],
			[
				'id' => 3,
				'usergroupId' => 2,
				'userId' => 2,
			],
			[
				'id' => 4,
				'usergroupId' => 2,
				'userId' => 3,
			],
			[
				'id' => 5,
				'usergroupId' => 3,
				'userId' => 3,
			],
		];

		$table = $this->table('usergroups_users');
		$table->truncate();
		$table->insert($data)->save();
	}
}
