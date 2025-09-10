<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * ContentTemplateElements seed.
 */
class ContentTemplateElementsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 100,
				'content_template_id' => 2,
				'identifier' => 'active',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 1,
			],
			[
				'id' => 101,
				'content_template_id' => 2,
				'identifier' => 'attributes.background_color',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 2,
			],
			[
				'id' => 102,
				'content_template_id' => 2,
				'identifier' => 'form_id',
				'fieldset' => 'content',
				'required' => 0,
				'system_order' => 1,
			],
			[
				'id' => 103,
				'content_template_id' => 2,
				'identifier' => 'survey_id',
				'fieldset' => 'content',
				'required' => 0,
				'system_order' => 2,
			],
		];

		$lo_table = $this->table('content_template_elements');
		$lo_table->insert($la_data)->save();
	}
}
