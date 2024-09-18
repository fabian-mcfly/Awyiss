<?php declare(strict_types=1);


/**
 * Class Datatables
 */
class Datatables {
	/**
	 * @var \Initial $migration The migration that is being migrated
	 */
	protected Initial $migration;


	/**
	 * Constructor
	 *
	 * @param \Initial $migration The migration that is being migrated
	 */
	public function __construct(Initial $migration) {
		$this->migration = $migration;
	}


	/**
	 * Migrate Up.
	 * 
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->migration->table('datatables')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
		])->addColumn('identifier', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
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
				'identifier',
			], [
				'name' => 'DATATABLES_IDENTIFIER',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'DATATABLES_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'DATATABLES_DELETED',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('datatables')->drop()->save();
	}
}
