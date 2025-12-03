<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * Languages seed.
 */
class LanguagesCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 100,
				'realm' => 'Frontend',
				'shortcode' => 'es',
				'timezone' => 'Europe/Berlin',
				'locale' => 'de_DE',
				'title' => 'Esperanto',
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
				'id' => 101,
				'realm' => 'Dummy',
				'shortcode' => 'xy',
				'timezone' => 'Europe/Berlin',
				'locale' => 'de_DE',
				'title' => 'Klingon',
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
				'id' => 102,
				'realm' => 'Frontend',
				'shortcode' => 'zu',
				'timezone' => 'Europe/Berlin',
				'locale' => 'en_AG',
				'title' => 'Klingon',
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

		$table = $this->table('languages');
		$table->insert($data)->save();
	}
}
