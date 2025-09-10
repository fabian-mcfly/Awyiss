<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * SurveyQuestions seed.
 */
class SurveyQuestionsCustomSeed extends BaseSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'type' => 'single_choice',
				'title' => 'Question #1',
				'subtitle' => null,
				'text' => null,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 2,
				'type' => 'multiple_choice',
				'title' => 'Question #2',
				'subtitle' => null,
				'text' => null,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 3,
				'type' => 'info_text',
				'title' => 'Question #3',
				'subtitle' => null,
				'text' => '<p>Info text with inline img tag<br><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p>',
				'active' => 0,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 4,
				'type' => 'free_text',
				'title' => 'Question #4',
				'subtitle' => null,
				'text' => null,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 5,
				'type' => 'single_choice',
				'title' => 'Question #5',
				'subtitle' => null,
				'text' => null,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$lo_table = $this->table('survey_questions');
		$lo_table->insert($la_data)->save();
	}
}
