<?php declare(strict_types=1);

/**
 * Class SurveyEntries
 */
class SurveyEntries {
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
		if ($this->migration->hasTable('survey_entries')) {
			$this->migration->table('survey_entries')->drop()->save();
		}

		$this->migration->table('survey_entries')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('survey_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
		])->addColumn('page_id', 'integer', [
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
			'null' => false,
		])->addColumn('identifier', 'char', [
			'default' => null,
			'limit' => 40,
			'null' => false,
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
				'survey_id',
			], [
				'name' => 'SURVEY_ENTRIES_SURVEY_ID',
			]
		)->addIndex(
			[
				'identifier',
			], [
				'name' => 'SURVEY_ENTRIES_IDENTIFIER',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'SURVEY_ENTRIES_DELETED',
			]
		)->addIndex(
			[
				'created_on',
			], [
				'name' => 'SURVEY_ENTRIES_CREATED_ON',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('survey_entries')->drop()->save();
	}
}
