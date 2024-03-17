<?php declare(strict_types=1);


/**
 * Class Attributes
 */
class Attributes  {
	/**
	 * @var \Initial $migration The migration that is being migrated
	 */
	protected Initial $migration;
	
	
	/**
	 * Constructor
	 *
	 * @param \Initial $migration The migration that is being migrated
	 */
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
		$this->migration->table('attributes')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('scope', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('identifier', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
		])->addColumn('type', 'string', [
			'default' => 'varchar(255)',
			'limit' => 20,
			'null' => false,
		])->addColumn('has_index', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('fieldset', 'string', [
			'default' => null,
			'limit' => 30,
			'null' => false,
		])->addColumn('input_type', 'string', [
			'default' => 'text',
			'limit' => 30,
			'null' => false,
		])->addColumn('default_value', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => true,
		])->addColumn('required', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('translatable', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('system_order', 'integer', [
			'default' => '0',
			'limit' => null,
			'null' => false,
			'signed' => true,
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
				'name' => 'key',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'active',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'deleted',
			]
		)->addIndex(
			[
				'system_order',
			], [
				'name' => 'system_order',
			]
		)->addIndex(
			[
				'scope',
			], [
				'name' => 'scope',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('attributes')->drop()->save();
	}
}
