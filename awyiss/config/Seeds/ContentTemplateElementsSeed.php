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
		$data = [
			[
				'id' => 1,
				'content_template_id' => 1,
				'identifier' => 'active',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 1,
			],
			[
				'id' => 2,
				'content_template_id' => 1,
				'identifier' => 'content_template_id',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 2,
			],
			[
				'id' => 3,
				'content_template_id' => 1,
				'identifier' => 'language_shortcode',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 3,
			],
			[
				'id' => 4,
				'content_template_id' => 1,
				'identifier' => 'page_id',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 4,
			],
			[
				'id' => 5,
				'content_template_id' => 1,
				'identifier' => 'content_area_id',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 5,
			],
			[
				'id' => 6,
				'content_template_id' => 1,
				'identifier' => 'parent_id',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 6,
			],
			[
				'id' => 7,
				'content_template_id' => 1,
				'identifier' => 'system_order',
				'fieldset' => 'conditions',
				'required' => 1,
				'system_order' => 7,
			],
			[
				'id' => 8,
				'content_template_id' => 1,
				'identifier' => 'css_class',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 8,
			],
			[
				'id' => 9,
				'content_template_id' => 1,
				'identifier' => 'column_width',
				'fieldset' => 'presentation',
				'column_span' => '6/12',
				'required' => 0,
				'system_order' => 9,
			],
			[
				'id' => 10,
				'content_template_id' => 1,
				'identifier' => 'column_indent',
				'fieldset' => 'presentation',
				'column_span' => '6/12',
				'required' => 0,
				'system_order' => 10,
			],
			[
				'id' => 15,
				'content_template_id' => 1,
				'identifier' => 'text',
				'fieldset' => 'content',
				'required' => 0,
				'system_order' => 15,
			],
			[
				'id' => 19,
				'content_template_id' => 2,
				'identifier' => 'active',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 1,
			],
			[
				'id' => 20,
				'content_template_id' => 2,
				'identifier' => 'content_template_id',
				'fieldset' => 'presentation',
				'required' => 1,
				'system_order' => 2,
			],
			[
				'id' => 21,
				'content_template_id' => 2,
				'identifier' => 'language_shortcode',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 3,
			],
			[
				'id' => 22,
				'content_template_id' => 2,
				'identifier' => 'page_id',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 4,
			],
			[
				'id' => 23,
				'content_template_id' => 2,
				'identifier' => 'content_area_id',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 5,
			],
			[
				'id' => 24,
				'content_template_id' => 2,
				'identifier' => 'system_order',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 6,
			],
			[
				'id' => 25,
				'content_template_id' => 2,
				'identifier' => 'css_class',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 7,
			],
		];

		$table = $this->table('content_template_elements');
		$table->insert($data)->save();
	}
}
