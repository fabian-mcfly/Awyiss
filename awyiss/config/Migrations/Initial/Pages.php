<?php declare(strict_types=1);


/**
 * Class Pages
 */
class Pages {
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
		$this->migration->table('pages')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('page_role_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('page_template_id', 'integer', [
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
		])->addColumn('slug', 'string', [
			'default' => null,
			'limit' => 1024,
			'null' => false,
		])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
		])->addColumn('redirect_link', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('meta_title', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => true,
		])->addColumn('meta_description', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('robots_index', 'boolean', [
			'default' => true,
			'limit' => null,
			'null' => false,
		])->addColumn('robots_follow', 'boolean', [
			'default' => true,
			'limit' => null,
			'null' => false,
		])->addColumn('duplicate_of', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
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
		])->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('pages')->drop()->save();
	}
}
