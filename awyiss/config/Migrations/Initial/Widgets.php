<?php declare(strict_types=1);

/**
 * Class Widgets
 */
class Widgets {
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
		$this->migration->table('widgets')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('identifier', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('widget_template_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('parent_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('subtitle', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('text', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('link', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('column_width', 'string', [
			'default' => '1/1',
			'limit' => 5,
			'null' => false,
		])->addColumn('column_indent', 'string', [
			'default' => null,
			'limit' => 5,
			'null' => true,
		])->addColumn('column_last', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('column_rtl', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('css_class', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('data', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('form_id', 'integer', [
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
				'name' => 'WIDGETS_IDENTIFIER',
			]
		)->addIndex(
			[
				'widget_template_id',
			], [
				'name' => 'WIDGETS_WIDGET_TEMPLATE_ID',
			]
		)->addIndex(
			[
				'parent_id',
			], [
				'name' => 'WIDGETS_PARENT_ID',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'WIDGETS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'WIDGETS_DELETED',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('widgets')->drop()->save();
	}
}
