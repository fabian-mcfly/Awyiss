<?php declare(strict_types=1);

/**
 * Class Designs
 */
class Designs {
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
		$this->migration->table('designs')->addColumn('id', 'integer', [
				'autoIncrement' => true,
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])->addPrimaryKey(['id'])->addColumn('identifier', 'char', [
				'default' => null,
				'limit' => 12,
				'null' => false,
			])->addColumn('title', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => false,
			])->addColumn('description', 'string', [
				'default' => null,
				'limit' => 255,
				'null' => true,
			])->addColumn('settings', 'text', [
				'default' => null,
				'limit' => 4294967295,
				'null' => true,
			])->addColumn('css', 'text', [
				'default' => null,
				'limit' => 4294967295,
				'null' => true,
			])->addColumn('in_use', 'boolean', [
				'default' => false,
				'limit' => null,
				'null' => false,
			])->addColumn('is_preview', 'boolean', [
				'default' => false,
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
				'name' => 'identifier',
			]
		)->addIndex(
			[
				'in_use',
			], [
				'name' => 'in_use',
			]
		)->addIndex(
			[
				'is_preview',
			], [
				'name' => 'is_preview',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'deleted',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('designs')->drop()->save();
	}
}
