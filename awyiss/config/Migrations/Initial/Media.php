<?php declare(strict_types=1);


/**
 * Class Media
 */
class Media {
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
		$this->migration->table('media')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('media_folder_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('mime_type', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
		])->addColumn('name', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
		])->addColumn('path', 'string', [
			'default' => null,
			'limit' => 1124,
			'null' => false,
		])->addColumn('alt', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('width', 'float', [
			'default' => null,
			'null' => true,
			'precision' => 10,
			'scale' => 5,
			'signed' => true,
		])->addColumn('height', 'float', [
			'default' => null,
			'null' => true,
			'precision' => 10,
			'scale' => 5,
			'signed' => true,
		])->addColumn('meta_data', 'text', [
			'collation' => 'utf8mb4_bin',
			'default' => null,
			'limit' => 4294967295,
			'null' => true,
		])->addColumn('average_color', 'char', [
			'default' => null,
			'limit' => 8,
			'null' => true,
		])->addColumn('preview', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('webp', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('crop', 'text', [
			'default' => null,
			'limit' => 16777215,
			'null' => true,
		])->addColumn('focus_point', 'char', [
			'default' => null,
			'limit' => 3,
			'null' => true,
		])->addColumn('system_order', 'integer', [
			'default' => '0',
			'limit' => null,
			'null' => false,
			'signed' => true,
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
				'name',
			], [
				'name' => 'file_name',
			]
		)->addIndex(
			[
				'path',
			], [
				'name' => 'file_path',
			]
		)->addIndex(
			[
				'media_folder_id',
			], [
				'name' => 'media_folders_id',
			]
		)->addIndex(
			[
				'preview',
			], [
				'name' => 'preview',
			]
		)->addIndex(
			[
				'webp',
			], [
				'name' => 'webp',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('media')->drop()->save();
	}
}
