<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;


/**
 * Create customer_group_access_settings table
 */
class CreateCustomerGroupAccessSettings extends BaseMigration {
	public function change(): void {
		$this->table('customer_group_access_settings')->addPrimaryKey(['id'])->addColumn('scope', 'string', [
			'limit' => 50,
			'null' => false,
		])->addColumn('foreign_key', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('access_type', 'string', [
			'default' => null,
			'limit' => 20,
			'null' => false,
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
				'scope',
				'foreign_key',
			], [
				'name' => 'CUSTOMER_GROUP_ACCESS_SETTINGS_SCOPE_FOREIGN_KEY',
			]
		)->addIndex(
			[
				'access_type',
			], [
				'name' => 'CUSTOMER_GROUP_ACCESS_SETTINGS_ACCESS_TYPE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'CUSTOMER_GROUP_ACCESS_SETTINGS_DELETED',
			]
		)->create();
	}
}
