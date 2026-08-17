<?php declare(strict_types=1);

/**
 * Class FormElements
 */
class FormElements {
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
		if ($this->migration->hasTable('form_elements')) {
			$this->migration
				->table('form_elements')
				->drop()
				->save()
			;
		}

		$this->migration
			->table('form_elements')
			->addColumn('id', 'integer', [
				'autoIncrement' => true,
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addPrimaryKey(['id'])
			->addColumn('form_id', 'integer', [
				'default' => '0',
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
			->addColumn('type', 'string', [
				'default' => 'text',
				'limit' => 20,
				'null' => false,
			])
			->addColumn('identifier', 'string', [
				'default' => null,
				'limit' => 50,
				'null' => true,
			])
			->addColumn('title', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => true,
			])
			->addColumn('title_email', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => true,
			])
			->addColumn('placeholder', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => true,
			])
			->addColumn('text', 'text', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('options', 'text', [
				'default' => null,
				'limit' => null,
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
			->addColumn('required', 'boolean', [
				'default' => false,
				'limit' => null,
				'null' => false,
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
					'parent_id',
				], [
					'name' => 'FORM_ELEMENTS_PARENT_ID',
				]
			)
			->addIndex(
				[
					'active',
				], [
					'name' => 'FORM_ELEMENTS_ACTIVE',
				]
			)
			->addIndex(
				[
					'deleted',
				], [
					'name' => 'FORM_ELEMENTS_DELETED',
				]
			)
			->addIndex(
				[
					'system_order',
				], [
					'name' => 'FORM_ELEMENTS_SYSTEM_ORDER',
				]
			)
			->addIndex(
				[
					'form_id',
				], [
					'name' => 'FORM_ELEMENTS_FORM_ID',
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
			->table('form_elements')
			->drop()
			->save()
		;
	}
}
