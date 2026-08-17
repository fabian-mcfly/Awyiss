<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseSeed;


/**
 * PageTemplateContentAreas seed.
 */
class PageTemplateContentAreasSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'pageTemplateId' => 1,
				'contentAreaId' => 1,
				'systemOrder' => 1,
			],
		];

		$table = $this->table('page_template_content_areas');
		$table
			->insert($data)
			->save()
		;
	}
}
