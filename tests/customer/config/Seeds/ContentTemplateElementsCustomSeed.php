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
		$data = [
			[
				'id' => 100,
				'contentTemplateId' => 2,
				'identifier' => 'active',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 1,
			],
			[
				'id' => 101,
				'contentTemplateId' => 2,
				'identifier' => 'attributes.backgroundColor',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 2,
			],
			[
				'id' => 102,
				'contentTemplateId' => 2,
				'identifier' => 'formId',
				'fieldset' => 'content',
				'required' => 0,
				'systemOrder' => 1,
			],
			[
				'id' => 103,
				'contentTemplateId' => 2,
				'identifier' => 'surveyId',
				'fieldset' => 'content',
				'required' => 0,
				'systemOrder' => 2,
			],
			[
				'id' => 104,
				'contentTemplateId' => 1,
				'identifier' => 'duplicateOf',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 17,
			],
			[
				'id' => 105,
				'contentTemplateId' => 1,
				'identifier' => 'columnLast',
				'fieldset' => 'presentation',
				'columnSpan' => '6/12',
				'required' => 0,
				'systemOrder' => 11,
			],
			[
				'id' => 106,
				'contentTemplateId' => 1,
				'identifier' => 'columnRtl',
				'fieldset' => 'presentation',
				'columnSpan' => '6/12',
				'required' => 0,
				'systemOrder' => 12,
			],
			[
				'id' => 107,
				'contentTemplateId' => 1,
				'identifier' => 'title',
				'fieldset' => 'content',
				'required' => 0,
				'systemOrder' => 13,
			],
			[
				'id' => 108,
				'contentTemplateId' => 1,
				'identifier' => 'subtitle',
				'fieldset' => 'content',
				'required' => 0,
				'systemOrder' => 14,
			],
			[
				'id' => 109,
				'contentTemplateId' => 1,
				'identifier' => 'link',
				'fieldset' => 'media',
				'required' => 0,
				'systemOrder' => 16,
			],
			// Test data for updateTemplateElementIdentifiers - using fake template IDs
			[
				'id' => 200,
				'contentTemplateId' => 999,
				'identifier' => 'attributes.oldIdentifier',
				'fieldset' => 'content',
				'required' => 0,
				'systemOrder' => 1,
			],
			[
				'id' => 201,
				'contentTemplateId' => 999,
				'identifier' => 'attributes.anotherAttribute',
				'fieldset' => 'content',
				'required' => 0,
				'systemOrder' => 2,
			],
		];

		$table = $this->table('content_template_elements');
		$table->insert($data)->save();
	}
}
