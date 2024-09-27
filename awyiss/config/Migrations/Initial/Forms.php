<?php declare(strict_types=1);

/**
 * Class Forms
 */
class Forms {
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
		if ($this->migration->hasTable('forms')) {
			$this->migration->table('forms')->drop()->save();
		}

		$this->migration->table('forms')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
		])->addColumn('identifier', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('send_email', 'boolean', [
			'default' => true,
			'limit' => null,
			'null' => false,
		])->addColumn('email_template_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('send_confirmation_email', 'boolean', [
			'default' => true,
			'limit' => null,
			'null' => false,
		])->addColumn('confirmation_email_template_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('owner_email', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('owner_name', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('user_email', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('user_name', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('cc', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('bcc', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('subject', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('subject_confirmation', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('salutation', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('salutation_confirmation', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('success_message', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('multistep', 'boolean', [
			'default' => false,
			'limit' => null,
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
				'identifier',
			], [
				'name' => 'FORMS_IDENTIFIER',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'FORMS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'FORMS_DELETED',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('forms')->drop()->save();
	}
}
