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
				'scope' => 'Pages',
				'foreignKey' => 1,
				'accessType' => 'specificGroups',
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
				'scope' => 'Pages',
				'foreignKey' => 2,
				'accessType' => 'allGroups',
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
				'scope' => 'Surveys',
				'foreignKey' => 1,
				'accessType' => 'hideOnLogin',
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => '2001-11-11 11:11:11',
				'changedBy' => 2,
				'changedOn' => '2002-02-22 22:22:22',
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('customer_group_access_settings');
		$table->truncate();
		$table->insert($data)->save();
	}
}
