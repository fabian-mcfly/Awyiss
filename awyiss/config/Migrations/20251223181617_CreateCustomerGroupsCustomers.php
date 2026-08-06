<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;


/**
 * Create customer_groups_customers table
 */
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
