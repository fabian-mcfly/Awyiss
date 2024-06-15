<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * ContentTemplateElements seed.
 */
class ContentTemplateElementsSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'content_template_id' => 1,
				'identifier' => 'active',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
			[
				'id' => 2,
				'content_template_id' => 1,
				'identifier' => 'content_template_id',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
			[
				'id' => 3,
				'content_template_id' => 1,
				'identifier' => 'language_shortcode',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 0,
			],
			[
				'id' => 4,
				'content_template_id' => 1,
				'identifier' => 'page',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 0,
			],
			[
				'id' => 5,
				'content_template_id' => 1,
				'identifier' => 'content_area_id',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 0,
			],
			[
				'id' => 6,
				'content_template_id' => 1,
				'identifier' => 'parent_id',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 0,
			],
			[
				'id' => 7,
				'content_template_id' => 1,
				'identifier' => 'system_order',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 1,
			],
			[
				'id' => 8,
				'content_template_id' => 1,
				'identifier' => 'css_class',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
			[
				'id' => 9,
				'content_template_id' => 1,
				'identifier' => 'column_width',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
			[
				'id' => 10,
				'content_template_id' => 1,
				'identifier' => 'column_indent',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
			[
				'id' => 11,
				'content_template_id' => 1,
				'identifier' => 'column_last',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
			[
				'id' => 12,
				'content_template_id' => 1,
				'identifier' => 'column_rtl',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
			[
				'id' => 13,
				'content_template_id' => 1,
				'identifier' => 'title',
				'title' => '',
				'fieldset' => 'content',
				'required' => 0,
			],
			[
				'id' => 14,
				'content_template_id' => 1,
				'identifier' => 'subtitle',
				'title' => '',
				'fieldset' => 'content',
				'required' => 0,
			],
			[
				'id' => 15,
				'content_template_id' => 1,
				'identifier' => 'text',
				'title' => '',
				'fieldset' => 'content',
				'required' => 0,
			],
			[
				'id' => 16,
				'content_template_id' => 1,
				'identifier' => 'link',
				'title' => '',
				'fieldset' => 'media',
				'required' => 0,
			],
			[
				'id' => 17,
				'content_template_id' => 1,
				'identifier' => 'duplicate_of',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
			[
				'id' => 19,
				'content_template_id' => 2,
				'identifier' => 'active',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
			[
				'id' => 20,
				'content_template_id' => 2,
				'identifier' => 'content_template_id',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 1,
			],
			[
				'id' => 21,
				'content_template_id' => 2,
				'identifier' => 'language_shortcode',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 0,
			],
			[
				'id' => 22,
				'content_template_id' => 2,
				'identifier' => 'page',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 0,
			],
			[
				'id' => 23,
				'content_template_id' => 2,
				'identifier' => 'content_area_id',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 0,
			],
			[
				'id' => 24,
				'content_template_id' => 2,
				'identifier' => 'system_order',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 0,
			],
			[
				'id' => 25,
				'content_template_id' => 2,
				'identifier' => 'css_class',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
			],
		];

		$lo_table = $this->table('content_template_elements');
		$lo_table->insert($la_data)->save();
	}
}
