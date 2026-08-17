<?php declare(strict_types=1);

/**
 * Class Contents
 */
class Contents {
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
		if ($this->migration->hasTable('contents')) {
			$this->migration
				->table('contents')
				->drop()
				->save()
			;
		}

		$this->migration
			->table('contents')
			->addColumn('id', 'integer', [
				'autoIncrement' => true,
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addPrimaryKey(['id'])
			->addColumn('page_id', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addColumn('content_area_id', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addColumn('content_template_id', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addColumn('parent_id', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('title', 'string', [
				'default' => null,
				'limit' => 255,
				'null' => true,
			])
			->addColumn('title_tag', 'string', [
				'default' => null,
				'limit' => 2,
				'null' => true,
			])
			->addColumn('subtitle', 'string', [
				'default' => null,
				'limit' => 255,
				'null' => true,
			])
			->addColumn('subtitle_tag', 'string', [
				'default' => null,
				'limit' => 2,
				'null' => true,
			])
			->addColumn('text', 'text', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('link', 'string', [
				'default' => null,
				'limit' => 255,
				'null' => true,
			])
			->addColumn('column_width', 'string', [
				'default' => '1/1',
				'limit' => 5,
				'null' => false,
			])
			->addColumn('column_indent', 'string', [
				'default' => null,
				'limit' => 5,
				'null' => true,
			])
			->addColumn('column_last', 'boolean', [
				'default' => false,
				'limit' => null,
				'null' => false,
			])
			->addColumn('column_rtl', 'boolean', [
				'default' => false,
				'limit' => null,
				'null' => false,
			])
			->addColumn('css_class', 'string', [
				'default' => null,
				'limit' => 255,
				'null' => true,
			])
			->addColumn('duplicate_of', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('data', 'text', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('form_id', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('survey_id', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('system_order', 'integer', [
				'default' => '0',
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addColumn('active', 'boolean', [
				'default' => true,
				'limit' => null,
				'null' => false,
			])
			->addColumn('deleted', 'boolean', [
				'default' => false,
				'limit' => null,
				'null' => false,
			])
			->addColumn('created_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('created_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('changed_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('changed_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('deleted_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('deleted_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addIndex(
				[
					'page_id',
				], [
					'name' => 'CONTENTS_PAGE_ID',
				]
			)
			->addIndex(
				[
					'content_area_id',
				], [
					'name' => 'CONTENTS_CONTENT_AREA_ID',
				]
			)
			->addIndex(
				[
					'content_template_id',
				], [
					'name' => 'CONTENTS_CONTENT_TEMPLATE_ID',
				]
			)
			->addIndex(
				[
					'parent_id',
				], [
					'name' => 'CONTENTS_PARENT_ID',
				]
			)
			->addIndex(
				[
					'form_id',
				], [
					'name' => 'CONTENTS_FORM_ID',
				]
			)
			->addIndex(
				[
					'survey_id',
				], [
					'name' => 'CONTENTS_SURVEY_ID',
				]
			)
			->addIndex(
				[
					'active',
				], [
					'name' => 'CONTENTS_ACTIVE',
				]
			)
			->addIndex(
				[
					'deleted',
				], [
					'name' => 'CONTENTS_DELETED',
				]
			)
			->addIndex(
				[
					'system_order',
				], [
					'name' => 'CONTENTS_SYSTEM_ORDER',
				]
			)
			->addIndex(
				[
					'deleted',
					'system_order',
				], [
					'name' => 'CONTENTS_DELETED_ORDER',
				]
			)
			->create()
		;
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration
			->table('contents')
			->drop()
			->save()
		;
	}
}
