<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * GlobalContentTemplates seed.
 */
class GlobalContentTemplatesCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 2,
				'title' => 'Dummy',
				'file_name' => 'dummy',
				'in_content_row' => 0,
				'system_order' => 2,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$table = $this->table('global_content_templates');
		$table->insert($data)->save();
	}
}
