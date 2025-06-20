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
		$la_data = [
			[
				'id' => 1,
				'type' => 'configurator',
				'title' => 'Dummy Survey',
				'identifier' => 'dummy_survey',
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

		$lo_table = $this->table('surveys');
		$lo_table->insert($la_data)->save();
	}
}
