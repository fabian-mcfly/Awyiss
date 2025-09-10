<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * SurveyEntries seed.
 */
class SurveyEntriesCustomSeed extends BaseSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'survey_id' => 1,
				'page_id' => 1,
				'data' => 'eJx1kM0OgjAQhF/FjNeG2NJW7M1H8EyIAVuNCYJSiAfCu7siqPhz253d/WayLc5Veaic9zAtIiWkdcrBSIa9XmVcywgmjtgqYVharaSUGga7xtflCaSJhZJ7voThvGODvi781VVEjOmqcqlNs5yYcYtL43x9LIvt0cIIhrTf7DtyHKdksBnK2ZxjXCP5QSYx4OjYBy+c8IbQP5nijRk/oSK4668uRPLloSYeRZPnfyzke+zhXV80PaFx/oelfr1AUdou6W4rYo8f',
				'ip_hash' => 'f528764d624db129b32c21fbca0cb8d6',
				'post_hash' => '2d12c01d690373e2932f49b983cd726ff3d10822',
				'identifier' => '419840e6c9eae0682dec94a92e065136',
				'deleted' => 0,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$lo_table = $this->table('survey_entries');
		$lo_table->insert($la_data)->save();
	}
}
