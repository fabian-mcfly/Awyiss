<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * SurveySurveyQuestions seed.
 */
class SurveySurveyQuestionsCustomSeed extends BaseSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 2,
				'survey_id' => 1,
				'survey_question_id' => 1,
				'identifier' => '8524de5e',
				'title' => null,
				'subtitle' => null,
				'text' => null,
				'next_action' => 'next_question',
				'next_action_target' => null,
				'allow_custom_answer' => 0,
				'custom_answer_title' => null,
				'system_order' => 1,
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
				'survey_id' => 1,
				'survey_question_id' => 2,
				'identifier' => 'f69b1648',
				'title' => null,
				'subtitle' => null,
				'text' => null,
				'next_action' => 'next_question',
				'next_action_target' => null,
				'allow_custom_answer' => 0,
				'custom_answer_title' => null,
				'system_order' => 2,
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
				'id' => 4,
				'survey_id' => 1,
				'survey_question_id' => 3,
				'identifier' => '0194a883',
				'title' => null,
				'subtitle' => null,
				'text' => null,
				'next_action' => 'next_question',
				'next_action_target' => null,
				'allow_custom_answer' => null,
				'custom_answer_title' => null,
				'system_order' => 3,
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
				'survey_id' => 1,
				'survey_question_id' => 4,
				'identifier' => '7d654446',
				'title' => null,
				'subtitle' => null,
				'text' => null,
				'next_action' => 'next_question',
				'next_action_target' => null,
				'allow_custom_answer' => null,
				'custom_answer_title' => null,
				'system_order' => 4,
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
				'id' => 6,
				'survey_id' => 1,
				'survey_question_id' => 5,
				'identifier' => '72054f17',
				'title' => null,
				'subtitle' => null,
				'text' => null,
				'next_action' => 'next_question',
				'next_action_target' => null,
				'allow_custom_answer' => 0,
				'custom_answer_title' => null,
				'system_order' => 5,
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

		$lo_table = $this->table('survey_survey_questions');
		$lo_table->insert($la_data)->save();
	}
}
