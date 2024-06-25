<?php declare(strict_types=1);


/**
 * Class MediaElementAssignments
 */
class MediaElementAssignments {
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
		$this->migration->table('media_element_assignments')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('media_element_id', 'integer', [
			'limit' => 11,
			'null' => false,
		])->addColumn('scope', 'string', [
			'limit' => 50,
			'null' => false,
		])->addColumn('foreign_key', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addIndex(
			[
				'media_element_id',
			], [
				'name' => 'media_element_id',
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
		$this->migration->table('media_element_assignments')->drop()->save();
	}
}
