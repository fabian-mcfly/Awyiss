<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * Languages seed.
 */
class LanguagesSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'realm' => 'Frontend',
				'shortcode' => 'de',
				'timezone' => 'Europe/Berlin',
				'locale' => 'de_DE',
				'title' => 'Deutsch',
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
				'id' => 2,
				'realm' => 'Backend',
				'shortcode' => 'de',
				'timezone' => 'Europe/Berlin',
				'locale' => 'de_DE',
				'title' => 'Deutsch',
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
				'realm' => 'Backend',
				'shortcode' => 'en',
				'timezone' => 'Europe/London',
				'locale' => 'en_GB',
				'title' => 'English',
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
		];

		$lo_table = $this->table('languages');
		$lo_table->insert($la_data)->save();
	}
}
