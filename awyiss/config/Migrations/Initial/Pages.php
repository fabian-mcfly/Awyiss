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
		if ($this->migration->hasTable('pages')) {
			$this->migration->table('pages')->drop()->save();
		}

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
			'limit' => 255,
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
		])->addColumn('form_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('survey_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
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
				'page_role_id',
			], [
				'name' => 'PAGES_PAGE_ROLE_ID',
			]
		)->addIndex(
			[
				'page_template_id',
			], [
				'name' => 'PAGES_PAGE_TEMPLATE_ID',
			]
		)->addIndex(
			[
				'parent_id',
			], [
				'name' => 'PAGES_PARENT_ID',
			]
		)->addIndex(
			[
				'language_shortcode',
			], [
				'name' => 'PAGES_LANGUAGE_SHORTCODE',
			]
		)->addIndex(
			[
				'slug',
			], [
				'name' => 'PAGES_SLUG',
			]
		)->addIndex(
			[
				'form_id',
			], [
				'name' => 'PAGES_FORM_ID',
			]
		)->addIndex(
			[
				'survey_id',
			], [
				'name' => 'PAGES_SURVEY_ID',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'PAGES_ACTIVE',
			]
		)->addIndex(
			[
				'parents_active',
			], [
				'name' => 'PAGES_PARENTS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'PAGES_DELETED',
			]
		)->addIndex(
			[
				'system_order',
			], [
				'name' => 'PAGES_SYSTEM_ORDER',
			]
		)->addIndex(
			[
				'deleted',
				'system_order',
			], [
				'name' => 'PAGES_DELETED_ORDER',
			]
		)->create();
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
