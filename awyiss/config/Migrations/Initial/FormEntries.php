<?php declare(strict_types=1);

/**
 * Class FormEntries
 */
class FormEntries {
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
		$this->migration->table('form_entries')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('form_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('subject', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('subject_confirmation', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('body', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('body_confirmation', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('data', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('ip_hash', 'char', [
			'default' => null,
			'limit' => 40,
			'null' => false,
		])->addColumn('post_hash', 'char', [
			'default' => null,
			'limit' => 40,
			'null' => true,
		])->addColumn('deleted', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('created_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => false,
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
				'form_id',
			], [
				'name' => 'FORM_ENTRIES_FORM_ID',
			]
		)->addIndex(
			[
				'ip_hash',
			], [
				'name' => 'FORM_ENTRIES_IP_HASH',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'FORM_ENTRIES_DELETED',
			]
		)->addIndex(
			[
				'created_on',
			], [
				'name' => 'FORM_ENTRIES_CREATED_ON',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('form_entries')->drop()->save();
	}
}
