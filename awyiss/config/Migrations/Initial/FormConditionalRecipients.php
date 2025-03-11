<?php declare(strict_types=1);


/**
 * Class FormConditionalRecipients
 */
class FormConditionalRecipients {
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
		if ($this->migration->hasTable('form_conditional_recipients')) {
			$this->migration->table('form_conditional_recipients')->drop()->save();
		}

		$this->migration->table('form_conditional_recipients')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('form_id', 'integer', [
			'default' => '0',
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('type', 'string', [
			'default' => null,
			'limit' => 20,
			'null' => false,
		])->addColumn('field', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => true,
		])->addColumn('operator', 'string', [
			'default' => null,
			'limit' => 30,
			'null' => false,
		])->addColumn('value', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('recipient', 'string', [
			'default' => null,
			'limit' => 255,
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
		])->addIndex(
			[
				'form_id',
			], [
				'name' => 'FORM_CONDITIONAL_RECIPIENTS_FORM_ID',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('form_conditional_recipients')->drop()->save();
	}
}
