<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseSeed;


/**
 * PageTemplates seed.
 */
class PageTemplatesSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'pageRoleId' => 1,
				'title' => 'Standard',
				'fileName' => 'standard',
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
				'pageRoleId' => 1,
				'title' => 'Mit Seitenteaser',
				'fileName' => 'with_page_teaser',
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

		$table = $this->table('page_templates');
		$table
			->insert($data)
			->save()
		;
	}
}
