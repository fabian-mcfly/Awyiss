<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * PageTemplates seed.
 */
class PageTemplatesCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 2,
				'page_role_id' => 3,
				'title' => 'Standard',
				'file_name' => 'news',
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
				'id' => 4,
				'page_role_id' => 1,
				'title' => 'Unused',
				'file_name' => 'unused',
				'system_order' => 2,
				'active' => 2,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$table = $this->table('page_templates');
		$table->insert($data)->save();
	}
}
