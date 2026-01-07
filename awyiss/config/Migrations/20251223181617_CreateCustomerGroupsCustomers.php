<?php declare(strict_types=1);


use Migrations\BaseMigration;


class CreateCustomerGroupsCustomers extends BaseMigration {
	public function change(): void {
		$this->table('customer_groups_customers')->addPrimaryKey(['id'])->addColumn('customer_group_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('customer_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addIndex(
			[
				'customer_group_id',
			], [
				'name' => 'CUSTOMER_GROUPS_CUSTOMERS_CUSTOMER_GROUP_ID',
			]
		)->addIndex(
			[
				'customer_id',
			], [
				'name' => 'CUSTOMER_GROUPS_CUSTOMERS_CUSTOMER_ID',
			]
		)->create();
	}
}
