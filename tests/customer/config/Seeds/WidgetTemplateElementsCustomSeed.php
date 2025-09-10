<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * WidgetTemplateElements seed.
 */
class WidgetTemplateElementsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 10,
				'widget_template_id' => 1,
				'identifier' => 'attributes.free_text',
				'fieldset' => 'content',
				'required' => 1,
				'system_order' => 10,
			],
			[
				'id' => 101,
				'widget_template_id' => 2,
				'identifier' => 'active',
				'title' => null,
				'fieldset' => 'presentation',
				'column_span' => '12/12',
				'required' => false,
				'system_order' => 1,
			],
			[
				'id' => 102,
				'widget_template_id' => 2,
				'identifier' => 'widget_template_id',
				'title' => null,
				'fieldset' => 'presentation',
				'column_span' => '12/12',
				'required' => false,
				'system_order' => 2,
			],
			[
				'id' => 103,
				'widget_template_id' => 2,
				'identifier' => 'column_width',
				'title' => null,
				'fieldset' => 'presentation',
				'column_span' => '6/12',
				'required' => false,
				'system_order' => 3,
			],
			[
				'id' => 104,
				'widget_template_id' => 2,
				'identifier' => 'column_indent',
				'title' => null,
				'fieldset' => 'presentation',
				'column_span' => '6/12',
				'required' => false,
				'system_order' => 4,
			],
			[
				'id' => 105,
				'widget_template_id' => 2,
				'identifier' => 'identifier',
				'title' => null,
				'fieldset' => 'conditions',
				'column_span' => '12/12',
				'required' => false,
				'system_order' => 5,
			],
			[
				'id' => 106,
				'widget_template_id' => 2,
				'identifier' => 'system_order',
				'title' => null,
				'fieldset' => 'conditions',
				'column_span' => '12/12',
				'required' => false,
				'system_order' => 7,
			],
			[
				'id' => 107,
				'widget_template_id' => 2,
				'identifier' => 'text',
				'title' => null,
				'fieldset' => 'content',
				'column_span' => '12/12',
				'required' => false,
				'system_order' => 8,
			],
			[
				'id' => 108,
				'widget_template_id' => 2,
				'identifier' => 'parent_id',
				'title' => null,
				'fieldset' => 'conditions',
				'column_span' => '12/12',
				'required' => false,
				'system_order' => 6,
			],
			[
				'id' => 109,
				'widget_template_id' => 2,
				'identifier' => 'attributes.free_text',
				'title' => null,
				'fieldset' => 'content',
				'column_span' => '12/12',
				'required' => true,
				'system_order' => 9,
			],
			[
				'id' => 110,
				'widget_template_id' => 2,
				'identifier' => 'form_id',
				'title' => null,
				'fieldset' => 'content',
				'column_span' => '12/12',
				'required' => false,
				'system_order' => 10,
			],
			[
				'id' => 111,
				'widget_template_id' => 2,
				'identifier' => 'survey_id',
				'title' => null,
				'fieldset' => 'content',
				'column_span' => '12/12',
				'required' => false,
				'system_order' => 11,
			],
		];

		$lo_table = $this->table('widget_template_elements');
		$lo_table->insert($la_data)->save();
	}
}
