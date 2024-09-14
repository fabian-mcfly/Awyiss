<?php declare(strict_types=1);

/**
 * Class EmailTemplates
 */
class EmailTemplates {
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
		$this->migration->table('email_templates')->addColumn('id', 'integer', [
				'autoIncrement' => true,
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])->addPrimaryKey(['id'])->addColumn('title', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => false,
			])->addColumn('text_html', 'text', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])->addColumn('text_plain', 'text', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])->addColumn('file_name', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => false,
			])->addColumn('layout', 'string', [
				'default' => null,
				'limit' => 100,
				'null' => false,
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
					'active',
				], [
					'name' => 'active',
				]
			)->addIndex(
				[
					'deleted',
				], [
					'name' => 'deleted',
				]
			)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('email_templates')->drop()->save();
	}
}
