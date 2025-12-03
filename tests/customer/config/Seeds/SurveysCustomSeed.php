<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * Surveys seed.
 */
class SurveysCustomSeed extends BaseSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'type' => 'configurator',
				'title' => 'Dummy Survey',
				'identifier' => 'dummy_survey',
				'success_message' => '<p>Success</p>',
				'failure_message' => '<p>Failure</p>',
				'final_action' => 'save_and_end',
				'form_id' => 1,
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
				'type' => 'configurator',
				'title' => 'Dummy Survey (Inactive)',
				'identifier' => 'dummy_survey2',
				'success_message' => '<p>Success</p>',
				'failure_message' => '<p>Failure</p>',
				'final_action' => 'save_and_end',
				'form_id' => null,
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
				'id' => 3,
				'type' => 'configurator',
				'title' => 'Dummy Survey (Inline Image)',
				'identifier' => 'dummy_survey3',
				'success_message' => '<p>Success with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
				'failure_message' => '<p>Failure with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
				'final_action' => 'save_and_end',
				'form_id' => null,
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
				'type' => 'configurator',
				'title' => 'Dummy Survey (Survey Results)',
				'identifier' => 'dummy_survey4',
				'success_message' => '<p>Success</p>',
				'failure_message' => '<p>Failure</p>',
				'final_action' => 'save_and_end',
				'form_id' => null,
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

		$table = $this->table('surveys');
		$table->insert($data)->save();
	}
}
