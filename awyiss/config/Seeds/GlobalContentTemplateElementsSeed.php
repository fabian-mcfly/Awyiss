<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseSeed;


/**
 * GlobalContentTemplateElements seed.
 */
class GlobalContentTemplateElementsSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'globalContentTemplateId' => 1,
				'identifier' => 'active',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 1,
			],
			[
				'id' => 2,
				'globalContentTemplateId' => 1,
				'identifier' => 'globalContentTemplateId',
				'fieldset' => 'presentation',
				'required' => 1,
				'systemOrder' => 2,
			],
			[
				'id' => 3,
				'globalContentTemplateId' => 1,
				'identifier' => 'identifier',
				'fieldset' => 'conditions',
				'required' => 1,
				'systemOrder' => 3,
			],
			[
				'id' => 4,
				'globalContentTemplateId' => 1,
				'identifier' => 'parentId',
				'fieldset' => 'conditions',
				'required' => 0,
				'systemOrder' => 4,
			],
			[
				'id' => 5,
				'globalContentTemplateId' => 1,
				'identifier' => 'systemOrder',
				'fieldset' => 'conditions',
				'required' => 1,
				'systemOrder' => 5,
			],
			[
				'id' => 6,
				'globalContentTemplateId' => 1,
				'identifier' => 'cssClass',
				'fieldset' => 'presentation',
				'required' => 0,
				'systemOrder' => 6,
			],
			[
				'id' => 7,
				'globalContentTemplateId' => 1,
				'identifier' => 'columnWidth',
				'fieldset' => 'presentation',
				'columnSpan' => '6/12',
				'required' => 0,
				'systemOrder' => 7,
			],
			[
				'id' => 8,
				'globalContentTemplateId' => 1,
				'identifier' => 'columnIndent',
				'fieldset' => 'presentation',
				'columnSpan' => '6/12',
				'required' => 0,
				'systemOrder' => 8,
			],
			[
				'id' => 9,
				'globalContentTemplateId' => 1,
				'identifier' => 'text',
				'fieldset' => 'content',
				'required' => 0,
				'systemOrder' => 9,
			],
		];

		$table = $this->table('global_content_template_elements');
		$table
			->insert($data)
			->save()
		;
	}
}
