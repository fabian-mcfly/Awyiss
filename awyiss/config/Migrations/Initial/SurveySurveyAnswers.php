<?php declare(strict_types=1);

/**
 * Class SurveySurveyAnswers
 */
class SurveySurveyAnswers {
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
		if ($this->migration->hasTable('survey_survey_answers')) {
			$this->migration->table('survey_survey_answers')->drop()->save();
		}

		$this->migration->table('survey_survey_answers')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('survey_answer_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('survey_survey_question_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
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
		])->addColumn('next_action', 'string', [
			'default' => null,
			'limit' => 20,
			'null' => true,
		])->addColumn('next_action_target', 'string', [
			'default' => null,
			'limit' => 20,
			'null' => true,
		])->addColumn('system_order', 'integer', [
			'default' => '0',
			'limit' => null,
			'null' => true,
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
				'survey_answer_id',
			], [
				'name' => 'SURVEY_SURVEY_ANSWERS_SURVEY_ANSWER_ID',
			]
		)->addIndex(
			[
				'survey_survey_question_id',
			], [
				'name' => 'SURVEY_SURVEY_ANSWERS_SURVEY_SURVEY_QUESTION_ID',
			]
		)->addIndex(
			[
				'system_order',
			], [
				'name' => 'SURVEY_SURVEY_ANSWERS_SYSTEM_ORDER',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'SURVEY_SURVEY_ANSWERS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'SURVEY_SURVEY_ANSWERS_DELETED',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('survey_survey_answers')->drop()->save();
	}
}
