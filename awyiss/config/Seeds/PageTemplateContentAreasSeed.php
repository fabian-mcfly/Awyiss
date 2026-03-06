<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * PageTemplateContentAreas seed.
 */
class PageTemplateContentAreasSeed extends AbstractSeed {
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
		$table->insert($data)->save();
	}
}
