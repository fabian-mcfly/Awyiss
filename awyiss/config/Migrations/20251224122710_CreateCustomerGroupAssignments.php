<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;


/**
 * Create customer_group_assignments table
 */
class CreateCustomerGroupAssignments extends BaseMigration {
	public function change(): void {
		$this->table('customer_group_assignments')->addPrimaryKey(['id'])->addColumn('customer_group_id', 'integer', [
			'limit' => 11,
			'null' => false,
		])->addColumn('scope', 'string', [
			'limit' => 50,
			'null' => false,
		])->addColumn('foreign_key', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('deleted', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('created_by', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('created_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('changed_by', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('changed_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('deleted_by', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('deleted_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addIndex(
			[
				'customer_group_id',
			], [
				'name' => 'CUSTOMER_GROUP_ASSIGNMENTS_CUSTOMER_GROUP_ID',
			]
		)->addIndex(
			[
				'scope',
				'foreign_key',
			], [
				'name' => 'CUSTOMER_GROUP_ASSIGNMENTS_SCOPE_FOREIGN_KEY',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'CUSTOMER_GROUP_ASSIGNMENTS_DELETED',
			]
		)->create();
	}
}
