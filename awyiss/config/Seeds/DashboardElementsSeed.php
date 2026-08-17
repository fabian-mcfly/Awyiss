<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseSeed;


/**
 * DashboardElements seed.
 */
class DashboardElementsSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'scope' => 'FormEntries',
				'title' => 'Neue Formulareinträge',
				'access' => '{"scope":"FormEntries","identifier":"read"}',
				'settings' => '{"fields":["formId","languageShortcode","subject","createdOn"],"filter":{"createdOn":{"active":"1","operator":"sinceLastLogin"}},"sort":[{"field":"createdOn","direction":"desc"}]}',
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
				'scope' => 'UrlsNotFound',
				'title' => 'Neue 404 Fehler',
				'access' => '{"scope":"UrlsNotFound","identifier":"read"}',
				'settings' => '{"fields":["url","isRobot","createdOn"],"filter":{"createdOn":{"active":"1","operator":"sinceLastLogin"}},"sort":[{"field":"createdOn","direction":"desc"}]}',
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

		$table = $this->table('dashboard_elements');
		$table
			->insert($data)
			->save()
		;
	}
}
