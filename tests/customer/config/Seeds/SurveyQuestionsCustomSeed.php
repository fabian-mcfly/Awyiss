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
		$data = [
			[
				'id' => 1,
				'type' => 'singleChoice',
				'title' => 'Question #1',
				'subtitle' => null,
				'text' => null,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 2,
				'type' => 'multipleChoice',
				'title' => 'Question #2',
				'subtitle' => null,
				'text' => null,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 3,
				'type' => 'infoText',
				'title' => 'Question #3',
				'subtitle' => null,
				'text' => '<p>Info text with inline img tag<br><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p>',
				'active' => 0,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 4,
				'type' => 'freeText',
				'title' => 'Question #4',
				'subtitle' => null,
				'text' => null,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 5,
				'type' => 'singleChoice',
				'title' => 'Question #5',
				'subtitle' => null,
				'text' => null,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('survey_questions');
		$table->insert($data)->save();
	}
}
