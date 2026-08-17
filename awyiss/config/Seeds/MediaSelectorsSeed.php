<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseSeed;


/**
 * MediaSelectors seed.
 */
class MediaSelectorsSeed extends BaseSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'title' => 'Einzeldatei',
				'identifier' => 'singleFile',
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
				'title' => 'Mehrfachauswahl',
				'identifier' => 'multiFile',
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
				'title' => 'Ordnerauswahl',
				'identifier' => 'folder',
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

		$table = $this->table('media_selectors');
		$table
			->insert($data)
			->save()
		;
	}
}
