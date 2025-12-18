<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * ContentTemplates seed.
 */
class ContentTemplatesSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'title' => 'Standard',
				'file_name' => 'standard',
				'in_content_row' => 1,
				'system_order' => 1,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 2,
				'title' => 'Inhaltsblock',
				'file_name' => 'section',
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

		$table = $this->table('content_templates');
		$table->insert($data)->save();
	}
}
