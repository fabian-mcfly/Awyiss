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
				'contentTemplateId' => 1,
				'identifier' => 'active',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 1,
			],
			[
				'id' => 2,
				'contentTemplateId' => 1,
				'identifier' => 'contentTemplateId',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 2,
			],
			[
				'id' => 3,
				'contentTemplateId' => 1,
				'identifier' => 'languageShortcode',
				'fieldset' => 'conditions',
				'required' => 0,
				'systemOrder' => 3,
			],
			[
				'id' => 4,
				'contentTemplateId' => 1,
				'identifier' => 'pageId',
				'fieldset' => 'conditions',
				'required' => 0,
				'systemOrder' => 4,
			],
			[
				'id' => 5,
				'contentTemplateId' => 1,
				'identifier' => 'contentAreaId',
				'fieldset' => 'conditions',
				'required' => 0,
				'systemOrder' => 5,
			],
			[
				'id' => 6,
				'contentTemplateId' => 1,
				'identifier' => 'parentId',
				'fieldset' => 'conditions',
				'required' => 0,
				'systemOrder' => 6,
			],
			[
				'id' => 7,
				'contentTemplateId' => 1,
				'identifier' => 'systemOrder',
				'fieldset' => 'conditions',
				'required' => 1,
				'systemOrder' => 7,
			],
			[
				'id' => 8,
				'contentTemplateId' => 1,
				'identifier' => 'cssClass',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 8,
			],
			[
				'id' => 9,
				'contentTemplateId' => 1,
				'identifier' => 'columnWidth',
				'fieldset' => 'presentation',
				'columnSpan' => '6/12',
				'required' => 0,
				'systemOrder' => 9,
			],
			[
				'id' => 10,
				'contentTemplateId' => 1,
				'identifier' => 'columnIndent',
				'fieldset' => 'presentation',
				'columnSpan' => '6/12',
				'required' => 0,
				'systemOrder' => 10,
			],
			[
				'id' => 15,
				'contentTemplateId' => 1,
				'identifier' => 'text',
				'fieldset' => 'content',
				'required' => 0,
				'systemOrder' => 15,
			],
			[
				'id' => 19,
				'contentTemplateId' => 2,
				'identifier' => 'active',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 1,
			],
			[
				'id' => 20,
				'contentTemplateId' => 2,
				'identifier' => 'contentTemplateId',
				'fieldset' => 'presentation',
				'required' => 1,
				'systemOrder' => 2,
			],
			[
				'id' => 21,
				'contentTemplateId' => 2,
				'identifier' => 'languageShortcode',
				'fieldset' => 'conditions',
				'required' => 0,
				'systemOrder' => 3,
			],
			[
				'id' => 22,
				'contentTemplateId' => 2,
				'identifier' => 'pageId',
				'fieldset' => 'conditions',
				'required' => 0,
				'systemOrder' => 4,
			],
			[
				'id' => 23,
				'contentTemplateId' => 2,
				'identifier' => 'contentAreaId',
				'fieldset' => 'conditions',
				'required' => 0,
				'systemOrder' => 5,
			],
			[
				'id' => 24,
				'contentTemplateId' => 2,
				'identifier' => 'systemOrder',
				'fieldset' => 'conditions',
				'required' => 0,
				'systemOrder' => 6,
			],
			[
				'id' => 25,
				'contentTemplateId' => 2,
				'identifier' => 'cssClass',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 7,
			],
		];

		$table = $this->table('content_template_elements');
		$table->insert($data)->save();
	}
}
