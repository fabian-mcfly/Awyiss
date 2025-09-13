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
			[
				'id' => 104,
				'content_template_id' => 1,
				'identifier' => 'duplicate_of',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 17,
			],
			[
				'id' => 105,
				'content_template_id' => 1,
				'identifier' => 'column_last',
				'fieldset' => 'presentation',
				'column_span' => '6/12',
				'required' => 0,
				'system_order' => 11,
			],
			[
				'id' => 106,
				'content_template_id' => 1,
				'identifier' => 'column_rtl',
				'fieldset' => 'presentation',
				'column_span' => '6/12',
				'required' => 0,
				'system_order' => 12,
			],
			[
				'id' => 107,
				'content_template_id' => 1,
				'identifier' => 'title',
				'fieldset' => 'content',
				'required' => 0,
				'system_order' => 13,
			],
			[
				'id' => 108,
				'content_template_id' => 1,
				'identifier' => 'subtitle',
				'fieldset' => 'content',
				'required' => 0,
				'system_order' => 14,
			],
			[
				'id' => 109,
				'content_template_id' => 1,
				'identifier' => 'link',
				'fieldset' => 'media',
				'required' => 0,
				'system_order' => 16,
			],
		];

		$lo_table = $this->table('content_template_elements');
		$lo_table->insert($la_data)->save();
	}
}
