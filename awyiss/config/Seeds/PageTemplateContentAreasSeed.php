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
		$la_data = [
			[
				'id' => 1,
				'page_template_id' => 1,
				'content_area_id' => 1,
				'system_order' => 1,
			],
		];

		$lo_table = $this->table('page_template_content_areas');
		$lo_table->insert($la_data)->save();
	}
}
