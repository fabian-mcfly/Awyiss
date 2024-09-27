<?php declare(strict_types=1);


/**
 * Class MenuEntries
 */
class MenuEntries {
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
		if ($this->migration->hasTable('menu_entries')) {
			$this->migration->table('menu_entries')->drop()->save();
		}

		$this->migration->table('menu_entries')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('menu_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('parent_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('language_shortcode', 'char', [
			'default' => null,
			'limit' => 2,
			'null' => false,
		])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
		])->addColumn('link', 'string', [
			'default' => '',
			'limit' => 255,
			'null' => false,
		])->addColumn('external', 'boolean', [
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
				'menu_id',
			], [
				'name' => 'MENU_ENTRIES_MENU_ID',
			]
		)->addIndex(
			[
				'parent_id',
			], [
				'name' => 'MENU_ENTRIES_PARENT_ID',
			]
		)->addIndex(
			[
				'language_shortcode',
			], [
				'name' => 'MENU_ENTRIES_LANGUAGE_SHORTCODE',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'MENU_ENTRIES_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'MENU_ENTRIES_DELETED',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('menu_entries')->drop()->save();
	}
}
