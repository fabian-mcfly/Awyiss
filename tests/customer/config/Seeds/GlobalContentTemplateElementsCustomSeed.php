<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * GlobalContentTemplateElements seed.
 */
class GlobalContentTemplateElementsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 10,
				'globalContentTemplateId' => 1,
				'identifier' => 'attributes.freeText',
				'fieldset' => 'content',
				'required' => 1,
				'systemOrder' => 10,
			],
			[
				'id' => 101,
				'globalContentTemplateId' => 2,
				'identifier' => 'active',
				'title' => null,
				'fieldset' => 'presentation',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 1,
			],
			[
				'id' => 102,
				'globalContentTemplateId' => 2,
				'identifier' => 'globalContentTemplateId',
				'title' => null,
				'fieldset' => 'presentation',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 2,
			],
			[
				'id' => 103,
				'globalContentTemplateId' => 2,
				'identifier' => 'columnWidth',
				'title' => null,
				'fieldset' => 'presentation',
				'columnSpan' => '6/12',
				'required' => false,
				'systemOrder' => 3,
			],
			[
				'id' => 104,
				'globalContentTemplateId' => 2,
				'identifier' => 'columnIndent',
				'title' => null,
				'fieldset' => 'presentation',
				'columnSpan' => '6/12',
				'required' => false,
				'systemOrder' => 4,
			],
			[
				'id' => 105,
				'globalContentTemplateId' => 2,
				'identifier' => 'identifier',
				'title' => null,
				'fieldset' => 'conditions',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 5,
			],
			[
				'id' => 106,
				'globalContentTemplateId' => 2,
				'identifier' => 'systemOrder',
				'title' => null,
				'fieldset' => 'conditions',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 7,
			],
			[
				'id' => 107,
				'globalContentTemplateId' => 2,
				'identifier' => 'text',
				'title' => null,
				'fieldset' => 'content',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 8,
			],
			[
				'id' => 108,
				'globalContentTemplateId' => 2,
				'identifier' => 'parentId',
				'title' => null,
				'fieldset' => 'conditions',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 6,
			],
			[
				'id' => 109,
				'globalContentTemplateId' => 2,
				'identifier' => 'attributes.freeText',
				'title' => null,
				'fieldset' => 'content',
				'columnSpan' => '12/12',
				'required' => true,
				'systemOrder' => 9,
			],
			[
				'id' => 110,
				'globalContentTemplateId' => 2,
				'identifier' => 'formId',
				'title' => null,
				'fieldset' => 'content',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 10,
			],
			[
				'id' => 111,
				'globalContentTemplateId' => 2,
				'identifier' => 'surveyId',
				'title' => null,
				'fieldset' => 'content',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 11,
			],
			// Test data for updateTemplateElementIdentifiers - using fake template IDs
			[
				'id' => 200,
				'globalContentTemplateId' => 998,
				'identifier' => 'attributes.oldGlobalIdentifier',
				'title' => null,
				'fieldset' => 'content',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 1,
			],
			[
				'id' => 201,
				'globalContentTemplateId' => 998,
				'identifier' => 'attributes.anotherGlobalAttribute',
				'title' => null,
				'fieldset' => 'content',
				'columnSpan' => '12/12',
				'required' => false,
				'systemOrder' => 2,
			],
		];

		$table = $this->table('global_content_template_elements');
		$table->insert($data)->save();
	}
}
