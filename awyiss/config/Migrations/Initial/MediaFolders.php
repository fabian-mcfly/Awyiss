<?php declare(strict_types=1);


/**
 * Class MediaFolders
 */
class MediaFolders {
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
		if ($this->migration->hasTable('media_folders')) {
			$this->migration->table('media_folders')->drop()->save();
		}

		$this->migration->table('media_folders')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('parent_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('language_shortcode', 'char', [
			'default' => null,
			'limit' => 2,
			'null' => true,
		])->addColumn('path', 'string', [
			'default' => null,
			'limit' => 1024,
			'null' => false,
		])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
		])->addColumn('hidden', 'boolean', [
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
		])->addColumn('parents_active', 'boolean', [
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
				'parent_id',
			], [
				'name' => 'MEDIA_FOLDERS_PARENT_ID',
			]
		)->addIndex(
			[
				'language_shortcode',
			], [
				'name' => 'MEDIA_FOLDERS_LANGUAGE_SHORTCODE',
			]
		)->addIndex(
			[
				'path',
			], [
				'name' => 'MEDIA_FOLDERS_PATH',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'MEDIA_FOLDERS_ACTIVE',
			]
		)->addIndex(
			[
				'parents_active',
			], [
				'name' => 'MEDIA_FOLDERS_PARENTS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'MEDIA_FOLDERS_DELETED',
			]
		)->addIndex(
			[
				'system_order',
			], [
				'name' => 'MEDIA_FOLDERS_SYSTEM_ORDER',
			]
		)->addIndex(
			[
				'deleted',
				'system_order',
			], [
				'name' => 'MEDIA_FOLDERS_DELETED_ORDER',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('media_folders')->drop()->save();
	}
}
