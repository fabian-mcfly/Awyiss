<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseSeed;


/**
 * ContentTemplateContentAreas seed.
 */
class ContentTemplateContentAreasSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'contentTemplateId' => 1,
				'contentAreaId' => 1,
				'pageTemplateId' => 1,
			],
			[
				'id' => 2,
				'contentTemplateId' => 2,
				'contentAreaId' => 1,
				'pageTemplateId' => 1,
			],
		];

		$table = $this->table('content_template_content_areas');
		$table
			->insert($data)
			->save()
		;
	}
}
