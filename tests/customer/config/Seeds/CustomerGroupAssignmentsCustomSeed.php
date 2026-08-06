<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * CustomerGroupAssignments seed.
 */
class CustomerGroupAssignmentsCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'customerGroupId' => 1,
				'scope' => 'Pages',
				'foreignKey' => 1,
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
				'customerGroupId' => 1,
				'scope' => 'Pages',
				'foreignKey' => 2,
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
				'customerGroupId' => 2,
				'scope' => 'Pages',
				'foreignKey' => 1,
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
				'customerGroupId' => 2,
				'scope' => 'Surveys',
				'foreignKey' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => '2001-11-11 11:11:11',
				'changedBy' => 2,
				'changedOn' => '2002-02-22 22:22:22',
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('customer_group_assignments');
		$table->truncate();
		$table->insert($data)->save();
	}
}
