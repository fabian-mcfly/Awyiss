<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * WidgetTemplateElements seed.
 */
class WidgetTemplateElementsSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'widget_template_id' => 1,
				'identifier' => 'active',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 1,
			],
			[
				'id' => 2,
				'widget_template_id' => 1,
				'identifier' => 'widget_template_id',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 1,
				'system_order' => 2,
			],
			[
				'id' => 3,
				'widget_template_id' => 1,
				'identifier' => 'identifier',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 1,
				'system_order' => 3,
			],
			[
				'id' => 4,
				'widget_template_id' => 1,
				'identifier' => 'parent_id',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 0,
				'system_order' => 4,
			],
			[
				'id' => 5,
				'widget_template_id' => 1,
				'identifier' => 'system_order',
				'title' => '',
				'fieldset' => 'conditions',
				'required' => 1,
				'system_order' => 5,
			],
			[
				'id' => 6,
				'widget_template_id' => 1,
				'identifier' => 'css_class',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 6,
			],
			[
				'id' => 7,
				'widget_template_id' => 1,
				'identifier' => 'column_width',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 7,
			],
			[
				'id' => 8,
				'widget_template_id' => 1,
				'identifier' => 'column_indent',
				'title' => '',
				'fieldset' => 'presentation',
				'required' => 0,
				'system_order' => 8,
			],
			[
				'id' => 9,
				'widget_template_id' => 1,
				'identifier' => 'text',
				'title' => '',
				'fieldset' => 'widget',
				'required' => 0,
				'system_order' => 9,
			],
		];

		$lo_table = $this->table('widget_template_elements');
		$lo_table->insert($la_data)->save();
	}
}
