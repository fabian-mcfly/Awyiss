<?php declare(strict_types=1);

/**
 * Class SurveyQuestions
 */
class SurveyQuestions {
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
		if ($this->migration->hasTable('survey_questions')) {
			$this->migration->table('survey_questions')->drop()->save();
		}

		$this->migration->table('survey_questions')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('type', 'string', [
			'default' => null,
			'limit' => 20,
			'null' => false,
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
				'name' => 'SURVEY_QUESTIONS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'SURVEY_QUESTIONS_DELETED',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('survey_questions')->drop()->save();
	}
}
