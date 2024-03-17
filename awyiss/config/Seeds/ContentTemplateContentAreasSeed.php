<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * ContentTemplateContentAreas seed.
 */
class ContentTemplateContentAreasSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'content_template_id' => 1,
				'content_area_id' => 1,
				'page_template_id' => 1,
			],
			[
				'id' => 2,
				'content_template_id' => 2,
				'content_area_id' => 1,
				'page_template_id' => 1,
			],
		];

		$lo_table = $this->table('content_template_content_areas');
		$lo_table->insert($la_data)->save();
	}
}
