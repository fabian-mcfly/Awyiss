<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseSeed;


/**
 * Languages seed.
 */
class LanguagesSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'realm' => 'Frontend',
				'shortcode' => 'de',
				'timezone' => 'Europe/Berlin',
				'locale' => 'de_DE',
				'title' => 'Deutsch',
				'systemOrder' => 1,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => new \Cake\I18n\DateTime('now')->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 2,
				'realm' => 'Backend',
				'shortcode' => 'de',
				'timezone' => 'Europe/Berlin',
				'locale' => 'de_DE',
				'title' => 'Deutsch',
				'systemOrder' => 1,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => new \Cake\I18n\DateTime('now')->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 3,
				'realm' => 'Backend',
				'shortcode' => 'en',
				'timezone' => 'Europe/London',
				'locale' => 'en_GB',
				'title' => 'English',
				'systemOrder' => 2,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => new \Cake\I18n\DateTime('now')->format('Y-m-d H:i:s'),
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
