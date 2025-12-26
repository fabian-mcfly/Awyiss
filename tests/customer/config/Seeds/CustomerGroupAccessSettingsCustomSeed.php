<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * CustomerGroupAccessSettings seed.
 */
class CustomerGroupAccessSettingsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'scope' => 'pages',
				'foreign_key' => 1,
				'access_type' => 'specific_groups',
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
				'scope' => 'pages',
				'foreign_key' => 2,
				'access_type' => 'all_groups',
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
				'scope' => 'surveys',
				'foreign_key' => 1,
				'access_type' => 'hide_on_login',
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => '2001-11-11 11:11:11',
				'changed_by' => 2,
				'changed_on' => '2002-02-22 22:22:22',
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$table = $this->table('customer_group_access_settings');
		$table->truncate();
		$table->insert($data)->save();
	}
}
