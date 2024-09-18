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
		])->addPrimaryKey(['id'])->addColumn('media_element_id', 'integer', [
			'limit' => 11,
			'null' => false,
		])->addColumn('media_element_selector_identifier', 'string', [
			'limit' => 50,
			'null' => false,
		])->addColumn('media_id', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('media_folder_id', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
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
				'media_element_id',
			], [
				'name' => 'MEDIA_ASSIGNMENTS_MEDIA_ELEMENT_ID',
			]
		)->addIndex(
			[
				'media_element_selector_identifier',
			], [
				'name' => 'MEDIA_ASSIGNMENTS_MEDIA_ELEMENT_SELECTOR_IDENTIFIER',
			]
		)->addIndex(
			[
				'media_element_id',
				'media_element_selector_identifier',
			], [
				'name' => 'MEDIA_ASSIGNMENTS_MEDIA_ELEMENT_MEDIA_ELEMENT_SELECTOR',
			]
		)->addIndex(
			[
				'scope',
				'foreign_key',
			], [
				'name' => 'MEDIA_ASSIGNMENTS_SCOPE_FOREIGN_KEY',
			]
		)->addIndex(
			[
				'media_id',
			], [
				'name' => 'MEDIA_ASSIGNMENTS_MEDIA_ID',
			]
		)->addIndex(
			[
				'media_folder_id',
			], [
				'name' => 'MEDIA_ASSIGNMENTS_MEDIA_FOLDER_ID',
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
