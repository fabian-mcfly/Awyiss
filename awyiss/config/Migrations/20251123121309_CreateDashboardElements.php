<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;


/**
 * Create dashboard_elements table
 */
class CreateDashboardElements extends BaseMigration {
	public function change(): void {
		$this
			->table('dashboard_elements')
			->addPrimaryKey(['id'])
			->addColumn('scope', 'string', [
				'default' => null,
				'limit' => 50,
				'null' => false,
			])
			->addColumn('title', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => false,
			])
			->addColumn('access', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => true,
			])
			->addColumn('settings', 'text', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('system_order', 'integer', [
				'default' => '0',
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addColumn('active', 'boolean', [
				'default' => true,
				'limit' => null,
				'null' => false,
			])
			->addColumn('deleted', 'boolean', [
				'default' => false,
				'limit' => null,
				'null' => false,
			])
			->addColumn('created_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('created_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('changed_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('changed_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('deleted_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('deleted_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addIndex(
				[
					'active',
				], [
					'name' => 'DASHBOARD_ELEMENTS_ACTIVE',
				]
			)
			->addIndex(
				[
					'deleted',
				], [
					'name' => 'DASHBOARD_ELEMENTS_DELETED',
				]
			)
			->create()
		;
	}
}
