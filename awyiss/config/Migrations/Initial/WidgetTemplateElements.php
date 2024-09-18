<?php declare(strict_types=1);


/**
 * Class WidgetTemplateElements
 */
class WidgetTemplateElements {
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
		$this->migration->table('widget_template_elements')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('widget_template_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('identifier', 'string', [
			'default' => null,
			'limit' => 61,
			'null' => false,
		])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => true,
		])->addColumn('fieldset', 'string', [
			'default' => '',
			'limit' => 50,
			'null' => false,
		])->addColumn('column_span', 'string', [
			'default' => '12/12',
			'limit' => 5,
			'null' => false,
		])->addColumn('required', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('system_order', 'integer', [
			'default' => '0',
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addIndex(
			[
				'widget_template_id',
			], [
				'name' => 'WIDGET_TEMPLATE_ELEMENTS_WIDGET_TEMPLATE_ID',
			]
		)->addIndex(
			[
				'identifier',
			], [
				'name' => 'WIDGET_TEMPLATE_ELEMENTS_IDENTIFIER',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('widget_template_elements')->drop()->save();
	}
}
