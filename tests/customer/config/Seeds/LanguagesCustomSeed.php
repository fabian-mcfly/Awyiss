<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * Languages seed.
 */
class LanguagesCustomSeed extends BaseSeed {
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
				'systemOrder' => 3,
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
				'id' => 101,
				'realm' => 'Dummy',
				'shortcode' => 'xy',
				'timezone' => 'Europe/Berlin',
				'locale' => 'de_DE',
				'title' => 'Klingon',
				'systemOrder' => 1,
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
				'id' => 102,
				'realm' => 'Frontend',
				'shortcode' => 'zu',
				'timezone' => 'Europe/Berlin',
				'locale' => 'en_AG',
				'title' => 'Klingon',
				'systemOrder' => 2,
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

		$table = $this->table('languages');
		$table->insert($data)->save();
	}
}
