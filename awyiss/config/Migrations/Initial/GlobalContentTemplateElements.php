<?php declare(strict_types=1);

/**
 * Class GlobalContentTemplateElements
 */
class GlobalContentTemplateElements {
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
		if ($this->migration->hasTable('global_content_template_elements')) {
			$this->migration
				->table('global_content_template_elements')
				->drop()
				->save()
			;
		}

		$this->migration
			->table('global_content_template_elements')
			->addColumn('id', 'integer', [
				'autoIncrement' => true,
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addPrimaryKey(['id'])
			->addColumn('global_content_template_id', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addColumn('identifier', 'string', [
				'default' => null,
				'limit' => 61,
				'null' => false,
			])
			->addColumn('title', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => true,
			])
			->addColumn('fieldset', 'string', [
				'default' => '',
				'limit' => 50,
				'null' => false,
			])
			->addColumn('column_span', 'string', [
				'default' => '12/12',
				'limit' => 5,
				'null' => false,
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
			->addIndex(
				[
					'global_content_template_id',
				], [
					'name' => 'global_content_TEMPLATE_ELEMENTS_global_content_TEMPLATE_ID',
				]
			)
			->addIndex(
				[
					'identifier',
				], [
					'name' => 'global_content_TEMPLATE_ELEMENTS_IDENTIFIER',
				]
			)
			->addIndex(
				[
					'fieldset',
				], [
					'name' => 'global_content_TEMPLATE_ELEMENTS_FIELDSET',
				]
			)
			->addIndex(
				[
					'system_order',
				], [
					'name' => 'global_content_TEMPLATE_ELEMENTS_SYSTEM_ORDER',
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
			->table('global_content_template_elements')
			->drop()
			->save()
		;
	}
}
