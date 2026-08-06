<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * Customer Groups seed.
 */
class CustomerGroupsCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'title' => 'Premium',
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
				'title' => 'Standard',
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
				'title' => 'Basic',
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => '2001-11-11 11:11:11',
				'changedBy' => 2,
				'changedOn' => '2002-02-22 22:22:22',
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('customer_groups');
		$table->truncate();
		$table->insert($data)->save();
	}
}
