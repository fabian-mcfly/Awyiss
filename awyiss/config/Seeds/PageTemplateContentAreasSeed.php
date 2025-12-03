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
				'page_template_id' => 1,
				'content_area_id' => 1,
				'system_order' => 1,
			],
		];

		$table = $this->table('page_template_content_areas');
		$table->insert($data)->save();
	}
}
