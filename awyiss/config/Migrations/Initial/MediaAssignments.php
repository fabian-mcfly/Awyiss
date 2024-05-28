<?php declare(strict_types=1);

/**
 * Class MediaAssignments
 */
class MediaAssignments {
	/**
	 * @var \Initial $migration The migration that is being migrated
	 */
	protected \Initial $migration;


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
		$this->migration->table('media_assignments')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('media_composite_id', 'integer', [
			'limit' => 11,
			'null' => false,
		])->addColumn('media_composite_selector_identifier', 'string', [
			'limit' => 50,
			'null' => false,
		])->addColumn('media_id', 'integer', [
			'limit' => 11,
			'null' => false,
		])->addColumn('scope', 'string', [
			'limit' => 50,
			'null' => false,
		])->addColumn('foreign_key', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('system_order', 'integer', [
			'default' => '0',
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('deleted', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addIndex(
			[
				'media_composite_id',
			], [
				'name' => 'media_composite_id',
			]
		)->addIndex(
			[
				'scope',
				'foreign_key',
			], [
				'name' => 'scope_foreign_key',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('media_assignments')->drop()->save();
	}
}
