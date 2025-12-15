<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * GlobalContentTemplateElements seed.
 */
class GlobalContentTemplateElementsSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'global_content_template_id' => 1,
				'identifier' => 'active',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 1,
			],
			[
				'id' => 2,
				'global_content_template_id' => 1,
				'identifier' => 'global_content_template_id',
				'fieldset' => 'presentation',
				'required' => 1,
				'system_order' => 2,
			],
			[
				'id' => 3,
				'global_content_template_id' => 1,
				'identifier' => 'identifier',
				'fieldset' => 'conditions',
				'required' => 1,
				'system_order' => 3,
			],
			[
				'id' => 4,
				'global_content_template_id' => 1,
				'identifier' => 'parent_id',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 4,
			],
			[
				'id' => 5,
				'global_content_template_id' => 1,
				'identifier' => 'system_order',
				'fieldset' => 'conditions',
				'required' => 1,
				'system_order' => 5,
			],
			[
				'id' => 6,
				'global_content_template_id' => 1,
				'identifier' => 'css_class',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 6,
			],
			[
				'id' => 7,
				'global_content_template_id' => 1,
				'identifier' => 'column_width',
				'fieldset' => 'presentation',
				'column_span' => '6/12',
				'required' => 0,
				'system_order' => 7,
			],
			[
				'id' => 8,
				'global_content_template_id' => 1,
				'identifier' => 'column_indent',
				'fieldset' => 'presentation',
				'column_span' => '6/12',
				'required' => 0,
				'system_order' => 8,
			],
			[
				'id' => 9,
				'global_content_template_id' => 1,
				'identifier' => 'text',
				'fieldset' => 'content',
				'required' => 0,
				'system_order' => 9,
			],
		];

		$table = $this->table('global_content_template_elements');
		$table->insert($data)->save();
	}
}
