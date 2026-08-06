<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;


/**
 * Create customers table
 */
class CreateCustomers extends BaseMigration {
	public function change(): void {
		$this->table('customers')->addPrimaryKey(['id'])->addColumn('email', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('password', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('firstname', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('lastname', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('last_login', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('failed_attempts', 'integer', [
			'default' => '0',
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('verified', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('verified_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('verification_code', 'char', [
			'default' => null,
			'limit' => 64,
			'null' => true,
		])->addColumn('password_reset_code', 'char', [
			'default' => null,
			'limit' => 64,
			'null' => true,
		])->addColumn('password_reset_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('active', 'boolean', [
			'default' => true,
			'limit' => null,
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
				'email',
			], [
				'name' => 'CUSTOMERS_EMAIL',
			]
		)->addIndex(
			[
				'failed_attempts',
			], [
				'name' => 'CUSTOMERS_FAILED_ATTEMPTS',
			]
		)->addIndex(
			[
				'verified',
			], [
				'name' => 'CUSTOMERS_VERIFIED',
			]
		)->addIndex(
			[
				'verified_on',
			], [
				'name' => 'CUSTOMERS_VERIFIED_ON',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'CUSTOMERS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'CUSTOMERS_DELETED',
			]
		)->create();
	}
}
